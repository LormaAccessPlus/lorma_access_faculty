# Dynamic Grading System - Visual Workflow Guide

## 🎯 System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                   DYNAMIC GRADING SYSTEM                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   STUDENTS   │  │   GRADING    │  │  GRADE SHEET │     │
│  │     PAGE     │→ │     PAGE     │→ │     PAGE     │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│   Import & Match    Configure         Enter Grades         │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## 📊 Data Flow Diagram

```
┌─────────────┐
│  CSV File   │
└──────┬──────┘
       │ Upload
       ↓
┌─────────────────────┐
│  Auto-Match Engine  │
│  - Email Match      │
│  - Name Similarity  │
└──────┬──────────────┘
       │ Save
       ↓
┌─────────────────────┐
│ student_mappings    │
│ - csv_data          │
│ - auto_matched      │
│ - confidence        │
└──────┬──────────────┘
       │ Load Students
       ↓
┌─────────────────────┐
│  Grading Class      │
│  - Components       │
│  - Weights          │
│  - Formulas         │
└──────┬──────────────┘
       │ Enter Grades
       ↓
┌─────────────────────┐
│  student_grades     │
│  - score            │
│  - computed_score   │
└──────┬──────────────┘
       │ Calculate
       ↓
┌─────────────────────┐
│   Term Grade        │
└─────────────────────┘
```

## 🔄 Workflow Steps

### Step 1: Import Students

```
┌────────────────────────────────────────────────────┐
│                  STUDENTS PAGE                      │
├────────────────────────────────────────────────────┤
│                                                     │
│  Subject: Mathematics 101                          │
│  ┌──────────────────────────────────────┐         │
│  │  📁 Import CSV                        │         │
│  │                                       │         │
│  │  [Choose File] sample_students.csv   │         │
│  │                                       │         │
│  │  [Upload & Match]                    │         │
│  └──────────────────────────────────────┘         │
│                                                     │
│  Results:                                          │
│  ✅ 10 students imported                           │
│  ✅ 8 automatically matched                        │
│  ⚠️  2 need manual review                          │
│                                                     │
│  [View Students]                                   │
│                                                     │
└────────────────────────────────────────────────────┘
```

### Step 2: Add & Configure Class

```
┌────────────────────────────────────────────────────┐
│                  GRADING PAGE                       │
├────────────────────────────────────────────────────┤
│                                                     │
│  [+ Add Class]                                     │
│                                                     │
│  ┌──────────────────────────────────────┐         │
│  │  Select Subject: [Mathematics 101 ▼] │         │
│  │  Select Term:    [Prelim ▼]          │         │
│  │                                       │         │
│  │  [Add Class]                         │         │
│  └──────────────────────────────────────┘         │
│                                                     │
│  ↓ Redirects to Configuration                     │
│                                                     │
└────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────┐
│              CONFIGURATION PAGE                     │
├────────────────────────────────────────────────────┤
│                                                     │
│  Grading Components:                               │
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │ Component 1                                  │  │
│  │ Name: [Activities    ] Type: [Activity ▼]  │  │
│  │ Weight: [15] %  Formula: [score/total*100] │  │
│  │                                         [🗑️] │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │ Component 2                                  │  │
│  │ Name: [Quizzes      ] Type: [Quiz ▼]       │  │
│  │ Weight: [25] %  Formula: [score/total*100] │  │
│  │                                         [🗑️] │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │ Component 3                                  │  │
│  │ Name: [Exams        ] Type: [Exam ▼]       │  │
│  │ Weight: [60] %  Formula: [score/total*100] │  │
│  │                                         [🗑️] │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  [+ Add Component]                                 │
│                                                     │
│  Total Weight: 100% ✅                             │
│                                                     │
│  [Save Configuration]                              │
│                                                     │
└────────────────────────────────────────────────────┘
```

### Step 3: Enter Grades

