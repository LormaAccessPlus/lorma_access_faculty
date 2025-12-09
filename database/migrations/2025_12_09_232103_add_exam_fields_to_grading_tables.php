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
        Schema::table('grading_components', function (Blueprint $table) {
            $table->decimal('exam_max_score', 8, 2)->nullable()->after('formula');
        });
        
        Schema::table('student_grades', function (Blueprint $table) {
            $table->foreignId('component_id')->nullable()->after('grading_class_id')->constrained('grading_components')->onDelete('cascade');
            $table->decimal('exam_score', 8, 2)->nullable()->after('computed_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grading_components', function (Blueprint $table) {
            $table->dropColumn('exam_max_score');
        });
        
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropForeign(['component_id']);
            $table->dropColumn(['component_id', 'exam_score']);
        });
    }
};
