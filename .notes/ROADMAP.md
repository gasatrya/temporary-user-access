# Roadmap & Future Features

This document outlines high-impact features to be implemented after the initial v1.0.0 release.

## v1.1: Communication & Awareness
- **User Expiry Notifications**: Automated email sent to users (e.g., 24 hours before) warning them of impending account expiration.
- **Admin Batch Summary**: A summary email sent to the site administrator after the auto-deletion CRON runs, listing which accounts were removed.

## v1.2: Efficiency & Scale
- **Bulk Actions Support**: Add "Set Expiration" and "Clear Expiration" to the bulk actions dropdown on the WordPress Users list (`users.php`).
- **Quick Edit Support**: Allow editing the expiry date directly from the users list without entering the full profile edit screen.

## v1.3: Integration & Extensibility
- **WooCommerce Support**: Automatic expiry for new customers and order-based triggers.
- **Custom Email Templates**: Allow administrators to customize the content of notification emails.

## Completed Features
- **v1.0.1: Plugin Settings Page**: A dedicated settings screen to configure global defaults:
    - Default Grace Period (configurable, defaulted to 2 days).
    - Fixed Core Loading (wp-load.php) violation.
    - Standardized plugin prefixing for all identifiers.

## Long-term Ideas
- **Expiry Roles**: Automatically change a user's role upon expiration (e.g., from 'Editor' to 'Subscriber') instead of blocking login entirely.
- **Extended Logging**: Integration with audit log plugins (like WP Activity Log) to track expiration events.
