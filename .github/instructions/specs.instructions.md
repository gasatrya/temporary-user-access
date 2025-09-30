---
applyTo: '**'
---
# WordPress Plugin Specifications

## Environment
- **WordPress**: 6.8.2
- **PHP**: 8.2.27 (strict typing, return types)
- **MySQL**: 8.0

## Standards
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)
- Security & performance best practices
- WordPress Settings API for admin UI

## Development Workflow
1. **Task Management**: Break features into small, manageable tasks - **execute ONE task at a time**
2. **Code Quality**: 
   - Test new functions immediately
   - Run PHPCS/PHPCBF for code standards
   - Fix issues before proceeding
3. **Testing Cycle**:
   - Run all tests after changes
   - Fix broken tests immediately
   - Only proceed when tests pass
4. **Review Process**:
   - Request review after completing task
   - Fix issues if not approved
   - Repeat until approved
5. **Version Control**:
   - Stage changes after approval
   - Commit with conventional commit messages
   - Ask before pushing to remote
   - **STOP after each task completion for next instruction**

## AI Assistant Instructions
- Use context7 tool for latest coding standards
- Always validate code against WordPress standards
- Prioritize security and performance
- Build Settings UI first, then backend functionality
- Wait for approval before proceeding to next task
