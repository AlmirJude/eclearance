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
        Schema::create('student_governments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Supreme Student Government", "College Student Council"
            $table->string('abbreviation')->nullable(); // "SSG", "CSC"
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null'); // null = university-wide
            $table->string('academic_year'); // "2025-2026"
            $table->foreignId('adviser_id')->nullable()->constrained('users')->onDelete('set null'); // Faculty adviser
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_governments');
    }
};
