<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSubjectAssignment extends Model
{
    /**
     * The connection name for the model.
     */
    protected $connection = 'school_db';

    /**
     * The table associated with the model.
     */
    protected $table = 'subject_assignments';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'assignment_id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'faculty_id',
        'subject_id',
        'subject_code',
        'subject_name',
        'section',
        'units',
        'schedule',
        'room',
        'academic_year',
        'semester',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'assignment_id' => 'integer',
        'faculty_id' => 'integer',
        'subject_id' => 'integer',
        'units' => 'integer',
        'academic_year' => 'integer',
        'semester' => 'integer',
        'status' => 'string',
    ];

    /**
     * Get the faculty member assigned to this subject.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(SchoolFaculty::class, 'faculty_id', 'faculty_id');
    }

    /**
     * Get the students enrolled in this subject assignment.
     * Note: This assumes there's a pivot table or enrollment table
     */
    public function students()
    {
        // This would need to be adjusted based on actual school database structure
        // For now, assuming there's an enrollments table that links students to subject assignments
        return $this->belongsToMany(
            SchoolStudent::class,
            'enrollments',
            'assignment_id',
            'student_id',
            'assignment_id',
            'student_id'
        )->withPivot(['enrollment_date', 'status'])
         ->wherePivot('status', 'active');
    }

    /**
     * Get the subject display name with section.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->subject_code . ' - ' . $this->subject_name . ' (' . $this->section . ')';
    }

    /**
     * Scope to filter by academic year.
     */
    public function scopeByAcademicYear($query, int $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Scope to filter by semester.
     */
    public function scopeBySemester($query, int $semester)
    {
        return $query->where('semester', $semester);
    }

    /**
     * Scope to filter by faculty.
     */
    public function scopeByFaculty($query, int $facultyId)
    {
        return $query->where('faculty_id', $facultyId);
    }

    /**
     * Scope to filter current semester assignments.
     */
    public function scopeCurrent($query)
    {
        return $query->where('academic_year', config('app.current_academic_year', date('Y')))
                    ->where('semester', config('app.current_semester', 1));
    }

    /**
     * Scope to filter active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by subject code.
     */
    public function scopeBySubjectCode($query, string $subjectCode)
    {
        return $query->where('subject_code', $subjectCode);
    }

    /**
     * Scope to filter by section.
     */
    public function scopeBySection($query, string $section)
    {
        return $query->where('section', $section);
    }
}