```
┌──────────────────────────────────────────────────────────────────────┐
│                         GRADE SHEET                                   │
├──────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  Mathematics 101 - Prelim                                            │
│                                                                       │
│  [+ Add Item to Component]  [Configure]  [Back]                     │
│                                                                       │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │ Student      │ Activities (15%) │ Quizzes (25%)  │ Exams (60%) │ │
│  │              │ Act1  Act2  Total│ Q1   Q2  Total │ E1    Total │ │
│  │              │ /50   /50        │ /50  /50       │ /100        │ │
│  ├──────────────┼──────────────────┼────────────────┼─────────────┤ │
│  │ Juan DC      │ [45] [48]  93.0 │ [40] [45] 85.0 │ [85]   85.0 │ │
│  │ Maria S      │ [50] [50] 100.0 │ [48] [50] 98.0 │ [95]   95.0 │ │
│  │ Pedro R      │ [42] [45]  87.0 │ [35] [40] 75.0 │ [80]   80.0 │ │
│  └──────────────┴──────────────────┴────────────────┴─────────────┘ │
│                                                                       │
│  Term Grade Calculation:                                             │
│  = (Activities × 15%) + (Quizzes × 25%) + (Exams × 60%)            │
│                                                                       │
└──────────────────────────────────────────────────────────────────────┘
```

## 🎨 Component Configuration Examples

### Example 1: Simple Percentage

```
┌─────────────────────────────────────┐
│  Component: Activities              │
│  Weight: 15%                        │
│  Formula: score / total * 100      │
│                                     │
│  Example:                           │
│  Score: 45, Total: 50              │
│  Result: (45/50)*100 = 90.00       │
│  Weighted: 90 × 0.15 = 13.50       │
└─────────────────────────────────────┘
```

### Example 2: Transmuted Scale

```
┌─────────────────────────────────────┐
│  Component: Exam                    │
│  Weight: 60%                        │
│  Formula: score / total * 60 + 40  │
│                                     │
│  Example:                           │
│  Score: 45, Total: 50              │
│  Result: (45/50)*60+40 = 94.00     │
│  Weighted: 94 × 0.60 = 56.40       │
└─────────────────────────────────────┘
```

### Example 3: Custom Scale

```
┌─────────────────────────────────────┐
│  Component: Project                 │
│  Weight: 25%                        │
│  Formula: score / total * 50 + 50  │
│                                     │
│  Example:                           │
│  Score: 40, Total: 50              │
│  Result: (40/50)*50+50 = 90.00     │
│  Weighted: 90 × 0.25 = 22.50       │
└─────────────────────────────────────┘
```

## 📈 Calculation Flow Visualization

```
┌─────────────┐
│ Raw Score   │  Student enters: 45
└──────┬──────┘
       │
       ↓
┌─────────────────────┐
│ Apply Formula       │  Formula: score/total*60+40
│ score=45, total=50  │  Result: (45/50)*60+40 = 94
└──────┬──────────────┘
       │
       ↓
┌─────────────────────┐
│ Computed Score      │  94.00
└──────┬──────────────┘
       │
       ↓
┌─────────────────────┐
│ Component Average   │  If multiple items: sum/count
│ (if multiple items) │  Example: (94+90)/2 = 92
└──────┬──────────────┘
       │
       ↓
┌─────────────────────┐
│ Apply Weight        │  Weight: 60%
│                     │  Result: 92 × 0.60 = 55.20
└──────┬──────────────┘
       │
       ↓
┌─────────────────────┐
│ Weighted Component  │  55.20
└──────┬──────────────┘
       │
       ↓ (Repeat for all components)
       │
┌─────────────────────┐
│ Sum All Components  │  Activities: 13.50
│                     │  Quizzes:    21.25
│                     │  Exams:      55.20
└──────┬──────────────┘
       │
       ↓
┌─────────────────────┐
│ Term Grade          │  13.50 + 21.25 + 55.20 = 89.95
└─────────────────────┘
```

## 🔍 Auto-Matching Visualization

```
CSV Student                    GCR Student
┌──────────────────┐          ┌──────────────────┐
│ Juan Dela Cruz   │          │ Juan D. Cruz     │
│ juan@example.com │  ──────→ │ juan@example.com │
└──────────────────┘          └──────────────────┘
                              
Email Match: ✅ 100%
Name Match:  ✅ 95%
Result: AUTO-MATCHED ✅


CSV Student                    GCR Student
┌──────────────────┐          ┌──────────────────┐
│ Maria Santos     │          │ Maria S. Santos  │
│ maria@other.com  │  ──────→ │ maria@gcr.com    │
└──────────────────┘          └──────────────────┘
                              
Email Match: ❌ 0%
Name Match:  ✅ 90%
Result: AUTO-MATCHED ✅


CSV Student                    GCR Student
┌──────────────────┐          ┌──────────────────┐
│ John Smith       │          │ Juan Dela Cruz   │
│ john@example.com │  ──────→ │ juan@gcr.com     │
└──────────────────┘          └──────────────────┘
                              
Email Match: ❌ 0%
Name Match:  ❌ 20%
Result: NOT MATCHED ⚠️
```

