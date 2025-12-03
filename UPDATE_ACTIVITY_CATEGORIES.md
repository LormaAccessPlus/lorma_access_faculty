# Update Existing Activities with Categories

## Problem
The `activity_category` column was just added, so all existing activities have `category = 'activity'` by default. Quizzes need to be identified and updated.

## Solution: Run This Script

### Option 1: Via Tinker (Recommended)
```bash
php artisan tinker
```

Then run:
```php
// Update activities with "quiz" in the name
$quizCount = DB::table('activities')
    ->where(function($query) {
        $query->where('name', 'LIKE', '%quiz%')
              ->orWhere('name', 'LIKE', '%Quiz%')
              ->orWhere('name', 'LIKE', '%QUIZ%')
              ->orWhere('name', 'LIKE', '%test%')
              ->orWhere('name', 'LIKE', '%Test%')
              ->orWhere('name', 'LIKE', '%exam%')
              ->orWhere('name', 'LIKE', '%Exam%')
              ->orWhere('name', 'REGEXP', 'Q[0-9]+')
              ->orWhere('name', 'REGEXP', 'q[0-9]+');
    })
    ->update(['activity_category' => 'quiz']);

echo "Updated {$quizCount} activities to 'quiz' category\n";

// Verify the update
$activities = DB::table('activities')
    ->select('id', 'name', 'activity_category')
    ->get();

echo "\nAll activities:\n";
foreach($activities as $activity) {
    echo "ID: {$activity->id} | {$activity->name} | Category: {$activity->activity_category}\n";
}
```

### Option 2: Create a Migration
Create file: `database/migrations/2024_12_01_000003_update_existing_activity_categories.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update activities that look like quizzes
        DB::table('activities')
            ->where(function($query) {
                $query->where('name', 'LIKE', '%quiz%')
                      ->orWhere('name', 'LIKE', '%Quiz%')
                      ->orWhere('name', 'LIKE', '%QUIZ%')
                      ->orWhere('name', 'LIKE', '%test%')
                      ->orWhere('name', 'LIKE', '%Test%')
                      ->orWhere('name', 'LIKE', '%exam%')
                      ->orWhere('name', 'LIKE', '%Exam%')
                      ->orWhere('name', 'REGEXP', 'Q[0-9]+')
                      ->orWhere('name', 'REGEXP', 'q[0-9]+');
            })
            ->update(['activity_category' => 'quiz']);
    }

    public function down(): void
    {
        // Revert all to activity
        DB::table('activities')->update(['activity_category' => 'activity']);
    }
};
```

Then run:
```bash
php artisan migrate
```

### Option 3: Direct SQL
```sql
-- Update activities with quiz-like names
UPDATE activities 
SET activity_category = 'quiz' 
WHERE name LIKE '%quiz%' 
   OR name LIKE '%Quiz%'
   OR name LIKE '%QUIZ%'
   OR name LIKE '%test%'
   OR name LIKE '%Test%'
   OR name LIKE '%exam%'
   OR name LIKE '%Exam%'
   OR name REGEXP 'Q[0-9]+'
   OR name REGEXP 'q[0-9]+';

-- Verify the update
SELECT id, name, activity_category, type, term 
FROM activities 
ORDER BY term, activity_category, name;
```

## Verification

After running the update, check your database:

```sql
-- Count activities by category
SELECT activity_category, COUNT(*) as count 
FROM activities 
GROUP BY activity_category;

-- List all quizzes
SELECT id, name, type, term, activity_category 
FROM activities 
WHERE activity_category = 'quiz';
```

## Expected Result

After running the script, you should see:
- Activities with "Quiz", "Test", "Exam", "Q1", "Q2" etc. → `activity_category = 'quiz'`
- Other activities → `activity_category = 'activity'`

## Then Refresh the Page

Once the categories are updated:
1. Refresh the nursing term-grades page
2. You should now see:
   - **Activities** header spanning activity columns
   - **Quizzes** header spanning quiz columns
   - Separate "Total Activities" and "Total Quizzes" columns
   - Separate "Activities Grade" and "Quizzes Grade" columns

## For Future Imports

The Google Classroom import will now automatically detect and categorize:
- ✅ Already implemented in `ClassroomSyncController.php`
- ✅ Detects: "quiz", "test", "exam", "Q1", "Q2", etc.
- ✅ Automatically sets `activity_category = 'quiz'`

## Manual Creation

When creating activities manually:
- ✅ Already implemented in activity form
- ✅ Dropdown to select "Activity" or "Quiz"
- ✅ Default is "Activity"
