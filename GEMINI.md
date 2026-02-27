# GEMINI.md - GateFlow

This file provides context and instructions for AI agents working on the **GateFlow** WordPress plugin.

## Project Overview

**GateFlow** is a security-focused WordPress plugin designed to automate the lifecycle of temporary guest accounts. It allows administrators to set expiration dates for users, ensuring access is revoked automatically.

### Key Technologies
- **Language:** PHP 8.0+ (Namespaced, OOP).
- **Platform:** WordPress 6.9+.
- **Coding Standards:** WordPress Coding Standards (WPCS).
- **Dev Tools:** Composer, PHPCS.

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
