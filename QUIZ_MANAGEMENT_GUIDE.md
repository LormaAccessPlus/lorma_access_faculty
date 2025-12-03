# Quiz Management Guide - Nursing Matrix

## Overview
The nursing matrix requires separate tracking of **Activities** and **Quizzes** because they have different weights in the grade calculation:
- **Activities**: 15% weight
- **Quizzes**: 25% weight

## 🎯 Two Ways to Add Quizzes

### Method 1: Manual Creation

#### Step 1: Navigate to Activities Page
1. Go to your subject
2. Click "Manage Activities"

#### Step 2: Create New Activity/Quiz
1. Click "Add Activity" button
2. Fill in the form:
   - **Activity Name**: Enter the quiz name (e.g., "Quiz 1", "Midterm Quiz")
   - **Type**: Select Lecture or Lab
   - **Term**: Select Prelim, Midterm, or Finals
   - **Max Score**: Enter maximum points (e.g., 100)
   - **Weight**: Optional percentage weight
   - **Category**: Select "Quiz" (25% weight) or "Activity" (15% weight)

#### Step 3: Save
- Click "Add Activity"
- The quiz will be categorized appropriately

### Method 2: Import from Google Classroom (Automatic Categorization)

#### Step 1: Connect Google Classroom
1. Ensure your subject is connected to Google Classroom
2. Navigate to the subject's activity management page

#### Step 2: Import Coursework
1. Click "Import from Google Classroom"
2. Select the coursework you want to import
3. **Automatic Quiz Detection**: The system will automatically categorize coursework as "Quiz" if the title contains:
   - "quiz" (case-insensitive)
   - "test"
   - "exam"
   - Patterns like "Q1", "Q2", "Q3", etc.

#### Step 3: Review Imported Items
- Quizzes will be marked with the "quiz" category
- Regular activities will be marked with the "activity" category
- Grades will be imported automatically if available

## 🔍 How Automatic Quiz Detection Works

### Detection Logic
```php
// The system checks the title for these patterns:
- Contains "quiz" → Categorized as Quiz
- Contains "test" → Categorized as Quiz
- Contains "exam" → Categorized as Quiz
- Matches "Q1", "Q2", etc. → Categorized as Quiz
- Everything else → Categorized as Activity
```

### Examples

| Google Classroom Title | Detected Category | Reason |
|------------------------|-------------------|--------|
| "Quiz 1 - Chapter 1" | Quiz | Contains "quiz" |
| "Midterm Quiz" | Quiz | Contains "quiz" |
| "Q1: Introduction" | Quiz | Matches Q1 pattern |
| "Unit Test 1" | Quiz | Contains "test" |
| "Final Exam" | Quiz | Contains "exam" |
| "Assignment 1" | Activity | No quiz keywords |
| "Lab Exercise 1" | Activity | No quiz keywords |
| "Homework 1" | Activity | No quiz keywords |

## 📊 How Quizzes Appear in Nursing Matrix

### Term-Based Grading Page
```
┌──────────┬─────────────────────┬─────────────────────┬──────────┬──────────┬──────┬──────┐
│ Student  │ Activities (15%)    │ Quizzes (25%)       │ Act Score│ Quiz Score│ Exam │ Total│
├──────────┼─────────────────────┼─────────────────────┼──────────┼──────────┼──────┼──────┤
│ John Doe │ Act1 | Act2 | Act3  │ Q1 | Q2 | Q3        │  13.20   │  23.50   │54.60 │91.30 │
└──────────┴─────────────────────┴─────────────────────┴──────────┴──────────┴──────┴──────┘
```

### Full Matrix Page
```
┌──────────┬────────────────────────────────────┐
│ Student  │ Prelim                             │
│          ├─────┬────────┬──────┬──────────────┤
│          │ Act │ Quiz   │ Exam │    Total     │
├──────────┼─────┼────────┼──────┼──────────────┤
│ John Doe │13.20│  23.50 │54.60 │    91.30     │
└──────────┴─────┴────────┴──────┴──────────────┘
```

## 🔧 Manually Changing Category

### If an Activity Was Miscategorized

