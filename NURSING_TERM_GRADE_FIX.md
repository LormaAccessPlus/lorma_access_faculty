# Nursing Matrix Term Grade Discrepancy Fix

## Problem
The computed term grade displayed in the **term grade page** was different from the term grade shown in the **full matrix page** for nursing subjects.

## Root Cause
There was a discrepancy in how the maximum scores were calculated between the frontend (term grades view) and the backend (grade calculation logic):

### Frontend Calculation (Term Grades View)
```php
// In resources/views/grade-matrix/nursing/term-grades.blade.php
foreach($activities as $activity) {
    $gradeRecord = isset($gradeMatrix[$studentMapping->id][$activity->id]) ? $gradeMatrix[$studentMapping->id][$activity->id] : null;
    if($gradeRecord && $gradeRecord->score !== null) {
        $activitiesTotal += floatval($gradeRecord->score);
    }
    $activitiesMax += floatval($activity->max_score);  // ✅ Always adds max score
}
```

### Backend Calculation (Before Fix)
```php
// In app/Http/Controllers/GradeController.php
$activitiesTotal = $activityRecords->sum('score');
$activitiesMax = $activityRecords->sum('max_score');  // ❌ Only sums from existing grade records
```

**The Issue:** The backend only counted max scores for activities that had grade records, while the frontend counted max scores for ALL activities (including those not yet graded). This caused different denominators in the calculation, leading to different term grades.

## Solution
Updated the backend calculation in `GradeController.php` to match the frontend logic by:

1. **Always including ALL activity max scores** in the calculation, regardless of whether a grade record exists
2. **Only summing actual scores** from grade records that exist
3. Applied the same fix to both:
   - `recalculateNursingTermGrade()` method (called when saving activity/quiz grades)
   - `updateExamScore()` method (called when saving exam scores)

### Fixed Backend Calculation
```php
// Calculate totals for activities
$activitiesTotal = 0;
$activitiesMax = 0;

foreach ($activities->where('activity_category', '!=', 'quiz') as $activity) {
    $gradeRecord = $gradeRecords->firstWhere('activity_id', $activity->id);
    if ($gradeRecord && $gradeRecord->score !== null) {
        $activitiesTotal += floatval($gradeRecord->score);
    }
    // ✅ Always add max score, even if no grade record exists
    $activitiesMax += floatval($activity->max_score);
}

// Same logic applied to quizzes
$quizzesTotal = 0;
$quizzesMax = 0;

foreach ($activities->where('activity_category', 'quiz') as $quiz) {
    $gradeRecord = $gradeRecords->firstWhere('activity_id', $quiz->id);
    if ($gradeRecord && $gradeRecord->score !== null) {
        $quizzesTotal += floatval($gradeRecord->score);
    }
    // ✅ Always add max score, even if no grade record exists
    $quizzesMax += floatval($quiz->max_score);
}
```

## Impact
- **Term grades in the database** will now match **term grades displayed in the term grades view**
- **Full matrix page** will now show the same term grades as the **term grades page**
- The nursing formula calculation is now consistent across all views:
  - Activities: `(score/total × 60 + 40) × 15%`
  - Quizzes: `(score/total × 60 + 40) × 25%`
  - Exam: `(score/total × 60 + 40) × 60%`
  - Term Grade: `Activities + Quizzes + Exam`

## How to Apply the Fix

### For Existing Data
Since the term grades in the database were calculated with the old (buggy) logic, you need to recalculate them:

1. **Option 1: Use the Recalculate Button**
   - Go to the nursing matrix page for your subject
   - Click the **"Recalculate Grades"** button (purple button at the top)
   - This will recalculate all term grades using the fixed nursing formula

2. **Option 2: Re-save Grades**
   - Go to the term grades page
   - Re-save any activity, quiz, or exam score
   - This will trigger the recalculation for that student's term grade

### For New Data
All new grades entered after this fix will be calculated correctly automatically.

## Testing
To verify the fix:
1. Go to a nursing subject's term grades page
2. Enter some activity/quiz grades (leave some activities ungraded)
3. Enter an exam score
4. Note the computed term grade
5. Navigate to the full matrix page
6. Verify the term grade matches what was shown in the term grades page

## Files Modified
- `app/Http/Controllers/GradeController.php`
  - Updated `recalculateNursingTermGrade()` method
  - Updated `updateExamScore()` method (nursing calculation section)
- `routes/web.php`
  - Added `nursing.recalculate-grades` route for bulk recalculation
- `resources/views/grade-matrix/nursing/matrix.blade.php`
  - Added "Recalculate Grades" button
