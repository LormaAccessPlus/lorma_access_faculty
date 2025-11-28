# Grade Save Network Error - Troubleshooting Guide

## Issue
When inputting activity grades, a "Failed to save grade. Network error - please check your connection." message appears.

## Improvements Made

### 1. Enhanced Error Detection
- Added CSRF token validation before making the request
- Added 30-second timeout to prevent indefinite hanging
- Improved error messages to distinguish between different failure types

### 2. Better Error Messages
Now shows specific errors for:
- **Missing CSRF token**: "Security token missing. Please refresh the page."
- **Timeout**: "Request timeout - server took too long to respond."
- **Network error**: "Network error - please check your connection or try refreshing the page."
- **HTTP errors**: Shows the actual HTTP status code and message

### 3. Enhanced Logging
Added console logging for:
- CSRF token presence and value
- Request URL
- Request payload
- Response status
- Error details

## Troubleshooting Steps

### Step 1: Check Browser Console
1. Open browser Developer Tools (F12)
2. Go to Console tab
3. Try saving a grade
4. Look for error messages that show:
   - "CSRF Token exists: true/false"
   - "CSRF Token value: ..."
   - Any error messages

### Step 2: Check Network Tab
1. Open Developer Tools (F12)
2. Go to Network tab
3. Try saving a grade
4. Look for the POST request to `/grades/update`
5. Check:
   - **Status code**: Should be 200
   - **Request Headers**: Should include X-CSRF-TOKEN
   - **Request Payload**: Should have student_mapping_id, activity_id, score
   - **Response**: Should be JSON with success: true

### Step 3: Check Laravel Logs
Look at `storage/logs/laravel.log` for any server-side errors

### Step 4: Common Issues and Solutions

#### Issue: CSRF Token Missing
**Symptoms**: Error says "Security token missing"
**Solution**: Refresh the page. If persists, check that `<meta name="csrf-token">` exists in the page source.

#### Issue: 419 Page Expired
**Symptoms**: Network tab shows 419 status code
**Solution**: Session expired. Refresh the page or log in again.

#### Issue: 401 Unauthorized
**Symptoms**: Network tab shows 401 status code
**Solution**: You're not logged in. Log in again.

#### Issue: 422 Validation Error
**Symptoms**: Network tab shows 422 status code
**Solution**: Check the response body for validation errors. Usually means:
- Student mapping ID doesn't exist
- Activity ID doesn't exist
- Score is invalid (negative or exceeds max)

#### Issue: 500 Server Error
**Symptoms**: Network tab shows 500 status code
**Solution**: Check Laravel logs at `storage/logs/laravel.log` for the actual error.

#### Issue: Request Timeout
**Symptoms**: Error says "Request timeout"
**Solution**: Server is slow or unresponsive. Check:
- Database connection
- Server load
- Network connectivity

#### Issue: Failed to Fetch / TypeError
**Symptoms**: Error says "Network error"
**Possible causes**:
1. **No internet connection**: Check your network
2. **Server is down**: Check if the Laravel server is running
3. **CORS issue**: Check browser console for CORS errors
4. **Browser extension blocking**: Try disabling ad blockers or privacy extensions
5. **Firewall**: Check if firewall is blocking the request

### Step 5: Quick Fixes

#### Fix 1: Refresh the Page
Most issues are resolved by refreshing the page (Ctrl+F5 or Cmd+Shift+R)

#### Fix 2: Clear Browser Cache
1. Open Developer Tools (F12)
2. Right-click the refresh button
3. Select "Empty Cache and Hard Reload"

#### Fix 3: Check Server is Running
Make sure your Laravel development server is running:
```bash
php artisan serve
```

#### Fix 4: Check Database Connection
```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

## Testing the Fix

1. Open the term grades page
2. Open browser console (F12)
3. Enter a grade in any activity field
4. Press Tab or click outside the field
5. Check console for logs
6. Check Network tab for the request
7. Grade should save successfully

## If Issue Persists

If you're still seeing network errors after trying all the above:

1. **Collect information**:
   - Browser console logs
   - Network tab screenshot
   - Laravel log entries
   - Steps to reproduce

2. **Check specific scenarios**:
   - Does it happen for all students or just one?
   - Does it happen for all activities or just one?
   - Does it happen on page load or after some time?
   - Does it work in a different browser?

3. **Verify data integrity**:
   ```bash
   php artisan tinker
   >>> App\Models\StudentMapping::find(YOUR_STUDENT_MAPPING_ID)
   >>> App\Models\Activity::find(YOUR_ACTIVITY_ID)
   ```

The enhanced error handling should now provide much clearer information about what's actually failing.
