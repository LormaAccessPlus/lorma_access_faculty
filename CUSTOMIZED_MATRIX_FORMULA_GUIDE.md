# Customized Matrix - Excel-like Formula Guide

## Overview
The Customized Matrix now supports Excel-like formulas, allowing faculty to define custom calculations for activities, quizzes, exams, term grades, and final grades.

## Accessing Formula Configuration

1. Navigate to **Grade Matrix** → **Customized Matrix**
2. Select a subject
3. Click **Edit Term Grades**
4. Click the **Configure Formulas** button (orange button)

## Formula Syntax

### Variables

#### For Activities
- `A1`, `A2`, `A3`, ... - Individual activity scores
- `score` - The raw score for an activity (when defining activity formulas)
- `MAX_SCORE` - Maximum possible score for an activity

#### For Quizzes
- `Q1`, `Q2`, `Q3`, ... - Individual quiz scores

#### For Exams
- `E1` - Exam score
- `MAX_SCORE` - Maximum possible exam score

#### For Term Grades
- `CS` - Class Standing (calculated from activities and quizzes)
- `EXAM` - Exam score (after applying exam formula)

#### For Final Grades
- `PRELIM` - Prelim term grade
- `MIDTERM` - Midterm term grade
- `FINALS` - Finals term grade

### Operators

- `+` - Addition
- `-` - Subtraction
- `*` - Multiplication
- `/` - Division
- `()` - Parentheses for grouping

### Functions

#### SUM
Sum of values in a range or list.

**Examples:**
```
SUM(A1:A5)          // Sum of activities 1 through 5
SUM(Q1,Q2,Q3)       // Sum of specific quizzes
```

#### AVG
Average of values in a range or list.

**Examples:**
```
AVG(A1:A5)          // Average of activities 1 through 5
AVG(Q1,Q2,Q3,Q4)    // Average of specific quizzes
```

#### MAX
Maximum value in a range or list.

**Examples:**
```
MAX(A1:A5)          // Highest score among activities 1-5
MAX(Q1,Q2,Q3)       // Highest quiz score
```

#### MIN
Minimum value in a range or list.

**Examples:**
```
MIN(A1:A5)          // Lowest score among activities 1-5
MIN(Q1,Q2,Q3)       // Lowest quiz score
```

#### IF
Conditional logic.

**Syntax:**
```
IF(condition, value_if_true, value_if_false)
```

**Comparison Operators:**
- `>` - Greater than
- `<` - Less than
- `>=` - Greater than or equal
- `<=` - Less than or equal
- `==` - Equal to
- `!=` - Not equal to

**Examples:**
```
IF(A1 > 75, A1, 0)              // Use A1 if > 75, else 0
IF(AVG(A1:A5) >= 80, 100, 90)   // 100 if average >= 80, else 90
```

## Common Formula Examples

### Activity Formulas

**Percentage Formula (0-100):**
```
(score / MAX_SCORE) * 100
```

**Transmuted Formula (50-100):**
```
(score / MAX_SCORE) * 50 + 50
```

**Weighted Score:**
```
(score / MAX_SCORE) * 100 * 0.8
```

### Quiz Formula

**Average of all quizzes:**
```
AVG(Q1:Q5)
```

**Drop lowest quiz:**
```
(SUM(Q1:Q5) - MIN(Q1:Q5)) / 4
```

**Weighted average:**
```
Q1 * 0.2 + Q2 * 0.2 + Q3 * 0.2 + Q4 * 0.2 + Q5 * 0.2
```

### Exam Formula

**Percentage (0-100):**
```
(E1 / MAX_SCORE) * 100
```

**Transmuted (50-100):**
```
(E1 / MAX_SCORE) * 50 + 50
```

**Curved exam:**
```
IF((E1 / MAX_SCORE) * 100 > 90, 100, (E1 / MAX_SCORE) * 100 + 5)
```

### Term Grade Formula

**Standard 60/40 split:**
```
CS * 0.6 + EXAM * 0.4
```

**70/30 split:**
```
CS * 0.7 + EXAM * 0.3
```

**50/50 split:**
```
CS * 0.5 + EXAM * 0.5
```

**Conditional weighting:**
```
IF(EXAM > CS, CS * 0.5 + EXAM * 0.5, CS * 0.7 + EXAM * 0.3)
```

### Final Grade Formula

**Standard 30/30/40:**
```
PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4
```

**Equal weights:**
```
AVG(PRELIM, MIDTERM, FINALS)
```
or
```
(PRELIM + MIDTERM + FINALS) / 3
```

**Progressive weighting:**
```
PRELIM * 0.25 + MIDTERM * 0.30 + FINALS * 0.45
```

**Drop lowest term:**
```
(SUM(PRELIM, MIDTERM, FINALS) - MIN(PRELIM, MIDTERM, FINALS)) / 2
```

## Advanced Examples

### Complex Activity Calculation
```
(AVG(A1:A3) * 0.4 + AVG(A4:A6) * 0.6)
```
This weights the first 3 activities at 40% and the last 3 at 60%.

### Bonus Points
```
IF(AVG(A1:A5) >= 95, AVG(A1:A5) + 5, AVG(A1:A5))
```
Adds 5 bonus points if average is 95 or higher.

### Minimum Score Guarantee
```
MAX(CS * 0.6 + EXAM * 0.4, 60)
```
Ensures minimum grade of 60.

### Weighted Components with Bonus
```
(CS * 0.6 + EXAM * 0.4) + IF(CS > 90, 5, 0)
```
Standard calculation plus 5 bonus points if CS > 90.

## Testing Formulas

Before saving, you can test each formula:

1. Enter your formula in the input field
2. Click the **Test** button
3. The system will evaluate the formula with sample data
4. Check if the result matches your expectations

## Tips

1. **Start Simple**: Begin with basic formulas and add complexity as needed
2. **Test Thoroughly**: Always test formulas with different sample values
3. **Use Parentheses**: When in doubt, use parentheses to ensure correct order of operations
4. **Check Ranges**: Make sure variable ranges (A1:A5) match your actual activities
5. **Validate Weights**: Ensure percentage weights add up to 1.0 (or 100%)

## Troubleshooting

### Formula Not Working?
- Check for balanced parentheses
- Verify variable names are correct (case-sensitive)
- Ensure all operators are valid
- Test with the Test button before saving

### Unexpected Results?
- Review order of operations (multiplication/division before addition/subtraction)
- Add parentheses to control calculation order
- Check if variables have the expected values

### Error Messages?
- "Unbalanced parentheses" - Count opening and closing parentheses
- "Unknown function" - Check function name spelling (SUM, AVG, MAX, MIN, IF)
- "Invalid expression" - Review syntax for typos or invalid characters

## Support

If you need help with formulas:
1. Use the built-in examples as templates
2. Test formulas incrementally (build complex formulas step by step)
3. Contact your system administrator for assistance

## Migration from Old System

If you were using the old weight-based system:
- Old: 60% CS + 40% Exam
- New Formula: `CS * 0.6 + EXAM * 0.4`

The new system is more flexible and allows for custom calculations beyond simple weighted averages.
