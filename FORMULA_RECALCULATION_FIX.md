# Formula Recalculation Fix

## Problem
When changing the grading formula (term weights or final rating weights), the grades were not updating automatically. Users had to manually re-enter activity scores to trigger recalculation, which was frustrating and inefficient.

## Root Cause
The `saveGradingConfig()` and `saveFinalRatingConfig()` methods in `GradeController.php` were calling recalculation methods (`recalculateTermGradesForTerm()` and `recalculateFinalRatings()`), but these methods had critical bugs:

1. **recalculateTermGradesForTerm()** - Was using undefined variables `$classStandingWeight` and `$examWeight` instead of reading from the database
2. **recalculateFinalRatings()** - Was using undefined variables `$prelimWeight`, `$midtermWeight`, and `$finalsWeight` instead of reading from the subject's config

This caused PHP errors and prevented automatic grade recalculation when formulas were changed.

## Solution
Fixed both recalculation methods to:

### 1. recalculateTermGradesForTerm(Subject $subject, string $term)
- Reads the grading configuration from the database for the specified term
- Iterates through all students in the subject
- Recalculates class standing for each student (applies activity weights)
- Recalculates term grades using the formula: Term Grade = Class Standing + (Exam % × Exam Weight)
- Updates all term grade records in the database

### 2. recalculateFinalRatings(Subject $subject)
- Reads the final rating configuration from the subject
- Iterates through all students
- Calculates final rating using: FR = (Prelim × Prelim%) + (Midterm × Midterm%) + (Finals × Finals%)
- Updates final rating in the database

## How It Works Now

### When you change the term formula (e.g., 40% CS + 60% Exam → 50% CS + 50% Exam):
1. Formula is saved to the database
2. `recalculateTermGradesForTerm()` is called automatically
3. All student grades for that term are recalculated using the new weights
4. Page reloads showing updated grades

### When you change the final rating weights (e.g., 30% Prelim + 30% Midterm + 40% Finals):
1. Weights are saved to the subject's configuration
2. `recalculateFinalRatings()` is called automatically
3. All student final ratings are recalculated using the new weights
4. Page reloads showing updated final ratings

## Files Modified
- `app/Http/Controllers/GradeController.php`
  - Fixed `saveGradingConfig()` method call
  - Fixed `saveFinalRatingConfig()` method call
  - Updated `recalculateTermGradesForTerm()` signature and implementation
  - Updated `recalculateFinalRatings()` signature and implementation

## Additional Fix: Decimal Precision

Also fixed grade rounding to show 2 decimal places instead of whole numbers:
- Class Standing: 75.3% × 40% = **30.12** (was showing 30)
- Exam Grade: 60% × 60% = **36.00** (was showing 36)
- Term Grade: 30.12 + 36.00 = **66.12** (was showing 66)
- Final Rating: Calculated with 2 decimal precision

Changed all `round($value)` to `round($value, 2)` for:
- Class Standing calculation
- Exam Grade calculation
- Term Grade calculation
- Final Rating calculation

## Testing
Verified that:
- ✅ Changing term formula automatically recalculates all student grades
- ✅ Changing final rating weights automatically recalculates all final ratings
- ✅ No manual grade re-entry required
- ✅ All calculations use the correct database configuration
- ✅ Page reload shows updated values immediately
- ✅ All computed grades show 2 decimal places (e.g., 30.12, 66.12)
- ✅ Input scores still show whole numbers (e.g., 75, 60)

## User Experience Improvement
**Before:** Change formula → No visible change → Must re-enter all grades → Frustrating
**After:** Change formula → Automatic recalculation → Page reload → Updated grades with proper decimals → Seamless!
