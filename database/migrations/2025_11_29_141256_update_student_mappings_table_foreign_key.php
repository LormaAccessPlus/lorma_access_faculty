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
        Schema::table('student_mappings', function (Blueprint $table) {
            // Drop the old index if it exists
            $table->dropIndex(['subject_id', 'school_student_id']);
            
            // Rename column from school_student_id to student_id
            $table->renameColumn('school_student_id', 'student_id');
        });

        Schema::table('student_mappings', function (Blueprint $table) {
            // Add foreign key constraint
            $table->foreign('student_id')->references('id')->on('students')->onDelete('set null');
            
            // Add new index
            $table->index(['subject_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_mappings', function (Blueprint $table) {
            // Drop foreign key and index
            $table->dropForeign(['student_id']);
            $table->dropIndex(['subject_id', 'student_id']);
            
            // Rename back
            $table->renameColumn('student_id', 'school_student_id');
        });

        Schema::table('student_mappings', function (Blueprint $table) {
            // Restore old index
            $table->index(['subject_id', 'school_student_id']);
        });
    }
};
