<?php

namespace App\Console\Commands;

use App\Models\Subject;
use App\Models\StudentMapping;
use App\Models\GradeRecord;
use App\Models\TermGrade;
use App\Models\GradingConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecalculateSubjectGrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grades:recalculate {subject_id} {--term=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all grades for a subject (useful after changing formulas)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subjectId = $this->argument('subject_id');
        $term = $this->option('term');

        $subject = Subject::find($subjectId);

        if (!$subject) {
            $this->error("Subject with ID {$subjectId} not found!");
            return 1;
        }

        $this->info("Recalculating grades for: {$subject->subject_code} - {$subject->subject_name}");

        $terms = $term ? [$term] : ['prelim', 'midterm', 'finals'];

        foreach ($terms as $termName) {
            $this->info("\nProcessing {$termName} term...");
            $this->recalculateTermGrades($subject, $termName);
        }

        $this->info("\n✓ Grade recalculation complete!");
        return 0;
    }

    /**
     * Recalculate grades for a specific term
     */
    private function recalculateTermGrades(Subject $subject, string $term): void
    {
        // Check if this is a nursing subject
        $isNursing = $subject->matrix_type === 'nursing';

        if ($isNursing) {
            $this->info("  Matrix type: Nursing");
            $this->info("  Using nursing formulas: Activities (15%), Quizzes (25%), Exam (60%)");
            $this->recalculateNursingGrades($subject, $term);
            return;
        }

        // Get grading configuration for this term
        $gradingConfig = GradingConfig::where('subject_id', $subject->id)
            ->where('term', $term)
            ->first();

        if (!$gradingConfig) {
            $this->warn("  No grading config found for {$term} term. Skipping...");
            return;
        }

        $this->info("  Formula type: " . ($gradingConfig->formula_config['type'] ?? 'percentage'));
        $this->info("  Weights: {$gradingConfig->class_standing_weight}% CS + {$gradingConfig->exam_weight}% Exam");

        // Get all student mappings for this subject
        $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();

        $this->info("  Students to process: " . $studentMappings->count());

        $bar = $this->output->createProgressBar($studentMappings->count());
        $bar->start();

        foreach ($studentMappings as $studentMapping) {
            // Recalculate class standing
            $this->recalculateClassStanding($studentMapping, $term, $gradingConfig);

            // Get and recalculate term grade
            $termGrade = TermGrade::where('student_mapping_id', $studentMapping->id)
                ->where('subject_id', $subject->id)
                ->where('term', $term)
                ->first();

            if ($termGrade) {
                $this->calculateTermGrade($termGrade, $gradingConfig);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n  ✓ {$term} term recalculated");
    }

    /**
     * Recalculate nursing grades for a specific term
     */
    private function recalculateNursingGrades(Subject $subject, string $term): void
    {
        $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();
        $this->info("  Students to process: " . $studentMappings->count());

        $bar = $this->output->createProgressBar($studentMappings->count());
        $bar->start();

        $calculator = new \App\Services\NursingGradeCalculator();

        foreach ($studentMappings as $studentMapping) {
            // Get all grade records for this student in this term
            $gradeRecords = GradeRecord::whereHas('activity', function ($query) use ($subject, $term) {
                $query->where('subject_id', $subject->id)
                      ->where('term', $term);
            })->where('student_mapping_id', $studentMapping->id)->get();

            if ($gradeRecords->isEmpty()) {
                $bar->advance();
                continue;
            }

            // Get all activities for this term
            $activities = \App\Models\Activity::where('subject_id', $subject->id)
                ->where('term', $term)
                ->get();

            // Separate activities and quizzes
            $activityRecords = collect();
            $quizRecords = collect();
            
            foreach ($gradeRecords as $gradeRecord) {
                $activity = $activities->firstWhere('id', $gradeRecord->activity_id);
                if ($activity) {
                    if ($activity->activity_category === 'quiz') {
                        $quizRecords->push($gradeRecord);
                    } else {
                        $activityRecords->push($gradeRecord);
                    }
                }
            }

            // Calculate totals
            $activitiesTotal = $activityRecords->sum('score');
            $activitiesMax = $activityRecords->sum('max_score');
            $quizzesTotal = $quizRecords->sum('score');
            $quizzesMax = $quizRecords->sum('max_score');

            // Calculate scores using nursing formulas
            $activitiesScore = $calculator->calculateActivityScore($activitiesTotal, $activitiesMax);
            $quizzesScore = $calculator->calculateQuizScore($quizzesTotal, $quizzesMax);

            // Get existing term grade to preserve exam score
            $existingTermGrade = TermGrade::where('student_mapping_id', $studentMapping->id)
                ->where('subject_id', $subject->id)
                ->where('term', $term)
                ->first();

            $examScore = $existingTermGrade?->exam_score ?? 0;
            $examMaxScore = $existingTermGrade?->exam_max_score ?? 100;
            
            $examGrade = $calculator->calculateExamScore($examScore, $examMaxScore);
            $termGrade = $calculator->calculateTermGrade($activitiesScore, $quizzesScore, $examGrade);

            // Update or create term grade record
            TermGrade::updateOrCreate(
                [
                    'student_mapping_id' => $studentMapping->id,
                    'subject_id' => $subject->id,
                    'term' => $term,
                ],
                [
                    'exam_score' => $examScore,
                    'exam_max_score' => $examMaxScore,
                    'exam_grade' => round($examGrade, 2),
                    'term_grade' => round($termGrade, 2),
                    'formula_config' => [
                        'activities_total' => $activitiesTotal,
                        'activities_max' => $activitiesMax,
                        'activities_score' => round($activitiesScore, 2),
                        'quizzes_total' => $quizzesTotal,
                        'quizzes_max' => $quizzesMax,
                        'quizzes_score' => round($quizzesScore, 2),
                        'exam_total' => $examScore,
                        'exam_max' => $examMaxScore,
                    ],
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n  ✓ {$term} term recalculated with nursing formulas");
    }

    /**
     * Recalculate class standing for a student
     */
    private function recalculateClassStanding(StudentMapping $studentMapping, string $term, GradingConfig $gradingConfig): void
    {
        // Get all grade records for this student in this term
        $gradeRecords = GradeRecord::whereHas('activity', function ($query) use ($studentMapping, $term) {
            $query->where('subject_id', $studentMapping->subject_id)
                  ->where('term', $term);
        })->where('student_mapping_id', $studentMapping->id)->get();

        if ($gradeRecords->isEmpty()) {
            return;
        }

        // Calculate total score and total possible score
        $totalScore = $gradeRecords->sum('score');
        $totalPossible = $gradeRecords->sum('max_score');

        if ($totalPossible == 0) {
            return;
        }

        // Get formula type from config
        $formulaType = $gradingConfig->formula_config['type'] ?? 'percentage';
        
        // Calculate class standing based on formula type
        if ($formulaType === 'transmuted') {
            // Transmuted formula: (score/items) × 50 + 50
            $classStanding = (($totalScore / $totalPossible) * 50) + 50;
        } else {
            // Percentage formula: (score/items) × 100
            $classStanding = ($totalScore / $totalPossible) * 100;
        }

        // Update or create term grade record
        TermGrade::updateOrCreate(
            [
                'student_mapping_id' => $studentMapping->id,
                'subject_id' => $studentMapping->subject_id,
                'term' => $term,
            ],
            [
                'class_standing' => round($classStanding, 2),
            ]
        );
    }

    /**
     * Calculate term grade
     */
    private function calculateTermGrade(TermGrade $termGrade, GradingConfig $gradingConfig): void
    {
        $classWeight = $gradingConfig->class_standing_weight / 100;
        $examWeight = $gradingConfig->exam_weight / 100;
        
        // Calculate exam score using the appropriate formula
        $examScore = 0;
        if ($termGrade->exam_score !== null) {
            $examMaxScore = $termGrade->exam_max_score ?? 100;
            
            // Get formula type from config
            $formulaType = $gradingConfig->formula_config['type'] ?? 'percentage';
            
            // Calculate exam score based on formula type
            if ($formulaType === 'transmuted') {
                // Transmuted formula: (score/items) × 50 + 50
                $examScore = (($termGrade->exam_score / $examMaxScore) * 50) + 50;
            } else {
                // Percentage formula: (score/items) × 100
                $examScore = ($termGrade->exam_score / $examMaxScore) * 100;
            }
        }
        
        // Calculate term grade by applying weights
        $termGradeValue = (($termGrade->class_standing ?? 0) * $classWeight) + ($examScore * $examWeight);

        $termGrade->update([
            'exam_grade' => $termGrade->exam_score !== null ? round($examScore, 2) : null,
            'term_grade' => round($termGradeValue, 2),
        ]);
    }
}
