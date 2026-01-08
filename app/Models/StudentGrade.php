<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_class_id',
        'student_mapping_id',
        'component_id',
        'component_item_id',
        'score',
        'computed_score',
        'exam_score',
        'lecture_exam_score',
        'lab_exam_score',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'computed_score' => 'decimal:2',
        'exam_score' => 'decimal:2',
        'lecture_exam_score' => 'decimal:2',
        'lab_exam_score' => 'decimal:2',
    ];

    public function gradingClass(): BelongsTo
    {
        return $this->belongsTo(GradingClass::class);
    }

    public function studentMapping(): BelongsTo
    {
        return $this->belongsTo(StudentMapping::class);
    }

    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(ComponentItem::class);
    }
}
