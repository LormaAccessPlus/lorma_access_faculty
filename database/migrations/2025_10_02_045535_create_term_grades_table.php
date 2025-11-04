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
        Schema::create('term_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_mapping_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->enum('term', ['prelim', 'midterm', 'finals']);
            $table->decimal('class_standing', 5, 2)->nullable();
            $table->decimal('exam_score', 5, 2)->nullable();
            $table->decimal('exam_grade', 5, 2)->nullable();
            $table->decimal('term_grade', 5, 2)->nullable();
            $table->json('computation_config')->nullable();
            $table->timestamps();
            
            $table->unique(['student_mapping_id', 'subject_id', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_grades');
    }
};
