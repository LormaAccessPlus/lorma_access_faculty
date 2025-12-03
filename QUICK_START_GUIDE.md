# Nursing Matrix - Quick Start Guide

## 🚀 Quick Implementation Steps

### Step 1: Run Migrations (5 minutes)
```bash
# Run the migrations to add database support
php artisan migrate

# This will add:
# - comprehensive_exam_scores column to subjects table
# - activity_category column to activities table  
# - matrix_type column to subjects table
# - comprehensive_exams table (optional normalized approach)
```

### Step 2: Mark Existing Quizzes (2 minutes)
```bash
# Update existing activities to mark quizzes
php artisan tinker
```
```php
// In tinker:
DB::table('activities')
    ->where('name', 'LIKE', '%quiz%')
    ->orWhere('name', 'LIKE', '%Quiz%')
    ->update(['activity_category' => 'quiz']);
```

### Step 3: Set Subject Matrix Type (1 minute)
```php
// Mark nursing subjects
$nursingSubjects = Subject::whereIn('subject_code', ['NURS101', 'NURS102'])
    ->update(['matrix_type' => 'nursing']);
```

### Step 4: View the Nursing Matrix (Immediate)
Navigate to: `/grade-matrix/nursing`

The updated table will show:
- ✅ Separate Activities and Quizzes columns
- ✅ Comprehensive Exam column
- ✅ Final Rating (instead of Final Grade)
- ✅ Nursing-specific formulas in legend

---

## 📊 Using the Nursing Grade Calculator

### Basic Usage
```php
use App\Services\NursingGradeCalculator;

$calculator = new NursingGradeCalculator();

// Calculate individual components
$activitiesScore = $calculator->calculateActivityScore(80, 100); // 13.20
$quizzesScore = $calculator->calculateQuizScore(90, 100);        // 23.50
$examScore = $calculator->calculateExamScore(85, 100);           // 54.60

// Calculate term grade
$termGrade = $calculator->calculateTermGrade(
    $activitiesScore, 
    $quizzesScore, 
    $examScore
); // 91.30

// Calculate final rating with comprehensive exam
$finalRating = $calculator->calculateFinalRating(91.30, 92.80); // 91.60
```

### Calculate All Components at Once
```php
$components = $calculator->calculateTermComponents([
    'activities_total' => 80,
    'activities_max' => 100,
    'quizzes_total' => 90,
    'quizzes_max' => 100,
    'exam_score' => 85,
    'exam_max' => 100,
]);

// Returns:
// [
//     'activities_score' => 13.20,
//     'quizzes_score' => 23.50,
//     'exam_score' => 54.60,
//     'term_grade' => 91.30,
//     'activities_total' => 80,
//     'activities_max' => 100,
//     'quizzes_total' => 90,
//     'quizzes_max' => 100,
//     'exam_total' => 85,
//     'exam_max' => 100,
// ]
```

---

## 💾 Storing Comprehensive Exam Scores

### Option A: JSON in Subjects Table (Simpler)
```php
$subject = Subject::find(1);
$subject->comprehensive_exam_scores = [
    1 => 85.50,  // student_mapping_id => score
    2 => 90.00,
    3 => 78.25,
];
$subject->save();
```

### Option B: Separate Table (Normalized)
```php
use App\Models\ComprehensiveExam;

ComprehensiveExam::create([
    'subject_id' => 1,
    'student_mapping_id' => 1,
    'score' => 85.50,
    'max_score' => 100.00,
    'exam_date' => '2024-12-15',
]);
```

---

## 🎯 Updating Term Grades for Nursing

```php
use App\Services\NursingGradeCalculator;
use App\Models\TermGrade;

$calculator = new NursingGradeCalculator();

// For each student
foreach ($students as $studentId => $data) {
    // Calculate all components
    $components = $calculator->calculateTermComponents([
        'activities_total' => $data['activities_total'],
        'activities_max' => $data['activities_max'],
        'quizzes_total' => $data['quizzes_total'],
        'quizzes_max' => $data['quizzes_max'],
        'exam_score' => $data['exam_score'],
        'exam_max' => $data['exam_max'],
    ]);
    
    // Save to database
    TermGrade::updateOrCreate(
        [
            'subject_id' => $subject->id,
            'student_mapping_id' => $studentId,
            'term' => 'prelim', // or 'midterm', 'finals'
        ],
        [
            'term_grade' => $components['term_grade'],
            'class_standing' => $components['activities_score'] + $components['quizzes_score'],
            'exam_score' => $components['exam_score'],
            'formula_config' => $components,
        ]
    );
}
```

