# Grade Matrix Comparison: Nursing vs General Education vs Zero-Based

## Table Structure Comparison

### General Education & Zero-Based Matrix
```
┌─────────────────┬─────────┬─────────┬─────────┬─────────────┐
│ Student Info    │ Prelim  │ Midterm │ Finals  │ Final Grade │
├─────────────────┼─────────┼─────────┼─────────┼─────────────┤
│ John Doe        │  85.00  │  88.00  │  90.00  │    87.70    │
│                 │ CS: 80  │ CS: 85  │ CS: 87  │   PASSED    │
│                 │ Exam:90 │ Exam:91 │ Exam:93 │             │
└─────────────────┴─────────┴─────────┴─────────┴─────────────┘
```

**Columns:** 5 total
- Student Information
- Prelim (combined CS + Exam)
- Midterm (combined CS + Exam)
- Finals (combined CS + Exam)
- Final Grade

---

### Nursing Matrix (NEW)
```
┌──────────────┬────────────────────────────────────┬────────────────────────────────────┬────────────────────────────────────┬──────────┬──────────────┐
│ Student Info │           Prelim                   │           Midterm                  │           Finals                   │   Comp   │ Final Rating │
│              ├─────┬────────┬──────┬──────────────┼─────┬────────┬──────┬──────────────┼─────┬────────┬──────┬──────────────┤   Exam   │              │
│              │ Act │ Quiz   │ Exam │    Total     │ Act │ Quiz   │ Exam │    Total     │ Act │ Quiz   │ Exam │    Total     │          │              │
├──────────────┼─────┼────────┼──────┼──────────────┼─────┼────────┼──────┼──────────────┼─────┼────────┼──────┼──────────────┼──────────┼──────────────┤
│ John Doe     │12.50│  20.75 │48.00 │    81.25     │13.00│  21.50 │49.00 │    83.50     │13.50│  22.00 │50.00 │    85.50     │   88.00  │    87.04     │
│              │     │        │      │              │     │        │      │              │     │        │      │              │          │   PASSED     │
└──────────────┴─────┴────────┴──────┴──────────────┴─────┴────────┴──────┴──────────────┴─────┴────────┴──────┴──────────────┴──────────┴──────────────┘
```

**Columns:** 14 total
- Student Information
- **Prelim:** Activities, Quizzes, Exam, Total (4 columns)
- **Midterm:** Activities, Quizzes, Exam, Total (4 columns)
- **Finals:** Activities, Quizzes, Exam, Total (4 columns)
- Comprehensive Exam (1 column)
- Final Rating (1 column)

---

## Formula Comparison

### General Education & Zero-Based

**Class Standing Calculation:**
- Various methods (percentage, weighted average, etc.)
- Depends on configuration

**Term Grade:**
```
Term Grade = (Class Standing × CS%) + (Exam × Exam%)
```

**Final Grade:**
```
Final Grade = (Prelim × 30%) + (Midterm × 30%) + (Finals × 40%)
```

---

### Nursing Matrix

**Activities Calculation:**
```
Activities = (Total Score / Overall Score × 60 + 40) × 0.15
```

**Quizzes Calculation:**
```
Quizzes = (Total Score / Overall Score × 60 + 40) × 0.25
```

**Exam Calculation:**
```
Exam = (Total Score / Overall Score × 60 + 40) × 0.60
```

**Term Grade:**
```
Term Grade = Activities + Quizzes + Exam
```

**Final Grade:**
```
Final Grade = (Prelim × 30%) + (Midterm × 30%) + (Finals × 40%)
```

**Final Rating (with Comprehensive Exam):**
```
Final Rating = (Final Grade × 80%) + (Comprehensive Exam × 20%)
```

---

## Key Differences

| Feature | General Education / Zero-Based | Nursing |
|---------|-------------------------------|---------|
| **Activities & Quizzes** | Combined into Class Standing | Separated with different weights |
| **Activity Weight** | Varies by configuration | Fixed at 15% (0.15) |
| **Quiz Weight** | N/A (part of activities) | Fixed at 25% (0.25) |
| **Exam Weight** | Varies by configuration | Fixed at 60% (0.60) |
| **Comprehensive Exam** | Not included | 20% of final rating |
| **Final Calculation** | Based on 3 terms only | Includes comprehensive exam |
| **Table Columns** | 5 columns | 14 columns |
| **Formula Complexity** | Configurable | Fixed nursing-specific |

---

## Visual Color Coding

### General Education / Zero-Based
- **Blue:** Prelim
- **Green:** Midterm  
- **Purple:** Finals
- **Teal:** Final Grade

### Nursing Matrix
- **Blue shades:** Prelim (Activities, Quizzes, Exam, Total)
- **Green shades:** Midterm (Activities, Quizzes, Exam, Total)
- **Purple shades:** Finals (Activities, Quizzes, Exam, Total)
- **Orange:** Comprehensive Exam
- **Teal:** Final Rating

---

## Example Calculation

### Nursing Matrix Example

**Given:**
- Activities: 80/100 points
- Quizzes: 90/100 points
- Exam: 85/100 points
- Comprehensive Exam: 88/100

**Prelim Calculation:**
```
Activities = (80/100 × 60 + 40) × 0.15 = (48 + 40) × 0.15 = 13.20
Quizzes = (90/100 × 60 + 40) × 0.25 = (54 + 40) × 0.25 = 23.50
Exam = (85/100 × 60 + 40) × 0.60 = (51 + 40) × 0.60 = 54.60

Prelim Term Grade = 13.20 + 23.50 + 54.60 = 91.30
```

**Assuming similar scores for Midterm and Finals:**
```
Final Grade = (91.30 × 30%) + (91.30 × 30%) + (91.30 × 40%)
Final Grade = 27.39 + 27.39 + 36.52 = 91.30
```

**Final Rating (with Comprehensive Exam):**
```
Comprehensive Exam Score = (88/100 × 60 + 40) × 1.0 = 92.80

Final Rating = (91.30 × 80%) + (92.80 × 20%)
Final Rating = 73.04 + 18.56 = 91.60
```

---

## Implementation Status

✅ **Completed:**
- Nursing matrix table structure with 14 columns
- Separate Activities and Quizzes columns
- Comprehensive Exam column
- Formula documentation in legend
- Visual color coding
- Final Rating calculation display

⏳ **Pending Backend:**
- Database schema for comprehensive exam scores
- Activity category differentiation (activity vs quiz)
- NursingGradeCalculator service
- Google Classroom quiz fetching
- Term grades calculation with nursing formulas
- Comprehensive exam input interface
