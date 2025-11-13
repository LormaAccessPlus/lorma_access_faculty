<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoMapStudents extends Command
{
    protected $signature = 'map:auto {subject_id}';
    protected $description = 'Automatically map students by matching emails';

    public function handle()
    {
        $subjectId = $this->argument('subject_id');

        $this->info("Auto-mapping students for subject {$subjectId}...");
        $this->newLine();

        // Get unmapped students
        $unmappedStudents = DB::table('student_mappings')
            ->where('subject_id', $subjectId)
            ->whereNull('school_student_id')
            ->get();

        if ($unmappedStudents->isEmpty()) {
            $this->info('✓ All students are already mapped!');
            return 0;
        }

        $this->info("Found {$unmappedStudents->count()} unmapped students");
        $this->newLine();

        $mapped = 0;
        $notFound = 0;

        foreach ($unmappedStudents as $mapping) {
            $this->info("Processing: {$mapping->student_name}");
            
            // Try to find by email first
            $schoolStudent = null;
            
            if ($mapping->student_email) {
                $schoolStudent = DB::connection('school_db')
                    ->table('studentdata')
                    ->where('Email', $mapping->student_email)
                    ->first(['StudID', 'FirstName', 'LastName', 'Email']);
            }

            // If not found by email, try by name
            if (!$schoolStudent) {
                $nameParts = explode(' ', $mapping->student_name);
                $firstName = $nameParts[0] ?? '';
                $lastName = end($nameParts);

                $schoolStudent = DB::connection('school_db')
                    ->table('studentdata')
                    ->where('FirstName', 'LIKE', "%{$firstName}%")
                    ->where('LastName', 'LIKE', "%{$lastName}%")
                    ->first(['StudID', 'FirstName', 'LastName', 'Email']);
            }

            if ($schoolStudent) {
                // Update the mapping
                DB::table('student_mappings')
                    ->where('id', $mapping->id)
                    ->update([
                        'school_student_id' => $schoolStudent->StudID,
                        'mapping_confidence' => 0.90,
                        'updated_at' => now()
                    ]);

                $this->info("  ✓ Mapped to: {$schoolStudent->FirstName} {$schoolStudent->LastName} ({$schoolStudent->StudID})");
                $mapped++;
            } else {
                $this->warn("  ✗ Not found in school database");
                $notFound++;
            }
        }

        $this->newLine();
        $this->info("=== RESULTS ===");
        $this->info("✓ Mapped: {$mapped}");
        $this->warn("✗ Not found: {$notFound}");

        if ($notFound > 0) {
            $this->newLine();
            $this->info("To manually map remaining students:");
            $this->info("1. Find the student in school DB: php artisan find:student \"student name\"");
            $this->info("2. Map manually: php artisan map:student {mapping_id} {school_student_id}");
        }

        return 0;
    }
}
