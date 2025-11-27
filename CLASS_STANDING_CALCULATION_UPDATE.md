# Class Standing Calculation Update

## Summary
Updated the Class Standing (CS) column to show the **weighted percentage** based on the grading configuration formula.

## Previous Behavior
The CS column showed the **total CS score** (0-100 scale), which was:
- **Prelim**: Raw percentage (Total Score / Total Possible × 100)
- **Midterm/Finals**: Transmuted score ((percentage/100) × 50 + 50)

Then the term grade was calculated as: `(CS × CS_Weight) + (Exam × Exam_Weight)`

## New Behavior
The CS column now shows the **weighted CS portion** that contributes to the final term grade:
- **Total CS** is calculated first (same as before)
- **CS Weight** is applied: `CS_displayed = Total_CS × (CS_Weight / 100)`
- Term grade is calculated as: `CS_displayed + (Exam × Exam_Weight)`

## Example
Given:
- Activity scores: 41/55 = 74.55%
- Grading config: 60% CS, 40% Exam
- Term: Prelim

### Previous:
- CS column: **74.55**
- Term grade: (74.55 × 0.60) + (Exam × 0.40) = 44.73 + weighted exam

### New:
- CS column: **44.73** (74.55 × 0.60)
- Term grade: 44.73 + (Exam × 0.40) = 44.73 + weighted exam

## Benefits
1. **Clearer understanding**: The CS column directly shows its contribution to the term grade
2. **Easier verification**: CS + Weighted Exam = Term Grade (simple addition)
3. **Consistent with formula**: The displayed value matches the formula's CS portion

## Technical Changes
### Files Modified:
- `app/Http/Controllers/GradeController.php`
  - `recalculateClassStanding()`: Now applies CS weight from grading_configs
  - `calculateTermGrade()`: Simplified to add CS + weighted exam (no double weighting)
  - `recalculateTermGradesForTerm()`: Recalculates CS with new weights when config changes

### Database:
- All existing grades recalculated with new formula
- `grading_configs` table used for CS/Exam weights (default: 60/40)

## Migration Script
Run `recalculate_all_grades.php` to update all existing grades with the new formula.
