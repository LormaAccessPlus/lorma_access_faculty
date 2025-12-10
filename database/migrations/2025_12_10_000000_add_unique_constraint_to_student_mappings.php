<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove any existing duplicates
        $this->removeDuplicates();
        
        // Add unique constraint to prevent future duplicates
        Schema::table('student_mappings', function (Blueprint $table) {
            // Add unique constraint on subject_id + student_name combination
            $table->unique(['subject_id', 'student_name'], 'unique_subject_student_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_mappings', function (Blueprint $table) {
            $table->dropUnique('unique_subject_student_name');
        });
    }

    /**
     * Remove duplicate student mappings, keeping the most recent one
     */
    private function removeDuplicates(): void
    {
        // Find duplicates based on subject_id + student_name
        $duplicates = DB::select("
            SELECT subject_id, student_name, COUNT(*) as count
            FROM student_mappings 
            GROUP BY subject_id, student_name 
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicates as $duplicate) {
            // Keep the most recent record, delete the rest
            $mappings = DB::table('student_mappings')
                ->where('subject_id', $duplicate->subject_id)
                ->where('student_name', $duplicate->student_name)
                ->orderBy('updated_at', 'desc')
                ->get();

            // Skip the first (most recent) record, delete the rest
            $mappingsToDelete = $mappings->slice(1);
            
            foreach ($mappingsToDelete as $mapping) {
                DB::table('student_mappings')->where('id', $mapping->id)->delete();
            }
        }
    }
};