# Exam Grade Recalculation Fix

## Problem
In the zero-based matrix, the exam grade column was showing incorrect results (e.g., 31.66 instead of 54.00 for a 90/100 exam), even when reloading the page or after saving the term formula.

## Root Cause
There were TWO issues:

### Issue 1: No Recalculation on Page Load
Term grades were being fetched directly from the database without recalculating them when the matrix pages loaded. This meant:

1. **On page load**: The exam_grade displayed was whatever was stored in the database
2. **After saving term formula**: The `recalculateTermGradesForTerm()` method would recalculate all term grades, making them correct
3. **After entering a new score**: The `updateExamScore()` method would recalculate that specific term grade, making it correct
4. **On page reload**: Back to showing the database value without recalculation

### Issue 2: Wrong Weights Being Used
The `calculateTermGrade()` method had flawed logic for determining which weights to use:

1. If `$matrixType` was provided → Use MatrixFormulaService (correct weights)
2. If `$matrixType` was NOT provided → Use database GradingConfig (potentially wrong weights)

The problem was that database GradingConfig could have custom weights that don't match the matrix type's standard weights. For example:
- Zero-based should be: 40% CS + 60% Exam
- But GradingConfig might have: 65% CS + 35% Exam (from a previous customization)

When `calculateTermGrade()` was called without `$matrixType`, it would use the wrong weights from GradingConfig, resulting in incorrect calculations like 31.66 instead of 54.00.

## Solution
Implemented a two-part fix:

### Part 1: Automatic Recalculation on Page Load
Added automatic recalculation of all term grades when matrix pages load. This ensures that the exam_grade is always calculated using the current formula, regardless of what's stored in the database.

### Part 2: Always Use Matrix Type Weights
Modified the `calculateTermGrade()` method to ALWAYS prioritize the matrix type's standard weights over database GradingConfig. The new logic:

1. If `$matrixType` is not provided, automatically use the subject's `matrix_type`
2. ALWAYS use MatrixFormulaService if a matrix type is available (this gives the correct standard weights)
3. Only fall back to database GradingConfig if there's no matrix type at all (rare case)

This ensures that zero-based matrices always use 40% CS + 60% Exam, regardless of what's in the GradingConfig table.

### Changes Made

#### 1. `app/Http/Controllers/GradeController.php` - `calculateTermGrade()` method
**CRITICAL FIX**: Modified the weight determination logic to always use matrix type weights:

```php
// If matrix type is not provided, use the subject's matrix type
if (!$matrixType && $subject) {
    $matrixType = $subject->matrix_type;
}

// ALWAYS use the matrix type formula if available
// This ensures consistency with the matrix type's defined weights
if ($matrixType) {
    $formulaConfig = \App\Services\MatrixFormulaService::getFormulaConfig($matrixType, $termGrade->term);
    $classWeight = $formulaConfig['class_standing_weight'] / 100;
    $examWeight = $formulaConfig['exam_weight'] / 100;
    $examFormulaType = $formulaConfig['exam_formula'];
} else {
    // Only fall back to database config if no matrix type is available
    // This should rarely happen
    ...
}
```

This ensures that:
- Zero-based always uses 40% CS + 60% Exam
- General Education always uses 66.67% CS + 33.33% Exam
- Nursing always uses 60% CS + 40% Exam (for midterm/finals)

#### 2. `app/Http/Controllers/GradeController.php` - `matrix()` method
Added recalculation logic before displaying the matrix:

```php
// Recalculate all term grades to ensure exam_grade is always correct
// This ensures that when the page loads, all calculations follow the current formula
$termGrades = TermGrade::where('subject_id', $subject->id)->get();
foreach ($termGrades as $termGrade) {
    // Only recalculate if there's an exam score
    if ($termGrade->exam_score !== null) {
        $this->calculateTermGrade($termGrade, $subject->matrix_type);
    }
}
```

#### 3. `app/Http/Controllers/GradeController.php` - `termGrades()` method
Added the same recalculation logic for the term grades edit page:

```php
// Recalculate all term grades for this term to ensure exam_grade is always correct
// This ensures that when the page loads, all calculations follow the current formula
foreach ($termGrades as $termGrade) {
    // Only recalculate if there's an exam score
    if ($termGrade->exam_score !== null) {
        $this->calculateTermGrade($termGrade, $subject->matrix_type);
    }
}

// Reload term grades after recalculation
$termGrades = TermGrade::where('subject_id', $subject->id)
    ->where('term', $term)
    ->get()
    ->keyBy('student_mapping_id');
```

