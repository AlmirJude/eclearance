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
        Schema::create('clearance_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "1st Semester 2025-2026"
            $table->string('academic_year'); // e.g., "2025-2026"
            $table->enum('semester', ['1st', '2nd', 'summer'])->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearance_periods');
    }
};
