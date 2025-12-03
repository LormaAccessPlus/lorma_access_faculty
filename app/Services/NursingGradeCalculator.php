<?php

namespace App\Services;

/**
 * Nursing Grade Calculator Service
 * 
 * Implements the specific formulas used for nursing program grading:
 * - Activities: (Score/Total × 60 + 40) × 0.15
 * - Quizzes: (Score/Total × 60 + 40) × 0.25
 * - Exam: (Score/Total × 60 + 40) × 0.60
 * - Term Grade: Activities + Quizzes + Exam
 * - Final Rating: (Final Grade × 80%) + (Comprehensive Exam × 20%)
 */
class NursingGradeCalculator
{
    /**
     * Weight for activities component
     */
    const ACTIVITY_WEIGHT = 0.15;

    /**
     * Weight for quizzes component
     */
    const QUIZ_WEIGHT = 0.25;

    /**
     * Weight for exam component
     */
    const EXAM_WEIGHT = 0.60;

    /**
     * Weight for final grade in final rating
     */
    const FINAL_GRADE_WEIGHT = 0.80;

    /**
     * Weight for comprehensive exam in final rating
     */
    const COMPREHENSIVE_EXAM_WEIGHT = 0.20;

    /**
     * Calculate activity score using nursing formula
     * Formula: (Total Score / Overall Score × 60 + 40) × 0.15
     * 
     * @param float $totalScore Student's total score in activities
     * @param float $overallScore Maximum possible score in activities
     * @return float Calculated activity score
     */
    public function calculateActivityScore(float $totalScore, float $overallScore): float
    {
        if ($overallScore == 0) {
            return 0;
        }
        
        $percentage = ($totalScore / $overallScore) * 60 + 40;
        return round($percentage * self::ACTIVITY_WEIGHT, 2);
    }

    /**
     * Calculate quiz score using nursing formula
     * Formula: (Total Score / Overall Score × 60 + 40) × 0.25
     * 
     * @param float $totalScore Student's total score in quizzes
     * @param float $overallScore Maximum possible score in quizzes
     * @return float Calculated quiz score
     */
    public function calculateQuizScore(float $totalScore, float $overallScore): float
    {
        if ($overallScore == 0) {
            return 0;
        }
        
        $percentage = ($totalScore / $overallScore) * 60 + 40;
        return round($percentage * self::QUIZ_WEIGHT, 2);
    }

    /**
     * Calculate exam score using nursing formula
     * Formula: (Total Score / Overall Score × 60 + 40) × 0.60
     * 
     * @param float $totalScore Student's exam score
     * @param float $overallScore Maximum possible exam score
     * @return float Calculated exam score
     */
    public function calculateExamScore(float $totalScore, float $overallScore): float
    {
        if ($overallScore == 0) {
            return 0;
        }
        
        $percentage = ($totalScore / $overallScore) * 60 + 40;
        return round($percentage * self::EXAM_WEIGHT, 2);
    }

    /**
     * Calculate term grade
     * Formula: Activities + Quizzes + Exam
     * 
     * @param float $activitiesScore Calculated activities score
     * @param float $quizzesScore Calculated quizzes score
     * @param float $examScore Calculated exam score
     * @return float Term grade
     */
    public function calculateTermGrade(float $activitiesScore, float $quizzesScore, float $examScore): float
    {
        return round($activitiesScore + $quizzesScore + $examScore, 2);
    }

    /**
     * Calculate final grade from three terms
     * Formula: (Prelim × prelim_weight) + (Midterm × midterm_weight) + (Finals × finals_weight)
     * 
     * @param float $prelimGrade Prelim term grade
     * @param float $midtermGrade Midterm term grade
     * @param float $finalsGrade Finals term grade
     * @param array $weights Array with keys: prelim_weight, midterm_weight, finals_weight (as percentages)
     * @return float Final grade
     */
    public function calculateFinalGrade(
        float $prelimGrade, 
        float $midtermGrade, 
        float $finalsGrade, 
        array $weights = ['prelim_weight' => 30, 'midterm_weight' => 30, 'finals_weight' => 40]
    ): float {
        $finalGrade = ($prelimGrade * ($weights['prelim_weight'] / 100)) +
                     ($midtermGrade * ($weights['midterm_weight'] / 100)) +
                     ($finalsGrade * ($weights['finals_weight'] / 100));
        
        return round($finalGrade, 2);
    }

    /**
     * Calculate final rating with comprehensive exam
     * Formula: (Final Grade × 80%) + (Comprehensive Exam × 20%)
     * 
     * @param float $finalGrade Calculated final grade from three terms
     * @param float $comprehensiveExam Comprehensive exam score
     * @return float Final rating
     */
    public function calculateFinalRating(float $finalGrade, float $comprehensiveExam): float
    {
        $finalRating = ($finalGrade * self::FINAL_GRADE_WEIGHT) + 
                      ($comprehensiveExam * self::COMPREHENSIVE_EXAM_WEIGHT);
        
        return round($finalRating, 2);
    }

    /**
     * Calculate all components for a term at once
     * 
     * @param array $data Array containing:
     *   - activities_total: Student's total score in activities
     *   - activities_max: Maximum possible score in activities
     *   - quizzes_total: Student's total score in quizzes
     *   - quizzes_max: Maximum possible score in quizzes
     *   - exam_score: Student's exam score
     *   - exam_max: Maximum possible exam score
     * @return array Calculated scores with keys: activities_score, quizzes_score, exam_score, term_grade
     */
    public function calculateTermComponents(array $data): array
    {
        $activitiesScore = $this->calculateActivityScore(
            $data['activities_total'] ?? 0,
            $data['activities_max'] ?? 0
        );

        $quizzesScore = $this->calculateQuizScore(
            $data['quizzes_total'] ?? 0,
            $data['quizzes_max'] ?? 0
        );

        $examScore = $this->calculateExamScore(
            $data['exam_score'] ?? 0,
            $data['exam_max'] ?? 100
        );

        $termGrade = $this->calculateTermGrade($activitiesScore, $quizzesScore, $examScore);

        return [
            'activities_score' => $activitiesScore,
            'quizzes_score' => $quizzesScore,
            'exam_score' => $examScore,
            'term_grade' => $termGrade,
            'activities_total' => $data['activities_total'] ?? 0,
            'activities_max' => $data['activities_max'] ?? 0,
            'quizzes_total' => $data['quizzes_total'] ?? 0,
            'quizzes_max' => $data['quizzes_max'] ?? 0,
            'exam_total' => $data['exam_score'] ?? 0,
            'exam_max' => $data['exam_max'] ?? 100,
        ];
    }

    /**
     * Determine if a grade is passing
     * 
     * @param float $grade The grade to check
     * @param float $passingGrade The minimum passing grade (default: 75)
     * @return bool True if passing, false otherwise
     */
    public function isPassing(float $grade, float $passingGrade = 75.0): bool
    {
        return $grade >= $passingGrade;
    }

    /**
     * Get the status text for a grade
     * 
     * @param float $grade The grade to check
     * @param float $passingGrade The minimum passing grade (default: 75)
     * @return string 'PASSED' or 'FAILED'
     */
    public function getGradeStatus(float $grade, float $passingGrade = 75.0): string
    {
        return $this->isPassing($grade, $passingGrade) ? 'PASSED' : 'FAILED';
    }
}
