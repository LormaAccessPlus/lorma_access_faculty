# General Education Matrix - Exam Score Calculation Fix

## Problem Description

When re-entering or changing exam scores in the General Education matrix, the displayed result would be different from the correct value. However, after reloading the page, the grade would display correctly.

## Root Cause

The issue had THREE parts - two in the backend and one critical issue in the frontend JavaScript:

### 1. Inconsistent `exam_grade` Storage

The `exam_grade` field was being stored as the **weighted exam contribution** instead of the **raw calculated exam score**:

**Before (Incorrect):**
```php
$weightedExamGrade = $rawExamScore * $examWeight;
$termGrade->update([
    'exam_grade' => $termGrade->exam_score !== null ? round($weightedExamGrade, 2) : null,
    // ...
]);
```

This meant:
- Raw exam score: (22/30) × 50 + 50 = 86.67
- Weighted: 86.67 × 33.33% = 28.89
- Stored in `exam_grade`: 28.89 ❌ (This is wrong for display)

**After (Correct):**
```php
$weightedExamGrade = $rawExamScore * $examWeight;
$termGrade->update([
    'exam_grade' => $termGrade->exam_score !== null ? round($rawExamScore, 2) : null,
    // ...
]);
```

Now:
- Raw exam score: (22/30) × 50 + 50 = 86.67
- Stored in `exam_grade`: 86.67 ✓ (Correct for display)
- Weighted for term grade calculation: 86.67 × 33.33% = 28.89 ✓

### 2. Missing Matrix Type Parameter

The `updateExamScore` method wasn't passing the subject's `matrix_type` to `calculateTermGrade`, which could cause it to use the wrong formula:

**Before:**
```php
if ($termGrade->class_standing !== null && $termGrade->exam_score !== null) {
    $termGrade = $this->calculateTermGrade($termGrade);
}
```

**After:**
```php
if ($termGrade->class_standing !== null && $termGrade->exam_score !== null) {
    $termGrade = $this->calculateTermGrade($termGrade, $subject->matrix_type);
}
```

## General Education Formula

For reference, the General Education matrix uses:

- **Class Standing Formula**: Transmuted = (score/total) × 50 + 50
- **Class Standing Weight**: 66.67%
- **Exam Formula**: Transmuted = (score/total) × 50 + 50
- **Exam Weight**: 33.33%
- **Term Grade**: (CS × 66.67%) + (Exam × 33.33%)

### Example Calculation

Given:
- Activities: 57/60 points
- Exam: 22/30 points

**Step 1: Calculate Raw Class Standing**
- Raw CS = (57/60) × 50 + 50 = 95.17

**Step 2: Apply CS Weight**
- Weighted CS = 95.17 × 66.67% = 63.45

**Step 3: Calculate Raw Exam Score**
- Raw Exam = (22/30) × 50 + 50 = 86.67

**Step 4: Apply Exam Weight**
- Weighted Exam = 86.67 × 33.33% = 28.89

**Step 5: Calculate Term Grade**
- Term Grade = 63.45 + 28.89 = 92.34

### 3. Frontend JavaScript Cell Selector Issue (CRITICAL)

The most critical issue was in the `saveExamScore()` JavaScript function in `term-grades.blade.php`. The code was using fragile CSS selectors to update the grade cells:

**Before (Incorrect):**
```javascript
const examGradeCell = row.querySelector('td:nth-last-child(2) div');
const termGradeCell = row.querySelector('td:last-child div');
```

These selectors were:
1. **Fragile**: They relied on the position of cells, which could break if the table structure changed
2. **Incorrect**: They were selecting the wrong cells or failing to find the cells entirely
3. **Missing null checks**: No validation that the cells were found before updating

**After (Correct):**
```javascript
const examGradeCell = row.querySelector('td.bg-orange-25 .text-sm');
const termGradeCell = row.querySelector('td.bg-red-25 .text-sm');

if (examGradeCell) {
    if (data.term_grade && data.term_grade.exam_grade !== null) {
        examGradeCell.textContent = parseFloat(data.term_grade.exam_grade).toFixed(2);
    }
}

if (termGradeCell) {
    if (data.term_grade && data.term_grade.term_grade !== null) {
        termGradeCell.textContent = parseFloat(data.term_grade.term_grade).toFixed(2);
    }
}
```

Now:
- Uses specific class selectors (`bg-orange-25` for exam grade, `bg-red-25` for term grade)
- Includes null checks before updating
- Properly formats numbers to 2 decimal places
- Matches the exact cells defined in the table structure

## Files Modified

- `app/Http/Controllers/GradeController.php`
  - Fixed `calculateTermGrade()` method to store raw exam score in `exam_grade` field
  - Updated `updateExamScore()` method to pass `matrix_type` parameter

- `resources/views/grades/term-grades.blade.php`
  - Fixed `saveExamScore()` JavaScript function to use correct cell selectors
  - Added null checks before updating cell content
  - Ensured proper number formatting with 2 decimal places

## Testing

To verify the fix:

1. Go to a General Education subject
2. Enter or change an exam score
3. Verify the term grade is calculated correctly immediately (without reload)
4. Reload the page and verify the grade remains the same

## Impact

This fix ensures that:
- Exam scores are displayed correctly in the UI
- Term grades are calculated correctly on first entry
- No discrepancy between initial calculation and post-reload calculation
- The correct formula (transmuted) is used for General Education subjects
