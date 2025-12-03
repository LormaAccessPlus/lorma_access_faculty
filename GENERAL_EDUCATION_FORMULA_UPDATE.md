# General Education Grading Formula Update

## Summary
Updated the grading formulas specifically for the General Education matrix to use:
- **Term Formula**: 66.67% Class Standing + 33.33% Exam
- **Activities & Exam Formula**: (total score / total items) × 50 + 50 (transmuted formula)

## Changes Made

### 1. GradingConfig Model (`app/Models/GradingConfig.php`)
Updated the `getDefaultConfig()` method to accept a `$matrixType` parameter and return specific defaults for general education:

```php
public static function getDefaultConfig(string $term, ?string $matrixType = null): array
{
    // General Education Matrix has specific defaults
    if ($matrixType === 'general-education') {
        return [
            'class_standing_weight' => 66.67,
            'exam_weight' => 33.33,
            'formula_config' => [
                'type' => 'transmuted', // (score/items) × 50 + 50
                'components' => []
            ]
        ];
    }
    // ... other defaults
}
```

### 2. GradeMatrixController (`app/Http/Controllers/GradeMatrixController.php`)
Updated the `termGradesView()` method to automatically create grading configurations with the correct defaults based on matrix type:

```php
// If no grading config exists, create one with defaults based on matrix type
if (!$gradingConfig) {
    $defaults = \App\Models\GradingConfig::getDefaultConfig($term, $matrixType);
    $gradingConfig = \App\Models\GradingConfig::create([
        'subject_id' => $subject->id,
        'term' => $term,
        'class_standing_weight' => $defaults['class_standing_weight'],
        'exam_weight' => $defaults['exam_weight'],
        'formula_config' => $defaults['formula_config']
    ]);
}
```

### 3. GradeController (`app/Http/Controllers/GradeController.php`)

#### Updated `recalculateClassStanding()` method:
Now checks the formula type and applies the transmuted formula when configured:

```php
// Calculate class standing based on formula type
if ($formulaType === 'transmuted') {
    // Transmuted formula: (score/items) × 50 + 50
    // Then apply the activity weight
    $transmutedScore = (($totalScore / $totalPossible) * 50) + 50;
    $classStanding = $transmutedScore * ($activityWeight / 100);
} else {
    // Percentage formula: (score/items) × 100
    // Then apply the activity weight
    $classStandingPercentage = ($totalScore / $totalPossible) * 100;
    $classStanding = $classStandingPercentage * ($activityWeight / 100);
}
```

#### Updated `calculateTermGrade()` method:
Now checks the formula type for exam score calculation:

```php
// Calculate exam grade based on formula type
if ($formulaType === 'transmuted') {
    // Transmuted formula: (score/items) × 50 + 50
    // Then apply the exam weight
    $transmutedExam = (($termGrade->exam_score / $examMaxScore) * 50) + 50;
    $examGrade = $transmutedExam * ($examWeight);
} else {
    // Percentage formula: (score/items) × 100
    // Then apply the exam weight
    $examPercentage = ($termGrade->exam_score / $examMaxScore) * 100;
    $examGrade = $examPercentage * ($examWeight);
}
```

## How It Works

### For General Education Subjects:

1. **When accessing term grades** for a general education subject, the system automatically creates a grading configuration with:
   - Class Standing Weight: 66.67%
   - Exam Weight: 33.33%
   - Formula Type: transmuted

2. **When calculating class standing** from activities:
   - Formula: `(total score / total items) × 50 + 50`
   - Then multiply by class standing weight (66.67%)
   - Example: If student scores 450/500 on activities:
     - Transmuted: (450/500) × 50 + 50 = 95
     - Weighted: 95 × 0.6667 = 63.34

3. **When calculating exam grade**:
   - Formula: `(exam score / exam max) × 50 + 50`
   - Then multiply by exam weight (33.33%)
   - Example: If student scores 85/100 on exam:
     - Transmuted: (85/100) × 50 + 50 = 92.5
     - Weighted: 92.5 × 0.3333 = 30.83

4. **Term Grade** = Class Standing + Exam Grade
   - Example: 63.34 + 30.83 = 94.17

## Testing

To test the changes:

1. Navigate to the General Education Matrix
2. Select a subject
3. Go to Term Grades for any term
4. The system will automatically use the new formula (66.67% CS + 33.33% Exam)
5. Enter activity scores and exam scores
6. Verify that the transmuted formula is applied: (score/items) × 50 + 50

## Notes

- The changes only affect the **General Education matrix**
- Other matrix types (Zero-based, Nursing, Customized) continue to use their existing formulas
- Faculty can still manually adjust the weights if needed through the grading configuration interface
- The transmuted formula ensures that scores are normalized to a 50-100 scale before weighting
