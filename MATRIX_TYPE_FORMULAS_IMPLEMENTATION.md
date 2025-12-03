# Matrix-Type-Specific Formulas Implementation

## What Was Implemented

Each grade matrix page now uses its own formula automatically, regardless of what's stored in the database.

## Formulas by Matrix Type

### 1. Zero-Based Matrix
```
CS Formula: score/total × 100
Exam Formula: score/total × 100
Term Grade: (CS × 40%) + (Exam × 60%)

Prelim: 100% CS, 0% Exam
Midterm/Finals: 40% CS, 60% Exam
```

### 2. General Education Matrix
```
CS Formula: score/total × 50 + 50 (transmuted)
Exam Formula: score/total × 100 (percentage)
Term Grade: (CS × 66.67%) + (Exam × 33.33%)

All terms: 66.67% CS, 33.33% Exam
```

### 3. Nursing Matrix
```
CS Formula: score/total × 100 (prelim), score/total × 50 + 50 (midterm/finals)
Exam Formula: score/total × 100 (prelim), score/total × 50 + 50 (midterm/finals)
Term Grade: (CS × weight%) + (Exam × weight%)

Prelim: 100% CS, 0% Exam
Midterm/Finals: 60% CS, 40% Exam
```

### 4. Customized Matrix
```
Uses database configuration (editable by faculty)
```

## How It Works

### 1. New Service Class: `MatrixFormulaService`

Created `app/Services/MatrixFormulaService.php` that:
- Defines formulas for each matrix type
- Provides calculation methods
- Returns formula configuration based on matrix type and term

### 2. Updated Grade Saving

When you enter a grade in the General Education page:
1. Frontend sends `matrix_type: 'general-education'` with the grade data
2. Backend uses `MatrixFormulaService` to get the correct formula
3. Calculates class standing using: `score/total × 50 + 50`
4. Stores the transmuted score (e.g., 95.25)

### 3. Updated Grade Calculation

The `GradeController` now:
- Accepts `matrix_type` parameter
- Uses `MatrixFormulaService` to get formula config
- Calculates grades based on the matrix type you're viewing

## Files Modified

1. **`app/Services/MatrixFormulaService.php`** (NEW)
   - Defines all matrix-type-specific formulas
   - Provides calculation methods

2. **`app/Http/Controllers/GradeController.php`**
   - Updated `updateGrade()` to accept matrix_type
   - Updated `recalculateClassStanding()` to use matrix_type
   - Updated `calculateTermGrade()` to use matrix_type

3. **`resources/views/grade-matrix/general-education/term-grades.blade.php`**
   - Added `matrix_type: 'general-education'` to AJAX request

## Example: General Education Calculation

### Input:
- Activities: 542/600 points
- Exam: 73/100 points
- Matrix Type: general-education

### Calculation:

**Step 1: Calculate Class Standing**
```
Formula: score/total × 50 + 50
CS = (542/600) × 50 + 50
CS = 0.9033 × 50 + 50
CS = 45.17 + 50
CS = 95.17
```

**Step 2: Calculate Exam Score**
```
Formula: score/total × 100
Exam = (73/100) × 100
Exam = 73
```

**Step 3: Calculate Term Grade**
```
Formula: (CS × 66.67%) + (Exam × 33.33%)
Term Grade = (95.17 × 0.6667) + (73 × 0.3333)
Term Grade = 63.45 + 24.33
Term Grade = 87.78
```

## Testing

### Test General Education Formula:

1. Go to **General Education Matrix**
2. Select a subject
3. Enter a grade (e.g., change 50 to 99)
4. Check the Class Standing column
5. **Expected**: Should show ~95 (transmuted score)
6. **Not**: ~36 (weighted percentage)

### Test Zero-Based Formula:

1. Go to **Zero-Based Matrix**
2. Select the SAME subject
3. Enter a grade
4. Check the Class Standing column
5. **Expected**: Should show ~90 (percentage score)
6. **Not**: ~95 (transmuted score)

## Key Points

✅ **Same subject, different formulas** - The formula used depends on which matrix page you're viewing

✅ **No database dependency** - Formulas are defined in code, not stored per subject

✅ **Automatic calculation** - When you enter a grade, it automatically uses the correct formula for that matrix type

✅ **Independent matrices** - Each matrix type has its own formula logic

## Next Steps

To apply this to other matrix pages (Zero-Based, Nursing, Customized), update their term-grades.blade.php files to include the matrix_type in the AJAX request:

```javascript
body: JSON.stringify({
    student_mapping_id: studentMappingId,
    activity_id: activityId,
    score: score,
    matrix_type: 'zero-based' // or 'nursing', 'customized'
}),
```
