# General Education Matrix - Calculation Flow

## Formula Overview

**General Education uses a 2-step process:**

1. **Step 1**: Apply transmuted formula to get raw scores
2. **Step 2**: Apply weights to calculate term grade

## Detailed Calculation Steps

### Step 1: Calculate Class Standing (from Activities)

```
Class Standing = (total score / total items) × 50 + 50
```

**Example:**
- Student scores 450 out of 500 on all activities
- Class Standing = (450/500) × 50 + 50
- Class Standing = 0.9 × 50 + 50
- Class Standing = 45 + 50
- **Class Standing = 95** ✓

### Step 2: Calculate Exam Score

```
Exam Score = (exam score / exam max) × 50 + 50
```

**Example:**
- Student scores 85 out of 100 on exam
- Exam Score = (85/100) × 50 + 50
- Exam Score = 0.85 × 50 + 50
- Exam Score = 42.5 + 50
- **Exam Score = 92.5** ✓

### Step 3: Calculate Term Grade (Apply Weights)

```
Term Grade = (Class Standing × 66.67%) + (Exam Score × 33.33%)
```

**Example:**
- Class Standing = 95
- Exam Score = 92.5
- Term Grade = (95 × 0.6667) + (92.5 × 0.3333)
- Term Grade = 63.34 + 30.83
- **Term Grade = 94.17** ✓

## What Gets Stored in Database

| Field | Value | Description |
|-------|-------|-------------|
| `class_standing` | 95.00 | Raw transmuted score from activities |
| `exam_score` | 85 | Raw exam score entered by faculty |
| `exam_grade` | 92.50 | Transmuted exam score |
| `term_grade` | 94.17 | Final weighted term grade |

## Key Points

✅ **Class Standing is stored as the transmuted score (95), NOT the weighted portion (63.34)**

✅ **Exam Grade is stored as the transmuted score (92.5), NOT the weighted portion (30.83)**

✅ **Weights (66.67% and 33.33%) are applied ONLY when calculating the term grade**

✅ **This allows the system to recalculate term grades if weights change**

## Comparison with Old Calculation

### ❌ OLD (Incorrect):
```
Class Standing = (450/500) × 100 = 90
Exam Score = (85/100) × 100 = 85
Term Grade = (90 × 0.6667) + (85 × 0.3333) = 60 + 28.33 = 88.33
```

### ✅ NEW (Correct):
```
Class Standing = (450/500) × 50 + 50 = 95
Exam Score = (85/100) × 50 + 50 = 92.5
Term Grade = (95 × 0.6667) + (92.5 × 0.3333) = 63.34 + 30.83 = 94.17
```

**Difference:** The transmuted formula normalizes scores to a 50-100 scale, which is the standard in Philippine education systems.

## Testing the Calculation

To verify the formula is working correctly:

1. Go to **General Education Matrix**
2. Select a subject
3. Go to **Edit Term Grades**
4. Enter activity scores (e.g., 450/500)
5. Enter exam score (e.g., 85/100)
6. Check the calculated values:
   - Class Standing should show **95.00**
   - Exam Grade should show **92.50**
   - Term Grade should show **94.17**

If you see these values, the formula is working correctly! ✓
