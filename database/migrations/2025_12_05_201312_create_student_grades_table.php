<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_class_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_mapping_id')->constrained()->onDelete('cascade');
            $table->foreignId('component_item_id')->constrained()->onDelete('cascade');
            $table->decimal('score', 8, 2)->nullable(); // Raw score entered
            $table->decimal('computed_score', 8, 2)->nullable(); // After formula application
            $table->timestamps();
            
            $table->unique(['student_mapping_id', 'component_item_id']);
            $table->index('grading_class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
