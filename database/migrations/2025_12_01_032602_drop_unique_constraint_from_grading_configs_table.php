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
        Schema::table('grading_configs', function (Blueprint $table) {
            // Drop the old unique constraint on subject_id and term
            $table->dropUnique(['subject_id', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grading_configs', function (Blueprint $table) {
            // Restore the unique constraint
            $table->unique(['subject_id', 'term']);
        });
    }
};
