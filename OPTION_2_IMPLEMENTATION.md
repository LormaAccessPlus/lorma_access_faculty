# Option 2 Implementation - Weighted Class Standing

## What Changed

The Class Standing column now shows the **weighted contribution** to the term grade, not the raw score.

## Column Meanings

### General Education Matrix Example:

**Given:**
- Activities: 542/600 points
- Exam: 73/100 points
- Formula: 66.67% CS + 33.33% Exam

**Display:**

| Column | Value | Calculation | Meaning |
|--------|-------|-------------|---------|
| **Total CS** | 542 / 600<br>95.17 | (542/600) × 50 + 50 | Raw transmuted score |
| **Class Standing** | 63.45 | 95.17 × 66.67% | Weighted contribution to term grade |
| **Exam Score** | 73 / 100 | Raw input | Exam score entered |
| **Exam Grade** | 24.33 | 73 × 33.33% | Weighted contribution to term grade |
| **Term Grade** | 87.78 | 63.45 + 24.33 | Final term grade |

## Calculation Flow

### Step 1: Calculate Raw Scores

**Total CS (Transmuted):**
```
Formula: (score/total) × 50 + 50
Result: (542/600) × 50 + 50 = 95.17
```

**Exam (Percentage):**
```
Formula: (score/total) × 100
Result: (73/100) × 100 = 73
```

### Step 2: Apply Weights

**Class Standing (Weighted):**
```
Formula: Raw CS × CS Weight
Result: 95.17 × 66.67% = 63.45
Stored in: term_grades.class_standing
```

**Exam Grade (Weighted):**
```
Formula: Raw Exam × Exam Weight
Result: 73 × 33.33% = 24.33
Stored in: term_grades.exam_grade
```

### Step 3: Calculate Term Grade

**Term Grade:**
```
Formula: Weighted CS + Weighted Exam
Result: 63.45 + 24.33 = 87.78
Stored in: term_grades.term_grade
```

## Database Storage

### term_grades table:

| Field | Value | Description |
|-------|-------|-------------|
| `class_standing` | 63.45 | Weighted class standing (contribution to term grade) |
| `exam_score` | 73 | Raw exam score (input) |
| `exam_grade` | 24.33 | Weighted exam grade (contribution to term grade) |
| `term_grade` | 87.78 | Final term grade (sum of weighted components) |

## Code Changes

### 1. `recalculateClassStanding()` Method

**Before:**
```php
// Stored raw transmuted score
$classStanding = (($totalScore / $totalPossible) * 50) + 50; // 95.17
```

**After:**
```php
// Calculate raw score
$rawClassStanding = (($totalScore / $totalPossible) * 50) + 50; // 95.17

// Apply weight
$classStanding = $rawClassStanding * ($csWeight / 100); // 95.17 × 66.67% = 63.45
```

### 2. `calculateTermGrade()` Method

**Before:**
```php
// Applied weights to both CS and exam
$termGrade = (CS × weight) + (exam × weight)
```

**After:**
```php
// CS is already weighted, only apply weight to exam
$weightedExam = rawExam × examWeight;
$termGrade = weightedCS + weightedExam; // Just add them
```

## Benefits of Option 2

✅ **Clear Contribution**: Each column shows its contribution to the final grade

✅ **Easy to Verify**: Term Grade = Class Standing + Exam Grade (simple addition)

✅ **Transparent**: Faculty can see exactly how much each component contributes

✅ **Consistent**: All weighted values are stored, not calculated on-the-fly

## Example Scenarios

### Scenario 1: Perfect Score
```
Activities: 600/600 → Raw CS: 100 → Weighted CS: 66.67
Exam: 100/100 → Raw Exam: 100 → Weighted Exam: 33.33
Term Grade: 66.67 + 33.33 = 100
```

### Scenario 2: Failing Activities
```
Activities: 300/600 → Raw CS: 75 → Weighted CS: 50.00
Exam: 90/100 → Raw Exam: 90 → Weighted Exam: 30.00
Term Grade: 50.00 + 30.00 = 80.00
```

### Scenario 3: No Exam Yet
```
Activities: 542/600 → Raw CS: 95.17 → Weighted CS: 63.45
Exam: Not entered → Weighted Exam: 0
Term Grade: 63.45 + 0 = 63.45
```

## Verification

To verify the calculation is correct:

1. Check **Total CS** shows transmuted score (~95)
2. Check **Class Standing** shows weighted value (~63)
3. Check **Exam Grade** shows weighted value (~24)
4. Check **Term Grade** = Class Standing + Exam Grade

The formula is working correctly if:
```
Term Grade ≈ Class Standing + Exam Grade
```
