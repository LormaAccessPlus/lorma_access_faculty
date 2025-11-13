<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyGradeSync extends Command
{
    protected $signature = 'grades:verify {subject_id}';
    protected $description = 'Verify grades are synced between web app and school database';

    public function handle()
    {
        $subjectId = $this->argument('subject_id');

        $this->info('=== GRADE SYNC VERIFICATION ===');
        $this->newLine();

        // Get subject info
        $subject = DB::table('subjects')->where('id', $subjectId)->first();
        if (!$subject) {
            $this->error("Subject not found!");
            return 1;
        }

        $this->info("Subject: {$subject->subject_code} - {$subject->subject_name}");
        $this->info("School Schedule Code: {$subject->school_schedule_code}");
        $this->newLine();

        // Get grades from web app
        $this->info('=== WEB APP DATABASE (lorma_access_faculty) ===');
        $webAppGrades = DB::table('final_ratings')
            ->join('student_mappings', 'final_ratings.student_mapping_id', '=', 'student_mappings.id')
            ->where('final_ratings.subject_id', $subjectId)
            ->select(
                'student_mappings.school_student_id',
                'student_mappings.student_name',
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
            return 0;
        }

        $this->table(
            ['StudID', 'Student Name', 'Prelim', 'Midterm', 'Finals', 'Final Rating'],
            $webAppGrades->map(function ($grade) {
                return [
                    $grade->school_student_id,
                    $grade->student_name,
                    $grade->prelim_grade,
                    $grade->midterm_grade,
                    $grade->finals_grade,
                    $grade->final_rating,
                ];
            })
        );

        $this->newLine();

        // Get grades from school database
        $this->info('=== SCHOOL DATABASE (access_db) ===');
        
        $academicYear = $webAppGrades->first()->academic_year;
        $semester = $webAppGrades->first()->semester;
        
        // Convert semester to term
        $termMap = ['1st' => '1st', '2nd' => '2nd', '1' => '1st', '2' => '2nd'];
        $term = $termMap[$semester] ?? $semester;

        $this->info("Looking for: Academic Year = {$academicYear}, Term = {$term}");
        $this->newLine();

        $studIds = $webAppGrades->pluck('school_student_id')->toArray();

        // Try with the generated CodeNumber first
        $codeNumber = $this->generateCodeNumber($subject, $semester, $academicYear);
        $this->info("Generated CodeNumber: {$codeNumber}");
        
        $schoolGrades = DB::connection('school_db')
            ->table('termgrades')
            ->join('studentdata', 'termgrades.StudID', '=', 'studentdata.StudID')
            ->where('termgrades.CodeNumber', $codeNumber)
            ->where('termgrades.Term', $term)
            ->where('termgrades.SchoolYear', $academicYear)
            ->whereIn('termgrades.StudID', $studIds)
            ->select(
                'termgrades.StudID',
                DB::raw("CONCAT(studentdata.FirstName, ' ', studentdata.LastName) as student_name"),
                'termgrades.PrelimGrade',
                'termgrades.MidtermGrade',
                'termgrades.FinalsGrade',
                'termgrades.CodeNumber',
                'termgrades.LastUpdate'
            )
            ->get();

        if ($schoolGrades->isEmpty()) {
            $this->warn("No grades found with CodeNumber: {$codeNumber}");
            $this->info("Searching all records for these students...");
            
            // Search without CodeNumber restriction
            $schoolGrades = DB::connection('school_db')
                ->table('termgrades')
                ->join('studentdata', 'termgrades.StudID', '=', 'studentdata.StudID')
                ->where('termgrades.SchoolYear', $academicYear)
                ->where('termgrades.Term', $term)
                ->whereIn('termgrades.StudID', $studIds)
                ->select(
                    'termgrades.StudID',
                    DB::raw("CONCAT(studentdata.FirstName, ' ', studentdata.LastName) as student_name"),
                    'termgrades.PrelimGrade',
                    'termgrades.MidtermGrade',
                    'termgrades.FinalsGrade',
                    'termgrades.CodeNumber',
                    'termgrades.LastUpdate'
                )
                ->get();
        }

        if ($schoolGrades->isEmpty()) {
            $this->error('No matching grades found in school database!');
            $this->warn('Grades may not have been synced yet.');
            return 1;
        }

        $this->table(
            ['StudID', 'Student Name', 'Prelim', 'Midterm', 'Finals', 'CodeNumber', 'Last Update'],
            $schoolGrades->map(function ($grade) {
                return [
                    $grade->StudID,
                    $grade->student_name,
                    $grade->PrelimGrade ?: 'NULL',
                    $grade->MidtermGrade ?: 'NULL',
                    $grade->FinalsGrade ?: 'NULL',
                    $grade->CodeNumber,
                    $grade->LastUpdate,
                ];
            })
        );

        $this->newLine();
        $this->info('✓ Verification complete!');

        return 0;
    }

    private function generateCodeNumber($subject, string $semester, string $academicYear): string
    {
        $semesterNum = $this->convertSemesterToNumber($semester);
        $year = substr($academicYear, -2);
        $maxSubjectLength = 12 - 3;
        $subjectCode = substr($subject->subject_code, 0, $maxSubjectLength);
        
        return $subjectCode . $semesterNum . $year;
    }

    private function convertSemesterToNumber(string $semester): string
    {
        $semesterMap = [
            '1' => '1',
            '2' => '2',
            '3' => '3',
            'first' => '1',
            'second' => '2',
            'summer' => '3',
            '1st' => '1',
            '2nd' => '2'
        ];

        return $semesterMap[strtolower($semester)] ?? '1';
    }
}
