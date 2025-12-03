<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'google_user_id',
        'course',
        'year_level',
        'section',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'year_level' => 'integer',
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
     * Get the subjects this student is enrolled in.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'student_subject')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * Get the student mappings for this student.
     */
    public function mappings(): HasMany
    {
        return $this->hasMany(StudentMapping::class, 'student_id');
    }

    /**
     * Scope to filter active students.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to find student by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to find student by student number.
     */
    public function scopeByStudentNumber($query, string $studentNumber)
    {
        return $query->where('student_number', $studentNumber);
    }

    /**
     * Scope to find student by Google User ID.
     */
    public function scopeByGoogleUserId($query, string $googleUserId)
    {
        return $query->where('google_user_id', $googleUserId);
    }
}
