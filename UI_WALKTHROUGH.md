# Formula Configuration UI Walkthrough

## Visual Guide to Using the Formula System

### Step 1: Access Formula Configuration

```
┌─────────────────────────────────────────────────────────────────┐
│  Grade Matrix → Customized Matrix                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  📚 CS101 - Computer Science                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Students: 30    Activities: 12                          │   │
│  │                                                           │   │
│  │  [📊 Full Grade Matrix]  [✏️ Edit Term Grades]          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                           ↓ Click "Edit Term Grades"
```

### Step 2: Navigate to Formula Config

```
┌─────────────────────────────────────────────────────────────────┐
│  Term-Based Grading - CS101                                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  [🧮 Configure Formulas] [📊 Full Matrix] [← Back]              │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Prelim (100%) │ Midterm (80%) │ Finals (60%)           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                           ↓ Click "Configure Formulas"
```

### Step 3: Formula Configuration Page

```
┌─────────────────────────────────────────────────────────────────┐
│  🧮 Excel-like Formula Configuration                             │
│  CS101 - Computer Science (Prelim Term)                          │
│                                                [← Back to Grades] │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ℹ️ How to Use Formulas                              [▼ Expand] │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Variables: A1, A2, A3... for activities                │   │
│  │  Functions: SUM, AVG, MAX, MIN, IF                       │   │
│  │  Examples: AVG(A1:A5) * 0.6 + E1 * 0.4                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ✨ Quick Formula Templates                                      │
│  ┌──────────────────┐ ┌──────────────────┐                     │
│  │ Standard 60/40   │ │ 70/30 Split      │                     │
│  │ CS*0.6+EXAM*0.4  │ │ CS*0.7+EXAM*0.3  │                     │
│  └──────────────────┘ └──────────────────┘                     │
│  ┌──────────────────┐ ┌──────────────────┐                     │
│  │ Percentage 0-100 │ │ Transmuted 50-100│                     │
│  │ (E1/MAX)*100     │ │ (E1/MAX)*50+50   │                     │
│  └──────────────────┘ └──────────────────┘                     │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Step 4: Activity Formulas Section

```
┌─────────────────────────────────────────────────────────────────┐
│  📝 Activity Formulas                                            │
│  Define how each activity score is calculated                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ [A1] Quiz 1 (Lecture - Max: 50)                         │   │
│  │ Formula: [(score / MAX_SCORE) * 100          ] [🧪Test] │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ [A2] Assignment 1 (Lecture - Max: 100)                  │   │
│  │ Formula: [(score / MAX_SCORE) * 50 + 50      ] [🧪Test] │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ [A3] Lab Exercise 1 (Lab - Max: 75)                     │   │
│  │ Formula: [(score / MAX_SCORE) * 100          ] [🧪Test] │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Step 5: Quiz Formula Section

```
┌─────────────────────────────────────────────────────────────────┐
│  ❓ Quiz Formula                                                 │
│  Define how quiz scores are combined                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Formula: [AVG(Q1:Q5)                              ] [🧪Test]   │
│                                                                   │
│  💡 Common patterns:                                             │
│     • AVG(Q1:Q5) - Average all quizzes                          │
│     • (SUM(Q1:Q5) - MIN(Q1:Q5)) / 4 - Drop lowest              │
│     • SUM(Q1:Q5) / 5 - Same as average                          │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Step 6: Exam Formula Section

```
┌─────────────────────────────────────────────────────────────────┐
│  📄 Exam Formula                                                 │
│  Define how exam score is calculated                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Formula: [(E1 / MAX_SCORE) * 100                 ] [🧪Test]   │
│                                                                   │
│  💡 Common patterns:                                             │
│     • (E1 / MAX_SCORE) * 100 - Percentage (0-100)              │
│     • (E1 / MAX_SCORE) * 50 + 50 - Transmuted (50-100)         │
│     • IF(E1 > 40, (E1/MAX_SCORE)*100, 0) - Minimum threshold   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Step 7: Term Grade Formula Section

