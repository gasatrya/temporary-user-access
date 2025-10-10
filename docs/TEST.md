Ran terminal command: composer run lint

## 🧪 Comprehensive Testing Guide for Temporary User Access Plugin

### **Prerequisites**
1. **Enable Debug Logging**: Add to `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Set Up Test Environment**:
   - Create a local WordPress installation
   - Install and activate the plugin
   - Create test users (admin + regular users)

---

## **1. User Creation & Expiry Setup Testing**

### **Test Case 1.1: Create User with Expiry Date**
```bash
# Via WordPress Admin
1. Go to Users → Add New
2. Fill in user details
3. Set "Account Expiry Date" to tomorrow's date
4. Check "Auto-delete user after expiry (7 days grace period)"
5. Click "Add New User"
```

**Expected Results:**
- ✅ User created successfully
- ✅ Expiry date saved in user meta
- ✅ Auto-delete setting saved
- ✅ User appears in Users list with expiry status

### **Test Case 1.2: Date Validation**
```bash
# Try to set expiry date in the past
1. Edit user profile
2. Set expiry date to yesterday
3. Save changes
```

**Expected Results:**
- ❌ Error message: "Please enter a valid expiry date that is in the future"
- ✅ Date not saved

---

## **2. Authentication & Login Testing**

### **Test Case 2.1: Active User Login**
```bash
1. Create user with future expiry date
2. Log out of admin
3. Try to log in as that user
```

**Expected Results:**
- ✅ Login successful
- ✅ User redirected to dashboard
- ✅ Check `wp-content/debug.log` for any auth-related logs

### **Test Case 2.2: Expired User Login**
```bash
1. Create user with yesterday's expiry date
2. Try to log in as that user
```

**Expected Results:**
- ❌ Login blocked with error: "Your account has expired. Please contact the administrator"
- ✅ User cannot access dashboard

### **Test Case 2.3: Admin Exemption**
```bash
1. Set expiry date on admin user
2. Try to log in as admin
```

**Expected Results:**
- ✅ Admin login works normally (admins are exempt)
- ✅ Admin can still access all features

---

## **3. Auto-Deletion Testing**

### **Test Case 3.1: Manual Auto-Deletion Trigger**
```bash
# Set up test user
1. Create user with auto-delete enabled
2. Set expiry date to yesterday
3. Manually trigger deletion via code or wait for cron

# Via WP-CLI (if available):
wp cron event run tua_auto_delete_cron

