@extends('layouts.admin')

@section('title', 'Attendance Device')
@section('page_title', 'Attendance Device')
@section('breadcrumb', 'Home / Device')

@php
    $privLabels = [0 => 'user', 2 => 'enroller', 6 => 'manager', 14 => 'admin'];
    $privColors = [0 => 'secondary', 2 => 'info', 6 => 'warning', 14 => 'danger'];
    $verifyLabels = [0 => 'password', 1 => 'fingerprint', 15 => 'face', 4 => 'card'];
    $statusColors = ['pending' => 'warning', 'sent' => 'info', 'done' => 'success', 'failed' => 'danger'];
@endphp

@section('content')
    {{-- Status + quick actions --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-fingerprint fa-2x text-primary mb-2"></i>
                    <h6 class="mb-1">Device</h6>
                    <span class="badge {{ $knownDevices->isNotEmpty() ? 'bg-success' : 'bg-secondary' }}">
                        {{ $knownDevices->isNotEmpty() ? $knownDevices->implode(', ') : 'Not connected' }}
                    </span>
                    <div class="small text-muted mt-2">
                        Last punch: {{ $lastPunchAt ?: 'never' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x text-info mb-2"></i>
                    <h6 class="mb-1">Device Users</h6>
                    <h4 class="mb-0">{{ $deviceUsers->count() }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-success mb-2"></i>
                    <h6 class="mb-1">Punches</h6>
                    <h4 class="mb-0">{{ $deviceLogs->count() }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-terminal fa-2x text-warning mb-2"></i>
                    <h6 class="mb-1">Pending Cmds</h6>
                    <h4 class="mb-0">{{ $commands->where('status','pending')->count() }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Push user to device --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-user-plus me-2 text-primary"></i>Push User to Device</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('device.setUser') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">PIN <span class="text-danger">*</span></label>
                            <input type="number" name="pin" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" maxlength="24" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Privilege (Role)</label>
                            <select name="privilege" class="form-select">
                                <option value="user">User</option>
                                <option value="enroller">Enroller</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Card No.</label>
                            <input type="number" name="card" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="text" name="password" class="form-control" maxlength="8">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-1"></i> Queue to Device
                        </button>
                    </form>
                    <form method="POST" action="{{ route('device.queryUsers') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-sync me-1"></i> Request User List from Device
                        </button>
                    </form>

                    <hr>
                    <form method="POST" action="{{ route('device.import') }}" enctype="multipart/form-data">
                        @csrf
                        <label class="form-label">Import USB export <small class="text-muted">(.dat/.txt — attendance or users)</small></label>
                        <div class="input-group">
                            <input type="file" name="file" class="form-control" accept=".dat,.txt" required>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-file-import"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Device users --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><i class="fas fa-users me-2 text-primary"></i>Device Users</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>PIN</th><th>Name</th><th>Card</th><th>Privilege</th><th>Group</th><th>Device</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deviceUsers as $u)
                                    <tr>
                                        <td>{{ $u->pin }}</td>
                                        <td>{{ $u->name ?: '—' }}</td>
                                        <td>{{ $u->card ?: '—' }}</td>
                                        <td><span class="badge bg-{{ $privColors[$u->privilege] ?? 'secondary' }}">{{ $privLabels[$u->privilege] ?? $u->privilege }}</span></td>
                                        <td>{{ $u->group }}</td>
                                        <td><small class="text-muted">{{ $u->device_sn ?? '—' }}</small></td>
                                        <td>
                                            <form method="POST" action="{{ route('device.deleteUser') }}" class="d-inline"
                                                  onsubmit="event.preventDefault(); confirmDelete('{{ route('device.deleteUser') }}','Delete Device User','Remove PIN {{ $u->pin }} from the device?') ; this.querySelector('[name=pin]') || this.insertAdjacentHTML('beforeend','<input type=hidden name=pin value={{ $u->pin }}>');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete from device"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No device users synced yet — click "Request User List from Device".</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        {{-- Raw punches --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><i class="fas fa-clock me-2 text-primary"></i>Device Punches (raw log)</div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="sticky-top bg-white">
                                <tr><th>PIN</th><th>Time</th><th>Status</th><th>Verify</th><th>Processed</th></tr>
                            </thead>
                            <tbody>
                                @forelse($deviceLogs as $log)
                                    <tr>
                                        <td>{{ $log->pin }}</td>
                                        <td>{{ $log->punch_time->format('Y-m-d H:i:s') }}</td>
                                        <td><span class="badge bg-{{ $log->status ? 'info' : 'success' }}">{{ $log->status ? 'out' : 'in' }}</span></td>
                                        <td>{{ $verifyLabels[$log->verify] ?? $log->verify }}</td>
                                        <td>{!! $log->processed ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-minus text-muted"></i>' !!}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No punches received yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Command queue --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><i class="fas fa-terminal me-2 text-primary"></i>Command Queue</div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="sticky-top bg-white">
                                <tr><th>#</th><th>Command</th><th>Status</th><th>Queued</th></tr>
                            </thead>
                            <tbody>
                                @forelse($commands as $c)
                                    <tr>
                                        <td>{{ $c->id }}</td>
                                        <td><code class="small">{{ Str::limit($c->command, 40) }}</code></td>
                                        <td><span class="badge bg-{{ $statusColors[$c->status] ?? 'secondary' }}">{{ $c->status }}</span></td>
                                        <td><small>{{ $c->created_at->diffForHumans() }}</small></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">No commands queued.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
