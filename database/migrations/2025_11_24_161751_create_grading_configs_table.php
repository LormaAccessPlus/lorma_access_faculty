<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->string('term'); // prelim, midterm, finals
            $table->json('formula_config')->nullable(); // Stores the formula configuration
            $table->decimal('class_standing_weight', 5, 2)->default(60.00); // e.g., 60%
            $table->decimal('exam_weight', 5, 2)->default(40.00); // e.g., 40%
            $table->timestamps();
            
            $table->unique(['subject_id', 'term']);
        });

        // Add final rating configuration to subjects table
        Schema::table('subjects', function (Blueprint $table) {
            $table->json('final_rating_config')->nullable()->after('type');
            // Default: 30% Prelim, 30% Midterm, 40% Finals
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('final_rating_config');
        });
        
        Schema::dropIfExists('grading_configs');
    }
};
