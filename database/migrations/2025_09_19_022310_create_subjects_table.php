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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_subject_id')->nullable()->comment('Reference to school database subject');
            $table->foreignId('faculty_id')->constrained()->onDelete('cascade');
            $table->string('subject_code', 20);
            $table->string('subject_name', 255);
            $table->string('section', 50);
            $table->enum('type', ['lecture_only', 'lecture_lab'])->default('lecture_only');
            $table->string('academic_year', 20);
            $table->string('semester', 20);
            $table->string('gcr_class_id')->nullable()->comment('Google Classroom class ID');
            $table->timestamps();

            $table->index(['faculty_id', 'academic_year', 'semester']);
            $table->index(['subject_code', 'section', 'academic_year', 'semester']);
            $table->unique(['school_subject_id', 'faculty_id'], 'unique_school_subject_faculty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
