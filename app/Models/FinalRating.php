<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinalRating extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'student_mapping_id',
        'subject_id',
        'prelim_grade',
        'midterm_grade',
        'finals_grade',
        'final_rating',
        'academic_year',
        'semester'
    ];

    protected $casts = [
        'prelim_grade' => 'decimal:2',
        'midterm_grade' => 'decimal:2',
        'finals_grade' => 'decimal:2',
        'final_rating' => 'decimal:2'
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