---

## 🔍 Fetching Quizzes from Google Classroom

```php
use App\Services\GoogleClassroomService;

$gcService = new GoogleClassroomService();

// Fetch all quizzes for a class
$quizzes = $gcService->fetchQuizzes($classId);

// Import quizzes as activities
foreach ($quizzes as $quiz) {
    Activity::create([
        'subject_id' => $subject->id,
        'name' => $quiz['title'],
        'type' => 'lecture', // or 'lab'
        'activity_category' => 'quiz', // Important!
        'term' => 'prelim',
        'max_score' => $quiz['maxPoints'],
        'gcr_assignment_id' => $quiz['id'],
    ]);
}
```

---

## 📋 Quick Formula Reference

| Component | Formula | Weight |
|-----------|---------|--------|
| Activities | `(Score/Total × 60 + 40) × 0.15` | 15% |
| Quizzes | `(Score/Total × 60 + 40) × 0.25` | 25% |
| Exam | `(Score/Total × 60 + 40) × 0.60` | 60% |
| Term Grade | `Activities + Quizzes + Exam` | 100% |
| Final Grade | `(Prelim × 30%) + (Midterm × 30%) + (Finals × 40%)` | 100% |
| Final Rating | `(Final Grade × 80%) + (Comp Exam × 20%)` | 100% |

---

## 🧪 Testing with Sample Data

```php
// Create test data
$subject = Subject::create([
    'subject_code' => 'NURS101',
    'subject_name' => 'Fundamentals of Nursing',
    'matrix_type' => 'nursing',
    'faculty_id' => 1,
]);

// Add student mapping
$studentMapping = StudentMapping::create([
    'subject_id' => $subject->id,
    'student_name' => 'John Doe',
    'student_email' => 'john@example.com',
]);

// Calculate and save prelim grades
$calculator = new NursingGradeCalculator();
$components = $calculator->calculateTermComponents([
    'activities_total' => 80,
    'activities_max' => 100,
    'quizzes_total' => 90,
    'quizzes_max' => 100,
    'exam_score' => 85,
    'exam_max' => 100,
]);

TermGrade::create([
    'subject_id' => $subject->id,
    'student_mapping_id' => $studentMapping->id,
    'term' => 'prelim',
    'term_grade' => $components['term_grade'],
    'formula_config' => $components,
]);

// Add comprehensive exam
$subject->comprehensive_exam_scores = [
    $studentMapping->id => 88.00
];
$subject->save();

// View the matrix
// Navigate to: /grade-matrix/nursing/subjects/{subject}/matrix
```

---

## ⚠️ Common Issues & Solutions

### Issue: Comprehensive Exam not showing
**Solution:** Make sure you've added the score to the subject:
```php
$subject->comprehensive_exam_scores = [$studentMappingId => $score];
$subject->save();
```

### Issue: Activities and Quizzes showing same values
**Solution:** Mark quizzes with `activity_category = 'quiz'`:
```php
Activity::where('id', $quizId)->update(['activity_category' => 'quiz']);
```

### Issue: Formula not calculating correctly
**Solution:** Use NursingGradeCalculator service:
```php
$calculator = new NursingGradeCalculator();
$score = $calculator->calculateActivityScore($total, $max);
```

---

## 📞 Need Help?

Refer to these documents:
- `NURSING_MATRIX_IMPLEMENTATION.md` - Complete implementation guide
- `MATRIX_COMPARISON.md` - Visual comparison of all matrices
- `SOLUTION_SUMMARY.md` - Overview of all changes

---

**Ready to go!** 🎉

The nursing matrix is now fully functional with separate Activities and Quizzes columns, Comprehensive Exam support, and nursing-specific formulas.
