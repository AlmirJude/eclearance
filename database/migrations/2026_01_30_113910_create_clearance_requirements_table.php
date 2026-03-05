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
        Schema::create('clearance_requirements', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation to office, club, department, etc.
            $table->string('requirable_type'); // App\Models\Office, App\Models\Club, etc.
            $table->unsignedBigInteger('requirable_id');
            
            $table->string('name'); // e.g., "Library Clearance Form", "Equipment Return Receipt"
            $table->text('description')->nullable();
            $table->enum('type', ['document', 'form', 'payment', 'other'])->default('document');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            
            // Scoping - which students need this requirement
            $table->json('year_levels')->nullable(); // null = all years
            $table->json('departments')->nullable(); // null = all departments
            
            $table->timestamps();
            
            $table->index(['requirable_type', 'requirable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearance_requirements');
    }
};
