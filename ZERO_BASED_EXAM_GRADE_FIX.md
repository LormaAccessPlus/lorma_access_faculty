# Zero-Based Matrix Exam Grade Fix

## Problem
In the zero-based matrix, when an exam score of 90/100 was entered, the exam grade column was showing **90** instead of the expected **54** (90 × 0.6).

## Root Cause
The `exam_grade` field in the `term_grades` table was storing the **raw percentage** instead of the **weighted contribution** to the term grade.

In `app/Http/Controllers/GradeController.php`, line 641 was:
```php
'exam_grade' => $termGrade->exam_score !== null ? round($rawExamScore, 2) : null,
```

This stored the raw exam percentage (90) instead of the weighted value (54).

## Solution
Changed line 641 to store the **weighted exam grade**:
```php
'exam_grade' => $termGrade->exam_score !== null ? round($weightedExamGrade, 2) : null,
```

## How It Works Now

### Zero-Based Matrix (40% CS + 60% Exam)
- **Exam Score**: 90/100
- **Raw Exam Percentage**: (90/100) × 100 = 90
- **Weighted Exam Grade**: 90 × 0.6 = **54** ✓
- **This is what displays in the "Exam Grade" column**

### Example Calculation
- Class Standing: 85 × 0.4 = 34
- Exam Grade: 90 × 0.6 = 54
- Term Grade: 34 + 54 = 88

## Files Modified

### 1. `app/Http/Controllers/GradeController.php`

**Change 1 - Line 641 in `calculateTermGrade()` method:**
- Changed to store weighted exam grade instead of raw exam score
```php
// BEFORE
'exam_grade' => $termGrade->exam_score !== null ? round($rawExamScore, 2) : null,

// AFTER
'exam_grade' => $termGrade->exam_score !== null ? round($weightedExamGrade, 2) : null,
```

**Change 2 - Line 487-489 in `updateExamScore()` method:**
- Changed to ALWAYS recalculate term grade when exam score is updated (not just when class_standing exists)
```php
// BEFORE
if ($termGrade->class_standing !== null && $termGrade->exam_score !== null) {
    $termGrade = $this->calculateTermGrade($termGrade, $subject->matrix_type);
}

// AFTER
// Always recalculate term grade when exam score is updated
$termGrade = $this->calculateTermGrade($termGrade, $subject->matrix_type);
```

### 2. `resources/views/grades/matrix.blade.php`
- Updated all three term columns (Prelim, Midterm, Finals) to display `exam_grade` instead of `exam_score`
- Changed format from 0 decimals to 2 decimals for consistency

## Why Two Changes Were Needed

1. **First change** ensures the correct weighted value is stored in the database
2. **Second change** ensures the calculation happens EVERY time an exam score is entered, even if class_standing doesn't exist yet

Without the second change, if you entered an exam score before any activities were graded, the exam_grade wouldn't be calculated because the condition `class_standing !== null` would fail.

## Testing
1. Enter a new exam score (e.g., 90/100)
2. The exam grade should immediately show the weighted value (e.g., 54.00 for zero-based)
3. Refresh the page - the value should remain correct
4. Update the exam score - the exam grade should recalculate correctly

## Note
This fix ensures consistency across all matrix types - the exam_grade field now stores the weighted contribution that's actually used in the term grade calculation, and it's calculated every time an exam score is entered or updated.
