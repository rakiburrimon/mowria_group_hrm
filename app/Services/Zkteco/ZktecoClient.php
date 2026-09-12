<?php

namespace App\Services\Zkteco;

use RuntimeException;

/**
 * Native UDP client for ZKTeco terminals (SenseFace, SpeedFace, etc.)
 * speaking the ZK binary protocol on port 4370.
 *
 * No external packages — raw UDP sockets only.
 *
 * Packet layout (UDP):
 *   command (2) | checksum (2) | session_id (2) | reply_number (2) | data...
 * Checksum = one's-complement of the 16-bit word sum of the packet.
 */
class ZktecoClient
{
    // Core commands
    public const CMD_CONNECT       = 1000;
    public const CMD_EXIT          = 1001;
    public const CMD_ENABLEDEVICE  = 1002;
    public const CMD_DISABLEDEVICE = 1003;
    public const CMD_RESTART       = 1004;
    public const CMD_REFRESHDATA   = 1013;

    // Replies
    public const CMD_ACK_OK    = 2000;
    public const CMD_ACK_ERROR = 2001;
    public const CMD_DATA      = 1500;

    // User / attendance commands
    public const CMD_USER_WRQ        = 8;   // set user
    public const CMD_USERTEMP_RRQ    = 9;   // get users
    public const CMD_ATTLOG_RRQ      = 13;  // get attendance logs
    public const CMD_CLEAR_ATTLOG    = 14;  // clear attendance logs
    public const CMD_DELETE_USERTEMP = 18;  // delete user
    public const CMD_GET_FREE_SIZES  = 50;  // device capacity info

    // Privilege levels on the device ("roles")
    public const PRIV_USER    = 0;
    public const PRIV_ENROLLER = 2;
    public const PRIV_MANAGER  = 6;
    public const PRIV_ADMIN    = 14;

    public const PRIVILEGES = [
        'user'     => self::PRIV_USER,
        'enroller' => self::PRIV_ENROLLER,
        'manager'  => self::PRIV_MANAGER,
        'admin'    => self::PRIV_ADMIN,
    ];

    /** @var resource|null */
    private $socket = null;
    private int $sessionId = 0;
    private int $replyNumber = 0;
    private bool $connected = false;

    public function __construct(
        private string $ip,
        private int $port = 4370,
        private int $timeout = 5,
    ) {}

