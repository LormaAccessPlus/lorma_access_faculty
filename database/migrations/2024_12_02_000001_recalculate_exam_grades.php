<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get all term grades that have exam scores
        $termGrades = DB::table('term_grades')
            ->whereNotNull('exam_score')
            ->get();

        foreach ($termGrades as $termGrade) {
            // Get subject to determine matrix type
            $subject = DB::table('subjects')->find($termGrade->subject_id);
            
            if (!$subject) continue;

            // Skip nursing subjects - they already calculate correctly
            if ($subject->matrix_type === 'nursing') continue;

            // Get grading config for this term
            $gradingConfig = DB::table('grading_configs')
                ->where('subject_id', $termGrade->subject_id)
                ->where('term', $termGrade->term)
                ->first();

            // Determine weights and formula
            if ($gradingConfig) {
                $examWeight = $gradingConfig->exam_weight / 100;
                $formulaConfig = json_decode($gradingConfig->formula_config, true);
                $examFormulaType = $formulaConfig['type'] ?? 'percentage';
            } else {
                // Use matrix type defaults
                $matrixType = $subject->matrix_type ?? 'zero-based';
                $formulaConfig = $this->getFormulaConfig($matrixType, $termGrade->term);
                $examWeight = $formulaConfig['exam_weight'] / 100;
                $examFormulaType = $formulaConfig['exam_formula'];
            }

            // Calculate raw exam score
            $examMaxScore = $termGrade->exam_max_score ?? 100;
            
            if ($examFormulaType === 'transmuted') {
                $rawExamScore = (($termGrade->exam_score / $examMaxScore) * 50) + 50;
            } else {
                $rawExamScore = ($termGrade->exam_score / $examMaxScore) * 100;
            }

            // Calculate weighted exam grade
            $weightedExamGrade = $rawExamScore * $examWeight;

            // Recalculate term grade
            $termGradeValue = ($termGrade->class_standing ?? 0) + $weightedExamGrade;

            // Update the record
            DB::table('term_grades')
                ->where('id', $termGrade->id)
                ->update([
                    'exam_grade' => round($weightedExamGrade, 2),
                    'term_grade' => round($termGradeValue, 2),
                    'updated_at' => now()
                ]);
        }
    }

    public function down(): void
    {
        // Cannot reverse this migration
    }

    private function getFormulaConfig(string $matrixType, string $term): array
    {
        switch ($matrixType) {
            case 'zero-based':
                if ($term === 'prelim') {
                    return ['exam_weight' => 0.00, 'exam_formula' => 'percentage'];
                }
                return ['exam_weight' => 60.00, 'exam_formula' => 'percentage'];
            
            case 'general-education':
                return ['exam_weight' => 33.33, 'exam_formula' => 'transmuted'];
            
            case 'nursing':
                if ($term === 'prelim') {
                    return ['exam_weight' => 0.00, 'exam_formula' => 'percentage'];
                }
                return ['exam_weight' => 40.00, 'exam_formula' => 'transmuted'];
            
            default:
                return ['exam_weight' => 60.00, 'exam_formula' => 'percentage'];
        }
    }
};
