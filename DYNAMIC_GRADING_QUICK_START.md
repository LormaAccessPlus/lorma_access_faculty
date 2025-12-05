# Dynamic Grading System - Quick Start Guide

## 🚀 Getting Started in 3 Steps

### Step 1: Import Your Students (2 minutes)

1. **Navigate to Students page**
   - Click "Students" in the sidebar

2. **Prepare your CSV file**
   ```csv
   name,email
   Juan Dela Cruz,juan.delacruz@example.com
   Maria Santos,maria.santos@example.com
   Pedro Reyes,pedro.reyes@example.com
   ```

3. **Upload CSV**
   - Find your subject card
   - Click "Import CSV"
   - Select your CSV file
   - Click "Upload & Match"

4. **Review matches**
   - System automatically matches with Google Classroom students
   - Green badge = Successfully matched
   - Yellow badge = Not matched (manual review needed)

### Step 2: Configure Your Grading (5 minutes)

1. **Navigate to Grading page**
   - Click "Grading" in the sidebar

2. **Add a class**
   - Click "Add Class" button
   - Select your subject
   - Select term (Prelim/Midterm/Finals)
   - Click "Add Class"

3. **Configure components**
   - System shows default component (Activities 15%)
   - Click "Add Component" to add more
   - Example setup:
     ```
     Activities    15%   Formula: score / total * 100
     Quizzes       25%   Formula: score / total * 100
     Exams         60%   Formula: score / total * 100
     ```
   - Make sure total = 100%
   - Click "Save Configuration"

### Step 3: Enter Grades (Ongoing)

1. **Add items to components**
   - Click "Add Item to Component"
   - Select component (e.g., Quizzes)
   - Enter item name (e.g., "Quiz 1")
   - Enter max score (e.g., 50)
   - Click "Add Item"

2. **Enter student scores**
   - Type scores directly in the table
   - Scores auto-save after 500ms
   - Green flash = Saved successfully
   - System automatically calculates:
     - Component totals
     - Weighted scores
     - Term grades

## 📊 Example Configurations

### Traditional Grading (100-point scale)
```
Activities (15%)    Formula: score / total * 100
Quizzes (25%)       Formula: score / total * 100
Exams (60%)         Formula: score / total * 100
```

### Transmuted Grading (60-100 scale)
```
Class Standing (40%)    Formula: score / total * 60 + 40
Exam (60%)              Formula: score / total * 60 + 40
```

### Comprehensive Grading
```
Attendance (10%)        Formula: score / total * 100
Participation (15%)     Formula: score / total * 100
Projects (25%)          Formula: score / total * 100
Midterm Exam (25%)      Formula: score / total * 100
Final Exam (25%)        Formula: score / total * 100
```

## 💡 Tips & Tricks

### CSV Import Tips
- Include email column for better matching accuracy
- Use consistent name formats
- Check "View Students" to verify matches

### Formula Tips
- `score / total * 100` = Percentage (0-100)
- `score / total * 60 + 40` = Transmuted (60-100)
- `score / total * 50 + 50` = Scaled (50-100)
- Leave empty for raw scores

### Grading Tips
- Add all items before entering grades
- Use Tab key to move between cells quickly
- Grades save automatically (no save button needed)
- Refresh page to see updated calculations

## ⚠️ Common Issues

### "Total weight must equal 100%"
- Check all component weights
- Use decimals if needed (e.g., 33.33%)
- Save button disabled until fixed

### "No students found"
- Import students first (Students page)
- Make sure students are matched
- Check correct subject selected

### Grades not calculating
- Check formula syntax
- Ensure max score is set
- Refresh page after entering scores

## 🎯 Best Practices

1. **Import students early** - Do this at the start of the semester
2. **Configure once** - Set up components before entering any grades
3. **Add items progressively** - Add Quiz 1, then Quiz 2, etc. as needed
4. **Regular backups** - Export grades periodically (feature coming soon)
5. **Test formulas** - Enter test scores to verify calculations

## 📱 Navigation

- **Students** - Import and view student lists
- **Grading** - Configure and enter grades
- **Google Classroom** - Sync classes from GCR
- **Grade Matrix** - Legacy grading system (still available)

## 🆘 Need Help?

1. Check the full documentation: `DYNAMIC_GRADING_IMPLEMENTATION.md`
2. Review formula examples above
3. Test with sample data first
4. Contact support if issues persist

---

**Ready to start?** Go to Students page and import your first CSV! 🎓