## 🎯 Weight Validation Visualization

```
VALID Configuration ✅
┌─────────────────────────────┐
│ Activities:  15%            │
│ Quizzes:     25%            │
│ Exams:       60%            │
├─────────────────────────────┤
│ TOTAL:      100% ✅         │
│ [Save Configuration]        │
└─────────────────────────────┘


INVALID Configuration ❌
┌─────────────────────────────┐
│ Activities:  20%            │
│ Quizzes:     25%            │
│ Exams:       60%            │
├─────────────────────────────┤
│ TOTAL:      105% ❌         │
│ ⚠️ Must equal 100%          │
│ [Save Configuration] 🚫     │
└─────────────────────────────┘
```

## 💾 Database Relationships

```
┌─────────────────┐
│    subjects     │
└────────┬────────┘
         │
         │ has many
         ↓
┌─────────────────┐
│ grading_classes │
└────────┬────────┘
         │
         │ has many
         ↓
┌──────────────────────┐
│ grading_components   │
└────────┬─────────────┘
         │
         │ has many
         ↓
┌──────────────────────┐
│  component_items     │
└────────┬─────────────┘
         │
         │ has many
         ↓
┌──────────────────────┐
│   student_grades     │
└──────────────────────┘
         │
         │ belongs to
         ↓
┌──────────────────────┐
│  student_mappings    │
└──────────────────────┘
```

## 🎓 Complete Example

```
SCENARIO: Prof. Smith's Math 101 - Prelim

Step 1: Import Students
├─ Upload: students.csv (30 students)
├─ Auto-match: 28 matched, 2 manual
└─ Result: 30 students ready

Step 2: Configure Grading
├─ Activities (15%): score/total*100
├─ Quizzes (25%): score/total*100
└─ Exams (60%): score/total*100

Step 3: Add Items
├─ Activities: Act1(/50), Act2(/50), Act3(/50)
├─ Quizzes: Q1(/50), Q2(/50)
└─ Exams: Prelim(/100)

Step 4: Enter Grades (Student: Juan)
├─ Act1: 45/50 → 90.00
├─ Act2: 48/50 → 96.00
├─ Act3: 50/50 → 100.00
├─ Q1: 40/50 → 80.00
├─ Q2: 45/50 → 90.00
└─ Prelim: 85/100 → 85.00

Step 5: Calculate
├─ Activities Avg: (90+96+100)/3 = 95.33
├─ Quizzes Avg: (80+90)/2 = 85.00
├─ Exams Avg: 85.00
├─ Weighted Activities: 95.33 × 0.15 = 14.30
├─ Weighted Quizzes: 85.00 × 0.25 = 21.25
├─ Weighted Exams: 85.00 × 0.60 = 51.00
└─ TERM GRADE: 14.30 + 21.25 + 51.00 = 86.55
```

## 🚀 Quick Reference

```
┌─────────────────────────────────────────────────┐
│              QUICK REFERENCE                     │
├─────────────────────────────────────────────────┤
│                                                  │
│  Navigation:                                     │
│  • Students → Import CSV                        │
│  • Grading → Add Class → Configure             │
│  • Grade Sheet → Enter Grades                   │
│                                                  │
│  Formula Variables:                             │
│  • score = Student's raw score                  │
│  • total = Maximum possible score               │
│                                                  │
│  Common Formulas:                               │
│  • Percentage: score / total * 100             │
│  • Transmuted: score / total * 60 + 40         │
│  • Scaled: score / total * 50 + 50             │
│                                                  │
│  Validation Rules:                              │
│  • Component weights must total 100%            │
│  • At least one component required             │
│  • Formula optional (raw scores if empty)       │
│                                                  │
│  Auto-save:                                     │
│  • Grades save 500ms after input               │
│  • Green border = Success                       │
│  • Red border = Error                           │
│                                                  │
└─────────────────────────────────────────────────┘
```

---

**This visual guide provides a clear understanding of the system's workflow and functionality.**
