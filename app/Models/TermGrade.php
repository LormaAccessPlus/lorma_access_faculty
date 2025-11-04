<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TermGrade extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_mapping_id',
        'subject_id',
        'term',
        'class_standing',
        'exam_score',
        'exam_grade',
        'term_grade',
        'computation_config'
    ];

    protected $casts = [
        'computation_config' => 'array',
        'class_standing' => 'decimal:2',
        'exam_score' => 'decimal:2',
        'exam_grade' => 'decimal:2',
        'term_grade' => 'decimal:2'
    ];

    public function studentMapping(): BelongsTo
    {
        return $this->belongsTo(StudentMapping::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
