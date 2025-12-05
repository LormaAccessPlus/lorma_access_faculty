# Dynamic Grading System - Testing Checklist

## Pre-Testing Setup

- [ ] Database migrations completed successfully
- [ ] Views cache cleared (`php artisan view:clear`)
- [ ] Logged in as faculty user
- [ ] Have at least one subject synced from Google Classroom
- [ ] Sample CSV file ready (`sample_students.csv`)

## Phase 1: Students Page Testing

### CSV Upload
- [ ] Navigate to `/students` page
- [ ] Page loads without errors
- [ ] Subject cards display correctly
- [ ] Click "Import CSV" button
- [ ] Modal opens successfully
- [ ] Select `sample_students.csv` file
- [ ] Click "Upload & Match"
- [ ] Success message appears
- [ ] Student count badge updates

### View Students
- [ ] Click "View Students" button
- [ ] Modal opens with student list
- [ ] Students display with names and emails
- [ ] Match status badges show (Matched/Not Matched)
- [ ] Confidence percentages display
- [ ] Modal closes properly

### Auto-Matching Verification
- [ ] Check if students with matching emails show 100% confidence
- [ ] Check if students with similar names show appropriate confidence
- [ ] Verify "Matched" badge for high-confidence matches
- [ ] Verify "Not Matched" badge for low-confidence matches

## Phase 2: Grading Configuration Testing

### Add Class
- [ ] Navigate to `/grading` page
- [ ] Page loads without errors
- [ ] Click "Add Class" button
- [ ] Modal opens successfully
- [ ] Subject dropdown shows available subjects
- [ ] Select a subject
- [ ] Select term (Prelim)
- [ ] Click "Add Class"
- [ ] Redirects to configuration page

### Configure Components
- [ ] Configuration page loads
- [ ] Default component (Activities) displays
- [ ] Click "Add Component" button
- [ ] New component row appears
- [ ] Fill in component details:
  - Name: "Quizzes"
  - Type: "Quiz"
  - Weight: 25
  - Formula: `score / total * 100`
- [ ] Add another component:
  - Name: "Exams"
  - Type: "Exam"
  - Weight: 60
  - Formula: `score / total * 100`
- [ ] Total weight shows 100%
- [ ] Save button is enabled
- [ ] Click "Save Configuration"
- [ ] Success message appears
- [ ] Redirects to grade sheet

### Weight Validation
- [ ] Go back to configuration
- [ ] Change Activities weight to 20
- [ ] Total weight shows 105%
- [ ] Warning message appears
- [ ] Save button is disabled
- [ ] Change back to 15
- [ ] Warning disappears
- [ ] Save button is enabled

### Remove Component
- [ ] Click trash icon on a component
- [ ] Component is removed
- [ ] Total weight updates
- [ ] Try to remove last component
- [ ] Alert appears (must have at least one)

## Phase 3: Grade Sheet Testing

### Add Items to Components
- [ ] Grade sheet page loads
- [ ] Dynamic table displays with configured components
- [ ] Click "Add Item to Component"
- [ ] Modal opens
- [ ] Select component (Activities)
- [ ] Enter item name: "Activity 1"
- [ ] Enter max score: 50
- [ ] Enter date (optional)
- [ ] Click "Add Item"
- [ ] New column appears in table
- [ ] Repeat for other components:
  - Quiz 1 (max: 50)
  - Quiz 2 (max: 50)
  - Exam 1 (max: 100)

### Enter Grades
- [ ] Click in a score input field
- [ ] Enter a score (e.g., 45)
- [ ] Wait 500ms
- [ ] Green border appears (saved)
- [ ] Page reloads automatically
- [ ] Score persists after reload
- [ ] Computed score displays (if formula applied)
- [ ] Component total updates
- [ ] Term grade updates

### Grade Calculations
- [ ] Enter scores for all items for one student
- [ ] Verify component totals calculate correctly
- [ ] Verify weighted scores apply correctly
- [ ] Verify term grade = sum of weighted components
- [ ] Example verification:
  ```
  Activities: 45/50 = 90% × 15% = 13.5
  Quizzes: (40+45)/100 = 85% × 25% = 21.25
  Exams: 85/100 = 85% × 60% = 51
  Term Grade: 13.5 + 21.25 + 51 = 85.75
  ```

### Formula Application
- [ ] Enter score in field with formula
- [ ] Verify computed score applies formula correctly
- [ ] Example: score=45, total=50, formula=`score/total*60+40`
  - Expected: (45/50)*60+40 = 94
- [ ] Verify result displays with 2 decimals

## Phase 4: Multiple Classes Testing

### Add Another Class
- [ ] Go back to `/grading`
- [ ] Click "Add Class"
- [ ] Select same subject, different term (Midterm)
- [ ] Configure with different components:
  - Attendance (10%)
  - Participation (15%)
  - Projects (25%)
  - Midterm Exam (50%)
- [ ] Save configuration
- [ ] Verify both classes appear on grading index

