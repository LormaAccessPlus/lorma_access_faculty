<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_components', function (Blueprint $table) {
            $table->decimal('lecture_exam_max_score', 8, 2)->nullable()->after('exam_max_score')->comment('Max score for lecture exam (for lecture+lab subjects)');
            $table->decimal('lab_exam_max_score', 8, 2)->nullable()->after('lecture_exam_max_score')->comment('Max score for lab exam (for lecture+lab subjects)');
        });
    }

    public function down(): void
    {
        Schema::table('grading_components', function (Blueprint $table) {
            $table->dropColumn(['lecture_exam_max_score', 'lab_exam_max_score']);
        });
    }
};
