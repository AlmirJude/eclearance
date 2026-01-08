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
        Schema::create('department_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title'); // "Dean", "Homeroom Adviser", etc.
            $table->string('clearance_type'); // Specific clearance item they sign
            $table->json('year_levels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // A user can be signatory for multiple clearance types in same department
            $table->unique(['department_id', 'user_id', 'clearance_type'], 'dept_signatories_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_signatories');
    }
};
