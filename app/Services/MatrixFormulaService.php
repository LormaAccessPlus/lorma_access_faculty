<?php

namespace App\Services;

class MatrixFormulaService
{
    /**
     * Get formula configuration for a specific matrix type and term
     */
    public static function getFormulaConfig(string $matrixType, string $term): array
    {
        switch ($matrixType) {
            case 'zero-based':
                return self::getZeroBasedFormula($term);
            
            case 'general-education':
                return self::getGeneralEducationFormula($term);
            
            case 'nursing':
                return self::getNursingFormula($term);
            
            case 'customized':
                return self::getCustomizedFormula($term);
            
            default:
                return self::getDefaultFormula($term);
        }
    }
    
    /**
     * Zero-Based Matrix Formula
     * CS = score/total*100
     * Exam = score/total*100
     * Term Grade = (CS × 40%) + (Exam × 60%)
     * ALL TERMS use 40/60 split
     */
    private static function getZeroBasedFormula(string $term): array
    {
        return [
            'class_standing_weight' => 40.00,
            'exam_weight' => 60.00,
            'cs_formula' => 'percentage', // score/total*100
            'exam_formula' => 'percentage' // score/total*100
        ];
    }
    
    /**
     * General Education Matrix Formula
     * CS = score/total*50+50
     * Exam = score/total*50+50
     * Term Grade = (CS × 66.67%) + (Exam × 33.33%)
     */
    private static function getGeneralEducationFormula(string $term): array
    {
        return [
            'class_standing_weight' => 66.67,
            'exam_weight' => 33.33,
            'cs_formula' => 'transmuted', // score/total*50+50
            'exam_formula' => 'transmuted' // score/total*50+50
        ];
    }
    
    /**
     * Nursing Matrix Formula
     */
    private static function getNursingFormula(string $term): array
    {
        if ($term === 'prelim') {
            return [
                'class_standing_weight' => 100.00,
                'exam_weight' => 0.00,
                'cs_formula' => 'percentage',
                'exam_formula' => 'percentage'
            ];
        }
        
        return [
            'class_standing_weight' => 60.00,
            'exam_weight' => 40.00,
            'cs_formula' => 'transmuted',
            'exam_formula' => 'transmuted'
        ];
    }
    
    /**
     * Customized Matrix Formula (uses database config)
     */
    private static function getCustomizedFormula(string $term): array
    {
        // For customized, we'll use database config
        // This is just a fallback
        return [
            'class_standing_weight' => 60.00,
            'exam_weight' => 40.00,
            'cs_formula' => 'percentage',
            'exam_formula' => 'percentage'
        ];
    }
    
    /**
     * Default Formula
     */
    private static function getDefaultFormula(string $term): array
    {
        return [
            'class_standing_weight' => 40.00,
            'exam_weight' => 60.00,
            'cs_formula' => 'percentage',
            'exam_formula' => 'percentage'
        ];
    }
    
    /**
     * Calculate class standing based on formula type
     */
    public static function calculateClassStanding(float $totalScore, float $totalPossible, string $formulaType): float
    {
        if ($totalPossible == 0) {
            return 0;
        }
        
        if ($formulaType === 'transmuted') {
            // Transmuted: (score/total) × 50 + 50
            return (($totalScore / $totalPossible) * 50) + 50;
        } else {
            // Percentage: (score/total) × 100
            return ($totalScore / $totalPossible) * 100;
        }
    }
    
    /**
     * Calculate exam score based on formula type
     */
    public static function calculateExamScore(float $examScore, float $examMax, string $formulaType): float
    {
        if ($examMax == 0) {
            return 0;
        }
        
        if ($formulaType === 'transmuted') {
            // Transmuted: (score/total) × 50 + 50
            return (($examScore / $examMax) * 50) + 50;
        } else {
            // Percentage: (score/total) × 100
            return ($examScore / $examMax) * 100;
        }
    }
    
    /**
     * Calculate term grade
     */
    public static function calculateTermGrade(float $classStanding, float $examScore, float $csWeight, float $examWeight): float
    {
        return ($classStanding * ($csWeight / 100)) + ($examScore * ($examWeight / 100));
    }
}
