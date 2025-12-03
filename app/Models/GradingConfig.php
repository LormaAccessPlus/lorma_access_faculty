<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingConfig extends Model
{
    protected $fillable = [
        'subject_id',
        'term',
        'matrix_type',
        'formula_config',
        'class_standing_weight',
        'exam_weight',
        'custom_formulas', // Excel-like formulas for customized matrix
    ];

    protected $casts = [
        'formula_config' => 'array',
        'custom_formulas' => 'array', // Stores activity formulas, quiz formulas, exam formula, term grade formula
        'class_standing_weight' => 'decimal:2',
        'exam_weight' => 'decimal:2',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get default configuration for a term
     * 
     * @param string $term The term (prelim, midterm, finals)
     * @param string|null $matrixType The matrix type (general-education, nursing, zero-based, customized)
     */
    public static function getDefaultConfig(string $term, ?string $matrixType = null): array
    {
        // Zero-Based Matrix: 40% CS + 60% Exam (percentage formula) for all terms
        if ($matrixType === 'zero-based') {
            return [
                'class_standing_weight' => 40.00,
                'exam_weight' => 60.00,
                'formula_config' => [
                    'type' => 'percentage', // (score/items) × 100
                    'components' => []
                ]
            ];
        }

        // General Education Matrix: 66.67% CS + 33.33% Exam (transmuted formula)
        if ($matrixType === 'general-education') {
            return [
                'class_standing_weight' => 66.67,
                'exam_weight' => 33.33,
                'formula_config' => [
                    'type' => 'transmuted', // (score/items) × 50 + 50
                    'components' => []
                ]
            ];
        }

        // Nursing Matrix: 60% CS + 40% Exam (transmuted for midterm/finals)
        if ($matrixType === 'nursing') {
            if ($term === 'prelim') {
                return [
                    'class_standing_weight' => 100.00,
                    'exam_weight' => 0.00,
                    'formula_config' => [
                        'type' => 'percentage',
                        'components' => []
                    ]
                ];
            }
            return [
                'class_standing_weight' => 60.00,
                'exam_weight' => 40.00,
                'formula_config' => [
                    'type' => 'transmuted',
                    'components' => []
                ]
            ];
        }

        // Customized Matrix: 60% CS + 40% Exam (default, can be edited)
        if ($matrixType === 'customized') {
            if ($term === 'prelim') {
                return [
                    'class_standing_weight' => 100.00,
                    'exam_weight' => 0.00,
                    'formula_config' => [
                        'type' => 'percentage',
                        'components' => []
                    ]
                ];
            }
            return [
                'class_standing_weight' => 60.00,
                'exam_weight' => 40.00,
                'formula_config' => [
                    'type' => 'transmuted',
                    'components' => []
                ]
            ];
        }

        // Fallback default (if no matrix type specified)
        if ($term === 'prelim') {
            return [
                'class_standing_weight' => 100.00,
                'exam_weight' => 0.00,
                'formula_config' => [
                    'type' => 'percentage',
                    'components' => []
                ]
            ];
        }

        return [
            'class_standing_weight' => 60.00,
            'exam_weight' => 40.00,
            'formula_config' => [
                'type' => 'transmuted',
                'components' => []
            ]
        ];
    }
}
