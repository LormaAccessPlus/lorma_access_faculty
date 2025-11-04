<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\FinalRating;
use App\Models\GradeRecord;
use App\Models\StudentMapping;
use App\Models\Subject;
use App\Models\TermGrade;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeComputationService
{
    /**
     * Default computation configuration
     */
    private array $defaultConfig = [
        'prelim' => [
            'class_standing_weight' => 0.4,
            'exam_weight' => 0.6,
            'class_standing_formula' => 'percentage', // (total_score/total_items) × 100
        ],
        'midterm' => [
            'class_standing_weight' => 0.4,
            'exam_weight' => 0.6,
            'class_standing_formula' => 'transmuted', // (score/items) × 50 + 50
            'exam_formula' => 'transmuted', // (score/items) × 50 + 50
        ],
        'finals' => [
            'class_standing_weight' => 0.4,
            'exam_weight' => 0.6,
            'class_standing_formula' => 'transmuted', // (score/items) × 50 + 50
            'exam_formula' => 'transmuted', // (score/items) × 50 + 50
        ],
        'final_rating' => [
            'prelim_weight' => 0.3,
            'midterm_weight' => 0.3,
            'finals_weight' => 0.4,
        ]
    ];

    /**
     * Compute class standing for a student in a specific term
     */
    public function computeClassStanding(StudentMapping $studentMapping, Subject $subject, string $term): float
    {
        $activities = Activity::where('subject_id', $subject->id)
            ->where('term', $term)
            ->get();

        if ($activities->isEmpty()) {
            return 0.0;
        }

        $gradeRecords = GradeRecord::where('student_mapping_id', $studentMapping->id)
            ->whereIn('activity_id', $activities->pluck('id'))
            ->get()
            ->keyBy('activity_id');

        $totalScore = 0;
        $totalMaxScore = 0;

        foreach ($activities as $activity) {
            $gradeRecord = $gradeRecords->get($activity->id);
            if ($gradeRecord) {
                $totalScore += $gradeRecord->score;
                $totalMaxScore += $gradeRecord->max_score;
            } else {
                // If no grade record exists, assume 0 score but still count max score
                $totalMaxScore += $activity->max_score;
            }
        }

        if ($totalMaxScore == 0) {
            return 0.0;
        }

        $config = $this->getComputationConfig($subject);
        $termConfig = $config[$term] ?? $this->defaultConfig[$term];

        // Apply the appropriate formula based on term
        if ($term === 'prelim' || ($termConfig['class_standing_formula'] ?? 'percentage') === 'percentage') {
            // Prelim: (total_score/total_items) × 100
            return round(($totalScore / $totalMaxScore) * 100, 2);
        } else {
            // Midterm/Finals: (score/items) × 50 + 50
            return round((($totalScore / $totalMaxScore) * 50) + 50, 2);
        }
    }

    /**
     * Compute exam grade for a student in a specific term
     */
    public function computeExamGrade(float $examScore, float $examMaxScore, string $term, Subject $subject): float
    {
        if ($examMaxScore == 0) {
            return 0.0;
        }

        $config = $this->getComputationConfig($subject);
        $termConfig = $config[$term] ?? $this->defaultConfig[$term];

        // Apply the appropriate formula based on term
        if ($term === 'prelim' || ($termConfig['exam_formula'] ?? 'percentage') === 'percentage') {
            // Prelim: (score/items) × 100
            return round(($examScore / $examMaxScore) * 100, 2);
        } else {
            // Midterm/Finals: (score/items) × 50 + 50
            return round((($examScore / $examMaxScore) * 50) + 50, 2);
        }
    }

    /**
     * Compute term grade for a student
     */
    public function computeTermGrade(StudentMapping $studentMapping, Subject $subject, string $term, float $examScore = null, float $examMaxScore = null): TermGrade
    {
        $config = $this->getComputationConfig($subject);
        $termConfig = $config[$term] ?? $this->defaultConfig[$term];

        // Compute class standing
        $classStanding = $this->computeClassStanding($studentMapping, $subject, $term);

        // Compute exam grade if exam scores are provided
        $examGrade = 0.0;
        if ($examScore !== null && $examMaxScore !== null) {
            $examGrade = $this->computeExamGrade($examScore, $examMaxScore, $term, $subject);
        }

        // Compute term grade using weighted formula
        $termGrade = round(
            ($classStanding * $termConfig['class_standing_weight']) + 
            ($examGrade * $termConfig['exam_weight']),
            2
        );

        // Create or update TermGrade record
        return TermGrade::updateOrCreate(
            [
                'student_mapping_id' => $studentMapping->id,
                'subject_id' => $subject->id,
                'term' => $term,
            ],
            [
                'class_standing' => $classStanding,
                'exam_score' => $examScore,
                'exam_grade' => $examGrade,
                'term_grade' => $termGrade,
                'computation_config' => $termConfig,
            ]
        );
    }

    /**
     * Compute final rating for a student
     */
    public function computeFinalRating(StudentMapping $studentMapping, Subject $subject, string $academicYear, string $semester): FinalRating
    {
        $config = $this->getComputationConfig($subject);
        $finalConfig = $config['final_rating'] ?? $this->defaultConfig['final_rating'];

        // Get term grades
        $termGrades = TermGrade::where('student_mapping_id', $studentMapping->id)
            ->where('subject_id', $subject->id)
            ->whereIn('term', ['prelim', 'midterm', 'finals'])
            ->get()
            ->keyBy('term');

        $prelimGrade = $termGrades->get('prelim')?->term_grade ?? 0.0;
        $midtermGrade = $termGrades->get('midterm')?->term_grade ?? 0.0;
        $finalsGrade = $termGrades->get('finals')?->term_grade ?? 0.0;

        // Compute final rating using weighted formula
        $finalRating = round(
            ($prelimGrade * $finalConfig['prelim_weight']) +
            ($midtermGrade * $finalConfig['midterm_weight']) +
            ($finalsGrade * $finalConfig['finals_weight']),
            2
        );

        // Create or update FinalRating record
        return FinalRating::updateOrCreate(
            [
                'student_mapping_id' => $studentMapping->id,
                'subject_id' => $subject->id,
                'academic_year' => $academicYear,
                'semester' => $semester,
            ],
            [
                'prelim_grade' => $prelimGrade,
                'midterm_grade' => $midtermGrade,
                'finals_grade' => $finalsGrade,
                'final_rating' => $finalRating,
            ]
        );
    }

    /**
     * Compute all term grades for a subject
     */
    public function computeAllTermGrades(Subject $subject, string $term, array $examScores = []): Collection
    {
        $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();
        $termGrades = collect();

        foreach ($studentMappings as $studentMapping) {
            $examScore = $examScores[$studentMapping->id]['score'] ?? null;
            $examMaxScore = $examScores[$studentMapping->id]['max_score'] ?? null;

            $termGrade = $this->computeTermGrade($studentMapping, $subject, $term, $examScore, $examMaxScore);
            $termGrades->push($termGrade);
        }

        return $termGrades;
    }

    /**
     * Compute all final ratings for a subject
     */
    public function computeAllFinalRatings(Subject $subject, string $academicYear, string $semester): Collection
    {
        $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();
        $finalRatings = collect();

        foreach ($studentMappings as $studentMapping) {
            $finalRating = $this->computeFinalRating($studentMapping, $subject, $academicYear, $semester);
            $finalRatings->push($finalRating);
        }

        return $finalRatings;
    }

    /**
     * Get computation configuration for a subject
     */
    private function getComputationConfig(Subject $subject): array
    {
        // For now, return default config
        // In the future, this could be stored in database or configuration files
        return $this->defaultConfig;
    }

    /**
     * Validate computation inputs
     */
    public function validateComputationInputs(array $inputs): array
    {
        $errors = [];

        if (isset($inputs['exam_score']) && isset($inputs['exam_max_score'])) {
            if ($inputs['exam_score'] < 0) {
                $errors[] = 'Exam score cannot be negative';
            }
            if ($inputs['exam_max_score'] <= 0) {
                $errors[] = 'Exam max score must be greater than zero';
            }
            if ($inputs['exam_score'] > $inputs['exam_max_score']) {
                $errors[] = 'Exam score cannot exceed max score';
            }
        }

        if (isset($inputs['term']) && !in_array($inputs['term'], ['prelim', 'midterm', 'finals'])) {
            $errors[] = 'Invalid term specified';
        }

        return $errors;
    }

    /**
     * Get grade statistics for a subject and term
     */
    public function getGradeStatistics(Subject $subject, string $term): array
    {
        $termGrades = TermGrade::where('subject_id', $subject->id)
            ->where('term', $term)
            ->whereNotNull('term_grade')
            ->get();

        if ($termGrades->isEmpty()) {
            return [
                'count' => 0,
                'average' => 0,
                'highest' => 0,
                'lowest' => 0,
                'passing_count' => 0,
                'passing_rate' => 0,
            ];
        }

        $grades = $termGrades->pluck('term_grade');
        $passingGrades = $grades->filter(fn($grade) => $grade >= 75);

        return [
            'count' => $termGrades->count(),
            'average' => round($grades->average(), 2),
            'highest' => $grades->max(),
            'lowest' => $grades->min(),
            'passing_count' => $passingGrades->count(),
            'passing_rate' => round(($passingGrades->count() / $termGrades->count()) * 100, 2),
        ];
    }
}