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
        Schema::table('subjects', function (Blueprint $table) {
            // Google Classroom mapping fields
            $table->string('gcr_class_name')->nullable()->after('gcr_class_id');
            
            // School database mapping fields
            $table->string('school_subject_code')->nullable()->after('gcr_class_name');
            $table->string('school_subject_name')->nullable()->after('school_subject_code');
            $table->string('school_schedule_code')->nullable()->after('school_subject_name');
            
            // Mapping status and metadata
            $table->enum('mapping_status', [
                'pending_student_mapping',
                'completed',
                'needs_review'
            ])->default('pending_student_mapping')->after('type');
            
            $table->integer('student_mappings_count')->default(0)->after('mapping_status');
            $table->text('mapping_notes')->nullable()->after('student_mappings_count');
            
            // Update type to include mapped subjects
            $table->enum('type', [
                'lecture_only', 
                'lecture_lab', 
                'lab_only', 
                'mapped'
            ])->default('lecture_only')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn([
                'gcr_class_name',
                'school_subject_code',
                'school_subject_name', 
                'school_schedule_code',
                'mapping_status',
                'student_mappings_count',
                'mapping_notes'
            ]);
            
            // Revert type enum
            $table->enum('type', ['lecture_only', 'lecture_lab'])->default('lecture_only')->change();
        });
    }
};