<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentMapping extends Model
{
    use HasFactory;
    protected $fillable = [
        'subject_id',
        'student_id',
        'gcr_student_id',
        'student_name',
        'student_email',
        'mapping_confidence',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'student_id' => 'integer',
        'mapping_confidence' => 'decimal:2',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function gradeRecords(): HasMany
    {
        return $this->hasMany(GradeRecord::class);
    }
}
