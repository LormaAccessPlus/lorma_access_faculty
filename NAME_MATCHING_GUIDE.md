# Student Matching by Name - Configuration Guide

## ✅ Updated Matching Logic

The system now matches students **primarily by name** instead of email.

## Matching Priority (Updated)

### 1. **Name Matching** (PRIMARY - Highest Priority)
- **95%+ similarity** → 100% confidence → Automatic match ✅
- **85-95% similarity** → 90% confidence → Automatic match
- **75-85% similarity** → 80% confidence → Automatic match
- **Below 75%** → Proportional confidence → May need review

### 2. **Google User ID** (Secondary)
- Exact match → 100% confidence
- Used for already-matched students

### 3. **Email** (Tertiary - Bonus)
- Exact match → 100% confidence
- Similar email → +10% bonus
- Only used if available

## How Name Matching Works

### Exact Name Match Example:
```
GCR Student: "Mark Joshua Navida"
DB Student:  "Mark Joshua Navida"
Result: 100% confidence → Automatic match ✅
```

### Similar Name Match Example:
```
GCR Student: "Mark J. Navida"
DB Student:  "Mark Joshua Navida"
Result: ~90% confidence → Automatic match ✅
```

### Partial Name Match Example:
```
GCR Student: "Mark Navida"
DB Student:  "Mark Joshua Navida"
Result: ~85% confidence → Automatic match ✅
```

### Different Name Example:
```
GCR Student: "John Doe"
DB Student:  "Mark Joshua Navida"
Result: <50% confidence → Manual review needed ⚠️
```

## Name Matching Features

✅ **Case-insensitive** - "MARK NAVIDA" matches "Mark Navida"
✅ **Handles middle names** - "Mark Joshua Navida" matches "Mark Navida"
✅ **Removes suffixes** - "Mark Navida Jr." matches "Mark Navida"
✅ **Handles spacing** - "Mark  Navida" matches "Mark Navida"
✅ **Partial matching** - Matches first and last name combinations

## Current Students

### Database:
1. Mark Joshua Navida
2. Leandro Raphael Tenorio
3. Jasper Ace Lapitan

### Google Classroom:
1. Mark Joshua Navida → **100% match** ✅
2. Leandro Raphael Tenorio → **100% match** ✅
3. Jasper Ace Lapitan → **100% match** ✅

## How to Use

### Step 1: Ensure Students are in Database

Make sure students are added to the `students` table with their correct names:

```sql
INSERT INTO students (student_number, first_name, last_name, email, status)
VALUES ('2024001', 'Mark Joshua', 'Navida', 'mark@example.com', 'active');
```

Or import from Google Classroom:
```bash
php artisan import:gcr-students 2
```

### Step 2: Enroll Students in Subject

Students must be enrolled in the subject via `student_subject` table:

```sql
INSERT INTO student_subject (student_id, subject_id, status)
VALUES (1, 2, 'enrolled');
```

Or use the import command (does this automatically).

### Step 3: Auto Match

1. Go to **Student Mapping** page
2. Select your subject
3. Click **"Auto Match Students"**
4. Students will be matched by name automatically!

## Match Results

### High Confidence Matches (≥80%)
- Automatically saved
- Green indicator
- No manual review needed

### Medium Confidence (50-80%)
- Flagged for review
- Yellow indicator
- Verify manually

### Low Confidence (<50%)
- Not matched
- Red indicator
- Manual mapping required

## Tips for Best Results

### ✅ DO:
- Use full names in database (First Middle Last)
- Keep name format consistent
- Use proper capitalization
- Remove special characters

### ❌ DON'T:
- Use nicknames in database
- Mix name orders (Last, First vs First Last)
- Include titles (Mr., Ms., Dr.)
- Use abbreviations unless necessary

## Testing Name Matching

Test the matching confidence for any student:

```bash
php artisan tinker
```

```php
$service = app('App\Services\StudentMappingService');
$gcrStudent = [
    'userId' => '123',
    'profile' => ['name' => ['fullName' => 'Mark Joshua Navida']]
];
$dbStudent = App\Models\Student::where('first_name', 'Mark')->first();

// Use reflection to test private method
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('calculateMatchConfidence');
$method->setAccessible(true);
$confidence = $method->invoke($service, $gcrStudent, $dbStudent);

echo "Match confidence: " . ($confidence * 100) . "%\n";
```

## Troubleshooting

### Problem: Students not matching

**Solution 1:** Check name spelling
```bash
php artisan test:gcr-students 2
```
Compare GCR names with database names.

**Solution 2:** Check if students are enrolled
```sql
SELECT s.full_name, ss.status 
FROM students s
JOIN student_subject ss ON s.id = ss.student_id
WHERE ss.subject_id = 2;
```

**Solution 3:** Lower the matching threshold temporarily
Edit `StudentMappingService.php` line ~30:
```php
if ($match && $match['confidence'] >= 0.7) { // Changed from 0.8
```

### Problem: Too many false matches

**Solution:** Increase the matching threshold
```php
if ($match && $match['confidence'] >= 0.9) { // Changed from 0.8
```

## Summary

✅ **Name matching is now the primary method**
✅ **95%+ name similarity = automatic match**
✅ **No email required**
✅ **Works with current students perfectly**

The system will now automatically match students based on their names from Google Classroom to the database!
