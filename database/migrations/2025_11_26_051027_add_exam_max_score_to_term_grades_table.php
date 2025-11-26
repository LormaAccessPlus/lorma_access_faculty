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
        Schema::table('term_grades', function (Blueprint $table) {
            $table->decimal('exam_max_score', 5, 2)->default(100)->after('exam_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('term_grades', function (Blueprint $table) {
            $table->dropColumn('exam_max_score');
        });
    }
};
