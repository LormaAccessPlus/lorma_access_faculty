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
            $table->decimal('final_rating', 5, 2)->nullable()->after('term_grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('term_grades', function (Blueprint $table) {
            $table->dropColumn('final_rating');
        });
    }
};
