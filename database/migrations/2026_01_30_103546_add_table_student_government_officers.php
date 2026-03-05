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
        Schema::create('student_government_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_government_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Student user
            $table->string('position'); // "President", "Vice President", "Secretary", "Treasurer"
            $table->boolean('can_sign')->default(true); // Whether they can sign clearances
            $table->json('year_levels')->nullable(); // Year levels they can sign for
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['student_government_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_government_officers');
    }
};
