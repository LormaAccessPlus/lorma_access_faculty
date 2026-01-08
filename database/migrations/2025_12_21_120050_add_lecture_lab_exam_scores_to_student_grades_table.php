<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->decimal('lecture_exam_score', 8, 2)->nullable()->after('exam_score')->comment('Exam score for lecture portion (for lecture+lab subjects)');
            $table->decimal('lab_exam_score', 8, 2)->nullable()->after('lecture_exam_score')->comment('Exam score for lab portion (for lecture+lab subjects)');
        });
    }

    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropColumn(['lecture_exam_score', 'lab_exam_score']);
        });
    }
};