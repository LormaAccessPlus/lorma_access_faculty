# Dynamic Matrix Type Calculation

## The Requirement

The same subject should calculate grades differently depending on which matrix page it's viewed from:

- **Zero-Based page**: 40% CS + 60% Exam → 90/100 = 54.00
- **General Education page**: 66.67% CS + 33.33% Exam → 90/100 = 31.66  
- **Nursing page**: 60% CS + 40% Exam (transmuted) → 90/100 = 38.00

The matrix type should come from the **page/route**, NOT from the database `subjects.matrix_type` field.

## The Solution

### 1. Frontend: Pass matrix_type in AJAX Request

Updated all term-grades views to send `matrix_type` when saving exam scores:

**resources/views/grade-matrix/zero-based/term-grades.blade.php:**
```javascript
body: JSON.stringify({
    student_mapping_id: studentMappingId,
    subject_id: subjectId,
    term: term,
    exam_score: examScore,
    exam_max_score: examMaxScore,
    matrix_type: 'zero-based'  // ← Added this
})
```

**resources/views/grade-matrix/general-education/term-grades.blade.php:**
```javascript
matrix_type: 'general-education'
```

**resources/views/grade-matrix/nursing/term-grades.blade.php:**
```javascript
matrix_type: 'nursing'
```

**resources/views/grade-matrix/customized/term-grades.blade.php:**
```javascript
matrix_type: 'customized'
```

### 2. Backend: Use matrix_type from Request

**app/Http/Controllers/GradeController.php - updateExamScore():**

```php
// Get matrix type from request (from the page/route), NOT from database
$subject = Subject::find($request->subject_id);
$matrixType = $request->input('matrix_type', null);

// If no matrix type provided in request, fall back to subject's matrix_type
if (!$matrixType) {
    $matrixType = $subject->matrix_type;
}

// Use the matrix_type from the request when calculating
$termGrade = $this->calculateTermGrade($termGrade, $matrixType);
```

### 3. Calculation: Prioritize Provided Matrix Type

**app/Http/Controllers/GradeController.php - calculateTermGrade():**

```php
private function calculateTermGrade(TermGrade $termGrade, ?string $matrixType = null): TermGrade
{
    // Matrix type should be provided from the page/route
    if (!$matrixType) {
        // Fallback: try to get from subject (for backward compatibility)
        $subject = Subject::find($termGrade->subject_id);
        if ($subject && $subject->matrix_type) {
            $matrixType = $subject->matrix_type;
        } else {
            return $termGrade; // Cannot calculate without matrix type
        }
    }
    
    // Use MatrixFormulaService with the provided matrix type
    $formulaConfig = \App\Services\MatrixFormulaService::getFormulaConfig($matrixType, $termGrade->term);
    // ... rest of calculation
}
```

### 4. Fixed Zero-Based Formula

**app/Services/MatrixFormulaService.php:**

Changed zero-based to use 40/60 for ALL terms (prelim, midterm, finals):

```php
private static function getZeroBasedFormula(string $term): array
{
    return [
        'class_standing_weight' => 40.00,
        'exam_weight' => 60.00,
        'cs_formula' => 'percentage',
        'exam_formula' => 'percentage'
    ];
}
```

## How It Works Now

### Example: CS301 Subject

**Scenario 1: View in Zero-Based Page**
1. User opens: `/grade-matrix/zero-based/subjects/9/term/prelim`
2. User enters exam score: 90/100
3. JavaScript sends: `matrix_type: 'zero-based'`
4. Backend calculates: 90 × 0.60 = **54.00**
5. Display shows: **54.00**

**Scenario 2: Same Subject in General Education Page**
1. User opens: `/grade-matrix/general-education/subjects/9/term/prelim`
2. User enters exam score: 90/100
3. JavaScript sends: `matrix_type: 'general-education'`
4. Backend calculates: (90/100 × 50 + 50) × 0.3333 = 95 × 0.3333 = **31.66**
5. Display shows: **31.66**

**Scenario 3: Same Subject in Nursing Page**
1. User opens: `/grade-matrix/nursing/subjects/9/term/prelim`
2. User enters exam score: 90/100
3. JavaScript sends: `matrix_type: 'nursing'`
4. Backend calculates: (90/100 × 50 + 50) × 0.40 = 95 × 0.40 = **38.00**
5. Display shows: **38.00**

## Benefits

1. **Truly Dynamic**: Same subject can be used in any matrix type
2. **No Database Dependency**: Don't need to set `subjects.matrix_type`
3. **Flexible**: Teachers can experiment with different formulas
4. **Accurate**: Calculations always match the page/context

## Database Independence

The `subjects.matrix_type` field is now optional:
- If provided in request → Use it (primary)
- If not in request → Fall back to `subjects.matrix_type` (backward compatibility)
- If neither → Cannot calculate (return unchanged)

This means you can:
- Leave `subjects.matrix_type` NULL
- Or set it as a default/hint
- The page/route always takes precedence

## Testing

1. Set CS301 `matrix_type` to NULL in database
2. Open CS301 in zero-based page
3. Enter exam score 90/100
4. Should show: **54.00** ✅
5. Open same CS301 in general education page
6. Enter exam score 90/100
7. Should show: **31.66** ✅

## Files Modified

1. `app/Http/Controllers/GradeController.php`
   - `updateExamScore()` - Use matrix_type from request
   - `calculateTermGrade()` - Prioritize provided matrix_type

2. `app/Services/MatrixFormulaService.php`
   - `getZeroBasedFormula()` - Fixed to 40/60 for all terms

3. `resources/views/grade-matrix/zero-based/term-grades.blade.php`
   - Added `matrix_type: 'zero-based'` to AJAX request

4. `resources/views/grade-matrix/general-education/term-grades.blade.php`
   - Added `matrix_type: 'general-education'` to AJAX request

5. `resources/views/grade-matrix/customized/term-grades.blade.php`
   - Added `matrix_type: 'customized'` to AJAX request

6. `resources/views/grade-matrix/nursing/term-grades.blade.php`
   - Already had `matrix_type: 'nursing'`
