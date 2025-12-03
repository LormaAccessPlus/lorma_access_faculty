<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This is an ALTERNATIVE approach to storing comprehensive exams
     * in a separate table instead of JSON in subjects table.
     * 
     * Choose ONE approach:
     * - Use this table for normalized data (recommended for complex queries)
     * - OR use JSON in subjects table (simpler, less joins)
     */
    public function up(): void
    {
        Schema::create('comprehensive_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_mapping_id')->constrained('student_mappings')->onDelete('cascade');
            $table->decimal('score', 5, 2)->comment('Comprehensive exam score');
            $table->decimal('max_score', 5, 2)->default(100.00)->comment('Maximum possible score');
            $table->date('exam_date')->nullable()->comment('Date when exam was taken');
            $table->text('remarks')->nullable()->comment('Additional notes about the exam');
            $table->timestamps();

            // Unique constraint: one comprehensive exam per student per subject
            $table->unique(['subject_id', 'student_mapping_id'], 'unique_comp_exam');
            
            // Indexes for performance
            $table->index('subject_id');
            $table->index('student_mapping_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprehensive_exams');
    }
};
