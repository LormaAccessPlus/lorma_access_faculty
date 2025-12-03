# Alternative Student Matching Solution

## Problem

Google Classroom API is not returning student email addresses, even with the correct scopes. This can happen due to:

1. **Google Workspace Admin Restrictions** - Domain admin has disabled email sharing with third-party apps
2. **Student Account Privacy Settings** - Students using restricted/supervised accounts
3. **OAuth Consent Screen Configuration** - Missing sensitive scope approval

## Alternative Solution: Match by Student ID

Since we can get the Google user ID from the API, we can use that for matching instead of email.

### Option 1: Manual Student ID Mapping

1. **Get Google User IDs from Classroom**
   - We already have: `userId: "109026432821101673146"`
   
2. **Add Google User ID to Students Table**
   ```sql
   ALTER TABLE students ADD COLUMN google_user_id VARCHAR(255) UNIQUE AFTER email;
   ```

3. **Manually map students**
   - Admin enters the Google User ID for each student
   - System matches based on `google_user_id` instead of email

### Option 2: Use Student Names (Current Fallback)

The system already has name matching as a fallback:
- Name similarity matching (up to 50% confidence)
- Combined with manual verification

### Option 3: Ask Students to Self-Register

1. Create a student portal where students can:
   - Log in with Google (gets their Google User ID)
   - Enter their student number
   - System creates the mapping automatically

### Option 4: Export-Import Approach

1. **Export from Google Classroom:**
   - Go to Google Classroom
   - Export student list (includes emails if you're the teacher)
   
2. **Import to Database:**
   - Upload the CSV
   - System matches and creates student records

## Recommended Immediate Solution

### Step 1: Check Google Workspace Admin Settings

Ask your Google Workspace administrator to:

1. Go to **Google Admin Console** (admin.google.com)
2. Navigate to **Apps** → **Google Workspace** → **Google Classroom**
3. Check **Data Access** settings
4. Ensure "Allow third-party apps to access student data" is enabled
5. Add your OAuth Client ID to the allowlist if needed

### Step 2: Update OAuth Consent Screen

In Google Cloud Console:

1. Go to **APIs & Services** → **OAuth consent screen**
2. Click **Edit App**
3. In **Scopes**, add:
   - `https://www.googleapis.com/auth/classroom.rosters`
   - `https://www.googleapis.com/auth/classroom.profile.emails`
   - `https://www.googleapis.com/auth/classroom.profile.photos`
4. **Save** and **Submit for Verification** if needed

### Step 3: Re-authenticate

1. Revoke current access at https://myaccount.google.com/permissions
2. Clear browser cache
3. Connect to Google Classroom again
4. Accept ALL permissions

## If Email Access is Still Blocked

Use **Manual Matching** as a workaround:

1. Students are imported from Google Classroom (with names, without emails)
2. Admin manually matches each student to database records
3. System saves the mapping for future use

### Update the Matching Logic

We can modify the system to:
1. Try email matching first (if available)
2. Fall back to name matching (50-80% confidence)
3. Allow manual override for any student

This way, even without emails, you can still map students - it just requires more manual work initially.

## Long-term Solution

Implement a **Student Self-Service Portal**:
- Students log in with their Google account
- System captures their Google User ID automatically
- Students enter their student number
- Mapping is created automatically
- No email needed!

Would you like me to implement any of these alternative solutions?
