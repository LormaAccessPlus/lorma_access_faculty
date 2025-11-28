# Editable Exam Max Score Feature

## Overview
Made the exam max score editable so teachers can set different maximum scores for exams (not just /100).

## Changes Made

### 1. Updated Exam Score Column Layout
**Before:**
```
[Score Input]
/100 (fixed text)
```

**After:**
```
[Score Input] / [Max Score Input]
```

Both fields are now editable input fields side by side.

### 2. Input Field Configuration

**Exam Score Input:**
- Width: 16 (w-16)
- Type: number
- Min: 0
- Step: 0.01
- Placeholder: 0

**Exam Max Score Input:**
- Width: 16 (w-16)
- Type: number
- Min: 1
- Step: 0.01
- Placeholder: 100
- Default value: 100 (from database or defaults to 100)

### 3. JavaScript Handling

**Exam Max Score Input Events:**
- **Enter key**: Saves the exam score with new max score
- **Blur (lose focus)**: Automatically saves the exam score with new max score
- **Input**: Marks as changed and shows pending state

**Save Logic:**
- When max score changes, it triggers a save of the exam score
- The saveExamScore function now reads the max score from the adjacent input field
- Both score and max score are sent to the backend together

### 4. Backend Support
The backend already supports `exam_max_score` field:
- Database column: `exam_max_score` decimal(5, 2) with default 100
- Controller: `updateExamScore()` method accepts `exam_max_score` parameter
- Calculation: Exam percentage = (score / max_score) × 100

## Usage

### Setting a Custom Exam Max Score:
1. Navigate to Term Grades page
2. Find the Exam Score column
3. Enter the exam score in the first field (e.g., 45)
4. Enter the max score in the second field (e.g., 50)
5. Press Tab or click outside to save
6. System calculates: 45/50 = 90% → Exam Grade = 90% × Exam Weight

### Examples:

**Example 1: 50-point exam**
- Score: 45
- Max: 50
- Percentage: 90%
- If Exam Weight = 60%, Exam Grade = 54.00

**Example 2: 75-point exam**
- Score: 60
- Max: 75
- Percentage: 80%
- If Exam Weight = 60%, Exam Grade = 48.00

**Example 3: Standard 100-point exam**
- Score: 85
- Max: 100
- Percentage: 85%
- If Exam Weight = 60%, Exam Grade = 51.00

## Benefits

1. **Flexibility**: Teachers can use different exam formats (50-point, 75-point, 100-point, etc.)
2. **Accuracy**: Percentages calculated correctly regardless of max score
3. **Consistency**: All students in the same term use the same max score
4. **Ease of Use**: Simple inline editing, no need for separate configuration

## Visual Layout

```
┌─────────────────────────────────┐
│        Exam Score Column        │
├─────────────────────────────────┤
│  [45] / [50]                    │
│  ↑       ↑                      │
│  Score   Max Score              │
│  (editable) (editable)          │
└─────────────────────────────────┘
```

## Technical Details

### Data Flow:
1. User enters score: 45
2. User enters max: 50
3. User presses Tab/Enter or clicks away
4. JavaScript captures both values
5. Sends to backend: `{exam_score: 45, exam_max_score: 50}`
6. Backend saves both values
7. Backend calculates: percentage = 45/50 × 100 = 90%
8. Backend calculates: exam_grade = 90% × exam_weight
9. Backend calculates: term_grade = class_standing + exam_grade
10. Frontend updates display with new values

### Validation:
- Exam score must be ≥ 0
- Exam score must be ≤ max score
- Max score must be ≥ 1
- Both fields accept decimals (0.01 step)

## Files Modified
- `resources/views/grades/term-grades.blade.php`
  - Updated Exam Score column HTML structure
  - Added exam max score input handling
  - Updated saveExamScore function to read max score from input

The exam max score is now fully editable and integrated with the grading system!
