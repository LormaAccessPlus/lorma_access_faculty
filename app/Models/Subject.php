<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;
    protected $fillable = [
        'school_subject_id',
        'faculty_id',
        'subject_code',
        'subject_name',
        'section',
        'type',
        'academic_year',
        'semester',
        'gcr_class_id',
        'gcr_class_name',
        'school_subject_code',
        'school_subject_name',
        'school_schedule_code',
        'mapping_status',
        'student_mappings_count',
        'mapping_notes'
    ];

    protected $casts = [
        'school_subject_id' => 'integer',
        'faculty_id' => 'integer',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function studentMappings(): HasMany
    {
        return $this->hasMany(StudentMapping::class);
    }

    public function isLectureOnly(): bool
    {
        return $this->type === 'lecture_only';
    }

    public function hasLaboratory(): bool
    {
        return $this->type === 'lecture_lab';
    }
}
