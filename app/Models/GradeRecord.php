<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_mapping_id',
        'activity_id',
        'score',
        'max_score',
        'percentage',
        'term',
        'created_by'
    ];

    protected $casts = [
        'student_mapping_id' => 'integer',
        'activity_id' => 'integer',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'created_by' => 'integer',
    ];

    public function studentMapping(): BelongsTo
    {
        return $this->belongsTo(StudentMapping::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'created_by');
    }

    /**
     * Calculate and update percentage based on score and max_score
     */
    public function calculatePercentage(): void
    {
        if ($this->score !== null && $this->max_score > 0) {
            $this->percentage = ($this->score / $this->max_score) * 100;
        } else {
            $this->percentage = null;
        }
    }

    /**
     * Boot method to automatically calculate percentage when saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($gradeRecord) {
            $gradeRecord->calculatePercentage();
        });
    }
}