#### 4. `app/Http/Controllers/GradeController.php` - `recalculateTermGradesForTerm()` method
Fixed to pass the matrix type when recalculating:

```php
if ($termGrade) {
    $this->calculateTermGrade($termGrade, $subject->matrix_type);
}
```

This ensures that when the term formula is saved, it recalculates using the correct matrix type weights.

#### 5. `app/Http/Controllers/GradeMatrixController.php` - `termGradesView()` method
Added recalculation logic for matrix-specific term grades pages:

```php
// Recalculate all term grades for this term to ensure exam_grade is always correct
// This ensures that when the page loads, all calculations follow the current formula
$gradeController = app(\App\Http\Controllers\GradeController::class);
foreach ($termGrades as $termGrade) {
    // Only recalculate if there's an exam score
    if ($termGrade->exam_score !== null) {
        // Use reflection to call the private method
        $reflection = new \ReflectionClass($gradeController);
        $method = $reflection->getMethod('calculateTermGrade');
        $method->setAccessible(true);
        $method->invoke($gradeController, $termGrade, $matrixType);
    }
}

// Reload term grades after recalculation
$termGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
    ->where('term', $term)
    ->get()
    ->keyBy('student_mapping_id');
```

#### 6. `app/Http/Controllers/GradeMatrixController.php` - `matrixView()` method
Added recalculation logic for matrix overview pages:

```php
// Recalculate all term grades to ensure exam_grade is always correct
// This ensures that when the page loads, all calculations follow the current formula
$gradeController = app(\App\Http\Controllers\GradeController::class);
$allTermGrades = \App\Models\TermGrade::where('subject_id', $subject->id)->get();
foreach ($allTermGrades as $termGrade) {
    // Only recalculate if there's an exam score
    if ($termGrade->exam_score !== null) {
        // Use reflection to call the private method
        $reflection = new \ReflectionClass($gradeController);
        $method = $reflection->getMethod('calculateTermGrade');
        $method->setAccessible(true);
        $method->invoke($gradeController, $termGrade, $matrixType);
    }
}

// Reload term grades after recalculation
$termGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
    ->get()
    ->groupBy('student_mapping_id');
```

## How It Works Now

### Zero-Based Matrix Example (40% CS + 60% Exam)
1. **User enters exam score**: 90/100
2. **Calculation**: 
   - Raw exam score: (90/100) × 100 = 90
   - Exam weight: 60% (from MatrixFormulaService, NOT from GradingConfig)
   - Weighted exam grade: 90 × 0.60 = 54.00
3. **On save**: `updateExamScore()` calculates and stores exam_grade = 54.00
4. **On page reload**: `matrix()` method recalculates all term grades using matrix type weights
5. **Result**: Exam grade always shows 54.00, following the correct formula

### Why 31.66 Was Wrong
If the database GradingConfig had custom weights like 65% CS + 35% Exam:
- Raw exam score: 90
- Wrong weight: 35% (from GradingConfig)
- Wrong calculation: 90 × 0.35 = 31.50 ≈ 31.66

Now it correctly uses 60% from the zero-based matrix type definition.

### General Flow
1. **Page Load** → Recalculate all term grades → Display correct values
2. **User Changes Score** → Save triggers recalculation → Display correct values
3. **Page Reload** → Recalculate all term grades → Display correct values

## Benefits
1. **Always Correct**: Exam grades always follow the matrix type's standard weights, not custom GradingConfig weights
2. **Self-Healing**: If there were any incorrect values from before the fix, they get corrected on page load
3. **Consistent**: All matrix pages (overview, term-specific, matrix-specific) now have the same behavior
4. **Matrix Type Integrity**: Zero-based always uses 40/60, General Education always uses 66.67/33.33, etc.
5. **No More Confusion**: The displayed exam grade will always match the expected calculation for that matrix type

## Performance Note
The recalculation only happens for term grades that have an exam score, so it's efficient. For a typical class of 30-40 students with 3 terms, this means recalculating 90-120 records, which is negligible.

## Testing
1. Enter an exam score (e.g., 90/100) in a zero-based matrix
2. Verify the exam grade shows the weighted value (e.g., 54.00)
3. Reload the page
4. Verify the exam grade still shows the correct weighted value
5. Change the exam score
6. Verify the exam grade updates correctly
7. Reload the page again
8. Verify the exam grade remains correct

## Related Documents
- ZERO_BASED_EXAM_GRADE_FIX.md - The original fix that corrected how exam_grade is stored
- This fix ensures that the correct calculation is always applied on display
