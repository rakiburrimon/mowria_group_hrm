<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raw punches pushed by the attendance terminal (iclock protocol).
     */
    public function up(): void
    {
        Schema::create('device_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pin');           // device user PIN
            $table->dateTime('punch_time');           // punch timestamp
            $table->unsignedTinyInteger('status')->default(0);  // in/out state
            $table->unsignedTinyInteger('verify')->default(0);  // verify method (face/finger/card/pwd)
            $table->unsignedTinyInteger('workcode')->default(0);
            $table->string('device_sn')->nullable();  // device serial number
            $table->boolean('processed')->default(false); // synced into attendances
            $table->timestamps();

            $table->index(['pin', 'punch_time']);
            $table->unique(['pin', 'punch_time', 'status', 'device_sn'], 'device_logs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};
