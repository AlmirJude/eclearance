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
        Schema::create('requirement_submissions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('requirement_id')->constrained('clearance_requirements')->cascadeOnDelete();
            $table->foreignId('clearance_item_id')->constrained('clearance_items')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            
            // Submission details
            $table->string('file_path')->nullable(); // For document uploads
            $table->text('notes')->nullable(); // Student notes
            
            // Review status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();
            
            $table->timestamps();
            
            // Each student can only submit once per requirement per clearance item
            $table->unique(['requirement_id', 'clearance_item_id', 'student_id'], 'unique_submission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requirement_submissions');
    }
};
