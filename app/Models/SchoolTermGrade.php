<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolTermGrade extends Model
{
    /**
     * The connection name for the model.
     */
    protected $connection = 'school_db';

    /**
     * The table associated with the model.
     */
    protected $table = 'termgrades';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'ID';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'StudID',
        'CodeNumber',
        'Term',
        'SchoolYear',
        'PrelimGrade',
        'MidtermGrade',
        'FinalsGrade',
        'PreFinalsGrade',
        'FirstGrading',
        'SecondGrading',
        'ThirdGrading',
        'FourthGrading',
        'isRemedial',
        'LastUpdate',
        'PrelimDate',
        'MidtermDate',
        'FinalsDate',
        'PreFinalsDate',
        'FirstDate',
        'SecondDate',
        'ThirdDate',
        'FourthDate'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'ID' => 'integer',
        'isRemedial' => 'integer',
        'LastUpdate' => 'datetime',
        'PrelimDate' => 'datetime',
        'MidtermDate' => 'datetime',
        'FinalsDate' => 'datetime',
        'PreFinalsDate' => 'datetime',
        'FirstDate' => 'datetime',
        'SecondDate' => 'datetime',
        'ThirdDate' => 'datetime',
        'FourthDate' => 'datetime'
    ];

    /**
     * Get the student associated with this grade record.
     * Note: This assumes the school database has a students table
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(SchoolStudent::class, 'StudID', 'StudID');
    }

    /**
     * Get the schedule/class associated with this grade record.
     * Note: This assumes the school database has a schedule table
     */
    public function schedule()
    {
        return $this->belongsTo(SchoolSchedule::class, 'CodeNumber', 'CodeNumber');
    }

    /**
     * Scope to filter by student ID.
     */
    public function scopeByStudent($query, string $studId)
    {
        return $query->where('StudID', $studId);
    }

    /**
     * Scope to filter by code number (class identifier).
     */
    public function scopeByCodeNumber($query, string $codeNumber)
    {
        return $query->where('CodeNumber', $codeNumber);
    }

    /**
     * Scope to filter by term.
     */
    public function scopeByTerm($query, string $term)
    {
        return $query->where('Term', $term);
    }

    /**
     * Scope to filter by school year.
     */
    public function scopeBySchoolYear($query, string $schoolYear)
    {
        return $query->where('SchoolYear', $schoolYear);
    }

    /**
     * Scope to filter by academic period.
     */
    public function scopeByAcademicPeriod($query, string $term, string $schoolYear)
    {
        return $query->where('Term', $term)->where('SchoolYear', $schoolYear);
    }

    /**
     * Scope to filter non-remedial grades.
     */
    public function scopeRegular($query)
    {
        return $query->where('isRemedial', 0);
    }

    /**
     * Scope to filter remedial grades.
     */
    public function scopeRemedial($query)
    {
        return $query->where('isRemedial', 1);
    }

    /**
     * Get all grade values as an array.
     */
    public function getGradesAttribute(): array
    {
        return [
            'prelim' => $this->PrelimGrade,
            'midterm' => $this->MidtermGrade,
            'finals' => $this->FinalsGrade,
            'prefinals' => $this->PreFinalsGrade,
            'first' => $this->FirstGrading,
            'second' => $this->SecondGrading,
            'third' => $this->ThirdGrading,
            'fourth' => $this->FourthGrading
        ];
    }

    /**
     * Get all grade dates as an array.
     */
    public function getGradeDatesAttribute(): array
    {
        return [
            'prelim' => $this->PrelimDate,
            'midterm' => $this->MidtermDate,
            'finals' => $this->FinalsDate,
            'prefinals' => $this->PreFinalsDate,
            'first' => $this->FirstDate,
            'second' => $this->SecondDate,
            'third' => $this->ThirdDate,
            'fourth' => $this->FourthDate
        ];
    }

    /**
     * Check if any grades are recorded.
     */
    public function hasGrades(): bool
    {
        $grades = $this->grades;
        return !empty(array_filter($grades, fn($grade) => $grade !== null && $grade !== ''));
    }

    /**
     * Get the final rating based on available grades.
     * This is a simple calculation - actual implementation may vary.
     */
    public function getFinalRatingAttribute(): ?float
    {
        $prelim = $this->parseGrade($this->PrelimGrade);
        $midterm = $this->parseGrade($this->MidtermGrade);
        $finals = $this->parseGrade($this->FinalsGrade);

        if ($prelim !== null && $midterm !== null && $finals !== null) {
            // Standard formula: Prelim 30%, Midterm 30%, Finals 40%
            return round(($prelim * 0.3) + ($midterm * 0.3) + ($finals * 0.4), 2);
        }

        return null;
    }

    /**
     * Parse grade string to float.
     */
    private function parseGrade(?string $grade): ?float
    {
        if ($grade === null || $grade === '') {
            return null;
        }

        return is_numeric($grade) ? (float) $grade : null;
    }
}