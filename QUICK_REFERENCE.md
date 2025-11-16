# 🚀 Quick Reference Card

## UI Improvements

### Navigation Hover
- **Hover:** Green background + white text
- **Active:** Green background + white text + right border
- **Transition:** Smooth 300ms

## Export Feature

### Access
**Grade Matrix → Export Grades Button (Orange)**

### Options

| Export Type | File Name | Best For |
|------------|-----------|----------|
| Activities + Exam | `SubjectCode_Activities_Exam.pdf` | Complete breakdown |
| Grade (PP) | `SubjectCode_Grades_PP.pdf` | Summary report |
| Prelim Term | `SubjectCode_Prelim_Grades.pdf` | Term report |
| Midterm Term | `SubjectCode_Midterm_Grades.pdf` | Term report |
| Finals Term | `SubjectCode_Finals_Grades.pdf` | Term report |

### Quick Steps
1. Go to Grade Matrix
2. Click orange "Export Grades"
3. Choose export type
4. PDF downloads automatically

## Grade Weights

```
Final Grade = (Prelim × 30%) + (Midterm × 30%) + (Finals × 40%)

Each Term = (Class Standing × 40%) + (Exam × 60%)

Passing Grade = 75.00
```

## Routes

```
/grades/subjects/{subject}/export/activities
/grades/subjects/{subject}/export/pp
/grades/subjects/{subject}/export/term/prelim
/grades/subjects/{subject}/export/term/midterm
/grades/subjects/{subject}/export/term/finals
```

## Colors

- **Lorma Green:** #08695A
- **Hover Green:** #065A4A
- **Export Orange:** #E67E22
- **Pass Green:** #10b981
- **Fail Red:** #ef4444

---

**That's it! Simple and powerful! 🎉**
