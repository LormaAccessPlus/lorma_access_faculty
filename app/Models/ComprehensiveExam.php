<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comprehensive Exam Model
 * 
 * Stores comprehensive exam scores for nursing subjects.
 * This is used in the final rating calculation:
 * Final Rating = (Final Grade × 80%) + (Comprehensive Exam × 20%)
 */
class ComprehensiveExam extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subject_id',
        'student_mapping_id',
        'score',
        'max_score',
        'exam_date',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'exam_date' => 'date',
    ];

    /**
     * Get the subject that owns the comprehensive exam.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the student mapping that owns the comprehensive exam.
     */
    public function studentMapping(): BelongsTo
    {
        return $this->belongsTo(StudentMapping::class);
    }

    /**
     * Calculate the percentage score
     * 
     * @return float
     */
    public function getPercentageAttribute(): float
    {
        if ($this->max_score == 0) {
            return 0;
        }
        
        return round(($this->score / $this->max_score) * 100, 2);
    }

    /**
     * Calculate the transmuted score using nursing formula
     * Formula: (Score / Max Score × 60 + 40)
     * 
     * @return float
     */
    public function getTransmutedScoreAttribute(): float
    {
        if ($this->max_score == 0) {
            return 0;
        }
        
        return round(($this->score / $this->max_score) * 60 + 40, 2);
    }

    /**
     * Check if the exam score is passing
     * 
     * @param float $passingScore
     * @return bool
     */
    public function isPassing(float $passingScore = 75.0): bool
    {
        return $this->transmuted_score >= $passingScore;
    }

    /**
     * Scope a query to only include exams for a specific subject.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $subjectId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope a query to only include exams for a specific student.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $studentMappingId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForStudent($query, int $studentMappingId)
    {
        return $query->where('student_mapping_id', $studentMappingId);
    }
}
