<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MapStudent extends Command
{
    protected $signature = 'map:student {mapping_id} {school_student_id}';
    protected $description = 'Manually map a student to school database';

    public function handle()
    {
        $mappingId = $this->argument('mapping_id');
        $schoolStudentId = $this->argument('school_student_id');

        // Get the mapping
        $mapping = DB::table('student_mappings')->where('id', $mappingId)->first();
        
        if (!$mapping) {
            $this->error("Mapping ID {$mappingId} not found!");
            return 1;
        }

        // Check if school student exists
        $schoolStudent = DB::connection('school_db')
            ->table('studentdata')
            ->where('StudID', $schoolStudentId)
            ->first(['StudID', 'FirstName', 'LastName', 'Email']);

        if (!$schoolStudent) {
            $this->error("School student ID {$schoolStudentId} not found in school database!");
            $this->info("\nSearching for similar students...");
            
            // Try to find similar students
            $similar = DB::connection('school_db')
                ->table('studentdata')
                ->where(function($query) use ($mapping) {
                    $query->where('FirstName', 'LIKE', "%{$mapping->student_name}%")
                          ->orWhere('LastName', 'LIKE', "%{$mapping->student_name}%")
                          ->orWhere('Email', 'LIKE', "%{$mapping->student_email}%");
                })
                ->limit(10)
                ->get(['StudID', 'FirstName', 'LastName', 'Email']);

            if ($similar->isNotEmpty()) {
                $this->table(
                    ['StudID', 'First Name', 'Last Name', 'Email'],
                    $similar->map(fn($s) => [$s->StudID, $s->FirstName, $s->LastName, $s->Email])
                );
            } else {
                $this->warn('No similar students found.');
            }
            
            return 1;
        }

        // Update the mapping
        DB::table('student_mappings')
            ->where('id', $mappingId)
            ->update([
                'school_student_id' => $schoolStudentId,
                'mapping_confidence' => 1.00,
                'updated_at' => now()
            ]);

        $this->info("✓ Successfully mapped!");
        $this->info("  GCR Student: {$mapping->student_name}");
        $this->info("  School Student: {$schoolStudent->FirstName} {$schoolStudent->LastName} ({$schoolStudentId})");
        $this->info("  Email: {$schoolStudent->Email}");

        return 0;
    }
}
