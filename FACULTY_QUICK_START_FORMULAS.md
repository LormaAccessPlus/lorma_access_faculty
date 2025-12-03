# Faculty Quick Start Guide - Custom Formulas

## 🎯 What Can You Do?

With the new formula system, you can create **Excel-like formulas** to calculate grades exactly how you want them. No more being limited to preset percentages!

## 🚀 Getting Started (3 Easy Steps)

### Step 1: Navigate to Formula Configuration
1. Go to **Grade Matrix** → **Customized Matrix**
2. Click on your subject
3. Click **Edit Term Grades**
4. Click the orange **Configure Formulas** button

### Step 2: Set Your Formulas
Choose from quick templates or write your own formulas for:
- Individual activities
- Quizzes
- Exams
- Term grades
- Final grades

### Step 3: Test and Save
- Click **Test** to verify your formula works
- Click **Save Formulas** when ready

## 📝 Common Scenarios

### Scenario 1: "I want 60% class standing, 40% exam"

**Term Grade Formula:**
```
CS * 0.6 + EXAM * 0.4
```

### Scenario 2: "I want to use percentage grading (0-100)"

**Exam Formula:**
```
(E1 / MAX_SCORE) * 100
```

### Scenario 3: "I want transmuted grading (50-100)"

**Exam Formula:**
```
(E1 / MAX_SCORE) * 50 + 50
```

### Scenario 4: "I want to average all quizzes"

**Quiz Formula:**
```
AVG(Q1:Q5)
```
*Replace Q5 with your last quiz number*

### Scenario 5: "I want to drop the lowest quiz"

**Quiz Formula:**
```
(SUM(Q1:Q5) - MIN(Q1:Q5)) / 4
```
*This sums all quizzes, removes the lowest, then divides by 4*

### Scenario 6: "I want 30% prelim, 30% midterm, 40% finals"

**Final Grade Formula:**
```
PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4
```

### Scenario 7: "I want to give bonus points if class standing is high"

**Term Grade Formula:**
```
IF(CS > 90, CS * 0.6 + EXAM * 0.4 + 5, CS * 0.6 + EXAM * 0.4)
```
*Adds 5 bonus points if CS is above 90*

### Scenario 8: "I want different weights for different activity groups"

**Term Grade Formula:**
```
AVG(A1:A3) * 0.3 + AVG(A4:A6) * 0.3 + EXAM * 0.4
```
*First 3 activities = 30%, next 3 activities = 30%, exam = 40%*

## 🔧 Formula Building Blocks

### Basic Math
- `+` Add
- `-` Subtract
- `*` Multiply
- `/` Divide
- `()` Group operations

### Functions You Can Use

**SUM** - Add numbers together
```
SUM(A1:A5)      → Adds activities 1 through 5
SUM(Q1,Q2,Q3)   → Adds specific quizzes
```

**AVG** - Calculate average
```
AVG(A1:A5)      → Average of activities 1 through 5
AVG(Q1,Q2,Q3)   → Average of specific quizzes
```

**MAX** - Find highest value
```
MAX(A1:A5)      → Highest activity score
```

**MIN** - Find lowest value
```
MIN(A1:A5)      → Lowest activity score
```

**IF** - Conditional logic
```
IF(condition, value_if_true, value_if_false)

Examples:
IF(CS > 80, 100, 90)           → 100 if CS > 80, otherwise 90
IF(EXAM >= 75, EXAM, 0)        → Use exam score if ≥75, else 0
```

### Variables You Can Use

**For Activities:**
- `A1`, `A2`, `A3`... - Activity scores
- `score` - Raw score (when setting activity formula)
- `MAX_SCORE` - Maximum possible score

**For Quizzes:**
- `Q1`, `Q2`, `Q3`... - Quiz scores

**For Exams:**
- `E1` - Exam score
- `MAX_SCORE` - Maximum exam score

**For Term Grades:**
- `CS` - Class Standing
- `EXAM` - Exam score

**For Final Grades:**
- `PRELIM` - Prelim grade
- `MIDTERM` - Midterm grade
- `FINALS` - Finals grade

## 💡 Pro Tips

### Tip 1: Use Quick Templates
Click on any template button to instantly fill in a formula. You can then modify it!

### Tip 2: Test Before Saving
Always click the **Test** button to see if your formula gives the expected result.

### Tip 3: Start Simple
Begin with basic formulas like `CS * 0.6 + EXAM * 0.4`, then add complexity as needed.

### Tip 4: Use Parentheses
When in doubt, use parentheses to make sure calculations happen in the right order:
```
(A1 + A2 + A3) / 3        ✅ Correct
A1 + A2 + A3 / 3          ❌ Wrong (only A3 is divided)
```

### Tip 5: Check Your Percentages
Make sure percentages add up to 1.0 (or 100%):
```
CS * 0.6 + EXAM * 0.4     ✅ Adds to 1.0
CS * 0.5 + EXAM * 0.3     ❌ Only adds to 0.8
```

## ❓ Troubleshooting

### "My formula isn't working"
- Check for typos in variable names (case-sensitive!)
- Make sure parentheses are balanced: `(` must have matching `)`
- Verify you're using the right variables for the formula type

### "I get unexpected results"
- Use the Test button with sample numbers
- Check order of operations (multiply/divide before add/subtract)
- Add parentheses to control calculation order

### "I want to change my formula"
- You can edit formulas anytime
- Changes apply to future grade calculations
- Previous grades remain unchanged unless recalculated

## 📚 Need More Help?

1. **In-App Help**: Click the help section at the top of the formula config page
2. **Complete Guide**: See `CUSTOMIZED_MATRIX_FORMULA_GUIDE.md` for detailed examples
3. **Technical Details**: See `FORMULA_SYSTEM_FLOW.md` for how it works

## 🎓 Real-World Examples

### Example 1: Traditional Grading
```
Activities: (score / MAX_SCORE) * 100
Exam: (E1 / MAX_SCORE) * 100
Term Grade: CS * 0.6 + EXAM * 0.4
Final Grade: PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4
```

### Example 2: Transmuted Grading
```
Activities: (score / MAX_SCORE) * 50 + 50
Exam: (E1 / MAX_SCORE) * 50 + 50
Term Grade: CS * 0.6 + EXAM * 0.4
Final Grade: PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4
```

### Example 3: Progressive Weighting
```
Term Grade: CS * 0.7 + EXAM * 0.3
Final Grade: PRELIM * 0.25 + MIDTERM * 0.30 + FINALS * 0.45
```
*Finals count more than earlier terms*

### Example 4: Performance-Based Bonus
```
Term Grade: IF(CS > 95, CS * 0.6 + EXAM * 0.4 + 3, CS * 0.6 + EXAM * 0.4)
```
*3 bonus points for excellent class standing*

### Example 5: Minimum Grade Protection
```
Term Grade: MAX(CS * 0.6 + EXAM * 0.4, 60)
```
*Ensures minimum grade of 60*

## 🎉 You're Ready!

Start with simple formulas and experiment. The system is flexible and powerful - you can create any grading scheme you need!

**Remember:** Test your formulas before saving, and you can always change them later if needed.

---

**Questions?** Contact your system administrator or refer to the complete documentation.
