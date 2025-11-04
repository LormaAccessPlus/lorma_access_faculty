<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolFaculty extends Model
{
    /**
     * The connection name for the model.
     */
    protected $connection = 'school_db';

    /**
     * The table associated with the model.
     */
    protected $table = 'teacher';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'TeacherID';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'TeacherID',
        'FirstName',
        'MiddleName',
        'LastName',
        'DeptID',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'TeacherID' => 'string',
        'DeptID' => 'string',
    ];

    /**
     * Get the full name of the faculty member.
     */
    public function getFullNameAttribute(): string
    {
        $name = trim($this->FirstName . ' ' . $this->MiddleName . ' ' . $this->LastName);
        return preg_replace('/\s+/', ' ', $name);
    }

    /**
     * Get the subject assignments for this faculty member.
     */
    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SchoolSubjectAssignment::class, 'TeacherID', 'TeacherID');
    }

    /**
     * Get current semester subject assignments.
     */
    public function currentSubjectAssignments(): HasMany
    {
        return $this->subjectAssignments()
            ->where('academic_year', config('app.current_academic_year', date('Y')))
            ->where('semester', config('app.current_semester', 1));
    }

    /**
     * Scope to find teacher by ID.
     */
    public function scopeByTeacherId($query, string $teacherId)
    {
        return $query->where('TeacherID', $teacherId);
    }
}