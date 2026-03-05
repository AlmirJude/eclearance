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
        Schema::create('homeroom_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adviser_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->json('year_levels'); // [1, 2, 3, 4] - array of year levels
            $table->string('section')->nullable(); // e.g., "A", "B", "C"
            $table->string('academic_year'); // e.g., "2025-2026"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homeroom_assignments');
    }
};
