# ExpiryFlow – Temporary User Access & Expiry Manager

[![WordPress Requirements](https://img.shields.io/badge/WordPress-6.4%2B-0073AA.svg?style=flat-square&logo=wordpress)](https://wordpress.org/download/)
[![PHP Requirements](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg?style=flat-square&logo=php)](https://www.php.net/downloads)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-brightgreen.svg?style=flat-square)](LICENSE)

**ExpiryFlow** is a lightweight, security-focused WordPress plugin that automates the lifecycle of guest accounts. It allows site administrators to set expiration dates for users, ensuring that temporary access (for contractors, contributors, or trial users) is revoked automatically, keeping the site secure and the database clean.

## 🚀 Key Features

-   **Automated Offboarding** — Set an expiry date during user creation or editing.
-   **Manual Revocation** — Instantly kill access for any user by switching their status to "Expired".
-   **Strict Access Control** — Expired users are blocked from logging in immediately.
-   **Smart Cookie Expiration** — Temporary users get a forced 1-hour session limit to ensure frequent re-validation of their status.
-   **Auto-Deletion System** — Automatically remove expired users after a configurable grace period.
-   **Content Preservation** — Posts and comments from deleted users are safely reassigned to a primary administrator.
-   **Admin Immunity** — Built-in protection to prevent administrators from being accidentally expired or deleted.
-   **Modern Architecture** — Fully namespaced, object-oriented code following PHP 8 standards.

## 🛠 Tech Stack

-   **PHP:** 8.0+ (Namespaced, Singleton Pattern, Manual PSR-4 Autoloading).
-   **WordPress:** 6.4+ (Utilizes Metadata API, CRON API, and User API).
-   **JavaScript:** Vanilla jQuery (WordPress Admin standards).
-   **CSS:** Native CSS3 for color-coded status badges.
-   **Composer:** For development dependencies and quality control.

## 🏗 Architecture & Directory Structure

The plugin follows a modular, object-oriented structure for maximum maintainability.

-   `expiryflow.php` — Main entry point and manual PSR-4 autoloader.
-   `includes/` — Core logic and classes.
    -   `Core.php` — Singleton controller that initializes the plugin.
    -   `Admin/` — User Management UI and meta field handling.
    -   `Auth/` — Authentication filters and login enforcement.
    -   `Cron/` — Background tasks for auto-deletion.
    -   `Utils/` — Static helper methods and date calculations.
-   `assets/` — Admin-side CSS and JavaScript.
-   `languages/` — Translation files (.pot).

## 💻 Development Setup

### Local Environment
Requires a local WordPress installation.

```bash
# Clone the repository
git clone https://github.com/gasatrya/expiryflow.git

# Install dev dependencies (PHPCS / WordPress Coding Standards)
composer install
```

### Quality Control
This project strictly follows the **WordPress Coding Standards (WPCS)**.

```bash
# Run linting check
composer run phpcs

# Fix auto-fixable errors
composer run phpcbf
```

## 🗺 Roadmap

-   [ ] **v1.1:** Email notifications for users 24h before expiry.
-   [ ] **v1.2:** Bulk Actions support on the `users.php` screen.
-   [ ] **v1.3:** WooCommerce support (Automatic expiry for new customers/orders).
-   [x] **Done:** Settings page to customize grace period and batch sizes (Added in v1.0.2).

## 📄 License

This project is licensed under the GPL-2.0-or-later License.
