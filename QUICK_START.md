# 🚀 Quick Start Guide

## Your Account Setup

✅ **Faculty:** Mark Joshua Navida (markjoshua.navida@lorma.edu)  
✅ **Teacher ID:** MJNNAVIDA  
✅ **Subject Assigned:** A211 - Fundamentals of Electrical Circuits lec/lab  
✅ **Status:** READY TO USE

---

## Step-by-Step: Connect Google Classroom

### 1. Login
```
http://localhost:8000/login
```
Sign in with your @lorma.edu Google account

### 2. Go to Subject Mapping
From the dashboard, click on **"Subject Mapping"** or visit:
```
http://localhost:8000/subjects/mapping
```

### 3. Create Mapping
1. **Select Google Classroom Course** - Choose from your GCR courses
2. **Select School Subject** - Choose **A211** (Fundamentals of Electrical Circuits)
3. **Add Notes** (optional) - Any additional information
4. Click **"Create Mapping"**

### 4. Sync Students
After mapping:
1. The system will automatically fetch students from Google Classroom
2. Click **"Auto-Match Students"** to match GCR students with school database
3. Review and confirm matches
4. Manually map any unmatched students

### 5. Manage Grades
1. Go to the subject's grade matrix
2. Enter grades by term:
   - **Prelim** (30%)
   - **Midterm** (30%)
   - **Finals** (40%)
3. Grades are automatically computed
4. Sync back to school database when ready

---

## Key Features

### Subject Mapping
- Link Google Classroom courses to school subjects
- One-to-one mapping
- View mapping statistics

### Student Sync
- Automatic student import from GCR
- Smart auto-matching by name
- Manual mapping for edge cases
- Conflict resolution

### Grade Management
- Term-based grading (Prelim, Midterm, Finals)
- Automatic computation
- Class standing + Exam scores
- Configurable weights

### Grade Sync
- Preview before syncing
- Verify data integrity
- Sync to school database
- Audit trail

---

## Important URLs

| Feature | URL |
|---------|-----|
| Login | http://localhost:8000/login |
| Dashboard | http://localhost:8000/ |
| Subject Mapping | http://localhost:8000/subjects/mapping |
| Google Classroom | http://localhost:8000/classroom |
| Activities | http://localhost:8000/activities |
| Grades | http://localhost:8000/grades |

---

## Tips

💡 **Auto-Match Works Best When:**
- Student names in GCR match school database
- Names follow "FirstName LastName" format
- No special characters or nicknames

💡 **Grade Computation:**
- Weights are configurable in `.env`
- Default: Prelim 30%, Midterm 30%, Finals 40%
- Each term: Class Standing 40%, Exam 60%

💡 **Syncing:**
- Always preview before syncing
- Check for conflicts
- Verify student mappings first
- Grades sync is one-way (to school DB)

---

## Need Help?

- Check logs: `storage/logs/laravel.log`
- Clear cache: `php artisan optimize:clear`
- Test connection: Visit `/test-auth` route

---

**Ready to start! Login and map your first subject! 🎉**
