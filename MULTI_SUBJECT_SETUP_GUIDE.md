# Multi-Subject Setup Guide

## Setting Up Student Mapping for Multiple Subjects

### Current Status
- ✅ **Subject ID 2** (CS101 - Introduction to Computer Science) - Fully set up
  - 3 students enrolled
  - Connected to Google Classroom
  - Auto-match ready

### For Other Subjects

## Option 1: Using Commands (Recommended)

### Step 1: List Available Students
```bash
php artisan tinker --execute="App\Models\Student::all()->each(fn($s) => print($s->id . '. ' . $s->full_name . ' (' . $s->student_number . ')' . PHP_EOL));"
```

Output:
```
1. Mark Joshua Navida (2024001)
2. Leandro Raphael Tenorio (2024002)
3. Jasper Ace Lapitan (2024003)
```

### Step 2: Enroll Students in Subject
```bash
php artisan enroll:students {subject_id} --student_ids=1 --student_ids=2 --student_ids=3
```

Example - Enroll all 3 students in Calculus I (Subject ID 4):
```bash
php artisan enroll:students 4 --student_ids=1 --student_ids=2 --student_ids=3
```

### Step 3: Connect Subject to Google Classroom
1. Go to "My Subjects"
2. Click on the subject
3. Click "Connect to Google Classroom"
4. Select the Google Classroom class

### Step 4: Auto Match Students
1. Go to "Student Mapping"
2. Select the subject
3. Click "Auto Match Students"
4. Review matches
5. Click "Save Matches"

## Option 2: Using Database Directly

### Enroll Students Manually
```sql
-- Enroll students in Calculus I (subject_id = 4)
INSERT INTO student_subject (student_id, subject_id, status, created_at, updated_at)
VALUES 
(1, 4, 'enrolled', NOW(), NOW()),
(2, 4, 'enrolled', NOW(), NOW()),
(3, 4, 'enrolled', NOW(), NOW());
```

## Option 3: Import from Google Classroom

If the subject is already connected to Google Classroom and has students:

```bash
php artisan import:gcr-students {subject_id}
```

This will:
- Fetch students from Google Classroom
- Create student records if they don't exist
- Enroll them in the subject automatically

**Note:** Only use this if you want to auto-create students. Otherwise, manually add students first.

## Example Workflow: Setting Up Calculus I

### 1. Check if subject is connected to GCR
```bash
php artisan tinker --execute="$s = App\Models\Subject::find(4); echo $s->subject_name . ' | GCR: ' . ($s->gcr_class_id ?? 'Not connected') . PHP_EOL;"
```

### 2. Enroll existing students
```bash
php artisan enroll:students 4 --student_ids=1 --student_ids=2 --student_ids=3
```

### 3. Connect to Google Classroom (if not connected)
- Go to UI and connect the subject

### 4. Auto match students
- Go to Student Mapping page
- Click "Auto Match Students"
- Save matches

## Checking Enrollment Status

### See which students are enrolled in a subject:
```bash
php artisan tinker --execute="$subject = App\Models\Subject::find(4); echo 'Students in ' . $subject->subject_name . ':' . PHP_EOL; $subject->students->each(fn($s) => print('  - ' . $s->full_name . PHP_EOL));"
```

### See which subjects a student is enrolled in:
```bash
php artisan tinker --execute="$student = App\Models\Student::find(1); echo $student->full_name . ' enrolled in:' . PHP_EOL; $student->subjects->each(fn($s) => print('  - ' . $s->subject_name . PHP_EOL));"
```

## Important Notes

✅ **Same students, multiple subjects** - Students can be enrolled in multiple subjects
✅ **Separate mappings** - Each subject has its own student_mappings
✅ **Independent matching** - Auto-match works per subject
✅ **Reusable students** - Add students once, enroll in many subjects

## Quick Reference

### Enroll students in a subject:
```bash
php artisan enroll:students {subject_id} --student_ids=1 --student_ids=2
```

### Import students from GCR:
```bash
php artisan import:gcr-students {subject_id}
```

### List all subjects:
```bash
php artisan tinker --execute="App\Models\Subject::all()->each(fn($s) => print($s->id . '. ' . $s->subject_name . PHP_EOL));"
```

### List all students:
```bash
php artisan tinker --execute="App\Models\Student::all()->each(fn($s) => print($s->id . '. ' . $s->full_name . PHP_EOL));"
```
