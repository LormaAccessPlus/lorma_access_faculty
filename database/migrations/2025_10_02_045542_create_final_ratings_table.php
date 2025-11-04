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
        Schema::create('final_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_mapping_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->decimal('prelim_grade', 5, 2)->nullable();
            $table->decimal('midterm_grade', 5, 2)->nullable();
            $table->decimal('finals_grade', 5, 2)->nullable();
            $table->decimal('final_rating', 5, 2)->nullable();
            $table->string('academic_year');
            $table->string('semester');
            $table->timestamps();
            
            $table->unique(['student_mapping_id', 'subject_id', 'academic_year', 'semester'], 'final_ratings_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_ratings');
    }
};
