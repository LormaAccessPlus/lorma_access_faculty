<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update grading_configs to store component scores
        Schema::table('grading_configs', function (Blueprint $table) {
            $table->json('component_scores')->nullable()->after('formula_config');
        });
    }

    public function down(): void
    {
        Schema::table('grading_configs', function (Blueprint $table) {
            $table->dropColumn('component_scores');
        });
    }
};
