# Google Classroom Re-Authentication Required

## Why Re-authentication is Needed

The Google Classroom API is currently returning `null` for student email addresses because your current authentication token doesn't have permission to access email addresses.

## Current Situation

When fetching students from Google Classroom:
```json
{
    "userId": "109026432821101673146",
    "name": "Mark Joshua Navida",
    "emailAddress": null  ← THIS IS THE PROBLEM
}
```

## Solution Steps

### 1. Update API Scopes (Already Done ✓)

The `.env` file has been updated with the new scopes:
- `classroom.profile.emails` - To read student email addresses
- `classroom.profile.photos` - To read student photos

### 2. Re-authenticate with Google

**Option A: Through the Web Interface**

1. Go to your application
2. Navigate to **Google Classroom** page
3. Click **"Disconnect"** or **"Logout from Google"**
4. Click **"Connect to Google Classroom"** again
5. **Accept all permissions** when Google asks (especially email access)
6. You should now be re-authenticated with the new scopes

**Option B: Revoke and Re-grant Access**

1. Go to https://myaccount.google.com/permissions
2. Find your application "Lorma Access+"
3. Click **"Remove Access"**
4. Go back to your application
5. Click **"Connect to Google Classroom"**
6. **Accept all permissions**

### 3. Verify Email Access

After re-authenticating, run this command to verify:

```bash
php artisan test:gcr-students 2
```

You should now see:
```
Student ID: 109026432821101673146
Name: Mark Joshua Navida
Email: markjoshua.navida@lorma.edu  ← Should show email now
```

### 4. Run Auto-Match Again

Once emails are visible:
1. Go to **Student Mapping** page
2. Select your subject
3. Click **"Auto Match Students"**
4. Students should now match automatically based on email!

## Important Notes

⚠️ **Why This Happens**

Google OAuth tokens are "scoped" - they only have access to what was requested when you first authenticated. When we add new scopes (like email access), you need to re-authenticate to get a new token with those permissions.

⚠️ **Student Privacy**

Some student accounts may still not share email addresses if:
- They're using restricted/supervised accounts
- Their organization has privacy settings enabled
- They haven't accepted the class invitation yet

## Alternative: Manual Matching

If some students still don't have email addresses after re-authentication, you can:

1. Manually add them to the `students` table with their email
2. Manually map them in the Student Mapping interface
3. Use student names as a fallback matching criterion

## Troubleshooting

**Problem: Still no emails after re-authentication**

Check the Google Workspace admin settings:
- Ensure students can share email addresses with third-party apps
- Check if there are any restrictions on the Google Classroom API

**Problem: Can't re-authenticate**

Clear your browser cache and cookies, then try again.

**Problem: "Access Denied" error**

Make sure your Google Cloud Project has the Classroom API enabled and the OAuth consent screen is properly configured.
