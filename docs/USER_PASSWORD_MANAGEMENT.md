# User Password Management Guide

**Created**: December 10, 2025
**Purpose**: Managing user passwords for the TDS System

---

## Quick Start: Set Password for Akash

### Option 1: Using Web Interface (Recommended)
1. Go to: `https://www.bombayengg.net/tds/admin/set_password.php`
2. Select User: **Akash Akhade** from dropdown
3. Enter New Password: (minimum 8 characters)
4. Confirm Password: (re-enter the password)
5. Click: **Set Password**
6. Success message appears

**✅ Akash can now login with:**
- Email: `akash.tdf@gmail.com`
- Password: (the one you just set)

---

### Option 2: Using Command-Line (Fastest)
```bash
cd /home/bombayengg/public_html/tds
php set_password_cli.php akash.tdf@gmail.com MySecurePassword123
```

**Output** (if successful):
```
✓ Success!
------------------------------------------------------------
User:     Akash Akhade
Email:    akash.tdf@gmail.com
Password: MySecurePassword123
------------------------------------------------------------

The user can now login with:
  Email:    akash.tdf@gmail.com
  Password: (the password you just set)
```

---

## Password Requirements

✅ **What's Required**:
- Minimum 8 characters long
- Can contain letters (a-z, A-Z)
- Can contain numbers (0-9)
- Can contain special symbols (!@#$%^&*, etc)
- No spaces in password

❌ **What's NOT Required**:
- Uppercase letters (but recommended)
- Numbers (but recommended)
- Special characters (but recommended)

**Strong Password Examples**:
- MySecurePass123
- Akash@2025TDS
- BombayEng2024!
- Welcome#123TDS

---

## Database Structure

### Users Table
```sql
CREATE TABLE users (
  id                INT PRIMARY KEY AUTO_INCREMENT,
  name              VARCHAR(120) NOT NULL,
  email             VARCHAR(190) NOT NULL UNIQUE,
  password_hash     VARCHAR(255),
  role              ENUM('owner', 'staff'),
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Current Users
```
ID | Name            | Email                | Password
----|-----------------|----------------------|-----------
1  | Admin           | admin@example.com    | (set)
2  | Akash Akhade    | akash.tdf@gmail.com  | (needs to be set)
```

---

## Security Details

### Password Hashing
- Uses: PHP's `password_hash()` with DEFAULT algorithm (bcrypt)
- Hashing: One-way encryption (cannot be reversed)
- Verification: Uses `password_verify()` during login
- Storage: 255 character hash in database (not plaintext)

### Authentication Flow
```
User enters email + password at login
    ↓
System queries users table for email
    ↓
System calls password_verify(entered_password, stored_hash)
    ↓
If match: Create session and allow login
If no match: Show "Invalid credentials"
```

### Security Best Practices
1. ✅ Passwords never stored in plaintext
2. ✅ Each password hash is unique (bcrypt salt)
3. ✅ Password reset requires access to admin panel
4. ✅ Session timeout for security
5. ✅ HTTPS recommended for login

---

## Using the Web Interface

### Step-by-Step Instructions

**Step 1: Access Password Management**
```
URL: https://www.bombayengg.net/tds/admin/set_password.php
(Requires admin login)
```

**Step 2: Select User**
- Dropdown shows all registered users
- Currently shows:
  - Admin
  - Akash Akhade ← Select this one
- Click to select

**Step 3: Enter Password**
- First field: "New Password"
- Second field: "Confirm Password"
- Both must match

**Step 4: Submit**
- Click "Set Password" button
- If successful: Green success message
- If error: Red error message

**Step 5: User Can Login**
- User opens: `https://www.bombayengg.net/tds/admin/login.php`
- Email: `akash.tdf@gmail.com`
- Password: (the one you just set)
- Click "Login"

---

## Using the Command-Line Tool

### Quick Reference

**List all users**:
```bash
php set_password_cli.php --list
```

**Set password for specific user**:
```bash
php set_password_cli.php <email> <password>
```

**Examples**:
```bash
# Set password for Akash
php set_password_cli.php akash.tdf@gmail.com SecurePass2025!

# Set password for another user
php set_password_cli.php newuser@example.com MyPassword123

# Show available users
php set_password_cli.php --list

# Show help
php set_password_cli.php --help
```

**Terminal Output Example**:
```
$ php set_password_cli.php akash.tdf@gmail.com MyPassword123

✓ Success!
------------------------------------------------------------
User:     Akash Akhade
Email:    akash.tdf@gmail.com
Password: MyPassword123
------------------------------------------------------------

The user can now login with:
  Email:    akash.tdf@gmail.com
  Password: (the password you just set)
```

---

## Adding New Users

### Step 1: Add User to Database
```bash
php -r "
require_once 'tds/lib/db.php';

\$name = 'New User Name';
\$email = 'newuser@example.com';
\$role = 'staff';

\$stmt = \$pdo->prepare('INSERT INTO users (name, email, role) VALUES (?, ?, ?)');
\$stmt->execute([\$name, \$email, \$role]);

echo 'User created successfully';
"
```

### Step 2: Set Password
```bash
php set_password_cli.php newuser@example.com SomePassword123
```

### Step 3: User Can Login
User can now login at `/tds/admin/login.php` with:
- Email: `newuser@example.com`
- Password: `SomePassword123`

---

## Troubleshooting

### Problem: "User not found with email..."

**Cause**: Email address doesn't exist in database

**Solution**:
```bash
# List all users
php set_password_cli.php --list

# Use correct email from list
```

### Problem: "Password must be at least 8 characters"

**Cause**: Password is too short

**Solution**: Use at least 8 characters:
```bash
# ❌ Too short (7 chars)
php set_password_cli.php user@example.com Pass123

# ✅ Good (8 chars)
php set_password_cli.php user@example.com Pass1234

# ✅ Better (12 chars)
php set_password_cli.php user@example.com MyPassword123
```

### Problem: "Permission denied" when accessing set_password.php

**Cause**: Not logged in as admin

**Solution**:
1. Login first at `/tds/admin/login.php`
2. Use admin credentials
3. Then access `/tds/admin/set_password.php`

### Problem: "Failed to update password"

**Cause**: Database connection error or permission issue

**Solution**:
1. Check database connection
2. Ensure `users` table exists
3. Try again or contact support

---

## Login Process for Users

### How Akash Will Login

**URL**: `https://www.bombayengg.net/tds/admin/login.php`

**Form Fields**:
1. Email: `akash.tdf@gmail.com`
2. Password: (the password you set)

**What Happens**:
1. System verifies email exists in database
2. System verifies password matches hash
3. If valid: Session created, redirect to dashboard
4. If invalid: "Invalid credentials" message shown

**After Login**:
- Akash can access all pages under `/tds/admin/`
- Session lasts 24 hours (configurable)
- Click logout to end session

---

## Security Recommendations

### For System Administrators
1. ✅ Use strong passwords (12+ characters)
2. ✅ Don't share passwords via email
3. ✅ Only set passwords after verifying user identity
4. ✅ Recommend users change password on first login
5. ✅ Monitor failed login attempts

### For Users
1. ✅ Use unique password (not used elsewhere)
2. ✅ Don't share password with anyone
3. ✅ Logout after using system
4. ✅ Don't use password in public/shared devices
5. ✅ Report suspicious activity to admin

### System Level
1. ✅ Passwords encrypted in database (bcrypt)
2. ✅ HTTPS recommended for all login traffic
3. ✅ Session tokens used after login
4. ✅ Timeout mechanism for inactive sessions
5. ✅ Activity logs for password changes

---

## Changing Passwords

### If User Forgets Password

**Admin Action**:
```bash
# Reset user's password
php set_password_cli.php akash.tdf@gmail.com NewTemporaryPass123
```

**Tell User**: "Your password has been reset to: NewTemporaryPass123"

**User Should**: Change password immediately after first login

### If User Wants to Change Their Own Password

**Step 1**: User logs in to system

**Step 2**: Navigate to: `/tds/api/change_password.php`

**Step 3**: Enter:
- Current Password: (their existing password)
- New Password: (their new password)
- Confirm Password: (re-enter new password)

**Step 4**: Click Change Password

---

## Technical Details

### Password Hashing Algorithm
- **Type**: bcrypt (via PHP's `PASSWORD_DEFAULT`)
- **Cost**: 10 (default)
- **Hash Length**: 60 characters (in password_hash column)
- **Example Hash**: `$2y$10$...` (60 character string)

### Verification Process
```php
// In auth.php
$stored_hash = /* from database */;
$entered_password = /* from login form */;

if (password_verify($entered_password, $stored_hash)) {
    // Password is correct
} else {
    // Password is incorrect
}
```

### Session Management
- **Session ID**: Stored in PHP session
- **Duration**: 24 hours (or until explicit logout)
- **Storage**: Server-side (secure)
- **User ID**: `$_SESSION['uid']` contains user's database ID

---

## Files Involved

### Authentication Files
- `/tds/lib/auth.php` - Core authentication functions
- `/tds/admin/login.php` - Login form interface
- `/tds/admin/set_password.php` - Password management web interface
- `/tds/set_password_cli.php` - Command-line password tool
- `/tds/api/change_password.php` - User password change endpoint

### Database
- Table: `users` - Stores user data and password hashes
- Field: `password_hash` - Bcrypt hash of password

---

## Summary

### For Akash's Password Setup

**Choose One Method**:

1. **Web Interface** (easiest): Visit `/tds/admin/set_password.php` and set password
2. **Command Line** (fastest): Run `php set_password_cli.php akash.tdf@gmail.com YourPassword`

**Then**:
- Akash logs in with his email: `akash.tdf@gmail.com`
- And his password: (whatever you set)

**Done!** ✅

---

## Need Help?

If you encounter any issues:
1. Check troubleshooting section above
2. Verify user email matches exactly
3. Ensure password is at least 8 characters
4. Check database connection is working
5. Contact support with error message

---

**Last Updated**: December 10, 2025
**System**: Bombay Engineering TDS Management System
