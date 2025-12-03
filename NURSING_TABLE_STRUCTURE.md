# Nursing Matrix Table Structure

## ✅ Updated Column Structure

### Term-Based Grading Page (Nursing)

```
┌──────────────┬─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│              │                                                    NURSING MATRIX COLUMNS                                                    │
├──────────────┼──────────────────────────┬──────────────┬──────────────────────────┬──────────────┬────────────┬────────────┬─────────────┤
│ Student Name │      Activities          │  Activities  │       Quizzes            │   Quizzes    │    Exam    │    Exam    │    Term     │
│              │   (Individual Scores)    │    Score     │  (Individual Scores)     │    Score     │   Score    │   Grade    │    Grade    │
│              │                          │    (15%)     │                          │    (25%)     │  (Input)   │   (60%)    │  (Total)    │
├──────────────┼──────────────────────────┼──────────────┼──────────────────────────┼──────────────┼────────────┼────────────┼─────────────┤
│              │ Act1 │ Act2 │ Act3 │ ... │              │ Q1 │ Q2 │ Q3 │ Q4 │ ... │              │            │            │             │
├──────────────┼──────┼──────┼──────┼─────┼──────────────┼────┼────┼────┼────┼─────┼──────────────┼────────────┼────────────┼─────────────┤
│ John Doe     │  85  │  90  │  88  │ ... │    13.20     │ 45 │ 48 │ 50 │ 47 │ ... │    23.50     │  85/100    │   54.60    │    91.30    │
│              │      │      │      │     │   80/100     │    │    │    │    │     │   190/200    │            │            │   PASSING   │
└──────────────┴──────┴──────┴──────┴─────┴──────────────┴────┴────┴────┴────┴─────┴──────────────┴────────────┴────────────┴─────────────┘
```

## 📊 Column Details

### 1. Student Name
- Sticky column (stays visible when scrolling)
- Shows student name and email

### 2. Activities (Individual Columns)
- **Color**: Blue background
- **Content**: Individual activity scores
- **Input**: Editable number fields
- **Max Score**: Shown below each column header
- **Examples**: Assignment 1, Lab 1, Project 1

### 3. Activities Score
- **Color**: Dark blue background
- **Formula**: `(Total Score / Overall Score × 60 + 40) × 0.15`
- **Weight**: 15%
- **Display**: 
  - Large bold number (calculated score)
  - Small text below (total/max)
- **Example**: 
  ```
  13.20
  80/100
  ```

### 4. Quizzes (Individual Columns)
- **Color**: Purple background
- **Content**: Individual quiz scores
- **Input**: Editable number fields
- **Max Score**: Shown below each column header
- **Examples**: Quiz 1, Q2, Midterm Quiz

### 5. Quizzes Score
- **Color**: Dark purple background
- **Formula**: `(Total Score / Overall Score × 60 + 40) × 0.25`
- **Weight**: 25%
- **Display**: 
  - Large bold number (calculated score)
  - Small text below (total/max)
- **Example**: 
  ```
  23.50
  190/200
  ```

### 6. Exam Score
- **Color**: Yellow background
- **Content**: Exam score input
- **Input**: Two fields (score/max)
- **Format**: `85/100`
- **Editable**: Yes (both score and max)

### 7. Exam Grade
- **Color**: Dark yellow background
- **Formula**: `(Score / Max × 60 + 40) × 0.60`
- **Weight**: 60%
- **Display**: 
  - Large bold number (calculated grade)
  - Small text below (score/max)
- **Example**: 
  ```
  54.60
  85/100
  ```

### 8. Term Grade
- **Color**: Orange background
- **Formula**: `Activities Score + Quizzes Score + Exam Grade`
- **Weight**: 100%
- **Display**: 
  - Extra large bold number (total grade)
  - Status badge (Passing/Failing)
- **Example**: 
  ```
  91.30
  ✓ PASSING
  ```

## 🎨 Color Scheme

