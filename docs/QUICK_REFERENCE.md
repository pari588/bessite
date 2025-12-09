# Quick Reference Card - December 10, 2025

## Akash's Login Credentials

```
Email:    akash.tdf@gmail.com
Password: AkashSecure2025!
URL:      https://www.bombayengg.net/tds/admin/login.php
```

---

## Critical URLs

| Purpose | URL |
|---------|-----|
| **Login** | `https://www.bombayengg.net/tds/admin/login.php` |
| **Reports** | `https://www.bombayengg.net/tds/admin/reports.php` |
| **Dashboard** | `https://www.bombayengg.net/tds/admin/dashboard.php` |
| **Settings** | `https://www.bombayengg.net/tds/admin/settings.php` |
| **Set Password** | `https://www.bombayengg.net/tds/admin/set_password.php` |

---

## API Status

| API | Status | HTTP | Notes |
|-----|--------|------|-------|
| Reports (TDS) | ✅ WORKING | 200 | Form 24Q, 26Q, 27Q |
| Reports (TCS) | ✅ WORKING | 200 | Form 27EQ |
| Calculator | ✅ READY | N/A | Code updated, test needed |
| Analytics | ⚠️ BLOCKED | 400 | Payload format issue |

---

## Password Management

### Set New User Password (Web)
1. Go to: `/tds/admin/set_password.php`
2. Select user from dropdown
3. Enter password (min 8 chars)
4. Confirm password
5. Click "Set Password"

### Set New User Password (CLI)
```bash
php /home/bombayengg/public_html/tds/set_password_cli.php \
  <email> <password>
```

**Example**:
```bash
php set_password_cli.php akash.tdf@gmail.com MyPassword123
```

---

## Database Connection

**Location**: `/home/bombayengg/public_html/tds/lib/db.php`

**Connection Details**:
```
Host: localhost
Database: bombayengg_tds (or similar)
User: root
Charset: utf8mb4
```

---

## Key Files Modified (December 10)

### API Endpoints (Added 'production' environment)
- `/tds/api/filing/initiate.php`
- `/tds/api/filing/check-status.php`
- `/tds/api/filing/submit.php`
- `/tds/api/calculator_*.php` (4 files)
- `/tds/api/submit_analytics_job_*.php` (2 files)
- `/tds/api/poll_analytics_job.php`
- `/tds/api/fetch_analytics_jobs.php`

### Admin Pages (Added 'production' environment)
- `/tds/admin/reports.php`
- `/tds/admin/analytics.php`
- `/tds/admin/calculator.php`

### New Files (Password Management)
- `/tds/admin/set_password.php` - Web interface
- `/tds/set_password_cli.php` - CLI tool

---

## Testing Reports API

### Web Interface
1. Go to: `/tds/admin/reports.php`
2. Click: **🌐 Sandbox Reports** tab
3. Select: **Form 26Q Report** (or 24Q/27Q)
4. Click: **Submit to Sandbox**
5. Expected: ✅ Job ID appears

### Command Line
```bash
php -r "
require_once 'tds/lib/db.php';
require_once 'tds/lib/SandboxTDSAPI.php';

\$api = new SandboxTDSAPI(1, \$pdo, null, 'production');
\$result = \$api->submitTDSReportsJob('MUMT14861A', 'Q2', '26Q', 'FY 2025-26');

echo json_encode(\$result, JSON_PRETTY_PRINT);
"
```

---

## Financial Year Format

**Always use format**: `FY YYYY-YY`

**Examples**:
- ✅ `FY 2024-25` (correct)
- ✅ `FY 2025-26` (correct)
- ❌ `FY 2025--26` (wrong - double dash)
- ❌ `202425` (wrong - missing FY and formatting)
- ❌ `2024-25` (missing FY prefix)

---

## Users Table

```sql
SELECT id, name, email, role FROM users;
```

**Current Users**:
```
1 | Admin | admin@example.com | owner
2 | Akash Akhade | akash.tdf@gmail.com | staff
```

