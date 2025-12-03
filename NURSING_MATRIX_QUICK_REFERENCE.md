# 🎯 Nursing Matrix - Quick Reference Card

## 📍 URLs

| Page | URL |
|------|-----|
| Nursing Matrix List | `/grade-matrix/nursing` |
| Full Matrix View | `/grade-matrix/nursing/subjects/{id}/matrix` |
| Term Grades | `/grade-matrix/nursing/subjects/{id}/term/{prelim\|midterm\|finals}` |
| Comprehensive Exam | `/nursing/subjects/{id}/comprehensive-exam` |

## 📐 Formulas

```
Activities = (Score/Total × 60 + 40) × 0.15
Quizzes = (Score/Total × 60 + 40) × 0.25
Exam = (Score/Total × 60 + 40) × 0.60
Term Grade = Activities + Quizzes + Exam
Final Grade = (Prelim × 30%) + (Midterm × 30%) + (Finals × 40%)
Final Rating = (Final Grade × 80%) + (Comprehensive Exam × 20%)
```

## 🎨 Table Structure

```
Student | Prelim (A|Q|E|T) | Midterm (A|Q|E|T) | Finals (A|Q|E|T) | Comp | Final
```
- A = Activities
- Q = Quizzes
- E = Exam
- T = Total
- Comp = Comprehensive Exam

## 💻 Code Examples

### Calculate Nursing Grades
```php
use App\Services\NursingGradeCalculator;

$calc = new NursingGradeCalculator();

// Calculate components
$components = $calc->calculateTermComponents([
    'activities_total' => 80,
    'activities_max' => 100,
    'quizzes_total' => 90,
    'quizzes_max' => 100,
    'exam_score' => 85,
    'exam_max' => 100,
]);

// Result: activities_score, quizzes_score, exam_score, term_grade
```

### Save Comprehensive Exam
```php
// JSON approach
$subject->comprehensive_exam_scores = [
    1 => 85.50,  // student_mapping_id => score
    2 => 90.00,
];
$subject->save();

// Table approach
ComprehensiveExam::create([
    'subject_id' => 1,
    'student_mapping_id' => 1,
    'score' => 85.50,
]);
```

### Fetch Quizzes from Google Classroom
```php
use App\Services\GoogleClassroomService;

$service = new GoogleClassroomService();
$service->authenticateWithFaculty($faculty);

$quizzes = $service->getQuizzes($classId);
$activities = $service->getActivities($classId);
```

## 📊 CSV Import Format

```csv
email,score
student@example.com,85.50
```

## 🎨 Color Scheme

- 🔵 **Blue** - Prelim
- 🟢 **Green** - Midterm
- 🟣 **Purple** - Finals
- 🟠 **Orange** - Comprehensive Exam
- 🔷 **Teal** - Final Rating

## ⚡ Quick Actions

### Add Comprehensive Exam Button
Already added to nursing matrix list page!

### Mark Activities as Quizzes
```php
Activity::where('name', 'LIKE', '%quiz%')
    ->update(['activity_category' => 'quiz']);
```

### Set Subject as Nursing
```php
Subject::where('id', 1)
    ->update(['matrix_type' => 'nursing']);
```

## 🔧 Files Modified

1. `resources/views/grade-matrix/nursing/matrix.blade.php` - Table structure
2. `resources/views/grade-matrix/nursing.blade.php` - Added comp exam button
3. `app/Http/Controllers/GradeMatrixController.php` - Pass comp exam data
4. `app/Services/GoogleClassroomService.php` - Quiz methods
5. `routes/web.php` - Nursing routes

## 📦 Files Created

1. `app/Http/Controllers/ComprehensiveExamController.php`
2. `app/Models/ComprehensiveExam.php`
3. `app/Services/NursingGradeCalculator.php`
4. `resources/views/nursing/comprehensive-exam.blade.php`
5. Migrations for database support

## ✅ Status

**ALL STEPS COMPLETE** ✅

Ready for production use!
