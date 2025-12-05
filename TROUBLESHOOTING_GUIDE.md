# Dynamic Grading System - Troubleshooting Guide

## Common Issues and Solutions

### 1. CSV Upload Issues

#### Issue: "CSV must contain a name column"
**Cause:** CSV file doesn't have a recognized name column

**Solution:**
- Ensure your CSV has one of these column headers:
  - `name`
  - `student_name`
  - `full_name`
- Column names are case-insensitive
- Check for typos in column headers

**Example CSV:**
```csv
name,email
Juan Dela Cruz,juan@example.com
```

#### Issue: "No students matched"
**Cause:** Student names/emails don't match Google Classroom data

**Solution:**
- Include email column for better matching
- Check name spelling matches GCR
- Verify students are enrolled in GCR class
- Check confidence scores in "View Students"
- Manual matching may be needed for low confidence

#### Issue: CSV upload fails silently
**Cause:** File format or encoding issue

**Solution:**
- Save CSV as UTF-8 encoding
- Remove special characters
- Check file size (should be < 2MB)
- Try with sample_students.csv first
- Check Laravel logs: `storage/logs/laravel.log`

### 2. Configuration Issues

#### Issue: "Total component weights must equal 100%"
**Cause:** Component percentages don't add up to 100

**Solution:**
- Check all component weights
- Use decimals if needed (e.g., 33.33%)
- Calculator: 15 + 25 + 60 = 100 ✓
- Save button disabled until fixed

#### Issue: Can't remove component
**Cause:** Trying to remove the last component

**Solution:**
- Must have at least one component
- Add new component before removing old one
- Or modify existing component instead

#### Issue: Configuration not saving
**Cause:** Validation error or server issue

**Solution:**
- Check browser console for errors (F12)
- Verify total weight = 100%
- Check all required fields filled
- Try refreshing page and re-entering
- Check Laravel logs for server errors

### 3. Grade Entry Issues

#### Issue: Grades not saving
**Cause:** Auto-save failed or network issue

**Solution:**
- Check internet connection
- Look for red border (indicates error)
- Green border = saved successfully
- Refresh page to verify persistence
- Check browser console for AJAX errors

#### Issue: Computed scores not calculating
**Cause:** Formula error or missing formula

**Solution:**
- Verify formula syntax: `score / total * 100`
- Check for division by zero
- Ensure max_score is set
- Test formula with sample values
- Check Laravel logs for evaluation errors

#### Issue: Term grade shows "-"
**Cause:** No grades entered yet

**Solution:**
- Enter at least one score per component
- Verify scores are saved (green border)
- Refresh page to see calculations
- Check component totals first

### 4. Display Issues

#### Issue: Table too wide / horizontal scroll
**Cause:** Many components or items

**Solution:**
- This is expected behavior
- Use fullscreen mode if available
- Scroll horizontally to see all columns
- Consider fewer items per component
- Use external monitor for better view

#### Issue: Students not showing in grade sheet
**Cause:** No students imported or matched

**Solution:**
- Go to Students page
- Import CSV for this subject
- Verify students are matched
- Check "View Students" to confirm
- Refresh grade sheet page

#### Issue: Components not showing
**Cause:** Configuration not saved

**Solution:**
- Go to Configure page
- Verify components exist
- Save configuration again
- Return to grade sheet
- Refresh page

### 5. Navigation Issues

#### Issue: "Add Class" button disabled
**Cause:** No available subjects

**Solution:**
- Sync classes from Google Classroom first
- Import students for subjects
- Check if class already added for this term
- Verify you have active subjects

#### Issue: Can't find Students or Grading page
**Cause:** Navigation not updated

**Solution:**
- Clear browser cache
- Hard refresh (Ctrl+F5)
- Check sidebar for new menu items
- Verify routes are registered: `php artisan route:list`

### 6. Formula Issues

#### Issue: Formula gives wrong result
**Cause:** Incorrect formula syntax

**Solution:**
- Test formula manually:
  - score=45, total=50
  - Formula: `score / total * 100`
  - Expected: (45/50)*100 = 90
- Use parentheses for clarity
- Check operator precedence
- Verify variables: `score` and `total` only

#### Issue: Formula causes error
**Cause:** Invalid expression

**Solution:**
- Avoid division by zero
- Use only arithmetic operators: + - * /
- No functions or complex expressions
- Leave empty for raw scores
- Check logs for evaluation errors

### 7. Performance Issues

#### Issue: Page loads slowly
**Cause:** Large dataset or server load

**Solution:**
- Check number of students (50+ may be slow)
- Check number of items per component
- Clear browser cache
- Optimize database (run migrations)
- Check server resources

#### Issue: Auto-save is laggy
**Cause:** Too many rapid inputs

**Solution:**
- Wait 500ms between inputs
- Auto-save has built-in delay
- Don't spam inputs rapidly
- Use Tab key to move between cells
- Refresh if it becomes unresponsive

### 8. Data Issues

#### Issue: Grades disappeared
**Cause:** Database issue or wrong class

