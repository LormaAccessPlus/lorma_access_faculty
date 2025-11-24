# Activity Weights Feature

## Overview
Teachers can now assign individual weights to activities within the grading formula configuration. This allows for more flexible grading where different activities contribute different percentages to the class standing.

## Changes Made

### 1. Removed Weight Input from Activity Forms
**Files Modified:**
- `resources/views/activities/create.blade.php`
- `resources/views/activities/edit.blade.php`

**Changes:**
- Removed the weight input field from both create and edit forms
- Added informational text directing teachers to configure weights in Grading Formula settings
- Simplified the activity creation process

### 2. Added Activity Weights to Grading Configuration
**Location:** Term Grades page → Configure Term button

**Features:**
- Visual list of all activities for the current term
- Separate sections for Lecture and Laboratory activities
- Input field for each activity to set its weight (0-100%)
- Real-time weight assignment
- Automatic normalization if weights don't sum to 100%

### 3. Backend Updates
**Controller:** `app/Http/Controllers/GradeController.php`

**Enhanced `saveGradingConfig()` method:**
- Accepts `activity_weights` array in request
- Validates each weight (0-100%)
- Updates activity weights in database
- Returns success message

## How It Works

### Setting Activity Weights

1. **Navigate to Term Grades:**
   - Go to Grading System → Select Subject → Term Grading

2. **Open Configuration:**
   - Click "Configure Term" button
   - Scroll to "Activity Weights for Class Standing" section

3. **Set Weights:**
   - Each activity shows its name and max score
   - Enter weight percentage (0-100%) for each activity
   - Weights determine how much each activity contributes to class standing

4. **Save Configuration:**
   - Click "Save Term Config"
   - Activity weights are saved along with term configuration
   - Page reloads to show updated weights

### Weight Calculation Examples

#### Example 1: Equal Weighting (Default)
```
Quiz 1: 0% (or not set)
Quiz 2: 0% (or not set)
Quiz 3: 0% (or not set)

Result: Each quiz contributes equally (33.33% each)
```

#### Example 2: Custom Weighting
```
Quiz 1: 20%
Quiz 2: 30%
Quiz 3: 50%

Student Scores:
- Quiz 1: 90/100 = 90%
- Quiz 2: 85/100 = 85%
- Quiz 3: 95/100 = 95%

Class Standing = (90 × 0.20) + (85 × 0.30) + (95 × 0.50)
               = 18 + 25.5 + 47.5
               = 91%
```

#### Example 3: Partial Weighting (Auto-normalized)
```
Major Exam: 60%
Quiz 1: 0%
Quiz 2: 0%

Result: Major Exam = 60%, Quizzes = 20% each (remaining 40% split equally)
```

### Integration with Term Grade Calculation

**Full Formula:**
```
1. Calculate weighted class standing from activities
2. Apply term formula:
   - Prelim: CS × 100%
   - Midterm/Finals: (CS × 50 + 50) × CS_Weight + Exam × Exam_Weight

3. Calculate final rating:
   Final = (Prelim × P%) + (Midterm × M%) + (Finals × F%)
```

**Example with Weights:**
```
Midterm Activities:
- Quiz 1 (weight 20%): 85/100 = 85%
- Quiz 2 (weight 30%): 90/100 = 90%
- Lab 1 (weight 50%): 95/100 = 95%

Weighted CS = (85 × 0.20) + (90 × 0.30) + (95 × 0.50)
            = 17 + 27 + 47.5
            = 91.5%

Transmuted CS = (91.5/100) × 50 + 50 = 95.75

Exam Score = 88

Term Grade (60/40 split) = (95.75 × 0.60) + (88 × 0.40)
                         = 57.45 + 35.2
                         = 92.65
```

## UI Features

### Activity Weights Section
- **Color-coded:** Blue background for easy identification
- **Organized:** Separate sections for Lecture and Lab activities
- **Informative:** Shows activity name and max score
- **User-friendly:** Simple input fields with percentage symbol
- **Helpful tips:** Explains auto-normalization behavior

### Visual Layout
```
┌─────────────────────────────────────────────────┐
│ Activity Weights for Class Standing             │
├─────────────────────────────────────────────────┤
│ LECTURE ACTIVITIES                              │
│ ┌──────────────────┬──────────────────┐        │
│ │ Quiz 1           │ [  20  ] %       │        │
│ │ Max: 100         │                  │        │
│ └──────────────────┴──────────────────┘        │
│ ┌──────────────────┬──────────────────┐        │
│ │ Quiz 2           │ [  30  ] %       │        │
│ │ Max: 100         │                  │        │
│ └──────────────────┴──────────────────┘        │
│                                                  │
│ LABORATORY ACTIVITIES                           │
│ ┌──────────────────┬──────────────────┐        │
│ │ Lab 1            │ [  50  ] %       │        │
│ │ Max: 100         │                  │        │
│ └──────────────────┴──────────────────┘        │
│                                                  │
│ 💡 Tip: Weights will be normalized if they     │
│    don't sum to 100%. Leave at 0 for equal.    │
└─────────────────────────────────────────────────┘
```

## Database Structure

### Activities Table
```sql
- id
- subject_id
- name
- type (lecture, lab)
- term (prelim, midterm, finals)
- max_score
- weight (decimal 5,2) ← Used for activity weighting
- gcr_assignment_id
- timestamps
```

**Weight Column:**
- Stores percentage weight (0-100)
- Nullable (defaults to equal weighting if null)
- Decimal(5,2) for precision

## Benefits

1. **Flexibility:** Different activities can have different importance
2. **Accuracy:** Major exams can count more than quizzes
3. **Transparency:** Students know exactly how grades are weighted
4. **Simplicity:** Easy to configure and understand
5. **Automatic:** System handles all calculations
6. **Normalized:** Works even if weights don't sum to 100%

## Use Cases

### Use Case 1: Traditional Grading
```
Quizzes (3): 30% total (10% each)
Major Exam: 70%
```

### Use Case 2: Continuous Assessment
```
Weekly Quizzes (10): 50% total (5% each)
Projects (2): 30% total (15% each)
Final Exam: 20%
```

### Use Case 3: Lab-Heavy Course
```
Lecture Quizzes: 30%
Lab Exercises: 50%
Final Project: 20%
```

### Use Case 4: Equal Weighting
```
All activities: 0% (or not set)
Result: Each activity contributes equally
```

## Technical Implementation

### Frontend (Alpine.js)
- Reactive form handling
- Real-time validation
- Collapsible sections
- Success/error notifications

### Backend (Laravel)
- Validation of weight values
- Batch update of activity weights
- Transaction safety
- Error handling

### Calculation Logic
1. Collect all activity scores for term
2. Apply individual weights to each score
3. Sum weighted scores for class standing
4. Apply term formula (percentage or transmuted)
5. Combine with exam score using term weights
6. Calculate final rating using term weights

## Future Enhancements

Potential additions:
1. Weight templates (e.g., "Quiz Heavy", "Exam Heavy")
2. Copy weights from previous term
3. Bulk weight assignment
4. Weight validation warnings
5. Visual weight distribution chart
6. Import/export weight configurations
7. Activity categories with group weights