| Column | Background Color | Text Color | Border |
|--------|-----------------|------------|--------|
| Activities (individual) | Light Blue (`bg-blue-25`) | Blue (`text-blue-600`) | Blue |
| Activities Score | Blue 100 (`bg-blue-100`) | Dark Blue (`text-blue-900`) | Dark Blue |
| Quizzes (individual) | Light Purple (`bg-purple-25`) | Purple (`text-purple-600`) | Purple |
| Quizzes Score | Purple 100 (`bg-purple-100`) | Dark Purple (`text-purple-900`) | Dark Purple |
| Exam Score | Light Yellow (`bg-yellow-25`) | Yellow (`text-yellow-600`) | Yellow |
| Exam Grade | Yellow 100 (`bg-yellow-100`) | Dark Yellow (`text-yellow-900`) | Dark Yellow |
| Term Grade | Orange 100 (`bg-orange-100`) | Dark Orange (`text-orange-900`) | Orange |

## 📐 Formulas Summary

```
Activities Score = (Total Activities / Max Activities × 60 + 40) × 0.15
Quizzes Score = (Total Quizzes / Max Quizzes × 60 + 40) × 0.25
Exam Grade = (Exam Score / Exam Max × 60 + 40) × 0.60
Term Grade = Activities Score + Quizzes Score + Exam Grade
```

## 💡 Example Calculation

### Given:
- **Activities**: 80/100 points
- **Quizzes**: 190/200 points
- **Exam**: 85/100 points

### Calculations:

**Activities Score:**
```
(80/100 × 60 + 40) × 0.15
= (0.8 × 60 + 40) × 0.15
= (48 + 40) × 0.15
= 88 × 0.15
= 13.20
```

**Quizzes Score:**
```
(190/200 × 60 + 40) × 0.25
= (0.95 × 60 + 40) × 0.25
= (57 + 40) × 0.25
= 97 × 0.25
= 24.25
```

**Exam Grade:**
```
(85/100 × 60 + 40) × 0.60
= (0.85 × 60 + 40) × 0.60
= (51 + 40) × 0.60
= 91 × 0.60
= 54.60
```

**Term Grade:**
```
13.20 + 24.25 + 54.60 = 92.05
```

**Status:** ✓ PASSING (≥ 75)

## 🔄 Comparison with Other Matrices

### General Education / Zero-Based
```
┌──────────┬─────────────────────┬──────────┬──────────┬──────┬──────┐
│ Student  │ Activities (All)    │ Total CS │ CS Grade │ Exam │ Term │
└──────────┴─────────────────────┴──────────┴──────────┴──────┴──────┘
```
- **Columns**: 6
- **Activities**: Combined
- **Formula**: Configurable

### Nursing Matrix
```
┌──────────┬────────────┬──────────┬─────────┬──────────┬──────┬──────┬──────┐
│ Student  │ Activities │ Act Score│ Quizzes │ Quiz Score│ Exam │ Exam │ Term │
│          │            │  (15%)   │         │   (25%)   │ Score│ Grade│ Grade│
│          │            │          │         │           │      │(60%) │      │
└──────────┴────────────┴──────────┴─────────┴──────────┴──────┴──────┴──────┘
```
- **Columns**: 8
- **Activities**: Separated from Quizzes
- **Formula**: Fixed nursing formula

## ✅ Key Features

1. **Clear Separation**: Activities and Quizzes are visually distinct
2. **Calculated Scores**: All scores auto-calculate using nursing formulas
3. **Visual Feedback**: Color-coded columns for easy identification
4. **Status Indicators**: Passing/Failing badges on term grade
5. **Detailed Breakdown**: Shows both individual scores and calculated totals
6. **Responsive Design**: Horizontal scroll for many activities/quizzes

## 🎯 User Benefits

- **Faculty**: Easy to input grades and see calculations
- **Students**: Clear understanding of grade breakdown
- **Administrators**: Accurate nursing formula implementation
- **System**: Proper data structure for reporting

---

**Status**: ✅ Fully Implemented and Ready to Use
