<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update confidence for existing mappings where emails match
        $mappings = DB::table('student_mappings')
            ->whereNotNull('student_id')
            ->whereNotNull('student_email')
            ->get();

        foreach ($mappings as $mapping) {
            // Get the student
            $student = DB::table('students')->where('id', $mapping->student_id)->first();
            
            if ($student && $student->email) {
                // Check if emails match (case-insensitive)
                if (strtolower($student->email) === strtolower($mapping->student_email)) {
                    // Update confidence to 100% (1.0)
                    DB::table('student_mappings')
                        ->where('id', $mapping->id)
                        ->update(['mapping_confidence' => 1.0]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this data migration
    }
};
