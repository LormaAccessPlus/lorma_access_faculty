<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_mappings', function (Blueprint $table) {
            $table->json('csv_data')->nullable()->after('mapping_confidence');
            $table->boolean('auto_matched')->default(false)->after('csv_data');
        });
    }

    public function down(): void
    {
        Schema::table('student_mappings', function (Blueprint $table) {
            $table->dropColumn(['csv_data', 'auto_matched']);
        });
    }
};
