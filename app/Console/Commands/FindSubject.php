<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FindSubject extends Command
{
    protected $signature = 'find:subject {search?}';
    protected $description = 'Find subject by ID, code, or name';

    public function handle()
    {
        $search = $this->argument('search');

        if (!$search) {
            $this->info('=== ALL SUBJECTS ===');
            $subjects = DB::table('subjects')->get();
        } else {
            $this->info("=== SEARCHING FOR: {$search} ===");
            $subjects = DB::table('subjects')
                ->where('id', $search)
                ->orWhere('subject_code', 'LIKE', "%{$search}%")
                ->orWhere('subject_name', 'LIKE', "%{$search}%")
                ->orWhere('school_schedule_code', 'LIKE', "%{$search}%")
                ->get();
        }

        if ($subjects->isEmpty()) {
            $this->warn('No subjects found.');
            return 0;
        }

        $this->table(
            ['ID', 'Code', 'Name', 'Section', 'Type', 'Academic Year', 'Semester', 'School Code'],
            $subjects->map(function ($subject) {
                return [
                    $subject->id,
                    $subject->subject_code,
                    $subject->subject_name,
                    $subject->section,
                    $subject->type,
                    $subject->academic_year,
                    $subject->semester,
                    $subject->school_schedule_code,
                ];
            })
        );

        $this->newLine();
        $this->info('✓ Search complete!');

        return 0;
    }
}