### Switch Between Classes
- [ ] Click "Grade Sheet" on first class
- [ ] Verify correct configuration loads
- [ ] Go back to grading index
- [ ] Click "Grade Sheet" on second class
- [ ] Verify different configuration loads
- [ ] Verify students are the same (from CSV import)

## Phase 5: Edge Cases Testing

### Empty States
- [ ] Visit `/students` with no subjects
- [ ] Verify "No subjects found" message
- [ ] Visit `/grading` with no classes
- [ ] Verify "No grading classes yet" message
- [ ] Visit grade sheet with no items
- [ ] Verify "No components configured" message

### Invalid Input
- [ ] Try uploading non-CSV file
- [ ] Verify error message
- [ ] Try uploading CSV without name column
- [ ] Verify error message
- [ ] Try entering score > max score
- [ ] Verify validation (should allow, but highlight)
- [ ] Try entering negative score
- [ ] Verify validation

### Formula Errors
- [ ] Enter invalid formula: `score / 0`
- [ ] Save configuration
- [ ] Enter grades
- [ ] Verify error handling (should not crash)
- [ ] Check logs for error message

### Concurrent Editing
- [ ] Open grade sheet in two browser tabs
- [ ] Enter different scores in both tabs
- [ ] Verify last save wins
- [ ] Verify no data corruption

## Phase 6: Navigation Testing

### Menu Navigation
- [ ] Click "Students" in sidebar
- [ ] Verify active state highlights
- [ ] Click "Grading" in sidebar
- [ ] Verify active state highlights
- [ ] Click "Google Classroom"
- [ ] Verify old pages still work
- [ ] Click "Grade Matrix"
- [ ] Verify old grading system still works

### Breadcrumb Navigation
- [ ] From grade sheet, click "Back"
- [ ] Verify returns to grading index
- [ ] From configuration, click "Cancel"
- [ ] Verify returns to grading index
- [ ] Use browser back button
- [ ] Verify navigation works correctly

## Phase 7: Data Persistence Testing

### Refresh Testing
- [ ] Enter grades
- [ ] Refresh page (F5)
- [ ] Verify grades persist
- [ ] Modify configuration
- [ ] Refresh page
- [ ] Verify configuration persists

### Logout/Login Testing
- [ ] Enter grades
- [ ] Logout
- [ ] Login again
- [ ] Navigate to grade sheet
- [ ] Verify grades persist

### Database Verification
- [ ] Check `student_mappings` table
- [ ] Verify `csv_data` and `auto_matched` columns populated
- [ ] Check `grading_classes` table
- [ ] Verify class records exist
- [ ] Check `grading_components` table
- [ ] Verify components exist with correct weights
- [ ] Check `component_items` table
- [ ] Verify items exist
- [ ] Check `student_grades` table
- [ ] Verify grades saved with computed scores

## Phase 8: Performance Testing

### Load Testing
- [ ] Import CSV with 50+ students
- [ ] Verify upload completes in reasonable time
- [ ] Load grade sheet with 50+ students
- [ ] Verify page loads in reasonable time
- [ ] Enter grades for multiple students
- [ ] Verify auto-save doesn't lag

### Browser Console
- [ ] Open browser console (F12)
- [ ] Navigate through all pages
- [ ] Verify no JavaScript errors
- [ ] Verify no 404 errors
- [ ] Verify AJAX calls succeed

## Phase 9: Mobile Responsiveness

### Mobile View
- [ ] Open in mobile browser or use responsive mode
- [ ] Verify Students page is readable
- [ ] Verify Grading page is readable
- [ ] Verify Grade sheet is usable (may need horizontal scroll)
- [ ] Verify modals work on mobile
- [ ] Verify buttons are tappable

## Phase 10: Security Testing

### Authorization
- [ ] Try accessing another faculty's grading class
- [ ] Verify 403 or redirect
- [ ] Try modifying URL parameters
- [ ] Verify ownership checks work

### Input Validation
- [ ] Try SQL injection in CSV
- [ ] Verify sanitization works
- [ ] Try XSS in component names
- [ ] Verify escaping works
- [ ] Try malicious formulas
- [ ] Verify safe evaluation

## Bug Tracking

### Issues Found
| # | Issue | Severity | Status | Notes |
|---|-------|----------|--------|-------|
| 1 |       |          |        |       |
| 2 |       |          |        |       |
| 3 |       |          |        |       |

### Severity Levels
- **Critical:** System crash, data loss
- **High:** Feature doesn't work, major UX issue
- **Medium:** Minor functionality issue
- **Low:** Cosmetic issue, minor UX improvement

## Sign-Off

- [ ] All critical tests passed
- [ ] All high-priority tests passed
- [ ] Known issues documented
- [ ] System ready for production use

**Tested By:** ___________________
**Date:** ___________________
**Signature:** ___________________

## Notes

Use this space for additional observations, suggestions, or feedback:

```
[Your notes here]
```
