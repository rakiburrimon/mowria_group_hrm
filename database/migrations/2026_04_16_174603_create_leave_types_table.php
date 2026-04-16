<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // annual, sick, personal, etc.
            $table->text('description')->nullable();
            $table->decimal('max_days_per_year', 5, 2)->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('allow_carry_over')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('color_code', 7)->nullable(); // hex color code
            $table->timestamps();

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
