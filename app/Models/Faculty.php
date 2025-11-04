<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;

class Faculty extends Model implements Authenticatable
{
    use HasFactory, AuthenticatableTrait;

    protected $fillable = [
        'google_id',
        'email',
        'name',
        'school_faculty_id',
        'access_token',
        'refresh_token',
        'token_expires_at'
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    /**
     * Check if the faculty member has a valid access token
     */
    public function hasValidToken(): bool
    {
        return $this->access_token && 
               $this->token_expires_at && 
               $this->token_expires_at->isFuture();
    }

    /**
     * Check if the email domain is valid (@lorma.edu)
     */
    public function hasValidDomain(): bool
    {
        return str_ends_with($this->email, '@lorma.edu');
    }

    /**
     * Get the subjects for this faculty member
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * Get the linked school faculty record
     */
    public function schoolFaculty()
    {
        return $this->hasOne(SchoolFaculty::class, 'TeacherID', 'school_faculty_id');
    }

    /**
     * Check if faculty is linked to school database
     */
    public function isLinkedToSchool(): bool
    {
        return !empty($this->school_faculty_id);
    }
}