#### Option 1: Via Database (Quick Fix)
```sql
-- Change specific activity to quiz
UPDATE activities 
SET activity_category = 'quiz' 
WHERE id = 123;

-- Change all activities with "quiz" in name
UPDATE activities 
SET activity_category = 'quiz' 
WHERE name LIKE '%quiz%';

-- Change back to activity
UPDATE activities 
SET activity_category = 'activity' 
WHERE id = 123;
```

#### Option 2: Via Code (Bulk Update)
```php
use App\Models\Activity;

// Mark specific activities as quizzes
Activity::whereIn('id', [1, 2, 3])
    ->update(['activity_category' => 'quiz']);

// Auto-categorize all activities by name
Activity::where('name', 'LIKE', '%quiz%')
    ->orWhere('name', 'LIKE', '%test%')
    ->orWhere('name', 'LIKE', '%exam%')
    ->update(['activity_category' => 'quiz']);
```

## 📝 Formulas Reference

### Activities Calculation
```
Activities Score = (Total Score / Overall Score × 60 + 40) × 0.15
```

**Example:**
- Student scored 80/100 on activities
- Calculation: (80/100 × 60 + 40) × 0.15 = (48 + 40) × 0.15 = 13.20

### Quizzes Calculation
```
Quizzes Score = (Total Score / Overall Score × 60 + 40) × 0.25
```

**Example:**
- Student scored 90/100 on quizzes
- Calculation: (90/100 × 60 + 40) × 0.25 = (54 + 40) × 0.25 = 23.50

### Term Grade
```
Term Grade = Activities Score + Quizzes Score + Exam Score
```

**Example:**
- Activities: 13.20
- Quizzes: 23.50
- Exam: 54.60
- Term Grade: 13.20 + 23.50 + 54.60 = 91.30

## ✅ Best Practices

### 1. Naming Conventions
Use clear naming for automatic detection:
- ✅ "Quiz 1", "Quiz 2", "Quiz 3"
- ✅ "Q1: Introduction", "Q2: Chapter 2"
- ✅ "Midterm Quiz", "Final Quiz"
- ❌ "Assessment 1" (won't be detected as quiz)

### 2. Google Classroom Setup
- Name quizzes consistently in Google Classroom
- Use "Quiz" prefix or suffix
- The system will automatically categorize on import

### 3. Manual Review
After importing from Google Classroom:
1. Review the categorization
2. Manually adjust if needed
3. Verify in the nursing matrix view

### 4. For Nursing Subjects
- Always use the nursing matrix type
- Ensure activities are properly categorized
- Quizzes should have higher weight (25% vs 15%)

## 🐛 Troubleshooting

### Issue: Quiz Not Showing in Quiz Column
**Solution:**
1. Check the `activity_category` field in database
2. Update if needed:
```sql
UPDATE activities SET activity_category = 'quiz' WHERE id = [activity_id];
```

### Issue: Google Classroom Import Not Categorizing
**Solution:**
1. Check the coursework title in Google Classroom
2. Ensure it contains "quiz", "test", or "exam"
3. Or rename in Google Classroom and re-import

### Issue: Wrong Weight Applied
**Solution:**
- Quizzes automatically get 25% weight in nursing formula
- Activities automatically get 15% weight in nursing formula
- The `weight` field is separate and optional

## 📊 Verification

### Check Category in Database
```sql
SELECT id, name, activity_category, type, term 
FROM activities 
WHERE subject_id = [your_subject_id]
ORDER BY term, activity_category;
```

### Expected Output
```
| id | name          | activity_category | type    | term    |
|----|---------------|-------------------|---------|---------|
| 1  | Assignment 1  | activity          | lecture | prelim  |
| 2  | Quiz 1        | quiz              | lecture | prelim  |
| 3  | Lab 1         | activity          | lab     | prelim  |
| 4  | Q2            | quiz              | lecture | midterm |
```

## 🎯 Summary

1. **Manual Creation**: Select "Quiz" category when creating
2. **Google Classroom**: Automatic detection based on title
3. **Verification**: Check in nursing matrix view
4. **Adjustment**: Update category if needed

The system is designed to make quiz management as automatic as possible while giving you full control when needed!
