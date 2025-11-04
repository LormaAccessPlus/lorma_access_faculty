<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SchoolDataSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show summary of school database data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("📊 School Database Summary");
        $this->info("=" . str_repeat("=", 50));
        
        try {
            // Teachers
            $teachers = DB::connection('school')->table('teacher')->get();
            $this->info("\n👨‍🏫 Teachers: " . $teachers->count());
            foreach ($teachers as $teacher) {
                $this->line("  • {$teacher->FirstName} {$teacher->LastName} ({$teacher->TeacherID})");
            }
            
            // Subjects
            $subjects = DB::connection('school')->table('subject')->get();
            $this->info("\n📚 Subjects: " . $subjects->count());
            foreach ($subjects as $subject) {
                $this->line("  • {$subject->SubjectID}: {$subject->Description} ({$subject->Units} units)");
            }
            
            // Schedules
            $schedules = DB::connection('school')->table('schedule')
                ->join('subject', 'schedule.SubjectID', '=', 'subject.SubjectID')
                ->join('teacher', 'schedule.TeacherID', '=', 'teacher.TeacherID')
                ->select('schedule.*', 'subject.Description as SubjectName', 'teacher.FirstName', 'teacher.LastName')
                ->get();
            
            $this->info("\n📅 Schedules: " . $schedules->count());
            foreach ($schedules as $schedule) {
                $this->line("  • {$schedule->CodeNumber}: {$schedule->SubjectName} - {$schedule->FirstName} {$schedule->LastName}");
            }
            
            // Students by course
            $studentsByCourse = DB::connection('school')->table('studentdata')
                ->select('CourseID', DB::raw('COUNT(*) as count'))
                ->groupBy('CourseID')
                ->get();
            
            $totalStudents = DB::connection('school')->table('studentdata')->count();
            $this->info("\n👥 Students: {$totalStudents}");
            foreach ($studentsByCourse as $course) {
                $this->line("  • {$course->CourseID}: {$course->count} students");
            }
            
            // Enrollments by term
            $enrollmentsByTerm = DB::connection('school')->table('enrollment')
                ->select('Term', 'SchoolYear', DB::raw('COUNT(*) as count'))
                ->groupBy('Term', 'SchoolYear')
                ->get();
            
            $this->info("\n📝 Enrollments:");
            foreach ($enrollmentsByTerm as $enrollment) {
                $this->line("  • {$enrollment->SchoolYear} {$enrollment->Term}: {$enrollment->count} enrollments");
            }
            
            // Grade records
            $gradeRecords = DB::connection('school')->table('termgrades')->count();
            $gradesWithData = DB::connection('school')->table('termgrades')
                ->where(function($query) {
                    $query->whereNotNull('PrelimGrade')
                          ->orWhereNotNull('MidtermGrade')
                          ->orWhereNotNull('FinalsGrade');
                })
                ->count();
            
            $this->info("\n📊 Grade Records: {$gradeRecords}");
            $this->line("  • With grades entered: {$gradesWithData}");
            $this->line("  • Empty (ready for grading): " . ($gradeRecords - $gradesWithData));
            
            // Recent activity
            $recentStudents = DB::connection('school')->table('studentdata')
                ->orderBy('RegDate', 'desc')
                ->limit(5)
                ->get();
            
            $this->info("\n🕒 Recent Students:");
            foreach ($recentStudents as $student) {
                $this->line("  • {$student->FirstName} {$student->LastName} ({$student->StudID}) - {$student->RegDate}");
            }
            
        } catch (\Exception $e) {
            $this->error("Error accessing school database: " . $e->getMessage());
            $this->info("Make sure the school database connection is configured properly.");
            return 1;
        }
        
        $this->info("\n✅ Summary completed successfully!");
        return 0;
    }
}
