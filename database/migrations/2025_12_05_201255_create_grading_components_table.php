<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_class_id')->constrained()->onDelete('cascade');
            $table->string('component_name'); // Activities, Quizzes, Exams, etc.
            $table->string('component_type'); // activity, quiz, exam, attendance, custom
            $table->decimal('weight_percentage', 5, 2); // e.g., 15.00 for 15%
            $table->text('formula')->nullable(); // e.g., "score / total * 60 + 40"
            $table->integer('order')->default(0); // For ordering components
            $table->timestamps();
            
            $table->index('grading_class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_components');
    }
};
