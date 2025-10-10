1. When expiry date is empty, auto-delete checkbox should be disabled.
2. When clearing expire date, on save, auto-delete should be unchecked.

### 1. Security Vulnerabilities

#### CSRF Protection Gaps
- **Location**: user-management.php, lines 137, 138, 166, 169, 170, 179
- **Issue**: The `tua_save_user_fields()` function directly accesses `$_POST` data without explicit nonce verification. While hooked to WordPress actions (`user_register`, `profile_update`) that typically include nonce checks, the function should include its own nonce verification for defense in depth.
- **Risk**: Potential CSRF attacks if WordPress core nonce checks are bypassed or fail.
- **Recommendation**: Add `wp_verify_nonce()` check using the appropriate nonce field (e.g., `update-user_{$user_id}` for profile updates).

#### XSS Vulnerabilities
- **Location**: user-management.php, lines 258, 262, 275
- **Issue**: Inline CSS styles in HTML output (e.g., `style="color: #dc3232; font-weight: bold;"`) are hardcoded and safe, but ensure all dynamic content is properly escaped.
- **Status**: No XSS vulnerabilities found - all outputs use `esc_html__()`, `esc_attr()`, or `esc_html()` appropriately.

#### SQL Injection Risks
- **Status**: No direct SQL queries found. All database interactions use WordPress APIs (`get_user_meta()`, `update_user_meta()`, `get_users()`) which are safe from SQL injection.

#### Other Security Issues
- **Race Conditions**: Mitigated in auto-deletion.php with transient-based locking (`tua_auto_delete_lock`) to prevent concurrent auto-deletion runs.
- **Memory Leaks**: No obvious memory leaks detected.

### 2. Logic Errors or Bugs

#### Incorrect Filter Hook Parameters
- **Location**: authentication.php, line 44
- **Issue**: `add_filter( 'authenticate', 'tua_authenticate_user', 30, 3 );` but the function `tua_authenticate_user()` only accepts 1 parameter (`$user`). The `authenticate` filter actually passes 3 parameters: `$user`, `$username`, `$password`.
- **Impact**: Function may not receive expected parameters, potentially causing undefined variable errors.
- **Fix**: Change to `add_filter( 'authenticate', 'tua_authenticate_user', 30, 1 );` or update function signature.

#### Date Calculation Inconsistency
- **Location**: user-management.php, lines 279-307
- **Issue**: The "Expires" column calculation has two different methods: one using `DateTime::diff()` and another using timestamp arithmetic. The fallback calculation may not account for timezone properly.
- **Impact**: Potential minor discrepancies in displayed expiry times.
- **Recommendation**: Standardize on `DateTime` objects with proper timezone handling.

#### Unused Function Parameters
- **Location**: user-management.php, line 130
- **Issue**: `tua_validate_user_fields()` parameters `$update` and `$user` are unused (PHPCS warning).
- **Impact**: Code clarity issue, no functional bug.

### 3. Performance Issues

#### Inefficient Auto-Deletion Query
- **Location**: auto-deletion.php, lines 60-75
- **Issue**: Queries all users with `auto_delete = '1'`, then loops through them calling `tua_should_auto_delete_user()` which performs additional meta queries for each user.
- **Impact**: Multiple database queries per user in the batch.
- **Recommendation**: Combine meta queries to filter users more efficiently in the initial `get_users()` call.

#### Admin Users List Performance
- **Location**: user-management.php, lines 249-307
- **Issue**: For each user in the admin users list, performs multiple `get_user_meta()` calls and date calculations.
- **Impact**: Minor performance hit on large user lists, but acceptable for admin interface.

### 4. Code Quality Problems

#### WordPress Coding Standards Violations (PHPCS)
- **Location**: helpers.php, lines 62-63
- **Issue**: Assignment alignment not consistent (auto-fixable).
- **Location**: helpers.php, line 81
- **Issue**: `error_log()` usage (acceptable for debug logging when conditional on `WP_DEBUG`).
- **Location**: user-management.php, lines 137-179
- **Issue**: Missing nonce verification (security issue).

#### Code Structure Issues
- **Location**: user-management.php
- **Issue**: `tua_show_expiry_columns()` function is quite long (~60 lines) and handles multiple responsibilities.
- **Recommendation**: Break into smaller, focused functions.

#### Hardcoded Values
- **Location**: temporary-user-access.php, lines 25-27
- **Issue**: `TUA_AUTO_DELETE_BATCH_SIZE` and `TUA_GRACE_PERIOD_DAYS` are hardcoded constants.
- **Recommendation**: Make configurable via settings page for better flexibility.

### 5. WordPress Best Practice Violations

#### Missing Settings API Usage
- **Issue**: Plugin uses hardcoded constants for configurable values (batch size, grace period) instead of WordPress Settings API.
- **Recommendation**: Implement a settings page using `register_setting()`, `add_settings_section()`, etc.

#### Cron Scheduling
- **Location**: temporary-user-access.php, lines 47-52
- **Issue**: Cron job scheduled on plugin activation but cleared on deactivation. No handling for existing schedules on reactivation.
- **Recommendation**: Check for existing schedules before adding new ones.

#### Plugin Metadata Inconsistency
- **Location**: composer.json, line 2
- **Issue**: Package name is `"gasatrya/admin-only"` but should match the plugin slug `"temporary-user-access"`.
- **Impact**: Confusion in package management.

### 6. Missing Error Handling

#### Auto-Deletion Error Handling
- **Location**: auto-deletion.php, lines 95-105
- **Issue**: `wp_delete_user()` failure is logged but batch continues. No admin notification of failures.
- **Recommendation**: Add admin notices or email alerts for deletion failures.

#### User Meta Operations
- **Location**: Throughout plugin
- **Issue**: `get_user_meta()` and `update_user_meta()` calls don't check for failures.
- **Impact**: Silent failures if database operations fail.
- **Recommendation**: Add error checking and logging for critical operations.

#### Date Parsing Failures
- **Location**: helpers.php, lines 75-85
- **Issue**: `tua_get_expiry_timestamp()` doesn't handle `DateTime::createFromFormat()` failures robustly.
- **Recommendation**: Add better error handling for invalid dates.

### 7. Potential Improvements

#### Feature Enhancements
- **Settings Page**: Add admin settings page for configuring batch size, grace period, and other options.
- **Bulk Actions**: Allow bulk setting of expiry dates in users list.
- **Email Notifications**: Notify users before expiry or admins of auto-deletions.
- **Audit Log**: More detailed logging of expiry events.

#### Code Improvements
- **Unit Tests**: Add comprehensive test coverage, especially for date calculations and edge cases.
- **Input Validation**: Enhance validation for edge cases (e.g., malformed dates).
- **Internationalization**: Ensure all strings are properly translatable.
- **Accessibility**: Review admin interface for WCAG compliance.

#### Performance Optimizations
- **Caching**: Cache user expiry status to reduce repeated calculations.
- **Database Indexes**: Consider recommending meta key indexes for large sites.
- **Lazy Loading**: Defer non-critical operations in admin interface.

#### Security Enhancements
- **Capability Checks**: Add more granular capability checks beyond `create_users`.
- **Rate Limiting**: Implement rate limiting for sensitive operations.
- **Data Sanitization**: Double-check all user inputs, even when using WordPress APIs.

Overall, the plugin demonstrates solid WordPress development practices with proper API usage and security considerations. The main concerns are the missing nonce verification and some code quality issues that can be addressed. The auto-deletion logic is well-implemented with appropriate safeguards.