```
┌─────────────────────────────────────────────────────────────────┐
│  🧮 Term Grade Formula *                                         │
│  Define how the term grade is calculated                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Formula: [CS * 0.6 + EXAM * 0.4                  ] [🧪Test]   │
│                                                                   │
│  💡 Common patterns:                                             │
│     • CS * 0.6 + EXAM * 0.4 - Standard 60/40                   │
│     • CS * 0.7 + EXAM * 0.3 - More weight on CS                │
│     • CS * 0.5 + EXAM * 0.5 - Equal weight                     │
│     • IF(EXAM>CS, CS*0.5+EXAM*0.5, CS*0.7+EXAM*0.3) - Dynamic │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Step 8: Final Grade Formula Section

```
┌─────────────────────────────────────────────────────────────────┐
│  🏆 Final Grade Formula                                          │
│  Define how the final grade is calculated                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Formula: [PRELIM*0.3 + MIDTERM*0.3 + FINALS*0.4 ] [🧪Test]   │
│                                                                   │
│  💡 Common patterns:                                             │
│     • PRELIM*0.3 + MIDTERM*0.3 + FINALS*0.4 - Standard         │
│     • AVG(PRELIM, MIDTERM, FINALS) - Equal weight              │
│     • PRELIM*0.25 + MIDTERM*0.30 + FINALS*0.45 - Progressive   │
│     • (SUM(PRELIM,MIDTERM,FINALS)-MIN(...))/2 - Drop lowest    │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Step 9: Testing a Formula

```
┌─────────────────────────────────────────────────────────────────┐
│  🧪 Testing: Term Grade Formula                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Formula: CS * 0.6 + EXAM * 0.4                                 │
│                                                                   │
│  Sample Data:                                                     │
│    CS = 85                                                        │
│    EXAM = 90                                                      │
│                                                                   │
│  Calculation:                                                     │
│    85 * 0.6 + 90 * 0.4                                          │
│    = 51 + 36                                                      │
│    = 87                                                           │
│                                                                   │
│  ✅ Test Result: 87.00                                           │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Step 10: Saving Configuration

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                   │
│  [❌ Cancel]                              [💾 Save Formulas]     │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                           ↓ Click "Save Formulas"
┌─────────────────────────────────────────────────────────────────┐
│  ✅ Success!                                                     │
│  Formulas saved successfully!                                    │
│                                                                   │
│  Redirecting to grade entry page...                              │
└─────────────────────────────────────────────────────────────────┘
```

## Real-World Usage Example

### Scenario: Professor wants custom grading

**Requirements:**
- Activities: Percentage grading (0-100)
- Quizzes: Average of all, drop lowest
- Exam: Transmuted (50-100)
- Term: 60% CS, 40% Exam
- Final: 30% Prelim, 30% Midterm, 40% Finals

**Configuration:**

```
Activity Formulas:
  A1: (score / MAX_SCORE) * 100
  A2: (score / MAX_SCORE) * 100
  A3: (score / MAX_SCORE) * 100
  ... (same for all activities)

Quiz Formula:
  (SUM(Q1:Q5) - MIN(Q1:Q5)) / 4

Exam Formula:
  (E1 / MAX_SCORE) * 50 + 50

Term Grade Formula:
  CS * 0.6 + EXAM * 0.4

Final Grade Formula:
  PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4
```

**Result:**
- Student with activities [85, 90, 88, 92, 87]
- Quizzes [80, 85, 90, 88, 82]
- Exam 45/50

Calculations:
1. CS = AVG(85, 90, 88, 92, 87) = 88.4
2. Quiz = (80+85+90+88+82-80)/4 = 86.25 (dropped 80)
3. Exam = (45/50)*50+50 = 95
4. Term = 88.4*0.6 + 95*0.4 = 91.04

## Tips for Using the UI

### 1. Use Quick Templates
Click any template button to instantly fill a formula. Modify as needed.

### 2. Test Before Saving
Always click the 🧪Test button to verify your formula works correctly.

### 3. Read the Help Section
Click the help section at the top for detailed syntax and examples.

### 4. Start Simple
Begin with basic formulas, then add complexity as you get comfortable.

### 5. Save Often
Save your configuration after testing to avoid losing work.

## Common UI Interactions

### Clicking a Template Button
```
Before: Formula: [                                    ]
After:  Formula: [CS * 0.6 + EXAM * 0.4              ]
```

### Testing a Formula
```
Click [🧪Test] → Shows result in green box below
✅ Test Result: 87.00
```

### Error Display
```
❌ Error
Invalid formula: Unbalanced parentheses
```

### Success Message
```
✅ Success!
Formulas saved successfully!
```

## Mobile Responsive Design

The UI adapts to different screen sizes:

**Desktop:**
- Side-by-side template buttons
- Full-width formula inputs
- Inline test buttons

**Tablet:**
- Stacked template buttons
- Full-width formula inputs
- Inline test buttons

**Mobile:**
- Single column layout
- Full-width everything
- Test buttons below inputs

## Accessibility Features

- ✅ Keyboard navigation
- ✅ Screen reader friendly
- ✅ High contrast colors
- ✅ Clear error messages
- ✅ Descriptive labels
- ✅ Focus indicators

---

This visual walkthrough should help faculty understand exactly how to use the formula configuration system!
