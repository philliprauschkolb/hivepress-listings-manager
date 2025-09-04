# HivePress User Listings Manager

A WordPress plugin that allows administrators to manually set the number of available listings for users from the WordPress admin area. This plugin integrates seamlessly with HivePress and provides multiple ways to manage user listing quotas.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Database Information](#database-information)
- [Security Features](#security-features)
- [Support](#support)

## Features

### 🎯 **Admin Menu Integration**
- Adds a dedicated "User Listings" submenu under the WordPress Users menu
- Centralized management interface for all user listings

### 👤 **User Profile Integration**
- Adds a listings count field to individual user profile pages
- Easy access when editing specific users

### 📊 **Bulk Management**
- Update multiple users simultaneously
- Select users via checkboxes and apply the same listing count to all

### ⚡ **Quick Updates**
- Individual quick update buttons for each user
- Real-time AJAX updates without page refreshes
- Instant feedback on successful updates

### 📋 **Users List Column**
- Adds "Available Listings" column to the main WordPress users list
- Quick overview of all user listing quotas

### 🔄 **AJAX-Powered Interface**
- Smooth, responsive user experience
- No page reloads required for updates
- Real-time success/error feedback

## Requirements

- WordPress 4.0 or higher
- HivePress plugin installed and active
- Administrator privileges to manage user listings
- PHP 5.6 or higher

## Installation

### Method 1: Manual Installation

1. **Download the Plugin**
   - Save the plugin code as `hivepress-listings-manager.php`

2. **Upload to WordPress**
   - Upload the file to your `/wp-content/plugins/` directory
   - Alternatively, create a new folder `/wp-content/plugins/hivepress-listings-manager/` and place the file inside

3. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "HivePress User Listings Manager" in the list
   - Click "Activate"

### Method 2: WordPress Admin Upload

1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin"
3. Choose the plugin file and upload
4. Activate the plugin

## Usage

### 🔧 Individual User Management

1. Navigate to **Users → All Users**
2. Click **Edit** on any user
3. Scroll down to find the **"HivePress Listings"** section
4. Set the desired **"Available Listings"** count
5. Click **"Update User"** to save changes

### 📦 Bulk Management

1. Go to **Users → User Listings**
2. **Select Users:**
   - Use individual checkboxes to select specific users
   - Use the header checkbox to select all users at once
3. **Set Listings Count:**
   - Enter the desired number in the "Set listings count" field
   - Click **"Apply to Selected Users"**
4. **Confirmation:**
   - A success message will show how many users were updated

### ⚡ Quick Updates

1. Navigate to **Users → User Listings**
2. **For Each User:**
   - Modify the number in the "Quick Update" input field
   - Click the **"Update"** button next to it
3. **Real-time Feedback:**
   - Button changes to "Updating..." during processing
   - Shows "Updated!" confirmation when successful
   - Current count updates immediately

### 📊 Users List Overview

1. Go to **Users → All Users**
2. View the **"Available Listings"** column for quick overview
3. See all user listing quotas at a glance

## Database Information

### Storage Details
- **Table:** `wp_comments`
- **Column:** `comment_karma`
- **Data Type:** Integer

### How It Works
- The plugin stores listing counts in the `comment_karma` column of the `wp_comments` table
- For users without existing records, new entries are created with type `hivepress_listings`
- For users with existing records, the `comment_karma` value is updated
- All database operations use WordPress's built-in security functions

### Database Structure
```sql
-- Example of how data is stored
INSERT INTO wp_comments (
    comment_post_ID,
    comment_author,
    comment_author_email,
    comment_date,
    comment_date_gmt,
    comment_content,
    comment_approved,
    comment_type,
    user_id,
    comment_karma
) VALUES (
    0,
    'User Display Name',
    'user@example.com',
    '2024-01-01 12:00:00',
    '2024-01-01 12:00:00',
    'HivePress listings count record',
    '1',
    'hivepress_listings',
    123,  -- User ID
    5     -- Available listings count
);
```

## Security Features

### 🔐 **Access Control**
- Only users with `manage_options` capability (administrators) can modify listings
- Capability checks on all sensitive operations

### 🛡️ **Nonce Verification**
- All form submissions protected with WordPress nonces
- AJAX requests include security tokens
- Prevents CSRF attacks

### 🔒 **Data Sanitization**
- All user inputs are sanitized and validated
- SQL queries use WordPress prepared statements
- XSS protection on all outputs

### ✅ **Input Validation**
- Listing counts must be non-negative integers
- User IDs are validated before database operations
- Error handling for invalid data

## Troubleshooting

### Common Issues

**Plugin not appearing in menu:**
- Ensure you have administrator privileges
- Check that the plugin is activated
- Verify HivePress is installed and active

**Database errors:**
- Check WordPress database connection
- Ensure proper file permissions
- Verify database table structure

**AJAX updates not working:**
- Check browser console for JavaScript errors
- Ensure jQuery is loaded properly
- Verify admin-ajax.php is accessible

### Debug Mode
Enable WordPress debug mode by adding to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Changelog

### Version 1.0.0
- Initial release
- Admin menu integration
- User profile fields
- Bulk update functionality
- Quick AJAX updates
- Users list column integration
- Complete security implementation

## Support

### Getting Help
- Check the WordPress admin for error messages
- Review the browser console for JavaScript errors
- Enable WordPress debug logging for detailed error information

### Feature Requests
This plugin provides a solid foundation for managing HivePress user listings. Additional features can be added based on specific requirements.

## License

This plugin is licensed under the GPL v2 or later.

## Author

Created for HivePress integration and user listing management.

---

**Note:** This plugin directly modifies database records. Always backup your database before installation and testing.