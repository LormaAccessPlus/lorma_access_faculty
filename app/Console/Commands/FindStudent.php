<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FindStudent extends Command
{
    protected $signature = 'find:student {search}';
    protected $description = 'Find student by ID or name in both databases';

    public function handle()
    {
        $search = $this->argument('search');

        $this->info("=== SEARCHING FOR: {$search} ===");
        $this->newLine();

        // Search in web app database (student_mappings)
        $this->info('=== WEB APP DATABASE (lorma_access_faculty.student_mappings) ===');
        
        $webAppStudents = DB::table('student_mappings')
            ->where('student_name', 'LIKE', "%{$search}%")
            ->orWhere('school_student_id', 'LIKE', "%{$search}%")
            ->orWhere('gcr_student_id', 'LIKE', "%{$search}%")
            ->get(['id', 'student_name', 'school_student_id', 'gcr_student_id', 'subject_id']);

        if ($webAppStudents->isEmpty()) {
            $this->warn('No students found in web app database.');
        } else {
            $this->table(
                ['Mapping ID', 'Student Name', 'School ID', 'GCR ID', 'Subject ID'],
                $webAppStudents->map(function ($student) {
                    return [
                        $student->id,
                        $student->student_name,
                        $student->school_student_id,
                        $student->gcr_student_id,
                        $student->subject_id,
                    ];
                })
            );
        }

        $this->newLine();

        // Search in school database (studentdata)
        $this->info('=== SCHOOL DATABASE (access_db.studentdata) ===');
        
        $schoolStudents = DB::connection('school_db')
            ->table('studentdata')
            ->where(function ($query) use ($search) {
                $query->where('StudID', 'LIKE', "%{$search}%")
                    ->orWhere('FirstName', 'LIKE', "%{$search}%")
                    ->orWhere('LastName', 'LIKE', "%{$search}%")
                    ->orWhere('Email', 'LIKE', "%{$search}%");
            })
            ->select('StudID', 'FirstName', 'LastName', 'Email', 'CourseID', 'YearLevel')
            ->limit(20)
            ->get();

        if ($schoolStudents->isEmpty()) {
            $this->warn('No students found in school database.');
        } else {
            $this->table(
                ['StudID', 'First Name', 'Last Name', 'Email', 'Course', 'Year'],
                $schoolStudents->map(function ($student) {
                    return [
                        $student->StudID,
                        $student->FirstName,
                        $student->LastName,
                        $student->Email,
                        $student->CourseID,
                        $student->YearLevel,
                    ];
                })
            );
        }

        $this->newLine();
        $this->info('✓ Search complete!');

        return 0;
    }
}
