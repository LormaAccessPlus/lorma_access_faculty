# Grade Sync Commands Reference

## Overview
This guide shows all the commands you need to manage and sync grades between your web app and school database.

---

## 1. Verify Grade Sync Status

**Command:**
```bash
php artisan grades:verify {subject_id}
```

**Example:**
```bash
php artisan grades:verify 1
```

**What it does:**
- Shows grades from web app database (lorma_access_faculty)
- Shows grades from school database (access_db)
- Compares them side-by-side with student names and IDs
- Shows exact row IDs and CodeNumbers

**When to use:**
- After syncing grades to confirm they're in the school database
- To troubleshoot sync issues
- To see which students have grades synced

---

## 2. Sync Grades to School Database

**Command:**
```bash
php artisan grades:sync {subject_id} {academic_year} {semester}
```

**Example:**
```bash
php artisan grades:sync 1 "2025-2026" "1st"
```

**Parameters:**
- `{subject_id}` - The ID of the subject (e.g., 1, 2, 3)
- `{academic_year}` - Format: "YYYY-YYYY" (e.g., "2025-2026", "2024-2025")
- `{semester}` - Options: "1st", "2nd", "1", "2"

**What it does:**
- Takes final ratings from web app database
- Syncs them to school database (access_db.termgrades)
- Creates or updates records with Prelim, Midterm, Finals grades

**Requirements before syncing:**
- All students must be mapped (have school_student_id)
- Final ratings must be calculated for the term
- Subject must have school_schedule_code

**When to use:**
- After computing all final ratings for a term
- When you want to push grades to the official school system
- At the end of each grading period

---

## 3. Check Database Tables

### Check Web App Database Tables
```bash
php artisan tinker --execute="echo 'Grade Records: ' . DB::table('grade_records')->count() . PHP_EOL; echo 'Term Grades: ' . DB::table('term_grades')->count() . PHP_EOL; echo 'Final Ratings: ' . DB::table('final_ratings')->count();"
```

**What it shows:**
- Number of grade records (individual activity scores)
- Number of term grades (computed class standing + exam)
- Number of final ratings (final computed grades)

### Check School Database
```bash
php artisan tinker --execute="echo 'School DB termgrades: ' . DB::connection('school_db')->table('termgrades')->count();"
```

**What it shows:**
- Total number of grade records in school database

---

## 4. View Specific Student Grades

### From Web App Database
```bash
php artisan tinker --execute="print_r(DB::table('final_ratings')->join('student_mappings', 'final_ratings.student_mapping_id', '=', 'student_mappings.id')->where('final_ratings.subject_id', 1)->get(['student_mappings.student_name', 'final_ratings.prelim_grade', 'final_ratings.midterm_grade', 'final_ratings.finals_grade'])->toArray());"
```

Replace `1` with your subject_id.

### From School Database
```bash
php artisan tinker --execute="print_r(DB::connection('school_db')->table('termgrades')->where('CodeNumber', 'CS101126')->get(['StudID', 'PrelimGrade', 'MidtermGrade', 'FinalsGrade'])->toArray());"
```

Replace `CS101126` with your CodeNumber.

---

## 5. Find Subject Information

### List All Subjects
```bash
php artisan find:subject
```

### Search for Specific Subject
```bash
php artisan find:subject {search}
```

**Examples:**
```bash
php artisan find:subject CS101
php artisan find:subject "Programming"
php artisan find:subject 1
```

**What it shows:**
- Subject ID, code, name
- Section, type, academic year, semester
- School schedule code (needed for sync)

---

## 6. Find Student Information

### Search for Student
```bash
php artisan find:student {search}
```

**Examples:**
```bash
php artisan find:student Dadole
php artisan find:student 2511312
php artisan find:student "Ian Ceasar"
```

**What it shows:**
- Student mappings in web app database
- Student details in school database
- School student IDs and Google Classroom IDs
- Email addresses

## 7. Find Student Grades

### View All Grades for a Student
```bash
php artisan find:grades {student_id}
```

**Example:**
```bash
php artisan find:grades 2511312
```

**What it shows:**
- Student information (name, email, course)
- All grades from web app database (final ratings)
- All grades from school database (termgrades)
- Row IDs, CodeNumbers, and last update times

---

## Common Workflows

### Workflow 1: Complete Grade Entry and Sync
```bash
# 1. Find your subject
php artisan find:subject CS101

# 2. Enter grades in web app (via browser interface)
# Visit: http://localhost:8000/grades/subjects/{subject_id}/term/prelim

# 3. Check a student's grades
php artisan find:grades 2511312

# 4. Sync to school database
php artisan grades:sync 1 "2025-2026" "1st"

# 5. Verify sync was successful
php artisan grades:verify 1
```

### Workflow 2: Import from Google Classroom and Sync
```bash
# 1. Import grades from Google Classroom (via browser)
# Visit: http://localhost:8000/grades/subjects/{subject_id}/term/prelim
# Click "Import from Google Classroom"

# 2. Check imported grades
php artisan tinker --execute="echo 'Grade Records: ' . DB::table('grade_records')->where('activity_id', 1)->count();"

# 3. Compute final ratings (via browser interface)

# 4. Sync to school database
php artisan grades:sync 1 "2025-2026" "1st"

# 5. Verify sync
php artisan grades:verify 1
```

---

## Troubleshooting Commands

### Check if students are mapped
```bash
php artisan tinker --execute="echo 'Unmapped students: ' . DB::table('student_mappings')->where('subject_id', 1)->whereNull('school_student_id')->count();"
```

### Check database connections
```bash
php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Web App DB: Connected' . PHP_EOL; } catch(Exception \$e) { echo 'Web App DB: Failed' . PHP_EOL; } try { DB::connection('school_db')->getPdo(); echo 'School DB: Connected' . PHP_EOL; } catch(Exception \$e) { echo 'School DB: Failed' . PHP_EOL; }"
```

### Find specific grade record in school DB
```bash
php artisan tinker --execute="print_r(DB::connection('school_db')->table('termgrades')->where('StudID', '2503130')->where('SchoolYear', '2025-2026')->get()->toArray());"
```

Replace `2503130` with the student ID you're looking for.

---

## Quick Reference Table

| Task | Command |
|------|---------|
| Sync grades | `php artisan grades:sync {subject_id} {academic_year} {semester}` |
| Verify sync | `php artisan grades:verify {subject_id}` |
| Find subject | `php artisan find:subject {search}` |
| Find student | `php artisan find:student {search}` |
| Find student grades | `php artisan find:grades {student_id}` |
| List all subjects | `php artisan find:subject` |
| Check grade counts | `php artisan tinker --execute="echo DB::table('final_ratings')->count();"` |
| Test DB connections | See troubleshooting section above |

---

## Important Notes

1. **Subject ID**: Always check your subject IDs first before running commands
2. **Academic Year Format**: Must be "YYYY-YYYY" (e.g., "2025-2026")
3. **Semester Format**: Use "1st", "2nd", or "1", "2"
4. **Student Mapping**: All students must be mapped before syncing
5. **Final Ratings**: Must be computed before syncing to school database

---

## Web Interface Routes

Instead of commands, you can also use the web interface:

- **Grade Entry**: `/grades/subjects/{subject_id}/term/{term}`
- **Grade Sync**: `/subjects/{subject_id}/grades/sync`
- **Subject List**: `/subjects`

---

## Support

If you encounter issues:
1. Check database connections
2. Verify student mappings are complete
3. Ensure final ratings are computed
4. Check the Laravel logs: `storage/logs/laravel.log`
