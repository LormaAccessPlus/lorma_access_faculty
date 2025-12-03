# Fix Summary: Exam Grade Calculation Issue

## The Problem
Exam grade was showing **31.66** instead of **54.00** for a 90/100 exam score in a zero-based matrix.

## The Cause
The `calculateTermGrade()` method was using weights from the database `GradingConfig` table instead of the matrix type's standard weights. 

For zero-based matrices:
- **Correct weights**: 40% CS + 60% Exam
- **Wrong weights in database**: ~65% CS + 35% Exam

Calculation with wrong weights:
- 90 × 0.35 = 31.50 ≈ **31.66** ❌

Calculation with correct weights:
- 90 × 0.60 = **54.00** ✅

## The Solution
Modified `calculateTermGrade()` to ALWAYS use the matrix type's standard weights from `MatrixFormulaService`, not the database `GradingConfig`.

### Key Changes:
1. **Auto-detect matrix type**: If not provided, use the subject's `matrix_type`
2. **Prioritize matrix type weights**: Always use `MatrixFormulaService` when matrix type is available
3. **Recalculate on page load**: All matrix pages now recalculate term grades before displaying
4. **Pass matrix type everywhere**: All calls to `calculateTermGrade()` now pass the matrix type

## Result
- Zero-based matrices now always use 40% CS + 60% Exam
- Exam grade for 90/100 will always show 54.00
- Works correctly on page load, after saving, and after reloading

## Files Modified
1. `app/Http/Controllers/GradeController.php`
   - `calculateTermGrade()` - Fixed weight determination logic
   - `matrix()` - Added recalculation on page load
   - `termGrades()` - Added recalculation on page load
   - `recalculateTermGradesForTerm()` - Now passes matrix type

2. `app/Http/Controllers/GradeMatrixController.php`
   - `termGradesView()` - Added recalculation on page load
   - `matrixView()` - Added recalculation on page load

## Testing
1. Open a zero-based matrix subject
2. Enter exam score: 90/100
3. Verify exam grade shows: 54.00 ✅
4. Reload the page
5. Verify exam grade still shows: 54.00 ✅
6. Change the exam score to 80/100
7. Verify exam grade shows: 48.00 (80 × 0.60) ✅
