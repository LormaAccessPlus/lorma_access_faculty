# Troubleshooting: Nursing Matrix PDF Export

## Issue
The PDF export is not using the correct subject type (Lecture/Lab) even after saving the configuration.

## Steps to Debug

### 1. Check if the configuration is being saved
1. Open the Full Matrix page for your nursing subject
2. Open Browser Developer Console (F12) → Console tab
3. Expand the "Final Rating Formula" section
4. Select "Lecture" or "Laboratory"
5. Click "Save Final Rating Weights"
6. Check the console output:
   - Should see: `Saving weights: {subject_type: "lecture", ...}`
   - Should see: `Response: {success: true, ...}`

### 2. Check the PDF debug info
1. After saving, export the PDF
2. Look at the top of the PDF (small gray text below the header)
3. It should show:
   ```
   Matrix: nursing | Subject Type: lecture | isNursing: yes | isLecture: yes | isLab: no
   ```

### 3. Check the Laravel logs
Run this command to see the last 50 log entries:
```bash
tail -n 50 storage/logs/laravel.log
```

Look for an entry like:
```
Exporting PP PDF {"subject_id": X, "matrix_type": "nursing", "final_rating_config": {"subject_type": "lecture", ...}}
```

### 4. Check the database directly
If you have database access, run:
```sql
SELECT id, subject_code, matrix_type, final_rating_config 
FROM subjects 
WHERE matrix_type = 'nursing';
```

The `final_rating_config` should contain:
```json
{
  "subject_type": "lecture",
  "prelim_weight": 30,
  "midterm_weight": 30,
  "finals_weight": 40
}
```

## Common Issues

### Issue 1: Configuration not saving
**Symptoms**: Console shows error or no response
**Solution**: 
- Check if you're logged in
- Check if the CSRF token is valid
- Try refreshing the page and saving again

### Issue 2: Old cached data
**Symptoms**: PDF shows old configuration even after saving
**Solution**:
```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### Issue 3: Browser cache
**Symptoms**: Page doesn't show updated values
**Solution**:
- Hard refresh the page (Ctrl+F5 or Cmd+Shift+R)
- Clear browser cache

### Issue 4: Database not updating
**Symptoms**: Logs show old configuration
**Solution**:
- Check if the `final_rating_config` column exists in the subjects table
- Check if it's a JSON column type
- Try running migrations again:
  ```bash
  php artisan migrate:status
  ```

## Expected Behavior

### For Lecture Type:
- PDF should show columns: Prelim | Midterm | Finals | Final Grade (80%) | Comp. Exam (20%) | FINAL RATING | Remarks
- Final Rating = Final Grade (80%) + Comprehensive Exam (20%)
- Footer should say: "Subject Type: LECTURE"

### For Laboratory Type:
- PDF should show columns: Prelim | Midterm | Finals | Final Grade (100%) | FINAL RATING | Remarks
- Final Rating = Final Grade (100%)
- Footer should say: "Subject Type: LABORATORY"

## Files Modified
1. `resources/views/grades/matrix.blade.php` - Added subject type selector
2. `resources/views/grades/exports/pp.blade.php` - Added debug info and lab column
3. `app/Http/Controllers/GradeController.php` - Added refresh() and logging

## Next Steps
If the issue persists after checking all the above:
1. Share the console output when saving
2. Share the debug info from the PDF
3. Share the relevant log entries
4. Check if the subject's `matrix_type` is actually set to "nursing"
