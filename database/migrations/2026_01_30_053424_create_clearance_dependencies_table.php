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
        Schema::create('clearance_dependencies', function (Blueprint $table) {
            $table->id();
            
            // The dependent (what requires the prerequisite)
            $table->string('dependent_type'); // Office, Department, etc.
            $table->unsignedBigInteger('dependent_id');
            
            // The prerequisite (what must be completed first)
            $table->string('prerequisite_type'); // Club, Office, Department, 'homeroom_adviser'
            $table->unsignedBigInteger('prerequisite_id')->nullable(); // Null for 'all_clubs' rule
            
            $table->timestamps();
            
            $table->index(['dependent_type', 'dependent_id']);
            $table->index(['prerequisite_type', 'prerequisite_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearance_dependencies');
    }
};
