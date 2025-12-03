# Nursing Matrix Implementation Guide

## Overview
The Nursing Matrix has been updated with a specialized table structure and computation formulas that differ from the General Education and Zero-Based matrices.

## ✅ Completed Changes

### 1. Updated Table Structure
The nursing matrix (`resources/views/grade-matrix/nursing/matrix.blade.php`) now includes:

**Column Structure:**
- Student Information (sticky column)
- **Prelim Term:**
  - Activities
  - Quizzes
  - Exam
  - Total
- **Midterm Term:**
  - Activities
  - Quizzes
  - Exam
  - Total
- **Finals Term:**
  - Activities
  - Quizzes
  - Exam
  - Total
- **Comprehensive Exam** (new column)
- **Final Rating** (replaces Final Grade)

### 2. Nursing-Specific Formulas (Documented in Legend)

**Component Calculations:**
```
Activities = (Total Score / Overall Score × 60 + 40) × 0.15
Quizzes = (Total Score / Overall Score × 60 + 40) × 0.25
Exam = (Total Score / Overall Score × 60 + 40) × 0.60
```

**Term Grade:**
```
Term Grade = Activities + Quizzes + Exam
```

**Final Grade:**
```
Final Grade = (Prelim × 30%) + (Midterm × 30%) + (Finals × 40%)
```

**Final Rating (with Comprehensive Exam):**
```
Final Rating = (Final Grade × 80%) + (Comprehensive Exam × 20%)
```

### 3. Visual Enhancements
- Color-coded columns for each term (Blue for Prelim, Green for Midterm, Purple for Finals)
- Orange highlighting for Comprehensive Exam column
- Comprehensive legend explaining all formulas
- Separate display of Activities and Quizzes scores

## 🔧 Backend Implementation Needed

### 1. Database Schema Updates

#### Add Comprehensive Exam Storage
You need to store comprehensive exam scores. Options:

**Option A: Add to subjects table**
```php
// Migration
Schema::table('subjects', function (Blueprint $table) {
    $table->json('comprehensive_exam_scores')->nullable();
});
```

**Option B: Create separate table**
```php
// Migration
Schema::create('comprehensive_exams', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subject_id')->constrained()->onDelete('cascade');
    $table->foreignId('student_mapping_id')->constrained()->onDelete('cascade');
    $table->decimal('score', 5, 2);
    $table->timestamps();
});
```

#### Update term_grades table
Ensure `formula_config` JSON column can store:
```json
{
    "activities_score": 12.50,
    "quizzes_score": 20.75,
    "exam_score": 48.00
}
```

### 2. Activity Type Differentiation

#### Add activity_category to activities table
```php
// Migration
Schema::table('activities', function (Blueprint $table) {
    $table->enum('activity_category', ['activity', 'quiz'])->default('activity');
});
```

This allows you to:
- Separate activities from quizzes
- Apply different formulas to each category
- Fetch quizzes separately from Google Classroom

### 3. Google Classroom Quiz Integration

#### Update Google Classroom Service
Modify `app/Services/GoogleClassroomService.php` to:

```php
public function fetchQuizzes($classId)
{
    // Fetch coursework with type 'QUIZ' or specific naming pattern
    $courseWork = $this->service->courses_courseWork->listCoursesCourseWork($classId, [
        'courseWorkStates' => ['PUBLISHED'],
        'orderBy' => 'updateTime desc'
    ]);
    
    $quizzes = [];
    foreach ($courseWork->getCourseWork() as $work) {
        // Filter for quizzes (you can use naming convention or metadata)
        if (str_contains(strtolower($work->getTitle()), 'quiz')) {
            $quizzes[] = [
                'id' => $work->getId(),
                'title' => $work->getTitle(),
                'maxPoints' => $work->getMaxPoints(),
                'dueDate' => $work->getDueDate()
            ];
        }
    }
    
    return $quizzes;
}

public function fetchQuizSubmissions($classId, $quizId)
{
    // Similar to fetchSubmissions but for quizzes
    $submissions = $this->service->courses_courseWork_studentSubmissions
        ->listCoursesCourseWorkStudentSubmissions($classId, $quizId);
    
    return $submissions->getStudentSubmissions();
}
```

### 4. Controller Updates

#### Update GradeMatrixController
```php
public function nursingMatrix(Subject $subject): View
{
    // ... existing code ...
    
    // Get comprehensive exam scores
    $comprehensiveExams = ComprehensiveExam::where('subject_id', $subject->id)
        ->get()
        ->keyBy('student_mapping_id');
    
    // Or if using JSON storage:
    $comprehensiveExamScores = $subject->comprehensive_exam_scores ?? [];
    
    return view('grade-matrix.nursing.matrix', compact(
        'subject',
        'activitiesByTerm',
        'gradeMatrix',
        'termGrades',
        'matrixType',
        'comprehensiveExamScores' // or 'comprehensiveExams'
    ));
}
```

