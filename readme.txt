=== Temporary User Access ===
Contributors: gasatrya
Tags: user management, expiry, temporary access, auto-delete, user cleanup
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

With this plugin, you can add temporary user access to WordPress accounts. Set expiry dates, enable auto-deletion, and keep your user database clean.

== Description ==

Temporary User Access extends WordPress user management with powerful expiration functionality. Perfect for temporary staff, contractors, trial users, or any scenario where user accounts should automatically expire.

**Key Features**:

- Set expiry dates for individual users
- Auto-delete expired users after grace period
- Content preservation - posts/comments reassigned to administrators
- Administrator accounts exempt from expiry
- Clean admin interface with expiry status columns
- Secure with proper nonce validation
- Timezone-aware expiry calculations
- Debug logging when WP_DEBUG is enabled

**Why Choose Temporary User Access?**

* **Automated Cleanup**: Automatically delete expired user accounts to keep your database clean
* **Content Safety**: Preserve all user content by reassigning posts and comments to administrators
* **Flexible Expiry**: Set different expiry dates for each user based on their specific needs
* **Security First**: Built with WordPress security best practices including nonce validation
* **Timezone Aware**: Accurate expiry calculations using WordPress timezone settings
* **Admin Friendly**: Clear status columns in users table show expiry status at a glance

**Perfect For**:

* Temporary staff and contractors
* Trial accounts and demo users
* Event or project-based access
* Membership sites with time-limited access
* Any scenario requiring automated user cleanup

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/temporary-user-access` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Users → Add New or edit existing users to set expiry dates
4. Enable auto-delete for users you want automatically removed after expiry

== Frequently Asked Questions ==

= How does auto-deletion work? =
Auto-deletion runs hourly via WP-Cron. Users with auto-delete enabled are deleted 7 days after their expiry date, giving you a grace period to review.

= What happens to user content when they're deleted? =
All posts and comments are reassigned to an administrator account to preserve your site's content.

= Can administrators be expired? =
No, administrator accounts are exempt from expiry checks for security reasons.

= How accurate are the expiry calculations? =
The plugin uses WordPress timezone settings for accurate expiry calculations, ensuring users expire at the intended local time.

== Screenshots ==

1. User profile with expiry settings
2. Users list with expiry status columns

== Changelog ==

= 1.0.0 =
* Initial release with core expiry functionality
* Auto-deletion with content preservation
* Admin interface with expiry status columns
* Timezone-aware expiry calculations
* Secure implementation with nonce validation
* Debug logging when WP_DEBUG is enabled
