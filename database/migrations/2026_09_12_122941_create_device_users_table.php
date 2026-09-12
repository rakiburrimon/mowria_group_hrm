<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot of the users stored on the device — refreshed when the
     * device pushes its USERINFO table in response to a query command.
     */
    public function up(): void
    {
        Schema::create('device_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pin');
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('privilege')->default(0);
            $table->string('password')->nullable();
            $table->unsignedBigInteger('card')->default(0);
            $table->unsignedTinyInteger('group')->default(0);
            $table->string('device_sn')->nullable();
            $table->timestamps();

            $table->unique(['pin', 'device_sn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_users');
    }
};