### 5. Grade Calculation Service

Create `app/Services/NursingGradeCalculator.php`:

```php
<?php

namespace App\Services;

class NursingGradeCalculator
{
    /**
     * Calculate activity score using nursing formula
     * Formula: (Total Score / Overall Score × 60 + 40) × 0.15
     */
    public function calculateActivityScore($totalScore, $overallScore): float
    {
        if ($overallScore == 0) return 0;
        
        $percentage = ($totalScore / $overallScore) * 60 + 40;
        return $percentage * 0.15;
    }
    
    /**
     * Calculate quiz score using nursing formula
     * Formula: (Total Score / Overall Score × 60 + 40) × 0.25
     */
    public function calculateQuizScore($totalScore, $overallScore): float
    {
        if ($overallScore == 0) return 0;
        
        $percentage = ($totalScore / $overallScore) * 60 + 40;
        return $percentage * 0.25;
    }
    
    /**
     * Calculate exam score using nursing formula
     * Formula: (Total Score / Overall Score × 60 + 40) × 0.60
     */
    public function calculateExamScore($totalScore, $overallScore): float
    {
        if ($overallScore == 0) return 0;
        
        $percentage = ($totalScore / $overallScore) * 60 + 40;
        return $percentage * 0.60;
    }
    
    /**
     * Calculate term grade
     * Formula: Activities + Quizzes + Exam
     */
    public function calculateTermGrade($activitiesScore, $quizzesScore, $examScore): float
    {
        return $activitiesScore + $quizzesScore + $examScore;
    }
    
    /**
     * Calculate final rating with comprehensive exam
     * Formula: (Final Grade × 80%) + (Comprehensive Exam × 20%)
     */
    public function calculateFinalRating($finalGrade, $comprehensiveExam): float
    {
        return ($finalGrade * 0.80) + ($comprehensiveExam * 0.20);
    }
}
```

### 6. Update Term Grades Calculation

When saving term grades for nursing subjects, use the calculator:

```php
use App\Services\NursingGradeCalculator;

public function saveNursingTermGrades(Request $request, Subject $subject, $term)
{
    $calculator = new NursingGradeCalculator();
    
    foreach ($request->students as $studentId => $data) {
        // Calculate activities score
        $activitiesScore = $calculator->calculateActivityScore(
            $data['activities_total'],
            $data['activities_max']
        );
        
        // Calculate quizzes score
        $quizzesScore = $calculator->calculateQuizScore(
            $data['quizzes_total'],
            $data['quizzes_max']
        );
        
        // Calculate exam score
        $examScore = $calculator->calculateExamScore(
            $data['exam_score'],
            $data['exam_max']
        );
        
        // Calculate term grade
        $termGrade = $calculator->calculateTermGrade(
            $activitiesScore,
            $quizzesScore,
            $examScore
        );
        
        // Save to database
        TermGrade::updateOrCreate(
            [
                'subject_id' => $subject->id,
                'student_mapping_id' => $studentId,
                'term' => $term
            ],
            [
                'term_grade' => $termGrade,
                'formula_config' => [
                    'activities_score' => $activitiesScore,
                    'quizzes_score' => $quizzesScore,
                    'exam_score' => $examScore
                ]
            ]
        );
    }
}
```

## 📋 Next Steps

### Immediate Actions:
1. **Run migrations** to add comprehensive exam storage
2. **Add activity_category** column to differentiate activities from quizzes
3. **Create NursingGradeCalculator** service
4. **Update controllers** to use the new calculator for nursing subjects
5. **Implement comprehensive exam input** interface

### Google Classroom Integration:
1. **Update GoogleClassroomService** to fetch quizzes separately
2. **Add quiz import functionality** in the activities management
3. **Map quiz submissions** to the quizzes category

### UI Enhancements:
1. **Create comprehensive exam input page** for nursing subjects
2. **Update term grades page** to show separate inputs for activities and quizzes
3. **Add quiz management** interface

## 🎯 Testing Checklist

- [ ] Verify nursing matrix displays all columns correctly
- [ ] Test activity score calculation: (Score/Total × 60 + 40) × 0.15
- [ ] Test quiz score calculation: (Score/Total × 60 + 40) × 0.25
- [ ] Test exam score calculation: (Score/Total × 60 + 40) × 0.60
- [ ] Verify term grade = Activities + Quizzes + Exam
- [ ] Test final rating = (Final Grade × 80%) + (Comp Exam × 20%)
- [ ] Verify comprehensive exam column displays correctly
- [ ] Test with missing data (null values)
- [ ] Verify passing/failing status (≥ 75 = Pass)

## 📝 Notes

- The nursing matrix is now visually distinct from general education and zero-based matrices
- All formulas are clearly documented in the legend
- The comprehensive exam is a key differentiator for nursing programs
- Activities and quizzes are treated as separate components with different weights
- The final rating formula incorporates the comprehensive exam at 20% weight
