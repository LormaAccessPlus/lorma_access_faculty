# Formula Quick Reference Card

## 🎯 One-Page Cheat Sheet

### Access Formula Config
```
Grade Matrix → Customized Matrix → Edit Term Grades → Configure Formulas
```

---

## 📝 Functions

| Function | Syntax | Example | Result |
|----------|--------|---------|--------|
| **SUM** | `SUM(range)` | `SUM(A1:A5)` | 85+90+88+92+87 = 442 |
| **AVG** | `AVG(range)` | `AVG(A1:A5)` | (85+90+88+92+87)/5 = 88.4 |
| **MAX** | `MAX(range)` | `MAX(A1:A5)` | max(85,90,88,92,87) = 92 |
| **MIN** | `MIN(range)` | `MIN(A1:A5)` | min(85,90,88,92,87) = 85 |
| **IF** | `IF(cond, true, false)` | `IF(A1>75, A1, 0)` | if 85>75 then 85 = 85 |

---

## 🔢 Operators

| Operator | Meaning | Example |
|----------|---------|---------|
| `+` | Add | `A1 + A2` |
| `-` | Subtract | `A1 - A2` |
| `*` | Multiply | `A1 * 0.6` |
| `/` | Divide | `A1 / 2` |
| `()` | Group | `(A1 + A2) / 2` |
| `>` | Greater than | `IF(A1 > 75, ...)` |
| `<` | Less than | `IF(A1 < 60, ...)` |
| `>=` | Greater or equal | `IF(A1 >= 75, ...)` |
| `<=` | Less or equal | `IF(A1 <= 60, ...)` |
| `==` | Equal to | `IF(A1 == 100, ...)` |
| `!=` | Not equal | `IF(A1 != 0, ...)` |

---

## 📊 Variables by Context

### Activity Formulas
- `score` - Student's raw score
- `MAX_SCORE` - Maximum possible score

### Quiz Formula
- `Q1`, `Q2`, `Q3`... - Individual quiz scores

### Exam Formula
- `E1` - Exam score
- `MAX_SCORE` - Maximum exam score

### Term Grade Formula
- `CS` - Class Standing
- `EXAM` - Exam score

### Final Grade Formula
- `PRELIM` - Prelim term grade
- `MIDTERM` - Midterm term grade
- `FINALS` - Finals term grade

---

## 🎓 Common Formulas

### Activity Formulas

**Percentage (0-100):**
```
(score / MAX_SCORE) * 100
```

**Transmuted (50-100):**
```
(score / MAX_SCORE) * 50 + 50
```

**Weighted:**
```
(score / MAX_SCORE) * 100 * 0.8
```

---

### Quiz Formulas

**Average all:**
```
AVG(Q1:Q5)
```

**Drop lowest:**
```
(SUM(Q1:Q5) - MIN(Q1:Q5)) / 4
```

**Weighted:**
```
Q1*0.2 + Q2*0.2 + Q3*0.2 + Q4*0.2 + Q5*0.2
```

---

### Exam Formulas

**Percentage (0-100):**
```
(E1 / MAX_SCORE) * 100
```

**Transmuted (50-100):**
```
(E1 / MAX_SCORE) * 50 + 50
```

**With curve:**
```
IF((E1/MAX_SCORE)*100 > 90, 100, (E1/MAX_SCORE)*100 + 5)
```

---

### Term Grade Formulas

**60% CS, 40% Exam:**
```
CS * 0.6 + EXAM * 0.4
```

**70% CS, 30% Exam:**
```
CS * 0.7 + EXAM * 0.3
```

**50% CS, 50% Exam:**
```
CS * 0.5 + EXAM * 0.5
```

**With bonus:**
```
IF(CS > 90, CS*0.6 + EXAM*0.4 + 5, CS*0.6 + EXAM*0.4)
```

**Minimum guarantee:**
```
MAX(CS * 0.6 + EXAM * 0.4, 60)
```

---

### Final Grade Formulas

**30/30/40:**
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

**Progressive:**
```
PRELIM * 0.25 + MIDTERM * 0.30 + FINALS * 0.45
```

**Drop lowest:**
```
(SUM(PRELIM,MIDTERM,FINALS) - MIN(PRELIM,MIDTERM,FINALS)) / 2
```

---

## 💡 Tips

1. ✅ **Test first** - Always click Test before saving
2. ✅ **Use templates** - Click template buttons for quick start
3. ✅ **Check math** - Ensure percentages add to 1.0
4. ✅ **Use parentheses** - Control order of operations
5. ✅ **Start simple** - Build complexity gradually

---

## ⚠️ Common Mistakes

❌ **Wrong:** `A1 + A2 + A3 / 3` (only A3 is divided)
✅ **Right:** `(A1 + A2 + A3) / 3`

❌ **Wrong:** `CS * 0.5 + EXAM * 0.3` (only 80%)
✅ **Right:** `CS * 0.6 + EXAM * 0.4` (100%)

❌ **Wrong:** `IF(A1 > 75 A1 0)` (missing commas)
✅ **Right:** `IF(A1 > 75, A1, 0)`

❌ **Wrong:** `AVG(A1-A5)` (wrong separator)
✅ **Right:** `AVG(A1:A5)` (use colon for range)

---

## 🔍 Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| "Unbalanced parentheses" | Missing `(` or `)` | Count and match them |
| "Unknown function" | Typo in function name | Check spelling: SUM, AVG, MAX, MIN, IF |
| "Invalid expression" | Syntax error | Check operators and variables |
| Wrong result | Order of operations | Add parentheses |

---

## 📞 Quick Help

**Faculty:** `FACULTY_QUICK_START_FORMULAS.md`
**Complete Guide:** `CUSTOMIZED_MATRIX_FORMULA_GUIDE.md`
**Visual Guide:** `UI_WALKTHROUGH.md`

---

## 🎯 Example Walkthrough

**Goal:** 60% CS, 40% Exam, drop lowest quiz

**Steps:**
1. Quiz Formula: `(SUM(Q1:Q5) - MIN(Q1:Q5)) / 4`
2. Exam Formula: `(E1 / MAX_SCORE) * 100`
3. Term Grade: `CS * 0.6 + EXAM * 0.4`
4. Test each formula
5. Save!

**Result:** Custom grading exactly as you want it!

---

**Print this page for quick reference!** 📄
