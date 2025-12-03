# Current Semester Filter

## Overview
The application now filters subjects by the current semester across all major pages. Only subjects from the current academic period are displayed, keeping the interface focused and relevant.

## Implementation

### Changes Made

1. **ClassroomSyncController** (`app/Http/Controllers/ClassroomSyncController.php`)
   - Modified the `index()` method to filter subjects by current academic year and semester
   - Uses configuration values to determine the current academic period

2. **StudentMappingPageController** (`app/Http/Controllers/StudentMappingPageController.php`)
   - Modified the `index()` method to filter subjects by current academic year and semester
   - Ensures only current semester subjects appear in student mapping

3. **DashboardController** (`app/Http/Controllers/DashboardController.php`)
   - Updated `index()`, `getDashboardStats()`, and `getSubjectsNeedingAttention()` methods
   - All dashboard statistics and displays now reflect current semester only

4. **ActivityController** (`app/Http/Controllers/ActivityController.php`)
   - Modified the `index()` method to filter subjects and activities by current semester
   - Subject dropdown only shows current semester subjects

5. **GradingSystemController** (`app/Http/Controllers/GradingSystemController.php`)
   - Modified the `index()` method to filter subjects by current semester
   - Only current semester subjects appear in the grading system page

6. **SubjectService** (`app/Services/SubjectService.php`)
   - Updated `getCurrentSemesterSubjects()` and `getPastSemesterSubjects()` methods
   - Now uses config values as primary source, with school database as fallback

7. **Configuration** (`config/app.php`)
   - Added `current_academic_year` configuration key
   - Added `current_semester` configuration key
   - Both can be set via environment variables

8. **Environment Variables** (`.env` and `.env.example`)
   - Added `CURRENT_ACADEMIC_YEAR` (default: "2024-2025")
   - Added `CURRENT_SEMESTER` (default: "1")

## Pages Affected

The following pages now filter by current semester:

- **Dashboard** (`/`) - All statistics and subject lists
- **Google Classroom** (`/classroom`) - Connected and unconnected subjects
- **Student Mapping** (`/student-mapping`) - Available subjects for mapping
- **Subjects** (`/subjects`) - Current semester subjects (already implemented)
- **Activities** (`/activities`) - Only shows activities from current semester subjects
- **Grading System** (`/grading-system`) - Only shows subjects from current semester

## Updating the Current Semester

To update the current academic period (e.g., at the start of a new semester):

1. Edit the `.env` file
2. Update the values:
   ```
   CURRENT_ACADEMIC_YEAR=2024-2025
   CURRENT_SEMESTER=2
   ```
3. Clear the configuration cache (if in production):
   ```bash
   php artisan config:cache
   ```

## Semester Values

- `1` = First Semester
- `2` = Second Semester
- `3` = Summer/Third Semester (if applicable)

## Academic Year Format

Use the format: `YYYY-YYYY` (e.g., `2024-2025`)

## Benefits

- Faculty only see relevant subjects for the current semester
- Reduces clutter and confusion
- Prevents accidental connections to old subjects
- Maintains historical data while focusing on current work

## Past Semesters

On the "My Subjects" page, faculty can view past semester subjects by clicking "Show Past Subjects". The system will display subjects grouped by academic year and semester, excluding the current semester.

**Note:** The comparison logic uses `!=` (not equal) instead of `<` (less than) for academic years to properly handle the "YYYY-YYYY" format (e.g., "2024-2025").
