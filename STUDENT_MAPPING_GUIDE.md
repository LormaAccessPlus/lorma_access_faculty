# Student Mapping Guide

## What is Student Mapping?

Student mapping connects students from **Google Classroom** with students in the **School Database**. This is required before you can sync grades to the school system.

---

## How to Map Students

### Step 1: Go to Subject Page
Visit your subject page:
```
http://localhost:8000/subjects/{subject_id}
```

Example:
```
http://localhost:8000/subjects/1
```

### Step 2: Click "Map Students" or "Student Mappings"
On the subject page, look for a button or link that says:
- "Map Students"
- "Student Mappings"
- "Manage Mappings"

Or directly visit:
```
http://localhost:8000/subjects/{subject_id}/mappings
```

Example:
```
http://localhost:8000/subjects/1/mappings
```

---

## Mapping Methods

### Method 1: Auto-Match via Command (Fastest & Easiest)
Use the command line to automatically map all students:

```bash
php artisan map:auto {subject_id}
```

**Example:**
```bash
php artisan map:auto 1
```

**What it does:**
- Automatically matches students by email address
- Falls back to name matching if email not found
- Updates all mappings in one command
- Shows results with mapped/not found counts

**Output:**
```
Auto-mapping students for subject 1...
Found 2 unmapped students

Processing: Ian Ceasar Dadole
  ✓ Mapped to: Ian Ceasar Dadole (2511312)
Processing: Mark Joshua Navida
  ✓ Mapped to: Mark Joshua Navida (2503130)

=== RESULTS ===
✓ Mapped: 2
✗ Not found: 0
```

### Method 2: Auto-Match via Web Interface
The system will automatically match students based on:
- Email addresses
- Names (first name + last name)
- Student IDs

**Steps:**
1. Visit: `http://localhost:8000/subjects/{subject_id}/mappings`
2. Click "Auto-Match" button
3. Review the suggested matches
4. Confirm matches that look correct
5. Manually map any remaining students

### Method 3: Manual Mapping via Command
For students that couldn't be auto-matched:

```bash
# 1. Find the student in school database
php artisan find:student "student name"

# 2. Get the mapping ID from student_mappings table
php artisan tinker --execute="print_r(DB::table('student_mappings')->where('subject_id', 1)->whereNull('school_student_id')->get(['id', 'student_name'])->toArray());"

# 3. Map manually
php artisan map:student {mapping_id} {school_student_id}
```

**Example:**
```bash
# Find student in school DB
php artisan find:student "Ian Dadole"
# Output shows: StudID: 2511312

# Map the student
php artisan map:student 19 2511312
```

### Method 4: Manual Mapping via Web Interface
For students that couldn't be auto-matched:

1. **Select Google Classroom Student** (left side)
   - Shows students from your Google Classroom course
   
2. **Select School Database Student** (right side)
   - Shows students from the school database
   
3. **Click "Map" or "Link"** to connect them

---

## What You Need Before Mapping

### 1. Subject Must Be Connected to Google Classroom
- The subject needs a `gcr_class_id` (Google Classroom ID)
- Students will be pulled from Google Classroom

### 2. Students Must Exist in School Database
- Students should be enrolled in the school database
- They should have a `StudID` in the `studentdata` table

---

## Checking Mapping Status

### Via Web Interface
Visit: `http://localhost:8000/subjects/{subject_id}/mappings`

You'll see:
- ✅ Mapped students (green)
- ⚠️ Unmapped students (yellow/red)
- Mapping confidence scores

### Via Command Line

#### Check all mappings for a subject
```bash
php artisan tinker --execute="print_r(DB::table('student_mappings')->where('subject_id', 1)->get(['id', 'student_name', 'school_student_id', 'gcr_student_id'])->toArray());"
```

#### Check unmapped students count
```bash
php artisan tinker --execute="echo 'Unmapped students: ' . DB::table('student_mappings')->where('subject_id', 1)->whereNull('school_student_id')->count();"
```

#### List unmapped students
```bash
php artisan tinker --execute="print_r(DB::table('student_mappings')->where('subject_id', 1)->whereNull('school_student_id')->get(['id', 'student_name', 'student_email'])->toArray());"
```

---

## Mapping Status Explained

| Status | Meaning |
|--------|---------|
| Has `school_student_id` | Student is successfully linked to school database ✅ |
| `school_student_id` is NULL | Student needs to be mapped ⚠️ |
| Not in table | Student hasn't been imported from Google Classroom yet |

---

## Common Issues

