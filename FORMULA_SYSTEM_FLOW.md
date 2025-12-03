# Formula System Flow Diagram

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     FACULTY USER INTERFACE                       │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Customized Matrix → Edit Term Grades                     │  │
│  │                                                            │  │
│  │  [Configure Formulas Button] ──────────────────────┐     │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                          │       │
└──────────────────────────────────────────────────────────┼───────┘
                                                           │
                                                           ▼
┌─────────────────────────────────────────────────────────────────┐
│              FORMULA CONFIGURATION PAGE                          │
│                                                                   │
│  ┌────────────────────┐  ┌────────────────────┐                │
│  │  Activity Formulas │  │  Quiz Formula      │                │
│  │  A1: (score/max)*100│  │  AVG(Q1:Q5)       │                │
│  │  A2: (score/max)*50+50│ │                   │                │
│  └────────────────────┘  └────────────────────┘                │
│                                                                   │
│  ┌────────────────────┐  ┌────────────────────┐                │
│  │  Exam Formula      │  │  Term Grade Formula│                │
│  │  (E1/MAX)*100      │  │  CS*0.6 + EXAM*0.4 │                │
│  └────────────────────┘  └────────────────────┘                │
│                                                                   │
│  ┌────────────────────────────────────────────┐                │
│  │  Final Grade Formula                        │                │
│  │  PRELIM*0.3 + MIDTERM*0.3 + FINALS*0.4     │                │
│  └────────────────────────────────────────────┘                │
│                                                                   │
│  [Test Formula] [Save Formulas]                                 │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│              FORMULA CONFIG CONTROLLER                           │
│                                                                   │
│  • Validate formula syntax                                       │
│  • Check for balanced parentheses                                │
│  • Verify function names                                         │
│  • Save to database                                              │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    DATABASE STORAGE                              │
│                                                                   │
│  grading_configs table:                                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ id | subject_id | term    | matrix_type | custom_formulas│  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │ 1  | 5          | prelim  | customized  | {...JSON...}   │  │
│  │ 2  | 5          | midterm | customized  | {...JSON...}   │  │
│  │ 3  | 5          | finals  | customized  | {...JSON...}   │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│              GRADE CALCULATION (When Needed)                     │
│                                                                   │
│  1. Fetch grading config with formulas                           │
│  2. For each student:                                            │
│     a. Collect activity scores → A1, A2, A3...                  │
│     b. Collect quiz scores → Q1, Q2, Q3...                      │
│     c. Collect exam score → E1                                   │
│  3. Pass to Formula Evaluator                                    │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                  FORMULA EVALUATOR SERVICE                       │
│                                                                   │
│  Input: Formula + Variables                                      │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Formula: "AVG(A1:A5) * 0.6 + E1 * 0.4"                   │  │
│  │ Variables: {A1:85, A2:90, A3:88, A4:92, A5:87, E1:90}    │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                   │
│  Processing Steps:                                               │
│  1. Replace variables: "AVG(85,90,88,92,87) * 0.6 + 90 * 0.4"  │
│  2. Handle functions: "88.4 * 0.6 + 90 * 0.4"                   │
│  3. Evaluate math: "53.04 + 36"                                  │
│  4. Return result: 89.04                                         │
│                                                                   │
│  Output: Calculated Grade                                        │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    GRADE STORAGE                                 │
│                                                                   │
│  term_grades table:                                              │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ student_id | term    | class_standing | exam | term_grade│  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │ 101        | prelim  | 88.4           | 90   | 89.04     │  │
│  │ 102        | prelim  | 85.2           | 88   | 86.32     │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Formula Evaluation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    FORMULA EVALUATION PROCESS                    │
└─────────────────────────────────────────────────────────────────┘

Step 1: INPUT
┌──────────────────────────────────────────────────────────────┐
│ Formula: "AVG(A1:A5) * 0.6 + E1 * 0.4"                       │
│ Variables: {A1: 85, A2: 90, A3: 88, A4: 92, A5: 87, E1: 90} │
└──────────────────────────────────────────────────────────────┘
                           │
                           ▼
Step 2: PARSE FUNCTIONS
┌──────────────────────────────────────────────────────────────┐
│ Identify: AVG(A1:A5)                                          │
│ Extract range: A1 to A5                                       │
│ Get values: [85, 90, 88, 92, 87]                            │
│ Calculate: (85+90+88+92+87)/5 = 88.4                        │
│ Replace: "88.4 * 0.6 + E1 * 0.4"                            │
└──────────────────────────────────────────────────────────────┘
                           │
                           ▼
Step 3: REPLACE VARIABLES
┌──────────────────────────────────────────────────────────────┐
│ Find: E1                                                      │
│ Replace with: 90                                              │
│ Result: "88.4 * 0.6 + 90 * 0.4"                             │
└──────────────────────────────────────────────────────────────┘
                           │
                           ▼
Step 4: EVALUATE MATH
┌──────────────────────────────────────────────────────────────┐
│ Expression: "88.4 * 0.6 + 90 * 0.4"                         │
│ Calculate: 53.04 + 36                                         │
│ Result: 89.04                                                 │
└──────────────────────────────────────────────────────────────┘
                           │
                           ▼