    public function __destruct()
    {
        $this->disconnect();
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    // ------------------------------------------------------------------
    // Connection
    // ------------------------------------------------------------------

    /**
     * Open the UDP socket and handshake with the device.
     */
    public function connect(): bool
    {
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (! $this->socket) {
            throw new RuntimeException('Unable to create UDP socket: ' . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $this->timeout,
            'usec' => 0,
        ]);

        // 0xFFF7 is the conventional initial reply marker for a connect packet
        $this->send(self::CMD_CONNECT, '', 0, 0xFFF7);

        $response = $this->recv();

        if (! $response || strlen($response) < 8) {
            throw new RuntimeException("No response from {$this->ip}:{$this->port} — check IP, port 4370 and network reachability.");
        }

        $header = unpack('vcmd/vchecksum/vsession/vreply', substr($response, 0, 8));

        if ($header['cmd'] !== self::CMD_ACK_OK) {
            throw new RuntimeException('Device rejected the connection (cmd=' . $header['cmd'] . ').');
        }

        $this->sessionId = $header['session'];
        $this->replyNumber = $header['reply'];
        $this->connected = true;

        return true;
    }

    /**
     * Gracefully close the session.
     */
    public function disconnect(): void
    {
        if ($this->connected && $this->socket) {
            try {
                $this->send(self::CMD_EXIT);
            } catch (\Throwable) {
                // Device may already be gone — nothing to do
            }
        }

        if ($this->socket) {
            socket_close($this->socket);
        }

        $this->socket = null;
        $this->connected = false;
    }

    // ------------------------------------------------------------------
    // Attendance
    // ------------------------------------------------------------------

    /**
     * Pull all attendance logs from the device.
     *
     * @return array<int, array{pin:int, timestamp:string, state:int, verify:int, workcode:int}>
     */
    public function getAttendance(): array
    {
        $this->execute(self::CMD_DISABLEDEVICE);

        try {
            $data = $this->execute(self::CMD_ATTLOG_RRQ);
        } finally {
            $this->execute(self::CMD_ENABLEDEVICE);
        }

        $records = [];

        // Standard record is 40 bytes
        foreach (str_split($data, 40) as $rec) {
            if (strlen($rec) < 10) {
                continue;
            }

            $f = unpack('vpin/Vtime/Cstate/Cverify/Cworkcode', $rec);

            $records[] = [
                'pin' => $f['pin'],
                'timestamp' => self::decodeTime($f['time']),
                'state' => $f['state'],
                'verify' => $f['verify'],
                'workcode' => $f['workcode'],
            ];
        }

        return $records;
    }

    /**
     * Clear attendance logs on the device (after a successful sync).
     */
    public function clearAttendance(): bool
    {
        $this->execute(self::CMD_CLEAR_ATTLOG);

        return true;
    }

    // ------------------------------------------------------------------
    // Device users ("roles" = privilege level)
    // ------------------------------------------------------------------

    /**
     * List all users stored on the device.
     */
    public function getUsers(): array
    {
        $this->execute(self::CMD_DISABLEDEVICE);

        try {
            $data = $this->execute(self::CMD_USERTEMP_RRQ);
        } finally {
            $this->execute(self::CMD_ENABLEDEVICE);
        }

        $users = [];
        $offset = 0;
        $length = strlen($data);

        // Each user entry is prefixed with a 2-byte record size
        while ($offset + 2 <= $length) {
            $size = unpack('v', substr($data, $offset, 2))[1];

            if ($size < 72 || $offset + 2 + $size > $length) {
                break;
            }

            $rec = substr($data, $offset + 2, $size);
            $offset += 2 + $size;

            $users[] = [
                'uid'       => unpack('v', substr($rec, 0, 2))[1],
                'privilege' => ord($rec[2]),
                'password'  => rtrim(substr($rec, 3, 8), "\x00"),
                'name'      => rtrim(substr($rec, 11, 24), "\x00"),
                'card'      => unpack('V', substr($rec, 35, 4))[1],
                'group'     => ord($rec[39]),
            ];
        }

        return $users;
    }

    /**
     * Create or update a user on the device.
     *
     * $privilege is a privilege level constant (see PRIVILEGES).
     */
    public function setUser(int $pin, string $name = '', string $password = '', int $card = 0, int $privilege = self::PRIV_USER, int $group = 0): bool
    {
        // 72-byte user record
        $record = pack('v', $pin)                              // uid / pin
            . chr($privilege)                                  // privilege
            . str_pad(substr($password, 0, 8), 8, "\x00")      // password (8)
            . str_pad(substr($name, 0, 24), 24, "\x00")        // name (24)
            . pack('V', $card)                                 // card number (4)
            . chr($group)                                      // group
            . pack('v4', 1, 0, 0, 0)                           // timezones (4 x uint16)
            . str_pad('', 24, "\x00");                         // pin2 (24)

        $this->execute(self::CMD_USER_WRQ, $record);

        // Ask the device to reload its user tables
        try {
            $this->execute(self::CMD_REFRESHDATA);
        } catch (\Throwable) {
            // Some firmwares ack silently — refresh is best-effort
        }

        return true;
    }

    /**
     * Delete a user from the device by PIN.
     */
    public function deleteUser(int $pin): bool
    {
        $this->execute(self::CMD_DELETE_USERTEMP, pack('v', $pin));

        try {
            $this->execute(self::CMD_REFRESHDATA);
        } catch (\Throwable) {
            //
        }

        return true;
    }

    // ------------------------------------------------------------------
    // Protocol internals
    // ------------------------------------------------------------------

    /**
     * Send a command and return the data payload. When the device answers
     * with CMD_PREPARE_DATA the full data stream is read transparently.
     */
    private function execute(int $command, string $data = ''): string
    {
        if (! $this->connected) {
            $this->connect();
        }

        $this->replyNumber++;
        $this->send($command, $data, $this->sessionId, $this->replyNumber);

        $response = $this->recv();

        if (! $response || strlen($response) < 8) {
            throw new RuntimeException("No/invalid response for command {$command}.");
        }

        $header = unpack('vcmd/vchecksum/vsession/vreply', substr($response, 0, 8));
        $this->replyNumber = $header['reply'];

        if ($header['cmd'] === self::CMD_DATA) {
            // Data follows — first payload word is the announced size
            $size = unpack('V', substr($response, 8, 4))[1];

            return $this->readDataStream($size);
        }

        return substr($response, 8);
    }

    /**
     * Read a streamed data block until $size bytes arrived, then ack the
     * final CMD_DATA packet.
     */
    private function readDataStream(int $size): string
    {
        $data = '';
        $attempts = 0;

        while (strlen($data) < $size && $attempts < 2000) {
            $packet = $this->recv();
            $attempts++;

            if ($packet === false || $packet === '') {
                continue;
            }

            // A header packet with CMD_DATA marks the end of the stream
            if (strlen($packet) >= 8) {
                $h = unpack('vcmd', substr($packet, 0, 8));
                if ($h['cmd'] === self::CMD_DATA && strlen($data) >= $size) {
                    $this->send(self::CMD_ACK_OK, '', $this->sessionId, $this->replyNumber);
                    break;
                }
            }

            $data .= $packet;
        }

        // Final ack — safe even if the terminator was already acked
        $this->send(self::CMD_ACK_OK, '', $this->sessionId, $this->replyNumber);

        return $data;
    }

    /**
     * Build a packet: cmd | checksum | session | reply | data
     */
    private function send(int $command, string $data = '', int $sessionId = 0, int $replyNumber = 0): void
    {
        $buf = pack('vvvv', $command, 0, $sessionId, $replyNumber) . $data;
        $checksum = self::checksum($buf);
        $packet = pack('vvvv', $command, $checksum, $sessionId, $replyNumber) . $data;

        socket_sendto($this->socket, $packet, strlen($packet), 0, $this->ip, $this->port);
    }

    private function recv(): string|false
    {
        $from = '';
        $port = 0;
        $buf = '';
        $result = @socket_recvfrom($this->socket, $buf, 8192, 0, $from, $port);

        return $result === false ? false : $buf;
    }

    /**
     * ZK checksum: one's complement of the 16-bit word sum.
     */
    private static function checksum(string $buf): int
    {
        $sum = 0;
        $len = strlen($buf);

        for ($i = 0; $i + 1 < $len; $i += 2) {
            $sum += ord($buf[$i]) | (ord($buf[$i + 1]) << 8);
        }

        if ($len % 2) {
            $sum += ord($buf[$len - 1]);
        }

        return (~$sum) & 0xFFFF;
    }

    /**
     * Decode ZK time: seconds packed as
     * ((year-2000)*12*31 + (month-1)*31 + (day-1)) * 86400 + h*3600 + m*60 + s
     */
    public static function decodeTime(int $t): string
    {
        $sec = $t % 60;
        $t = intdiv($t, 60);
        $min = $t % 60;
        $t = intdiv($t, 60);
        $hour = $t % 24;
        $t = intdiv($t, 24);
        $day = $t % 31 + 1;
        $t = intdiv($t, 31);
        $month = $t % 12 + 1;
        $t = intdiv($t, 12);
        $year = $t + 2000;

        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $min, $sec);
    }
}
