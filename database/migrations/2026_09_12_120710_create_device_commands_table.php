<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Commands queued for the device — pushed to it when it polls
     * /iclock/getrequest (user add/update/delete, reboot, etc.)
     */
    public function up(): void
    {
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->string('device_sn')->nullable();   // target device serial (null = any)
            $table->text('command');                    // e.g. DATA UPDATE USERINFO ...
            $table->string('status')->default('pending'); // pending | sent | done | failed
            $table->text('response')->nullable();       // device's reply via devicecmd
            $table->timestamps();

            $table->index(['device_sn', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
