<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FindGrades extends Command
{
    protected $signature = 'find:grades {student_id}';
    protected $description = 'Find all grades for a student by their school student ID';

    public function handle()
    {
        $studentId = $this->argument('student_id');

        $this->info("=== GRADES FOR STUDENT ID: {$studentId} ===");
        $this->newLine();

        // Get student info from school database
        $student = DB::connection('school_db')
            ->table('studentdata')
            ->where('StudID', $studentId)
            ->first(['StudID', 'FirstName', 'LastName', 'Email', 'CourseID', 'YearLevel']);

        if (!$student) {
            $this->error('Student not found in school database!');
            return 1;
        }

        $this->info("Student: {$student->FirstName} {$student->LastName}");
        $this->info("Email: {$student->Email}");
        $this->info("Course: {$student->CourseID} | Year: {$student->YearLevel}");
        $this->newLine();

        // Get grades from web app database
        $this->info('=== WEB APP DATABASE (Final Ratings) ===');
        
        $webAppGrades = DB::table('final_ratings')
            ->join('student_mappings', 'final_ratings.student_mapping_id', '=', 'student_mappings.id')
            ->join('subjects', 'final_ratings.subject_id', '=', 'subjects.id')
            ->where('student_mappings.school_student_id', $studentId)
            ->select(
                'subjects.subject_code',
                'subjects.subject_name',
                'final_ratings.prelim_grade',
                'final_ratings.midterm_grade',
                'final_ratings.finals_grade',
                'final_ratings.final_rating',
                'final_ratings.academic_year',
                'final_ratings.semester'
            )
            ->get();

        if ($webAppGrades->isEmpty()) {
            $this->warn('No grades found in web app database.');
        } else {
            $this->table(
                ['Subject Code', 'Subject Name', 'Prelim', 'Midterm', 'Finals', 'Final', 'Year', 'Sem'],
                $webAppGrades->map(function ($grade) {
                    return [
                        $grade->subject_code,
                        $grade->subject_name,
                        $grade->prelim_grade,
                        $grade->midterm_grade,
                        $grade->finals_grade,
                        $grade->final_rating,
                        $grade->academic_year,
                        $grade->semester,
                    ];
                })
            );
        }

        $this->newLine();

        // Get grades from school database
        $this->info('=== SCHOOL DATABASE (termgrades) ===');
        
        $schoolGrades = DB::connection('school_db')
            ->table('termgrades')
            ->where('StudID', $studentId)
            ->orderBy('SchoolYear', 'desc')
            ->orderBy('Term', 'desc')
            ->limit(20)
            ->get(['ID', 'CodeNumber', 'Term', 'SchoolYear', 'PrelimGrade', 'MidtermGrade', 'FinalsGrade', 'LastUpdate']);

        if ($schoolGrades->isEmpty()) {
            $this->warn('No grades found in school database.');
        } else {
            $this->table(
                ['Row ID', 'Code', 'Term', 'Year', 'Prelim', 'Midterm', 'Finals', 'Last Update'],
                $schoolGrades->map(function ($grade) {
                    return [
                        $grade->ID,
                        $grade->CodeNumber,
                        $grade->Term,
                        $grade->SchoolYear,
                        $grade->PrelimGrade ?: 'NULL',
                        $grade->MidtermGrade ?: 'NULL',
                        $grade->FinalsGrade ?: 'NULL',
                        $grade->LastUpdate,
                    ];
                })
            );
        }

        $this->newLine();
        $this->info('✓ Search complete!');

        return 0;
    }
}
