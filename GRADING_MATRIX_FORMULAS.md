# Grading Matrix Formulas

This document outlines the different grading formulas used by each matrix type in the system.

## Matrix Types

The system supports four different grading matrix types, each with its own formula configuration:

1. **Zero-Based Matrix**
2. **Nursing Matrix**
3. **General Education Matrix**
4. **Customized Matrix**

---

## General Education Matrix

### Term Formula
- **Class Standing Weight**: 66.67%
- **Exam Weight**: 33.33%

### Activities Formula (Class Standing)
- **Type**: Transmuted
- **Formula**: `(total score / total items) × 50 + 50`
- **Result**: Raw class standing score (e.g., 95)

### Exam Formula
- **Type**: Transmuted
- **Formula**: `(exam score / exam max) × 50 + 50`
- **Result**: Raw exam score (e.g., 92.5)

### Term Grade Calculation
```
Term Grade = (Class Standing × 66.67%) + (Exam Score × 33.33%)
```

### Example Calculation
**Given:**
- Activities: 450/500 points
- Exam: 85/100 points

**Step 1 - Calculate Class Standing:**
- Formula: (450/500) × 50 + 50 = **95**

**Step 2 - Calculate Exam Score:**
- Formula: (85/100) × 50 + 50 = **92.5**

**Step 3 - Calculate Term Grade:**
- Formula: (95 × 0.6667) + (92.5 × 0.3333)
- Calculation: 63.34 + 30.83 = **94.17**

**Important:** The transmuted formula is applied FIRST to get raw scores, THEN weights are applied.

---

## Zero-Based Matrix

### Term Formula (Default)
- **Prelim**: 100% Class Standing, 0% Exam
- **Midterm/Finals**: 60% Class Standing, 40% Exam

### Activities Formula (Class Standing)
- **Prelim**: Percentage formula `(total score / total items) × 100`
- **Midterm/Finals**: Transmuted formula `(total score / total items) × 50 + 50`

### Exam Formula
- **Prelim**: No exam (0% weight)
- **Midterm/Finals**: Transmuted formula `(exam score / exam max) × 50 + 50`

---

## Nursing Matrix

### Term Formula (Default)
- **Prelim**: 100% Class Standing, 0% Exam
- **Midterm/Finals**: 60% Class Standing, 40% Exam

### Activities Formula (Class Standing)
- **Prelim**: Percentage formula `(total score / total items) × 100`
- **Midterm/Finals**: Transmuted formula `(total score / total items) × 50 + 50`

### Exam Formula
- **Prelim**: No exam (0% weight)
- **Midterm/Finals**: Transmuted formula `(exam score / exam max) × 50 + 50`

---

## Customized Matrix

### Term Formula (Default)
- **Prelim**: 100% Class Standing, 0% Exam
- **Midterm/Finals**: 60% Class Standing, 40% Exam

### Activities Formula (Class Standing)
- **Prelim**: Percentage formula `(total score / total items) × 100`
- **Midterm/Finals**: Transmuted formula `(total score / total items) × 50 + 50`

### Exam Formula
- **Prelim**: No exam (0% weight)
- **Midterm/Finals**: Transmuted formula `(exam score / exam max) × 50 + 50`

**Note**: Faculty can customize these formulas per subject in the Customized Matrix.

---

## Key Differences

### General Education vs Other Matrices

| Aspect | General Education | Zero-Based/Nursing/Customized |
|--------|------------------|-------------------------------|
| **All Terms** | 66.67% CS + 33.33% Exam | Varies by term |
| **Prelim** | Uses transmuted formula | Uses percentage formula |
| **Prelim Exam** | Has exam (33.33%) | No exam (0%) |
| **Formula Type** | Always transmuted | Percentage for prelim, transmuted for midterm/finals |

### Why Different Formulas?

1. **General Education** - Uses a consistent formula across all terms with transmuted scoring to normalize grades to 50-100 scale
2. **Zero-Based** - Designed for subjects that build progressively, with no exam in prelim
3. **Nursing** - Follows nursing education standards with specific term weightings
4. **Customized** - Allows faculty to define their own formulas per subject

---

## Implementation Details

### Code Location
- **Model**: `app/Models/GradingConfig.php` - Contains `getDefaultConfig()` method
- **Controller**: `app/Http/Controllers/GradeController.php` - Implements formula calculations
- **Matrix Controller**: `app/Http/Controllers/GradeMatrixController.php` - Creates default configs

### How Defaults Are Applied

When a faculty member accesses term grades for a subject in a specific matrix type:

1. System checks if a `GradingConfig` exists for that subject and term
2. If not, it creates one using `GradingConfig::getDefaultConfig($term, $matrixType)`
3. The matrix type determines which default formula is used
4. Faculty can still manually adjust these defaults through the UI

### Formula Type Detection

The system checks the `formula_config['type']` field:
- `'percentage'` - Uses `(score/items) × 100`
- `'transmuted'` - Uses `(score/items) × 50 + 50`

This is applied in both:
- `recalculateClassStanding()` - For activities
- `calculateTermGrade()` - For exam scores
