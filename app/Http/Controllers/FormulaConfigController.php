<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\GradingConfig;
use App\Services\FormulaEvaluator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FormulaConfigController extends Controller
{
    /**
     * Show formula configuration page
     */
    public function show(Subject $subject, string $term)
    {
        $validTerms = ['prelim', 'midterm', 'finals'];
        if (!in_array($term, $validTerms)) {
            abort(404, 'Invalid term');
        }

        // Load activities for this term
        $activities = $subject->activities()
            ->where('term', $term)
            ->orderBy('type')
            ->orderBy('created_at')
            ->get();

        // Get or create grading config
        $gradingConfig = GradingConfig::firstOrCreate(
            [
                'subject_id' => $subject->id,
                'term' => $term,
                'matrix_type' => 'customized'
            ],
            [
                'class_standing_weight' => 60,
                'exam_weight' => 40,
                'custom_formulas' => []
            ]
        );

        return view('grades.formula-config', compact('subject', 'term', 'activities', 'gradingConfig'));
    }

    /**
     * Save formula configuration
     */
    public function save(Request $request, Subject $subject, string $term): JsonResponse
    {
        $validated = $request->validate([
            'activity_formulas' => 'nullable|array',
            'activity_formulas.*' => 'nullable|string',
            'quiz_formula' => 'nullable|string',
            'exam_formula' => 'nullable|string',
            'term_grade_formula' => 'required|string',
            'final_grade_formula' => 'nullable|string',
        ]);

        // Validate all formulas
        $formulas = array_merge(
            $validated['activity_formulas'] ?? [],
            array_filter([
                'quiz' => $validated['quiz_formula'] ?? null,
                'exam' => $validated['exam_formula'] ?? null,
                'term_grade' => $validated['term_grade_formula'],
                'final_grade' => $validated['final_grade_formula'] ?? null,
            ])
        );

        foreach ($formulas as $key => $formula) {
            if ($formula) {
                $validation = FormulaEvaluator::validate($formula);
                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid formula for $key: " . implode(', ', $validation['errors'])
                    ], 422);
                }
            }
        }

        // Save configuration
        $gradingConfig = GradingConfig::updateOrCreate(
            [
                'subject_id' => $subject->id,
                'term' => $term,
                'matrix_type' => 'customized'
            ],
            [
                'custom_formulas' => [
                    'activity_formulas' => $validated['activity_formulas'] ?? [],
                    'quiz_formula' => $validated['quiz_formula'] ?? null,
                    'exam_formula' => $validated['exam_formula'] ?? null,
                    'term_grade_formula' => $validated['term_grade_formula'],
                    'final_grade_formula' => $validated['final_grade_formula'] ?? null,
                ]
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Formula configuration saved successfully'
        ]);
    }

    /**
     * Test a formula with sample data
     */
    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'formula' => 'required|string',
            'variables' => 'required|array',
        ]);

        $validation = FormulaEvaluator::validate($validated['formula']);
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid formula: ' . implode(', ', $validation['errors'])
            ], 422);
        }

        $result = FormulaEvaluator::evaluate($validated['formula'], $validated['variables']);

        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    }
}
