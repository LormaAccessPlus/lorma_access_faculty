<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_classes', function (Blueprint $table) {
            $table->decimal('lecture_percentage', 5, 2)->nullable()->after('term_formula')->comment('Percentage for lecture in class standing (for lecture+lab subjects)');
            $table->decimal('lab_percentage', 5, 2)->nullable()->after('lecture_percentage')->comment('Percentage for lab in class standing (for lecture+lab subjects)');
        });
    }

    public function down(): void
    {
        Schema::table('grading_classes', function (Blueprint $table) {
            $table->dropColumn(['lecture_percentage', 'lab_percentage']);
        });
    }
};