# How to Recalculate Grades for General Education Subjects

## Problem

Your existing grades were calculated with the old formula (percentage-based). After updating the code to use the transmuted formula, you need to recalculate all existing grades.

## Solution

Use the new artisan command to recalculate grades for your subject.

## Step 1: Find Your Subject ID

First, you need to know the subject ID. You can find it in the URL when viewing the subject, or run:

```bash
php artisan tinker --execute="App\Models\Subject::all()->each(fn(\$s) => print(\$s->id . ': ' . \$s->subject_code . ' - ' . \$s->subject_name . PHP_EOL));"
```

This will list all subjects with their IDs.

## Step 2: Recalculate Grades

### Recalculate All Terms

To recalculate all terms (prelim, midterm, finals) for a subject:

```bash
php artisan grades:recalculate {subject_id}
```

**Example:**
```bash
php artisan grades:recalculate 2
```

### Recalculate Specific Term

To recalculate only one term:

```bash
php artisan grades:recalculate {subject_id} --term=prelim
```

**Examples:**
```bash
php artisan grades:recalculate 2 --term=prelim
php artisan grades:recalculate 2 --term=midterm
php artisan grades:recalculate 2 --term=finals
```

## What the Command Does

1. **Reads the grading configuration** for the subject and term
2. **Checks the formula type** (transmuted or percentage)
3. **Recalculates class standing** for each student using the correct formula:
   - Transmuted: `(score/items) × 50 + 50`
   - Percentage: `(score/items) × 100`
4. **Recalculates exam scores** using the correct formula
5. **Recalculates term grades** by applying the weights

## Expected Results

After running the command, you should see:

### Before (Old Formula):
- Class Standing: 36.20 (weighted value)
- Exam Grade: 73 (percentage)
- Term Grade: ~60

### After (New Formula):
- Class Standing: 95.25 (transmuted score)
- Exam Grade: 92.5 (transmuted score)
- Term Grade: 94.17 (weighted)

## Example Output

```
Recalculating grades for: CS101 - Introduction to Computer Science

Processing prelim term...
  Formula type: transmuted
  Weights: 66.67% CS + 33.33% Exam
  Students to process: 3
  ███████████████████████████████ 3/3
  ✓ prelim term recalculated

Processing midterm term...
  Formula type: transmuted
  Weights: 66.67% CS + 33.33% Exam
  Students to process: 3
  ███████████████████████████████ 3/3
  ✓ midterm term recalculated

Processing finals term...
  Formula type: transmuted
  Weights: 66.67% CS + 33.33% Exam
  Students to process: 3
  ███████████████████████████████ 3/3
  ✓ finals term recalculated

✓ Grade recalculation complete!
```

## Verification

After running the command:

1. Go to the **General Education Matrix**
2. Select the subject
3. View the grades
4. You should now see:
   - **Class Standing**: ~95 (transmuted score)
   - **Exam Grade**: ~92.5 (transmuted score)
   - **Term Grade**: ~94 (weighted)

## Important Notes

⚠️ **This command will overwrite existing grades** - Make sure you have a backup if needed

✅ **The command is safe to run multiple times** - It will recalculate using the current formula configuration

✅ **Only affects the specified subject** - Other subjects are not touched

## Troubleshooting

### "No grading config found"

If you see this message, it means the subject doesn't have a grading configuration yet. To fix:

1. Go to the General Education Matrix
2. Click on the subject
3. Go to "Edit Term Grades" for the term
4. This will create the grading config automatically
5. Run the recalculate command again

### "Subject not found"

Make sure you're using the correct subject ID. Run the command from Step 1 to list all subjects.

## For All General Education Subjects

If you have multiple subjects in the General Education matrix, you'll need to run the command for each one:

```bash
php artisan grades:recalculate 2
php artisan grades:recalculate 5
php artisan grades:recalculate 8
# etc.
```

Or create a simple script to do them all at once.
