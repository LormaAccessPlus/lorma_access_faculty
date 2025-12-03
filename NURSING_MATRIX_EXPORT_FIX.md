# Nursing Matrix PDF Export Fix

## Problem
When exporting the PDF from the Full Matrix page in the Nursing Matrix, the export should respect whether the subject is configured as **Lecture** or **Laboratory**, as this affects the final rating calculation.

## Solution
Added a **Subject Type selector** to the Final Rating Weights configuration section in the Full Matrix page (`resources/views/grades/matrix.blade.php`).

## Changes Made

### 1. Updated Matrix View (resources/views/grades/matrix.blade.php)
- Added `subjectType` to the Alpine.js data model
- Added subject type selector (Lecture/Lab) for nursing matrix subjects
- The subject type is now displayed in the formula summary card
- The subject type is saved along with the weights when clicking "Save Final Rating Weights"

### 2. How It Works

#### For Nursing Matrix Subjects:
- **Lecture**: Final Rating = Final Grade (80%) + Comprehensive Exam (20%)
- **Laboratory**: Final Rating = Final Grade (100%)

#### The PDF Export Already Handles This:
The `resources/views/grades/exports/pp.blade.php` file already has the logic to:
1. Check if the subject is a nursing matrix (`$subject->matrix_type === 'nursing'`)
2. Read the subject type from `$subject->final_rating_config['subject_type']`
3. Apply the correct formula based on the subject type

## How to Use

1. Go to **Nursing Matrix** page
2. Click **Full Grade Matrix** for a subject
3. Click on the **Final Rating Formula** section to expand it
4. Select the appropriate **Subject Type**:
   - **Lecture**: If the subject includes a comprehensive exam (80% final grade + 20% comprehensive exam)
   - **Laboratory**: If the subject is lab-based (100% final grade)
5. Adjust the term weights if needed (Prelim, Midterm, Finals)
6. Click **Save Final Rating Weights**
7. Now when you export the PDF, it will use the correct formula based on the selected subject type

## Technical Details

### Database Storage
The subject type is stored in the `subjects` table in the `final_rating_config` JSON column:
```json
{
  "subject_type": "lecture",  // or "lab"
  "prelim_weight": 30,
  "midterm_weight": 30,
  "finals_weight": 40
}
```

### API Endpoint
The configuration is saved via POST to: `grades.save-final-rating-config`

The controller (`app/Http/Controllers/GradeController.php`) already accepts the `subject_type` parameter and saves it correctly.

## Testing
1. Set a nursing subject to "Lecture" type
2. Export the PDF - it should show columns for "Final Grade (80%)" and "Comp. Exam (20%)"
3. Set the same subject to "Laboratory" type
4. Export the PDF - it should show "Final Grade (100%)" column and "Final Rating" (which are the same value)

## PDF Export Columns

### For Lecture Type:
| Student | Prelim | Midterm | Finals | Final Grade (80%) | Comp. Exam (20%) | FINAL RATING | Remarks |

### For Laboratory Type:
| Student | Prelim | Midterm | Finals | Final Grade (100%) | FINAL RATING | Remarks |

Note: For lab, Final Grade and Final Rating are the same value (weighted average of the three terms)

## Notes
- This fix only applies to subjects with `matrix_type = 'nursing'`
- Other matrix types (zero-based, general-education, customized) are not affected
- The comprehensive exam scores must be entered separately via the "Comprehensive Exam" page
