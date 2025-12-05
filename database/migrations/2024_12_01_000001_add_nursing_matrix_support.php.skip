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
        // Add comprehensive exam scores to subjects table
        Schema::table('subjects', function (Blueprint $table) {
            $table->json('comprehensive_exam_scores')->nullable()->after('final_rating_config');
        });

        // Add activity category to differentiate activities from quizzes
        Schema::table('activities', function (Blueprint $table) {
            $table->enum('activity_category', ['activity', 'quiz'])->default('activity')->after('type');
            $table->index('activity_category');
        });

        // Add matrix type to subjects if not exists
        if (!Schema::hasColumn('subjects', 'matrix_type')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->enum('matrix_type', ['zero-based', 'general-education', 'nursing', 'customized'])
                    ->default('general-education')
                    ->after('type');
                $table->index('matrix_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('comprehensive_exam_scores');
            if (Schema::hasColumn('subjects', 'matrix_type')) {
                $table->dropColumn('matrix_type');
            }
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['activity_category']);
            $table->dropColumn('activity_category');
        });
    }
};
