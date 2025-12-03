# Quick Fix Steps - Update General Education Formula

## The Problem

Your subject's grading configuration was created before we added the transmuted formula support. It's still using the old percentage formula.

## The Solution (2 Commands)

### Step 1: Set the General Education Formula

This updates the grading configuration to use the transmuted formula:

```bash
php artisan grades:set-gened-formula {subject_id}
```

**Example:**
```bash
php artisan grades:set-gened-formula 2
```

This will:
- Set weights to 66.67% CS + 33.33% Exam
- Set formula type to "transmuted"
- Apply to all terms (prelim, midterm, finals)

### Step 2: Recalculate All Grades

Now recalculate the grades using the new formula:

```bash
php artisan grades:recalculate {subject_id}
```

**Example:**
```bash
php artisan grades:recalculate 2
```

## Complete Example

```bash
# Step 1: Set the formula
php artisan grades:set-gened-formula 2

# Step 2: Recalculate grades
php artisan grades:recalculate 2
```

## Expected Output

### Step 1 Output:
```
Setting General Education formula for: CS101 - Introduction to Computer Science

Processing prelim term...
  ✓ Updated existing config
  Formula: 66.67% CS + 33.33% Exam (transmuted)

Processing midterm term...
  ✓ Updated existing config
  Formula: 66.67% CS + 33.33% Exam (transmuted)

Processing finals term...
  ✓ Updated existing config
  Formula: 66.67% CS + 33.33% Exam (transmuted)

✓ Formula configuration complete!

Now run: php artisan grades:recalculate 2
```

### Step 2 Output:
```
Recalculating grades for: CS101 - Introduction to Computer Science

Processing prelim term...
  Formula type: transmuted
  Weights: 66.67% CS + 33.33% Exam
  Students to process: 3
  ███████████████████████████████ 3/3
  ✓ prelim term recalculated

✓ Grade recalculation complete!
```

## What Will Change

### Before:
```
Jasper Ace Lapitan
- Total CS: 543/600 (90.50%)
- Class Standing: 36.20  ← OLD
- Exam: 73
```

### After:
```
Jasper Ace Lapitan
- Total CS: 543/600 (90.50%)
- Class Standing: 95.25  ← NEW (transmuted)
- Exam: 86.50  ← NEW (transmuted)
```

## Verification

After running both commands:

1. Refresh the General Education Matrix page
2. Look at the "CLASS STANDING" column
3. You should see values around 90-95 instead of 30-40
4. The "% of Total CS" should show the correct percentage (90.50%)

## For Multiple Subjects

If you have multiple subjects in General Education, repeat for each:

```bash
# Subject 2
php artisan grades:set-gened-formula 2
php artisan grades:recalculate 2

# Subject 5
php artisan grades:set-gened-formula 5
php artisan grades:recalculate 5

# Subject 8
php artisan grades:set-gened-formula 8
php artisan grades:recalculate 8
```

## Why Two Commands?

1. **First command** updates the formula configuration in the database
2. **Second command** recalculates all grades using the new formula

Both are needed to fix existing data.

## Troubleshooting

### "Subject not found"
- Make sure you're using the correct subject ID
- Run: `php artisan tinker --execute="App\Models\Subject::all()->pluck('id', 'subject_code')"`

### Still showing old values
- Make sure you ran BOTH commands
- Clear your browser cache and refresh
- Check that the formula type is "transmuted" in the database

### Need to find subject IDs
```bash
php artisan tinker --execute="App\Models\Subject::all()->each(fn(\$s) => print(\$s->id . ': ' . \$s->subject_code . ' - ' . \$s->subject_name . PHP_EOL));"
```
