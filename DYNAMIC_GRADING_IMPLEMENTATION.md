# Dynamic Grading System - Implementation Complete

## Overview
A complete overhaul of the grading system that provides:
1. **CSV-based student import** with automatic Google Classroom matching
2. **Fully customizable grading components** (activities, quizzes, exams, attendance, etc.)
3. **Dynamic formula configuration** per component
4. **Flexible term grade calculation**

## Features Implemented

### 1. Students Page (CSV Import + Auto-Matching)
**Location:** `/students`

**Features:**
- Upload CSV files containing student lists
- Automatic matching with Google Classroom students based on:
  - Email (100% confidence if exact match)
  - Name similarity (using fuzzy matching algorithm)
- Display matched students with confidence scores
- View all students per subject
- Data saved to database for reuse

**CSV Format:**
- Required column: `name` (or `student_name`, `full_name`)
- Optional column: `email` (or `student_email`, `email_address`)
- Additional columns are stored but not used for matching

**Database:**
- Table: `student_mappings`
- New columns: `csv_data` (JSON), `auto_matched` (boolean)

### 2. Grading Page (Dynamic Configuration)
**Location:** `/grading`

**Features:**
- Add classes from your Google Classroom subjects
- Select term (Prelim, Midterm, Finals)
- Configure grading components:
  - Add/remove components
  - Set component names (Activities, Quizzes, Exams, etc.)
  - Assign percentage weights (must total 100%)
  - Define custom formulas per component
- Define term grade formula
- Real-time weight validation
- Save configurations to database

**Component Formula Variables:**
- `score` - Student's raw score
- `total` - Maximum possible score
- Example: `score / total * 60 + 40` (converts to 60-100 scale)

### 3. Grade Sheet (Dynamic Table)
**Location:** `/grading/{id}/grade-sheet`

**Features:**
- Dynamic table based on configured components
- Add items to components (Quiz 1, Activity 1, etc.)
- Enter scores with auto-save
- Automatic formula application
- Real-time grade calculation
- Component totals with percentage weights
- Term grade computation

**Calculation Flow:**
1. Enter raw score
2. Apply component formula (if defined)
3. Calculate component average
4. Apply component weight percentage
5. Sum all weighted components = Term Grade

## Database Schema

### grading_classes
Stores added classes with their configurations
```sql
- id
- subject_id (FK to subjects)
- faculty_id (FK to faculties)
- gcr_class_id
- class_name
- term (prelim/midterm/finals)
- term_formula (JSON)
- created_at, updated_at
```

### grading_components
Stores customizable components
```sql
- id
- grading_class_id (FK)
- component_name
- component_type (activity/quiz/exam/attendance/custom)
- weight_percentage (decimal)
- formula (string)
- order (int)
- created_at, updated_at
```

### component_items
Stores individual items within components
```sql
- id
- component_id (FK)
- item_name
- max_score (decimal)
- date
- created_at, updated_at
```

### student_grades
Stores actual grade data
```sql
- id
- grading_class_id (FK)
- student_mapping_id (FK)
- component_item_id (FK)
- score (decimal)
- computed_score (decimal)
- created_at, updated_at
```

## Usage Workflow

### Step 1: Import Students
1. Go to **Students** page
2. Select a subject
3. Click **Import CSV**
4. Upload CSV file
5. System automatically matches with GCR students
6. View matched students with confidence scores

### Step 2: Add Class for Grading
1. Go to **Grading** page
2. Click **Add Class**
3. Select subject from dropdown
4. Select term (Prelim/Midterm/Finals)
5. Click **Add Class**

### Step 3: Configure Grading Components
1. System redirects to configuration page
2. Add/remove components as needed
3. Set component names and types
4. Assign percentage weights (must total 100%)
5. Define formulas (optional)
6. Define term grade formula (optional)
7. Click **Save Configuration**

### Step 4: Enter Grades
1. System redirects to grade sheet
2. Add items to components (Quiz 1, Activity 1, etc.)
3. Enter scores in the table
4. Scores auto-save on input
5. System automatically:
   - Applies formulas
   - Calculates component averages
   - Applies percentage weights
   - Computes term grades

## Example Configuration

### Example 1: Traditional Grading
**Components:**
- Activities (15%) - Formula: `score / total * 100`
- Quizzes (25%) - Formula: `score / total * 100`
- Exams (60%) - Formula: `score / total * 100`

**Term Grade:** Weighted average (automatic)

### Example 2: Scaled Grading
**Components:**
- Class Standing (40%) - Formula: `score / total * 60 + 40`
- Exam (60%) - Formula: `score / total * 60 + 40`