Step 5: OUTPUT
┌──────────────────────────────────────────────────────────────┐
│ Final Grade: 89.04                                            │
└──────────────────────────────────────────────────────────────┘
```

## Supported Functions

```
┌─────────────────────────────────────────────────────────────────┐
│                      FUNCTION REFERENCE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  SUM(range)                                                      │
│  ├─ SUM(A1:A5) → 85+90+88+92+87 = 442                          │
│  └─ SUM(Q1,Q2,Q3) → 85+90+88 = 263                             │
│                                                                   │
│  AVG(range)                                                      │
│  ├─ AVG(A1:A5) → (85+90+88+92+87)/5 = 88.4                     │
│  └─ AVG(Q1,Q2,Q3) → (85+90+88)/3 = 87.67                       │
│                                                                   │
│  MAX(range)                                                      │
│  ├─ MAX(A1:A5) → max(85,90,88,92,87) = 92                      │
│  └─ MAX(Q1,Q2,Q3) → max(85,90,88) = 90                         │
│                                                                   │
│  MIN(range)                                                      │
│  ├─ MIN(A1:A5) → min(85,90,88,92,87) = 85                      │
│  └─ MIN(Q1,Q2,Q3) → min(85,90,88) = 85                         │
│                                                                   │
│  IF(condition, true_value, false_value)                         │
│  ├─ IF(A1 > 75, A1, 0) → if 85>75 then 85 else 0 = 85         │
│  ├─ IF(AVG(A1:A5) >= 80, 100, 90) → if 88.4>=80 then 100 = 100│
│  └─ IF(CS > EXAM, CS, EXAM) → if 88>90 then 88 else 90 = 90   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Variable Context by Formula Type

```
┌─────────────────────────────────────────────────────────────────┐
│                    VARIABLE CONTEXTS                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ACTIVITY FORMULAS                                               │
│  ├─ score      : Student's raw score on this activity           │
│  └─ MAX_SCORE  : Maximum possible score for this activity       │
│                                                                   │
│  QUIZ FORMULA                                                    │
│  ├─ Q1, Q2, Q3... : Individual quiz scores                      │
│  └─ MAX_SCORE     : Maximum possible quiz score                 │
│                                                                   │
│  EXAM FORMULA                                                    │
│  ├─ E1        : Student's exam score                            │
│  └─ MAX_SCORE : Maximum possible exam score                     │
│                                                                   │
│  TERM GRADE FORMULA                                              │
│  ├─ CS   : Class Standing (from activities/quizzes)            │
│  └─ EXAM : Exam score (after applying exam formula)            │
│                                                                   │
│  FINAL GRADE FORMULA                                             │
│  ├─ PRELIM  : Prelim term grade                                │
│  ├─ MIDTERM : Midterm term grade                               │
│  └─ FINALS  : Finals term grade                                │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Example Calculation Walkthrough

```
SCENARIO: Calculate term grade for a student

Given:
- Activities: A1=85, A2=90, A3=88, A4=92, A5=87
- Exam: E1=45 out of MAX_SCORE=50

Formulas:
- Activity: (score / MAX_SCORE) * 100
- Term Grade: CS * 0.6 + EXAM * 0.4

Step 1: Calculate Class Standing (CS)
┌──────────────────────────────────────────────────────────────┐
│ Activities already converted to 100-point scale              │
│ CS = AVG(A1:A5) = (85+90+88+92+87)/5 = 88.4                │
└──────────────────────────────────────────────────────────────┘

Step 2: Calculate Exam Score
┌──────────────────────────────────────────────────────────────┐
│ Formula: (E1 / MAX_SCORE) * 100                              │
│ Variables: {E1: 45, MAX_SCORE: 50}                          │
│ Calculation: (45 / 50) * 100 = 90                           │
│ EXAM = 90                                                     │
└──────────────────────────────────────────────────────────────┘

Step 3: Calculate Term Grade
┌──────────────────────────────────────────────────────────────┐
│ Formula: CS * 0.6 + EXAM * 0.4                               │
│ Variables: {CS: 88.4, EXAM: 90}                             │
│ Calculation: 88.4 * 0.6 + 90 * 0.4                          │
│            = 53.04 + 36                                       │
│            = 89.04                                            │
│ TERM GRADE = 89.04                                           │
└──────────────────────────────────────────────────────────────┘

Final Result: Student's term grade is 89.04
```

## Integration Points

```
┌─────────────────────────────────────────────────────────────────┐
│              WHERE TO INTEGRATE FORMULA EVALUATION               │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  1. GRADE CALCULATION SERVICE                                    │
│     Location: app/Services/GradeCalculationService.php          │
│     When: Calculating term grades from activities               │
│     Use: Evaluate activity and term grade formulas              │
│                                                                   │
│  2. GRADE CONTROLLER                                             │
│     Location: app/Http/Controllers/GradeController.php          │
│     When: Saving/updating grades                                │
│     Use: Apply formulas before storing grades                   │
│                                                                   │
│  3. TERM GRADE CALCULATION                                       │
│     Location: Wherever term grades are computed                 │
│     When: Combining class standing and exam                     │
│     Use: Evaluate term grade formula                            │
│                                                                   │
│  4. FINAL GRADE CALCULATION                                      │
│     Location: Grade matrix views                                │
│     When: Displaying final grades                               │
│     Use: Evaluate final grade formula                           │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

This visual guide should help understand how the formula system works from end to end!