# Or via direct function call in theme functions.php:
add_action('init', function() {
    if (isset($_GET['test_deletion'])) {
        tua_auto_delete_expired_users();
    }
});
// Then visit: yoursite.com/?test_deletion=1
```

**Expected Results:**
- ✅ User deleted after grace period (7 days past expiry)
- ✅ User's posts/pages reassigned to admin
- ✅ Debug log shows deletion success/failure

### **Test Case 3.2: Grace Period Testing**
```bash
1. Create user with expiry date = today - 6 days (within grace period)
2. Run auto-deletion
```

**Expected Results:**
- ✅ User NOT deleted (still within 7-day grace period)
- ✅ Debug log shows user skipped

---

## **4. Admin Interface Testing**

### **Test Case 4.1: User Profile Editing**
```bash
1. Go to Users → All Users
2. Click "Edit" on a test user
3. Modify expiry date and auto-delete settings
4. Click "Update User"
```

**Expected Results:**
- ✅ Settings saved successfully
- ✅ JavaScript "Clear" button works
- ✅ Status message appears when clearing expiry

### **Test Case 4.2: Users List Columns**
```bash
1. Go to Users → All Users
2. Check the "Status" and "Expires" columns
```

**Expected Results:**
- ✅ "Status" column shows "Active"/"Expired" with appropriate colors
- ✅ "Expires" column shows expiry date or "Never"
- ✅ Admin users show shield icon (exempt from expiry)

---

## **5. Error Handling & Logging Testing**

### **Test Case 5.1: Debug Logging**
```bash
1. Enable WP_DEBUG and WP_DEBUG_LOG
2. Perform various operations (create, delete, login attempts)
3. Check wp-content/debug.log
```

**Expected Results:**
- ✅ All plugin actions logged with "[Temporary User Access]" prefix
- ✅ Context data included (user IDs, timestamps, etc.)
- ✅ No PHP errors or warnings

### **Test Case 5.2: Error Scenarios**
```bash
# Test deletion failure scenarios
1. Create user with auto-delete enabled
2. Manually delete the admin user (so no reassignment target exists)
3. Run auto-deletion
```

**Expected Results:**
- ✅ Deletion fails gracefully
- ✅ Error logged with details
- ✅ Process continues with other users

---

## **6. Internationalization Testing**

### **Test Case 6.1: String Translation**
```bash
# Install a translation plugin or manually test
1. Check that all user-facing text appears in English
2. Verify text domain consistency
```

**Expected Results:**
- ✅ All strings use correct text domain `'temporary-user-access'`
- ✅ JavaScript strings properly localized
- ✅ No hardcoded English text

---

## **7. Performance & Security Testing**

### **Test Case 7.1: Batch Processing**
```bash
1. Create 100+ test users with expiry dates
2. Run auto-deletion cron
3. Monitor execution time and memory usage
```

**Expected Results:**
- ✅ Processes users in batches of 50 (configurable)
- ✅ No memory exhaustion
- ✅ Reasonable execution time

### **Test Case 7.2: Security Testing**
```bash
# Test direct file access
1. Try to access plugin files directly via URL
2. Verify include files are protected
```

**Expected Results:**
- ✅ Direct access blocked (ABSPATH check)
- ✅ No sensitive information exposed

---

## **8. Automated Testing Script**

Create a test script in your theme's `functions.php` for bulk testing:

```php
// Add to functions.php for testing
add_action('init', function() {
    if (!current_user_can('manage_options') || !isset($_GET['test_tua'])) return;
    
    // Test user creation
    $user_id = wp_create_user('test_user_' . time(), 'password123', 'test@example.com');
    update_user_meta($user_id, 'TUA_USER_EXPIRY_DATE', date('Y-m-d', strtotime('+1 day')));
    update_user_meta($user_id, 'TUA_USER_AUTO_DELETE', '1');
    
    echo "Test user created with ID: $user_id<br>";
    
    // Test expiry check
    $is_expired = tua_is_user_expired($user_id);
    echo "User expired status: " . ($is_expired ? 'true' : 'false') . "<br>";
    
    // Test auto-deletion check
    $should_delete = tua_should_auto_delete_user($user_id);
    echo "Should auto-delete: " . ($should_delete ? 'true' : 'false') . "<br>";
});
```

Then visit: `yoursite.com/?test_tua=1`

---

## **9. Testing Checklist**

- [x] User creation with expiry dates
- [x] Date validation (past dates rejected)
- [x] Active user login works
- [x] Expired user login blocked
- [x] Admin users exempt from expiry
- [x] Auto-deletion respects grace period
- [x] Content properly reassigned on deletion
- [x] Admin interface functions correctly
- [x] JavaScript features work
- [x] Debug logging captures all actions
- [x] Error scenarios handled gracefully
- [x] No PHP errors or warnings
- [ ] Performance acceptable with large user sets
- [x] Security measures in place

---

## **10. Troubleshooting**

**If tests fail:**
1. **Check debug.log** for error details
2. **Verify cron is running**: `wp cron event list`
3. **Check user meta**: Use a database tool to inspect `wp_usermeta`
4. **Test manually**: Call functions directly in a test script

**Common Issues:**
- **Cron not running**: Set up proper cron or use WP-CLI
- **Timezone issues**: Check WordPress timezone settings
- **Permission errors**: Ensure proper user capabilities
- **Database issues**: Check for corrupted user meta

This comprehensive testing approach will ensure all functionality works correctly and the plugin is production-ready! 🧪✨
