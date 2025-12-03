# Student Mapping Structure

## Database Tables (All in `lorma_access_faculty` database)

### 1. `students` Table
Stores all student information in your local database.

**Columns:**
- `id` - Primary key
- `student_number` - Unique student number
- `first_name` - Student's first name
- `middle_name` - Student's middle name (nullable)
- `last_name` - Student's last name
- `email` - Student's email (unique) - **PRIMARY MATCHING FIELD**
- `course` - Student's course (nullable)
- `year_level` - Student's year level (nullable)
- `section` - Student's section (nullable)
- `status` - Student status (active/inactive/graduated)
- `created_at`, `updated_at` - Timestamps

### 2. `subjects` Table
Stores all subject/course information.

**Key Columns:**
- `id` - Primary key
- `faculty_id` - Faculty teaching the subject
- `subject_code` - Subject code
- `subject_name` - Subject name
- `gcr_class_id` - Google Classroom class ID
- `academic_year` - Academic year
- `semester` - Semester

### 3. `student_subject` Table (Pivot)
Links students to subjects they are enrolled in.

**Columns:**
- `id` - Primary key
- `student_id` - Foreign key to `students.id`
- `subject_id` - Foreign key to `subjects.id`
- `status` - Enrollment status (enrolled/dropped/completed)
- `created_at`, `updated_at` - Timestamps

### 4. `student_mappings` Table
Maps Google Classroom students to local database students.

**Columns:**
- `id` - Primary key
- `subject_id` - Foreign key to `subjects.id`
- `student_id` - Foreign key to `students.id` (local student)
- `gcr_student_id` - Google Classroom student ID
- `student_name` - Student name from Google Classroom
- `student_email` - Student email from Google Classroom
- `mapping_confidence` - Confidence score (0.00 to 1.00)
- `created_at`, `updated_at` - Timestamps

## How Email Matching Works

### Automatic Matching Process

When a student joins a Google Classroom:

1. **System fetches GCR student data** including:
   - Google Classroom student ID
   - Student name
   - Student email

2. **System queries local `students` table** for students enrolled in that subject via `student_subject` pivot table

3. **Email matching validation** (Primary criterion):
   - If GCR email **exactly matches** database email → **100% confidence** → Automatic match
   - If emails are similar (80%+) → 50% confidence
   - Name matching adds up to 50% more confidence

4. **Matching thresholds**:
   - **≥ 80% confidence** → Automatic match
   - **50-80% confidence** → Flagged for manual review
   - **< 50% confidence** → Unmatched, requires manual intervention

5. **Record created in `student_mappings`**:
   - Links `student_id` (local DB) with `gcr_student_id` (Google Classroom)
   - Stores confidence score
   - Used for grade syncing between systems

## Current Data

**Students in database:** 2
- Mark Joshua Navida (markjoshua.navida@lorma.edu)
- Leandro Raphael Tenorio (leandroraphael.tenorio@lorma.edu)

**Student-Subject enrollments:** 2
**Mapped students:** 2 (100% mapped)

## Key Features

✅ **Email is the primary validation** - Exact email match = automatic mapping
✅ **All tables in one database** - No cross-database queries needed
✅ **Proper relationships** - Students ↔ Subjects ↔ Mappings
✅ **Existing data migrated** - All student_mappings data populated into students table
✅ **Grade syncing ready** - Mappings connect local students to GCR for grade sync