**Solution:**
- Verify you're viewing correct class
- Check correct term selected
- Check database: `student_grades` table
- Restore from backup if available
- Check Laravel logs for errors

#### Issue: Duplicate students
**Cause:** Multiple CSV imports

**Solution:**
- System uses updateOrCreate (should not duplicate)
- Check `student_mappings` table
- Delete duplicates manually if needed
- Re-import CSV (will update existing)

#### Issue: Wrong calculations
**Cause:** Formula or weight error

**Solution:**
- Verify component weights total 100%
- Check formula syntax
- Manually calculate expected result
- Compare with displayed result
- Report bug if consistently wrong

## Error Messages

### "Faculty not found"
- **Cause:** Not logged in or session expired
- **Solution:** Login again

### "Subject not found"
- **Cause:** Subject doesn't exist or wrong ID
- **Solution:** Verify subject exists, check URL

### "Grading class not found"
- **Cause:** Class doesn't exist or wrong ID
- **Solution:** Go to grading index, select valid class

### "Unauthorized"
- **Cause:** Trying to access another faculty's data
- **Solution:** Only access your own classes

### "Validation failed"
- **Cause:** Invalid input data
- **Solution:** Check all required fields, verify data types

## Debugging Steps

### Step 1: Check Browser Console
1. Press F12 to open developer tools
2. Go to Console tab
3. Look for red error messages
4. Note the error details
5. Search error message online

### Step 2: Check Network Tab
1. Open developer tools (F12)
2. Go to Network tab
3. Perform the action that fails
4. Look for failed requests (red)
5. Click on failed request
6. Check Response tab for error details

### Step 3: Check Laravel Logs
1. Open `storage/logs/laravel.log`
2. Scroll to bottom (most recent)
3. Look for ERROR or EXCEPTION
4. Note the stack trace
5. Identify the failing line of code

### Step 4: Check Database
1. Open database client (phpMyAdmin, etc.)
2. Check relevant tables:
   - `student_mappings`
   - `grading_classes`
   - `grading_components`
   - `component_items`
   - `student_grades`
3. Verify data exists
4. Check for NULL values
5. Verify foreign keys

### Step 5: Clear Caches
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

### Step 6: Test with Sample Data
1. Use `sample_students.csv`
2. Create test class
3. Use simple configuration:
   - Activities (50%)
   - Exams (50%)
4. Enter test grades
5. Verify calculations

## Getting Help

### Before Asking for Help
- [ ] Checked this troubleshooting guide
- [ ] Checked browser console for errors
- [ ] Checked Laravel logs
- [ ] Tried with sample data
- [ ] Cleared all caches
- [ ] Documented steps to reproduce

### Information to Provide
1. **What were you trying to do?**
   - Specific action taken
   
2. **What happened?**
   - Actual result
   - Error messages
   
3. **What did you expect?**
   - Expected result
   
4. **Screenshots**
   - Error messages
   - Browser console
   - Relevant UI
   
5. **Environment**
   - Browser and version
   - Operating system
   - PHP version
   - Laravel version

### Contact Information
- Check documentation first
- Review code comments
- Search Laravel documentation
- Check Stack Overflow
- Contact system administrator

## Prevention Tips

### Best Practices
1. **Import students early** - Do this at semester start
2. **Test configuration** - Use sample data first
3. **Save frequently** - Auto-save helps, but verify
4. **Regular backups** - Export grades periodically
5. **Use consistent naming** - Keep CSV format consistent
6. **Test formulas** - Verify calculations manually
7. **Monitor logs** - Check for errors regularly
8. **Update regularly** - Keep system up to date

### What NOT to Do
1. ❌ Don't import CSV multiple times unnecessarily
2. ❌ Don't change configuration after entering grades
3. ❌ Don't use complex formulas
4. ❌ Don't enter invalid data
5. ❌ Don't ignore error messages
6. ❌ Don't skip validation warnings
7. ❌ Don't work without backups
8. ❌ Don't share login credentials

## Quick Reference

### Formula Examples
```
Percentage (0-100):
score / total * 100

Transmuted (60-100):
score / total * 60 + 40

Scaled (50-100):
score / total * 50 + 50

Weighted:
(score / total * 100) * 0.6
```

### Common Calculations
```
Component Average:
Sum of computed scores / Number of items

Weighted Component:
Component Average × (Weight / 100)

Term Grade:
Sum of all weighted components
```

### File Locations
```
Controllers: app/Http/Controllers/
Models: app/Models/
Views: resources/views/
Routes: routes/web.php
Logs: storage/logs/laravel.log
Migrations: database/migrations/
```

## Still Having Issues?

If you've tried everything and still have issues:

1. **Document the problem**
   - Write down exact steps
   - Take screenshots
   - Copy error messages

2. **Check system status**
   - Is server running?
   - Is database accessible?
   - Is internet connected?

3. **Try alternative approach**
   - Use different browser
   - Use different device
   - Try at different time

4. **Report the bug**
   - Provide all documentation
   - Include steps to reproduce
   - Attach screenshots/logs
   - Note expected vs actual behavior

Remember: Most issues have simple solutions. Check the basics first!
