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
        Schema::create('clearance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('clearance_requests')->onDelete('cascade');
            
            // Polymorphic relation to Club, Office, or Department signatories
            $table->string('signable_type'); // Club, Office, Department
            $table->unsignedBigInteger('signable_id');
            
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('signed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('signed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->index(['signable_type', 'signable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearance_items');
    }
};
