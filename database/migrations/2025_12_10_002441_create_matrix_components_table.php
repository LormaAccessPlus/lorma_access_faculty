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
        Schema::create('matrix_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->string('component_name');
            $table->decimal('max_score', 8, 2)->default(100);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['subject_id', 'order']);
        });
        
        // Add table for storing scores
        Schema::create('matrix_component_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matrix_component_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_mapping_id')->constrained()->onDelete('cascade');
            $table->decimal('score', 8, 2)->nullable();
            $table->timestamps();
            
            $table->unique(['matrix_component_id', 'student_mapping_id'], 'matrix_comp_student_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matrix_component_scores');
        Schema::dropIfExists('matrix_components');
    }
};
