<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('faculty_id')->constrained()->onDelete('cascade');
            $table->string('gcr_class_id')->nullable();
            $table->string('class_name');
            $table->string('term'); // prelim, midterm, finals
            $table->json('term_formula')->nullable(); // Formula for computing term grade
            $table->timestamps();
            
            $table->index(['faculty_id', 'term']);
            $table->index('subject_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_classes');
    }
};
