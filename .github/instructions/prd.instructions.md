---
applyTo: '**'
---
# Temporary User Access - Product Requirements Document

## Overview
WordPress plugin that extends user management with expiration functionality, allowing administrators to create time-limited user accounts that automatically deactivate and optionally delete after specified dates.

## Core Features

### 1. User Account Expiry Management
- **Expiry Date Field**: Date picker for setting account expiration
- **Account Status**: Dropdown with "Active" and "Expired" options (similar to post status)
- **Real-time Enforcement**: Users blocked immediately upon login if expired
- **Enhanced Authentication**: Users with expiry dates get 1-hour cookie expiration via `auth_cookie_expiration` filter (forced re-authentication)

### 2. Auto-Deletion System
- **Optional Auto-Delete**: Checkbox to enable automatic user deletion after expiry
- **Grace Period**: 7-day delay after expiry before deletion
- **Content Preservation**: User posts/comments reassigned to admin, only personal data deleted

### 3. WordPress Integration
- **No New Interface**: Uses existing WordPress User management screens
- **Add New User Form**: Expiry fields appear in standard "Add New User" page
- **Edit User Form**: Same fields available in user profile editing
- **User List Enhancement**: Custom column showing expiry status in Users table

### 4. Plugin Activation Behavior
- **Existing User Migration**: All existing users automatically assigned "Active" status on plugin activation
- **Backward Compatibility**: No disruption to current user workflows

## User Roles & Permissions

### Administrator Exclusion
- **Complete Exemption**: Administrators excluded from all expiry functionality
- **No Expiry Fields**: Admin users don't see/have expiry date or status fields
- **Normal Authentication**: Administrators maintain standard cookie expiration
- **Management Access**: Only administrators can set expiry for other users

### Other User Roles
- **Universal Application**: All non-admin roles (Editor, Author, Subscriber, etc.) can have expiry dates
- **Role-Agnostic**: Expiry system works regardless of user role

## Technical Implementation

### Authentication Flow
1. **Login Attempt**: Check if user has expiry date
2. **Expiry Check**: Compare current date with user's expiry date
3. **Status Verification**: Confirm account status is "Active"
4. **Cookie Management**: Apply 1-hour expiration for users with expiry dates
5. **Access Control**: Block login if expired or inactive

### Auto-Deletion Process
1. **Trigger**: Activated on admin Users page load or user login attempt
2. **Grace Period Check**: Only delete users expired for 7+ days
3. **Content Handling**: Reassign posts/comments to admin user
4. **User Removal**: Delete user account and associated meta data
5. **Logging**: Record deletion for audit purposes

## User Experience

### For Administrators
- **Intuitive Interface**: Familiar WordPress UI with additional fields
- **Clear Warnings**: Prominent notices about auto-deletion consequences
- **Flexible Management**: Can modify expiry dates and disable auto-deletion anytime
- **Status Overview**: Easy identification of expired/expiring users in Users list

### For End Users
- **Transparent Experience**: Normal WordPress experience until expiry
- **Forced Re-authentication**: More frequent login prompts (1-hour intervals) for expiry-enabled accounts
- **Clean Cutoff**: Immediate access blocking when account expires

## Field Specifications

### New User Fields
```
Account Expiry Date: [Date Picker] (Optional)
Account Status: [Dropdown: Active/Expired] (Default: Active)
☐ Auto-delete user after expiry (7 days grace period)
   ℹ️ User account will be permanently deleted. Posts will be reassigned to admin.
```

### User List Table
- **Expiry Status Column**: Shows "Active", "Expired", or "Expires: [date]"
- **Visual Indicators**: Different styling for expired users

## Success Criteria
1. **Zero Disruption**: Existing users continue normal workflow
2. **Intuitive Management**: Admins can easily set and modify expiry dates
3. **Reliable Enforcement**: Users cannot access expired accounts
4. **Clean Automation**: Auto-deletion works without manual intervention
5. **Content Preservation**: No accidental loss of valuable content

## Technical Requirements
- **WordPress Compatibility**: 5.0+
- **PHP Version**: 7.4+
- **Database**: Uses standard WordPress user meta tables
- **Hosting**: Compatible with shared hosting (no cron dependencies)
- **Performance**: Minimal impact on site speed

## Plugin Name Options
- **Primary**: Temporary User Access
- **Alternative**: Temporary Account Access

*Recommendation: "Temporary User Access" - more descriptive and WordPress-standard terminology*
