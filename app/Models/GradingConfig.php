<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingConfig extends Model
{
    protected $fillable = [
        'subject_id',
        'term',
        'formula_config',
        'class_standing_weight',
        'exam_weight',
    ];

    protected $casts = [
        'formula_config' => 'array',
        'class_standing_weight' => 'decimal:2',
        'exam_weight' => 'decimal:2',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get default configuration for a term
     */
    public static function getDefaultConfig(string $term): array
    {
        if ($term === 'prelim') {
            return [
                'class_standing_weight' => 100.00,
                'exam_weight' => 0.00,
                'formula_config' => [
                    'type' => 'percentage', // percentage or transmuted
                    'components' => []
                ]
            ];
        }

        return [
            'class_standing_weight' => 60.00,
            'exam_weight' => 40.00,
            'formula_config' => [
                'type' => 'transmuted', // (score/items) × 50 + 50
                'components' => []
            ]
        ];
    }
}
