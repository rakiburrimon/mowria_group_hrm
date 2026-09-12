<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the role column to the users table.
 *
 * This column drives the simple role-based access control used
 * by the User model and the application policies.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a non-nullable role string defaulting to 'employee'.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Role used for authorization: super_admin, admin or employee
            $table->string('role')->default('employee')->after('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Removes the role column if the migration is rolled back.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
