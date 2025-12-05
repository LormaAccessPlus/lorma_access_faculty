# Dynamic Grading System Implementation Plan

## Overview
Complete overhaul of the grading system to support:
1. CSV-based student import with auto-matching
2. Fully customizable grading components
3. Dynamic formula configuration per component
4. Flexible term grade calculation

## Phase 1: Students Page (CSV Import + Auto-Match)

### Database Changes
- Keep `student_mappings` table (already suitable)
- Add `csv_data` JSON column to store original CSV data
- Add `auto_matched` boolean flag

### New Features
- CSV upload interface
- Auto-matching algorithm (name/email matching with GCR students)
- Display matched students with confidence scores
- Save to database for reuse across subjects

### Files to Create/Modify
- `app/Http/Controllers/StudentController.php` (new)
- `resources/views/students/index.blade.php` (new)
- `routes/web.php` (update)

## Phase 2: Dynamic Grading Components

### Database Changes
Create new tables:
1. `grading_classes` - Store added classes with their configurations
2. `grading_components` - Store customizable components (activities, quizzes, etc.)
3. `component_items` - Store individual items within components
4. `student_grades` - Store actual grade data

### Schema Design

```sql
grading_classes:
- id
- subject_id (FK to subjects)
- faculty_id (FK to faculties)
- gcr_class_id
- class_name
- term (prelim/midterm/finals)
- term_formula (JSON) - how to compute term grade
- created_at, updated_at

grading_components:
- id
- grading_class_id (FK)
- component_name (Activities, Quizzes, Exams, etc.)
- component_type (activity/quiz/exam/attendance/custom)
- weight_percentage (decimal)
- formula (string) - e.g., "score / total * 60 + 40"
- order (int)
- created_at, updated_at

component_items:
- id
- component_id (FK)
- item_name (e.g., "Quiz 1", "Activity 1")
- max_score (decimal)
- date
- created_at, updated_at

student_grades:
- id
- grading_class_id (FK)
- student_mapping_id (FK)
- component_item_id (FK)
- score (decimal)
- computed_score (decimal) - after formula application
- created_at, updated_at
```

### Features
- Add Class button → Select from GCR classes
- Load students from student_mappings
- Customize components (add/remove/reorder)
- Set percentage weights (must total 100%)
- Define formulas per component
- Define term grade formula
- Real-time validation
- Save/update configurations

### Files to Create
- Migrations for new tables
- Models: GradingClass, GradingComponent, ComponentItem, StudentGrade
- Controller: GradingSystemController
- Views: grading-system/index, grading-system/configure, grading-system/grade-sheet

## Phase 3: Grade Entry & Calculation

### Features
- Dynamic table based on configuration
- Enter scores per component item
- Auto-calculate using formulas
- Apply percentage weights
- Compute term grade
- Save to database
- Edit and recalculate

## Implementation Order
1. Create database migrations
2. Create models
3. Build Students page (CSV import)
4. Build Add Class functionality
5. Build component configuration UI
6. Build grade entry interface
7. Implement calculation engine
8. Add edit/update functionality

## Migration Path
- Keep existing data intact
- New system runs parallel initially
- Gradual migration of existing subjects
