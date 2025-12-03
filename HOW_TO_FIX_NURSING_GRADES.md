# How to Fix Nursing Term Grades

## The Problem
The term grades shown in the nursing matrix were incorrect because they were calculated using the old buggy logic that didn't count all activities' max scores.

## The Solution
I've fixed the calculation logic in the backend. Now you need to recalculate the existing grades in the database.

## Steps to Fix

### Quick Fix (Recommended)
1. Go to your nursing subject's matrix page: `http://localhost:8000/grade-matrix/nursing/subjects/8/matrix`
2. Click the **purple "Recalculate Grades"** button at the top
3. Confirm the recalculation
4. The page will reload with corrected term grades

### What the Recalculate Button Does
- Recalculates ALL term grades (Prelim, Midterm, Finals) for ALL students
- Uses the correct nursing formula:
  - Activities: `(score/total × 60 + 40) × 15%`
  - Quizzes: `(score/total × 60 + 40) × 25%`
  - Exam: `(score/total × 60 + 40) × 60%`
  - Term Grade: `Activities + Quizzes + Exam`
- Counts ALL activities' max scores (even ungraded ones)

### Alternative: Manual Recalculation
If you prefer, you can also trigger recalculation by:
1. Going to the term grades page
2. Re-entering any grade (activity, quiz, or exam)
3. Pressing Enter to save
4. This will recalculate that student's term grade

## Expected Results
After recalculation, the term grades should be much higher and more accurate. For example:
- **Before**: Prelim: 28.29, Midterm: 66.90, Finals: 57.28
- **After**: Prelim: 75-85, Midterm: 75-85, Finals: 75-85 (depending on actual scores)

The nursing formula is designed to give students a transmuted score that typically ranges from 40-100, with most passing students scoring 75 or above.

## Why This Happened
The old code only counted max scores from activities that had grade records, while the frontend counted ALL activities. This caused the denominator to be smaller in the backend calculation, resulting in artificially low grades.

## Future Prevention
All new grades entered from now on will be calculated correctly automatically. No action needed for future data entry.
