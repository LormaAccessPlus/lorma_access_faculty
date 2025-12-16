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
        'csv_data',
        'auto_matched',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'student_id' => 'integer',
        'mapping_confidence' => 'decimal:2',
        'csv_data' => 'array',
        'auto_matched' => 'boolean',
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

    /**
     * Get the formatted student name in "LASTNAME, Firstname" format
     */
    public function getFormattedNameAttribute(): string
    {
        return $this->formatNameToLastnameFirst($this->student_name);
    }

    /**
     * Format name to "LASTNAME, Firstname" format
     */
    private function formatNameToLastnameFirst($fullName): string
    {
        $fullName = trim($fullName);
        
        // If already in LASTNAME, Firstname format, return as is
        if (preg_match('/^[A-Z\s]+,\s*/', $fullName)) {
            return $fullName;
        }
        
        // Split name into parts
        $parts = array_values(array_filter(preg_split('/\s+/', $fullName)));
        
        if (count($parts) === 0) {
            return $fullName;
        }
        
        if (count($parts) === 1) {
            // Only one name part, return as is in uppercase
            return strtoupper($parts[0]);
        }
        
        // Last part is the last name, rest are first/middle names
        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);
        
        // Format: LASTNAME, First Name
        return strtoupper($lastName) . ', ' . $firstName;
    }
}
