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
        Schema::create('grade_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_mapping_id')->constrained()->onDelete('cascade');
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->enum('term', ['prelim', 'midterm', 'finals']);
            $table->foreignId('created_by')->constrained('faculties')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure unique combination of student, activity
            $table->unique(['student_mapping_id', 'activity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_records');
    }
};
