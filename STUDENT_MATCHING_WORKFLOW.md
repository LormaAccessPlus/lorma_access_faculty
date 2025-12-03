# Student Matching Workflow

## How It Works

### Step 1: Add Students to Database (Manual)
**You must manually add students to the `students` table first.**

```sql
INSERT INTO students (student_number, first_name, middle_name, last_name, email, status)
VALUES 
('2024001', 'Mark Joshua', NULL, 'Navida', 'markjoshua.navida@lorma.edu', 'active'),
('2024002', 'Leandro Raphael', NULL, 'Tenorio', 'leandroraphael.tenorio@lorma.edu', 'active'),
('2024003', 'Jasper Ace', NULL, 'Lapitan', 'jasperace.lapitan@lorma.edu', 'active');
```

### Step 2: Enroll Students in Subject (Manual)
**Link students to their subjects via the `student_subject` pivot table.**

```sql
INSERT INTO student_subject (student_id, subject_id, status)
VALUES 
(1, 2, 'enrolled'),  -- Mark in CS101
(2, 2, 'enrolled'),  -- Leandro in CS101
(3, 2, 'enrolled');  -- Jasper in CS101
```

### Step 3: Auto Match Students (Automatic)
**Click "Auto Match Students" in the UI.**

The system will:
1. ✅ Fetch students from Google Classroom
2. ✅ Compare names with students in database
3. ✅ Match students with 95%+ name similarity
4. ✅ Create mappings in `student_mappings` table
5. ❌ **Will NOT create new student records**

## Important Rules

### ✅ Students WILL be matched if:
- They exist in the `students` table
- They are enrolled in the subject (via `student_subject`)
- Their name matches 95%+ with GCR name

### ❌ Students will NOT be matched if:
- They don't exist in the `students` table
- They are not enrolled in the subject
- Their name similarity is below 75%

### ⚠️ Students will be flagged for review if:
- Name similarity is between 75-95%
- Multiple possible matches found

## Example Workflow

### Scenario: New semester starts

1. **Admin adds students to database:**
   ```sql
   INSERT INTO students (student_number, first_name, last_name, email, status)
   VALUES ('2024001', 'Mark Joshua', 'Navida', 'mark@lorma.edu', 'active');
   ```

2. **Admin enrolls students in subjects:**
   ```sql
   INSERT INTO student_subject (student_id, subject_id, status)
   VALUES (1, 2, 'enrolled');
   ```

3. **Teacher connects Google Classroom**
   - Go to "My Subjects"
   - Click "Connect to Google Classroom"
   - Select the class

4. **Teacher clicks "Auto Match Students"**
   - System finds "Mark Joshua Navida" in GCR
   - System finds "Mark Joshua Navida" in database
   - System creates mapping automatically
   - Done! ✅

### What happens if a student is NOT in the database?

**GCR Student:** "John Doe"
**Database:** (not found)

**Result:**
- Shows as "Unmatched" in the auto-match results
- Admin must manually add "John Doe" to the database first
- Then run auto-match again

## Database Tables

### `students` (Must be populated manually)
```
id | student_number | first_name | last_name | email | status
1  | 2024001       | Mark Joshua | Navida   | mark@lorma.edu | active
```

### `student_subject` (Must be populated manually)
```
id | student_id | subject_id | status
1  | 1         | 2         | enrolled
```

### `student_mappings` (Created automatically by auto-match)
```
id | subject_id | student_id | gcr_student_id | student_name | confidence
1  | 2         | 1         | 109026...      | Mark Joshua Navida | 1.00
```

## Benefits of This Approach

✅ **Full control** - You decide who gets added to the database
✅ **Data integrity** - No duplicate or incorrect records
✅ **Security** - Only authorized students are in the system
✅ **Flexibility** - You can add students before or after they join GCR
✅ **Audit trail** - You know exactly who added each student

## Summary

**The system will ONLY match existing students, NOT create new ones.**

This gives you full control over your student database while still providing automatic matching convenience.
