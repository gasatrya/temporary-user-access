## Quick orientation for AI coding agents

This repository is a WordPress plugin named `Temporary User Access`. The code is procedural PHP split across a small set of files under the plugin root and `includes/`.

High-level architecture
- Entry point: `temporary-user-access.php` — defines constants, activation hooks, loads textdomain and includes three functional files.
- Includes:
  - `includes/authentication.php` — login-time checks, `authenticate` filter, `auth_cookie_expiration` filter.
  - `includes/user-management.php` — admin UI fields, user meta handling, users list columns, enqueued admin assets.
  - `includes/auto-deletion.php` — auto-delete logic, transient-based throttling, `admin_init` and `wp_login` triggers.

Conventions & patterns to follow
- Naming: all plugin functions and constants use `tua_` / `TUA_` prefixes (stick to this). Examples: `tua_is_user_expired`, `TUA_USER_EXPIRY_DATE`.
- User data: plugin stores settings in user meta keys defined in the main file (e.g. `_user_expiry_date`, `_user_account_status`, `_user_auto_delete`). Prefer WP APIs (`get_user_meta`, `update_user_meta`) not direct SQL.
- Admin exemptions: Administrator accounts are intentionally exempt from expiry checks; many functions early-return for admins (`tua_is_user_admin`). Do not remove or change this behavior unless explicitly requested.
- Hooks: Work with existing hooks listed in files (e.g. `authenticate`, `auth_cookie_expiration`, `user_new_form`, `show_user_profile`, `admin_init`, `wp_login`, `manage_users_columns`, `pre_get_users`). Add hooks consistently and document them.
- Security: code already validates WP nonces for profile updates (`update-user_{ID}`). Preserve nonce checks and sanitization (`sanitize_text_field`, `wp_unslash`) when modifying saving/validation logic.

Key implementation details to be aware of
- Auto-deletion throttling: `auto-deletion.php` uses `set_transient( 'tua_last_auto_delete_check', time(), HOUR_IN_SECONDS )` to run once per hour and processes up to 50 users per batch.
- Grace period: auto-delete enforces a 7-day grace period after expiry (timestamp + 7 * DAY_IN_SECONDS).
- Content preservation: deletion reassigns posts/comments using `wp_delete_user( $user_id, $reassign_id )` — reassign id is the first administrator found or `1` fallback.
- Logging: Debug logging to `wp-content/debug.log` when WP_DEBUG is enabled (no persistent storage)

Developer workflows & commands
- Linting & standards (provided in `composer.json` scripts):
  - `composer run lint` — run PHPCS against project
  - `composer run lint:fix` — run PHPCBF to auto-fix
  - `composer run test:standards` — phpcsn summary report
  - `composer run zip` — create plugin zip archive
- Coding standard: project includes WP Coding Standards in `vendor/` and a `phpcs.xml.dist`. Run `composer run lint` before committing changes.

Files and locations to inspect for related changes
- Translation string files: `languages/` and `load_plugin_textdomain` call in the main plugin file.
- Assets (admin UI): `assets/jquery-ui.css` and `admin_enqueue_scripts` in `user-management.php`.
- PHPCS config: `phpcs.xml.dist` and `composer.json` require-dev entries — adhere to WordPress coding standards.

Common adjustments and examples
- Adding a settings option: follow the prefix and use `add_option` / `update_option` and register settings if exposing in admin.
- Adding a new user meta: define a new `TUA_` constant in `temporary-user-access.php` and use `update_user_meta` / `get_user_meta` everywhere.

Quick pitfalls to avoid
- Don’t change admin-exemption logic without a clear requirement.
- Avoid long-running loops on page loads; auto-deletion is intentionally batched and throttled.
- When manipulating user data, respect nonces and capabilities checks (`current_user_can`).

If anything here is unclear or you want additional examples (unit test skeletons, example patch for a hook, or how to run PHPCS locally on Windows PowerShell), ask and I'll iterate.
