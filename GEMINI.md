# GEMINI.md - Temporary User Access

This file provides context and instructions for AI agents working on the **Temporary User Access** WordPress plugin.

## Project Overview

**Temporary User Access** is a security-focused WordPress plugin designed to automate the lifecycle of temporary guest accounts. It allows administrators to set expiration dates for users, ensuring access is revoked automatically.

### Key Technologies
- **Language:** PHP 8.0+ (Namespaced, OOP).
- **Platform:** WordPress 6.4+.
- **Coding Standards:** WordPress Coding Standards (WPCS).
- **Dev Tools:** Composer, PHPCS.

### Architecture
- **Entry Point:** `temporary-user-access.php` (initializes autoloader and Core class).
- **Core Singleton:** `TemporaryUserAccess\Core` in `includes/Core.php` manages component initialization and constants.
- **Namespaces:** `TemporaryUserAccess` (PSR-4 mapped to `includes/`).
- **Data Storage:** Uses WordPress User Meta (`_user_expiry_date`, `_user_account_status`, `_user_auto_delete`).
- **Security:** Strict blocking of expired users via the `authenticate` filter; administrators are immune to expiration logic.

## Building and Running

This is a WordPress plugin and requires a WordPress environment to run.

### Setup
```bash
# Install development dependencies
composer install
```

### Quality Control (Linting)
The project strictly follows WordPress Coding Standards (WPCS).
```bash
# Run linting check
composer run phpcs

# Auto-fix linting issues
composer run phpcbf
```

### Packaging
```bash
# Create a distributable zip file
composer run zip
```

## Development Conventions

### PHP Style
- Use PHP 8.0+ features (constructor promotion, union types where applicable, though WPCS might prefer older syntax in some areas).
- Follow **WordPress Coding Standards**. Pay attention to:
    - Yoda conditions (`if ( true === $value )`).
    - Naming conventions (snake_case for functions/variables, PascalCase for classes).
    - File naming (Standard WPCS expects `class-name.php`, but this project uses PSR-4 style `ClassName.php`).
- Always use `ABSPATH` checks at the top of PHP files.

### Security
- Use `Helpers::is_user_admin()` to protect administrators from expiration/deletion logic.
- Always sanitize inputs and escape outputs using WordPress functions (`sanitize_text_field`, `esc_html`, etc.).

### User Meta Keys
Use the constants defined in `Core.php`:
- `TEMPUSAC_USER_EXPIRY_DATE`: `_user_expiry_date`
- `TEMPUSAC_USER_ACCOUNT_STATUS`: `_user_account_status`
- `TEMPUSAC_USER_AUTO_DELETE`: `_user_auto_delete`

### Statuses
- `active`: Account is functional.
- `expired`: Account is blocked from login.

## Directory Structure
- `assets/`: CSS/JS for admin screens.
- `includes/`: PSR-4 source code.
    - `Admin/`: UI and user profile enhancements.
    - `Auth/`: Authentication and login blocking logic.
    - `Cron/`: Background tasks for auto-deletion.
    - `Utils/`: Static helpers and shared logic.
- `languages/`: Translation files.
- `.notes/`: Documentation and roadmap.

## Commit Message Convention

All commits must follow the **Conventional Commits** specification. Use the following format:
`<type>[optional scope]: <description>`

**Common Types:**
- `feat`: A new feature.
- `fix`: A bug fix.
- `docs`: Documentation only changes.
- `style`: Changes that do not affect the meaning of the code (white-space, formatting, missing semi-colons, etc).
- `refactor`: A code change that neither fixes a bug nor adds a feature.
- `perf`: A code change that improves performance.
- `test`: Adding missing tests or correcting existing tests.
- `chore`: Changes to the build process or auxiliary tools and libraries such as documentation generation.
