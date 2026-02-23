# Roadmap & Future Features

This document outlines high-impact features to be implemented after the initial v1.0.0 release.

## v1.1: Communication & Awareness
- **User Expiry Notifications**: Automated email sent to users (e.g., 24 hours before) warning them of impending account expiration.
- **Admin Batch Summary**: A summary email sent to the site administrator after the auto-deletion CRON runs, listing which accounts were removed.

## v1.2: Efficiency & Scale
- **Bulk Actions Support**: Add "Set Expiration" and "Clear Expiration" to the bulk actions dropdown on the WordPress Users list (`users.php`).
- **Quick Edit Support**: Allow editing the expiry date directly from the users list without entering the full profile edit screen.

## v1.3: Customization
- **Plugin Settings Page**: A dedicated settings screen to configure global defaults:
    - Default Grace Period (currently hardcoded to 7 days).
    - Auto-deletion Batch Size (currently hardcoded to 50).
    - Custom Email Templates for notifications.

## Long-term Ideas
- **Expiry Roles**: Automatically change a user's role upon expiration (e.g., from 'Editor' to 'Subscriber') instead of blocking login entirely.
- **Extended Logging**: Integration with audit log plugins (like WP Activity Log) to track expiration events.
