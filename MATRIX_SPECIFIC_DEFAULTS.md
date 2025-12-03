# Matrix-Specific Default Formulas

## Overview

Each grade matrix now has its own default formula configuration that appears in the "Grading Formula Configuration" section.

## Default Formulas by Matrix Type

### 1. Zero-Based Matrix

**Prelim:**
- Class Standing: 100%
- Exam: 0%
- Formula: Percentage `(score/items) × 100`

**Midterm/Finals:**
- Class Standing: 40%
- Exam: 60%
- Formula: Percentage `(score/items) × 100`

### 2. General Education Matrix

**All Terms (Prelim, Midterm, Finals):**
- Class Standing: 66.67%
- Exam: 33.33%
- Formula: Transmuted `(score/items) × 50 + 50`

### 3. Nursing Matrix

**Prelim:**
- Class Standing: 100%
- Exam: 0%
- Formula: Percentage `(score/items) × 100`

**Midterm/Finals:**
- Class Standing: 60%
- Exam: 40%
- Formula: Transmuted `(score/items) × 50 + 50`

### 4. Customized Matrix

**Prelim:**
- Class Standing: 100%
- Exam: 0%
- Formula: Percentage `(score/items) × 100`

**Midterm/Finals:**
- Class Standing: 60%
- Exam: 40%
- Formula: Transmuted `(score/items) × 50 + 50`

*Note: Customized matrix allows faculty to edit these defaults*

## How It Works

### When You First Visit a Matrix Page:

1. System checks if grading config exists for the subject
2. If not, creates one with the matrix-specific defaults
3. Displays the default formula in "Grading Formula Configuration"

### Example:

**Zero-Based Matrix:**
```
When you visit: /grade-matrix/zero-based/subjects/2/term/prelim
Default shown: 40% CS + 60% Exam
```

**General Education Matrix:**
```
When you visit: /grade-matrix/general-education/subjects/2/term/prelim
Default shown: 66.67% CS + 33.33% Exam
```

## Configuration Display

The "Grading Formula Configuration" section will show:

```
Current Prelim Term Formula
Class Standing: 66.67%  ← Matrix-specific default
Exam: 33.33%            ← Matrix-specific default
```

## Editing Formulas

Faculty can still edit these formulas by:
1. Clicking "Configure Term" in the Grading Formula Configuration section
2. Changing the weights
3. Saving the configuration

The edited values will be stored in the database and used for that specific subject.

## Key Points

✅ **Different defaults per matrix** - Each matrix type has its own default formula

✅ **Automatic creation** - Defaults are applied when you first visit a matrix page

✅ **Editable** - Faculty can override defaults for specific subjects

✅ **Persistent** - Once edited, the custom formula is saved in the database

## Summary Table

| Matrix Type | Prelim | Midterm/Finals | Formula Type |
|-------------|--------|----------------|--------------|
| **Zero-Based** | 100% CS, 0% Exam | 40% CS, 60% Exam | Percentage |
| **General Education** | 66.67% CS, 33.33% Exam | 66.67% CS, 33.33% Exam | Transmuted |
| **Nursing** | 100% CS, 0% Exam | 60% CS, 40% Exam | Transmuted |
| **Customized** | 100% CS, 0% Exam | 60% CS, 40% Exam | Transmuted |

## Testing

To verify the defaults are correct:

1. **Create a new subject** (or use one without grading config)
2. **Visit Zero-Based Matrix** → Should show 40% CS + 60% Exam
3. **Visit General Education Matrix** → Should show 66.67% CS + 33.33% Exam
4. **Visit Nursing Matrix** → Should show 60% CS + 40% Exam

The defaults will be automatically applied when you first access each matrix page!
