# Grading Formula Configuration Fix

## Issues Fixed

### 1. Formula Input Not Saving Per Term
**Problem**: When inputting a formula for Prelims, it was being applied to Midterms and Finals as well.

**Solution**: 
- Fixed Alpine.js component scope in `_grading-calculator.blade.php`
- Each term now has its own independent `x-data` scope with its own `saveTermFormula()` method
- Term-specific formulas are now properly saved to the database with the correct term identifier

### 2. Grades Not Recalculating After Formula Changes
**Problem**: When changing the formula weights, the existing grades in the table were not being updated.

**Solution**:
- Added `recalculateTermGradesForTerm()` method in GradeController
- Added `recalculateFinalRatings()` method in GradeController
- When term formula is saved, all term grades for that specific term are recalculated
- When final rating weights are saved, all final ratings are recalculated
- Added `final_rating` column to `term_grades` table to store computed final ratings

## Files Modified

1. **resources/views/grades/_grading-calculator.blade.php**
   - Fixed Alpine.js data initialization
   - Moved `saveTermFormula()` into the term-specific x-data scope
   - Moved `saveFinalRatingWeights()` into the parent x-data scope
   - Removed standalone script functions

2. **app/Http/Controllers/GradeController.php**
   - Updated `saveGradingConfig()` to trigger grade recalculation
   - Updated `saveFinalRatingConfig()` to trigger final rating recalculation
   - Added `recalculateTermGradesForTerm()` private method
   - Added `recalculateFinalRatings()` private method

3. **database/migrations/2025_11_24_171009_add_final_rating_to_term_grades_table.php**
   - Created migration to add `final_rating` column to `term_grades` table

4. **app/Models/TermGrade.php**
   - Added `final_rating` to fillable array
   - Added `final_rating` to casts array

## How It Works Now

### Term Formula Configuration
1. Faculty opens a term (Prelim, Midterm, or Finals)
2. Faculty edits the Activities Weight and Exam Weight for that specific term
3. When saved, the system:
   - Saves the configuration for that term only
   - Recalculates all term grades for that term using the new formula
   - Updates the grade table immediately

### Final Rating Configuration
1. Faculty edits the Prelim %, Midterm %, and Finals % weights
2. When saved, the system:
   - Saves the final rating configuration
   - Recalculates all final ratings for all students using the new weights
   - Updates the final ratings in the database

### Grade Calculation Flow
1. **Activity Scores** → Entered by faculty
2. **Class Standing** → Calculated from activity scores
3. **Exam Score** → Entered by faculty
4. **Term Grade** → Calculated using: (Class Standing × CS%) + (Exam × Exam%)
5. **Final Rating** → Calculated using: (Prelim × P%) + (Midterm × M%) + (Finals × F%)

## Testing Checklist

- [x] Prelim formula saves independently
- [x] Midterm formula saves independently
- [x] Finals formula saves independently
- [x] Term grades recalculate when term formula changes
- [x] Final ratings recalculate when final rating weights change
- [x] No PHP errors
- [x] No JavaScript errors
- [x] Database migration successful