**Term Grade:** Weighted average (automatic)

### Example 3: Custom Grading
**Components:**
- Attendance (10%) - Formula: `score / total * 100`
- Participation (15%) - Formula: `score / total * 100`
- Projects (25%) - Formula: `score / total * 100`
- Midterm Exam (25%) - Formula: `score / total * 100`
- Final Exam (25%) - Formula: `score / total * 100`

**Term Grade:** Custom formula (if needed)

## Files Created/Modified

### New Files
**Migrations:**
- `2025_12_05_201221_create_grading_classes_table.php`
- `2025_12_05_201255_create_grading_components_table.php`
- `2025_12_05_201303_create_component_items_table.php`
- `2025_12_05_201312_create_student_grades_table.php`
- `2025_12_05_201319_add_csv_data_to_student_mappings_table.php`

**Models:**
- `app/Models/GradingClass.php`
- `app/Models/GradingComponent.php`
- `app/Models/ComponentItem.php`
- `app/Models/StudentGrade.php`

**Controllers:**
- `app/Http/Controllers/StudentController.php`
- `app/Http/Controllers/DynamicGradingController.php`

**Views:**
- `resources/views/students/index.blade.php`
- `resources/views/grading/index.blade.php`
- `resources/views/grading/configure.blade.php`
- `resources/views/grading/grade-sheet.blade.php`

### Modified Files
- `routes/web.php` - Added new routes
- `resources/views/layouts/admin.blade.php` - Updated navigation

## Routes

### Students
- `GET /students` - Students index page
- `POST /students/upload-csv` - Upload and process CSV

### Grading
- `GET /grading` - Grading index page
- `POST /grading/add-class` - Add new grading class
- `GET /grading/{id}/configure` - Configure grading components
- `POST /grading/{id}/save-configuration` - Save configuration
- `GET /grading/{id}/grade-sheet` - View/edit grade sheet
- `POST /grading/component/{componentId}/add-item` - Add item to component
- `POST /grading/save-grade` - Save individual grade (AJAX)

## Key Features

### Auto-Matching Algorithm
- **Email matching:** 100% confidence for exact email matches
- **Name matching:** Fuzzy matching with similarity scoring
  - Removes common suffixes (Jr, Sr, II, III, IV)
  - Splits names into parts
  - Compares each part with similarity threshold
  - Calculates overall confidence score
- **Threshold:** Only auto-matches with 80%+ confidence

### Formula Evaluation
- Safe evaluation using PHP's `eval()` with controlled input
- Variables: `score`, `total`
- Supports basic arithmetic operations
- Error handling for invalid formulas

### Real-time Validation
- Weight percentage validation (must equal 100%)
- Disables save button if weights don't total 100%
- Visual feedback with color indicators

### Auto-save Functionality
- Grades auto-save 500ms after input stops
- Visual feedback (green border on success)
- Automatic page reload to update calculations

## Security Considerations

1. **Faculty Authentication:** All routes protected by `auth.faculty` middleware
2. **Ownership Verification:** Controllers verify faculty owns the resources
3. **Input Validation:** All inputs validated before processing
4. **SQL Injection Prevention:** Using Eloquent ORM with parameter binding
5. **Formula Evaluation:** Limited to arithmetic operations only

## Future Enhancements

1. **Export Functionality:** Export grades to Excel/CSV
2. **Grade Analytics:** Charts and statistics
3. **Bulk Operations:** Bulk grade entry, bulk formula application
4. **Templates:** Save and reuse component configurations
5. **Grade History:** Track grade changes over time
6. **Comments:** Add comments/notes to grades
7. **Notifications:** Notify students of grade updates
8. **Mobile Optimization:** Responsive design improvements

## Migration from Old System

The new system runs parallel to the existing grading system:
- Old grade matrix pages remain functional
- New dynamic grading system is separate
- Faculty can use either system
- Gradual migration recommended

## Testing Checklist

- [x] Database migrations run successfully
- [x] Models created with proper relationships
- [x] Controllers implement all required methods
- [x] Views render correctly
- [x] Routes configured properly
- [x] Navigation updated
- [ ] CSV upload and auto-matching tested
- [ ] Component configuration tested
- [ ] Grade entry and calculation tested
- [ ] Formula evaluation tested
- [ ] Weight validation tested
- [ ] Auto-save functionality tested

## Support

For issues or questions:
1. Check this documentation
2. Review the code comments
3. Test with sample data
4. Check browser console for errors
5. Review Laravel logs in `storage/logs/`