---

## Environment Configuration

**Default Constructor** (uses TEST environment - DON'T USE):
```php
$api = new SandboxTDSAPI($firm_id, $pdo);
```

**Correct Constructor** (uses PRODUCTION - USE THIS):
```php
$api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');
```

**Difference**:
- TEST: `https://test-api.sandbox.co.in` (403 errors)
- PRODUCTION: `https://api.sandbox.co.in` (200 success)

---

## Recent Git Commits

```
f13122d - Add latest updates summary document
f0d6621 - Add comprehensive user password management system
c17d162 - Fix critical environment issue across all API endpoints
5eaeb23 - Fix financial year format handling in Reports API
```

**View all**:
```bash
git log --oneline
```

---

## Error Codes Reference

| Code | Meaning | Fix |
|------|---------|-----|
| 200 | ✅ Success | N/A |
| 400 | Invalid request body | Check payload format |
| 401 | Unauthorized | Check token/credentials |
| 403 | Insufficient privilege | Check 'production' parameter |
| 422 | Invalid financial year | Use `FY YYYY-YY` format |

---

## Important Paths

```
Base Directory:   /home/bombayengg/public_html/
TDS System:       /home/bombayengg/public_html/tds/
Library Files:    /home/bombayengg/public_html/tds/lib/
Admin Pages:      /home/bombayengg/public_html/tds/admin/
API Endpoints:    /home/bombayengg/public_html/tds/api/
Documentation:    /home/bombayengg/public_html/docs/
```

---

## Password Requirements

✅ **Required**:
- Minimum 8 characters
- Can use letters, numbers, symbols

❌ **Not Required**:
- Uppercase letters
- Numbers
- Special symbols

**Strong Examples**:
- `MyPassword123`
- `Akash@2025TDS`
- `SecurePass2024!`

---

## Common Tasks

### View API Credentials
```bash
php -r "
require_once 'tds/lib/db.php';
\$stmt = \$pdo->query('SELECT firm_id, environment, api_key FROM api_credentials');
while(\$row = \$stmt->fetch()) {
  echo \$row['firm_id'] . ' | ' . \$row['environment'] . ' | ' . substr(\$row['api_key'], 0, 20) . '...' . \"\n\";
}
"
```

### Check Token Status
```bash
php -r "
require_once 'tds/lib/db.php';
\$stmt = \$pdo->query('SELECT * FROM api_credentials WHERE firm_id=1 AND environment=\"production\"');
\$cred = \$stmt->fetch();
echo 'Token Status: ' . (empty(\$cred['access_token']) ? 'NONE' : 'EXISTS') . \"\n\";
echo 'Expires: ' . (\$cred['token_expires_at'] ?? 'Unknown') . \"\n\";
"
```

### List All Users
```bash
php /home/bombayengg/public_html/tds/set_password_cli.php --list
```

---

## Debugging Tips

### Enable Debug Mode
Add to `/tds/admin/reports.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check PHP Logs
```bash
tail -f /var/log/php-fpm/error.log
```

### Check Apache Logs
```bash
tail -f /var/log/apache2/error.log
```

---

## Support Resources

| Issue | Document |
|-------|----------|
| Password setup | `/docs/USER_PASSWORD_MANAGEMENT.md` |
| API testing | `/docs/TESTING_GUIDE.md` |
| Technical details | `/docs/DECEMBER_9_SESSION_SUMMARY.md` |
| Latest updates | `/docs/LATEST_UPDATES.md` |

---

## Remember

✅ **Always use 'production' environment** in new code
✅ **Always format financial year as `FY YYYY-YY`**
✅ **Passwords minimum 8 characters**
✅ **Reports API is fully operational**
⚠️ **Analytics API still broken (HTTP 400)**

---

**Last Updated**: December 10, 2025
**System Status**: Reports API ✅ | User Management ✅ | Analytics API ⚠️
