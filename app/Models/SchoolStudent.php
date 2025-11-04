<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolStudent extends Model
{
    /**
     * The connection name for the model.
     */
    protected $connection = 'school_db';

    /**
     * The table associated with the model.
     */
    protected $table = 'students';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'student_id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'course_id',
        'year_level',
        'section',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'student_id' => 'integer',
        'course_id' => 'integer',
        'year_level' => 'integer',
        'status' => 'string',
    ];

    /**
     * Get the full name of the student.
     */
    public function getFullNameAttribute(): string
    {
        $name = trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
        return preg_replace('/\s+/', ' ', $name);
    }

    /**
     * Get the student's subject enrollments.
     */
    public function subjectEnrollments(): HasMany
    {
        return $this->hasMany(SchoolSubjectAssignment::class, 'student_id', 'student_id');
    }

    /**
     * Get current semester enrollments.
     */
    public function currentEnrollments(): HasMany
    {
        return $this->subjectEnrollments()
            ->where('academic_year', config('app.current_academic_year', date('Y')))
            ->where('semester', config('app.current_semester', 1));
    }

    /**
     * Scope to filter active students.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to find student by student number.
     */
    public function scopeByStudentNumber($query, string $studentNumber)
    {
        return $query->where('student_number', $studentNumber);
    }

    /**
     * Scope to find student by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to filter by course.
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope to filter by year level.
     */
    public function scopeByYearLevel($query, int $yearLevel)
    {
        return $query->where('year_level', $yearLevel);
    }
}