### Issue 1: "No students found in Google Classroom"
**Solution:** 
- Make sure the subject is connected to Google Classroom
- Check if `gcr_class_id` is set for the subject
- Verify you have students enrolled in the Google Classroom course

### Issue 2: "Student not found in school database"
**Solution:**
- The student might not be enrolled in the school system yet
- Check if the student exists in `access_db.studentdata` table
- Contact admin to add the student to the school database

### Issue 3: "Cannot sync grades - students not mapped"
**Solution:**
- Map all students first before syncing grades
- Check unmapped students: `php artisan find:subject {subject_id}`
- Complete the mapping process

---

## Quick Workflow (Command Line - Fastest!)

```bash
# 1. Find your subject ID
php artisan find:subject CS101

# 2. Auto-map all students
php artisan map:auto 1

# 3. Verify all students are mapped
php artisan tinker --execute="echo 'Unmapped: ' . DB::table('student_mappings')->where('subject_id', 1)->whereNull('school_student_id')->count();"

# 4. If any students weren't mapped, find them manually
php artisan find:student "student name"

# 5. Map manually if needed
php artisan map:student {mapping_id} {school_student_id}

# 6. Now you can enter grades and sync!
```

## Quick Workflow (Web Interface)

```bash
# 1. Find your subject
php artisan find:subject CS101

# 2. Visit the mapping page
# http://localhost:8000/subjects/1/mappings

# 3. Click "Auto-Match" button

# 4. Review and confirm matches

# 5. Manually map remaining students

# 6. Verify all students are mapped
php artisan tinker --execute="echo 'Unmapped: ' . DB::table('student_mappings')->where('subject_id', 1)->whereNull('school_student_id')->count();"

# 7. Now you can enter grades and sync!
```

---

## After Mapping

Once students are mapped, you can:
1. ✅ Enter grades in the web app
2. ✅ Import grades from Google Classroom
3. ✅ Sync grades to school database
4. ✅ View student grades with `php artisan find:grades {student_id}`

---

## URLs Quick Reference

| Action | URL |
|--------|-----|
| View subject | `http://localhost:8000/subjects/{subject_id}` |
| Map students | `http://localhost:8000/subjects/{subject_id}/mappings` |
| Enter grades | `http://localhost:8000/grades/subjects/{subject_id}/term/prelim` |
| Sync grades | `http://localhost:8000/subjects/{subject_id}/grades/sync` |

---

## Command Reference

| Command | Description |
|---------|-------------|
| `php artisan map:auto {subject_id}` | Auto-map all unmapped students |
| `php artisan map:student {mapping_id} {school_student_id}` | Manually map a specific student |
| `php artisan find:student "name"` | Find student in school database |
| `php artisan find:subject {search}` | Find subject by code/name/ID |

## Need Help?

Run these commands to check your setup:

```bash
# Check subject info
php artisan find:subject 1

# Check student mappings
php artisan find:student "student name"

# Check if students are mapped
php artisan tinker --execute="print_r(DB::table('student_mappings')->where('subject_id', 1)->get(['id', 'student_name', 'school_student_id'])->toArray());"

# Count unmapped students
php artisan tinker --execute="echo 'Unmapped: ' . DB::table('student_mappings')->where('subject_id', 1)->whereNull('school_student_id')->count();"
```

## Troubleshooting

### "No students found to map"
**Problem:** The `student_mappings` table is empty for your subject.

**Solution:** Students need to be imported from Google Classroom first. This usually happens automatically when you visit the subject page or mapping page.

### "Student not found in school database"
**Problem:** The student exists in Google Classroom but not in the school database.

**Solution:**
1. Verify the student is enrolled in the school system
2. Check if they exist: `php artisan find:student "student name"`
3. Contact admin to add the student to `access_db.studentdata` table

### "Auto-map found 0 students"
**Problem:** Email addresses don't match between Google Classroom and school database.

**Solution:**
1. Check the emails: 
   ```bash
   php artisan tinker --execute="print_r(DB::table('student_mappings')->where('subject_id', 1)->get(['student_name', 'student_email'])->toArray());"
   ```
2. Map manually using: `php artisan map:student {mapping_id} {school_student_id}`

### "Cannot sync grades - students not mapped"
**Problem:** Some students still have NULL `school_student_id`.

**Solution:**
1. Check unmapped students:
   ```bash
   php artisan tinker --execute="print_r(DB::table('student_mappings')->where('subject_id', 1)->whereNull('school_student_id')->get(['id', 'student_name'])->toArray());"
   ```
2. Map them: `php artisan map:auto 1`
3. Or manually: `php artisan map:student {mapping_id} {school_student_id}`



 php artisan find:student "Mark Lemuel Peria"