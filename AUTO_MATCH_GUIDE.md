# Auto Match Students - Email-Based Matching Guide

## How It Works

When you click **"Auto Match Students"** on the Student Mapping page, the system will:

### 1. Fetch Students from Google Classroom
- Gets all students enrolled in the GCR class
- Retrieves their name, email, and GCR student ID

### 2. Fetch Students from Database
- Gets all students enrolled in the subject from the `students` table
- Uses the `student_subject` pivot table to find enrolled students

### 3. Email-Based Matching (Primary Validation)
The system compares emails with the following logic:

**Exact Email Match:**
- If GCR email **exactly matches** database email → **100% confidence**
- Result: **Automatic match** (no manual review needed)

**Similar Email Match:**
- If emails are 80%+ similar → 50% confidence
- Name matching adds up to 50% more confidence
- Result: May require manual review if total < 80%

### 4. Matching Results Categories

**High Confidence Matches (≥80%)**
- Automatically saved when you click "Save Matches"
- Typically exact email matches
- Green indicator

**Conflicts (50-80%)**
- Require manual review
- Yellow indicator
- You'll need to verify these manually

**Unmatched (<50%)**
- No suitable match found
- Red indicator
- Requires manual mapping

## Example Scenario

### Current Database Students:
1. Mark Joshua Navida - `markjoshua.navida@lorma.edu`
2. Leandro Raphael Tenorio - `leandroraphael.tenorio@lorma.edu`

### Google Classroom Students Join:
1. Mark Joshua Navida - `markjoshua.navida@lorma.edu` → **100% match** ✅
2. Leandro Raphael Tenorio - `leandroraphael.tenorio@lorma.edu` → **100% match** ✅
3. John Doe - `john.doe@gmail.com` → **No match** ❌ (not in database)

### Result:
- 2 high confidence matches (auto-saved)
- 1 unmatched student (needs manual enrollment in database first)

## Steps to Use Auto Match

1. **Navigate to Student Mapping**
   - Go to "Student Mapping" from the main menu
   - Select the subject you want to map

2. **Click "Auto Match Students"**
   - System fetches students from Google Classroom
   - Compares with database students enrolled in the subject
   - Shows matching results

3. **Review Results**
   - **Green section**: High confidence matches (will be saved)
   - **Yellow section**: Conflicts (review manually)
   - **Red section**: Unmatched (add to database first)

4. **Save Matches**
   - Click "Save X High Confidence Matches"
   - System creates mappings in `student_mappings` table
   - Links `student_id` (database) with `gcr_student_id` (Google Classroom)

5. **Handle Unmatched Students**
   - Add missing students to the `students` table
   - Enroll them in the subject via `student_subject` table
   - Run auto-match again

## Database Structure

```
students (local database)
├── id
├── email ← PRIMARY MATCHING FIELD
├── first_name, middle_name, last_name
└── student_number

student_subject (enrollment)
├── student_id → students.id
├── subject_id → subjects.id
└── status (enrolled/dropped/completed)

student_mappings (GCR mapping)
├── student_id → students.id
├── gcr_student_id (Google Classroom ID)
├── student_email (from GCR)
└── mapping_confidence (0.00 to 1.00)
```

## Important Notes

✅ **Email is the primary validation** - Exact email match = automatic mapping
✅ **Students must be enrolled first** - Add students to database and enroll them in the subject
✅ **Case-insensitive matching** - `John@lorma.edu` matches `john@lorma.edu`
✅ **One-time setup** - Once mapped, grades sync automatically
✅ **Manual override available** - You can manually map students if auto-match fails

## Troubleshooting

**Problem: No matches found**
- Solution: Ensure students are added to the `students` table with correct emails
- Solution: Ensure students are enrolled in the subject via `student_subject` table

**Problem: Low confidence matches**
- Solution: Check if emails match exactly
- Solution: Verify student names are spelled correctly
- Solution: Manually map the student

**Problem: Duplicate matches**
- Solution: Check for duplicate student records in database
- Solution: Use conflict resolution to keep the correct mapping
