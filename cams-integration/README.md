# ![CamsBiometric](https://static.camsbiometrics.com/landing/img/cams-logo.png) CamsBiometric API3

# CAMS Biometric Integrator (PHP)

This project provides a backend service for integrating with CAMS biometric devices, processing real-time punch logs and user updates, and evaluating attendance based on configurable policies. It is designed for flexibility, supporting various database backends.

---

## 🧩 Project Structure

```
my-cams-app-php/
├── composer.json               # PHP dependencies (managed by Composer)
├── vendor/                    # Composer-managed libraries
├── cams.conf                  # General server/database config
├── .env                       # (Optional) Environment variables
├── shift-attendance.json      # Attendance policy definitions
├── cams-callback.php          # HTTP server callback entry
├── day-attendance.php         # Day-based attendance logic
├── shift-attendance.php       # Shift-based attendance logic
├── cams-rest-api.php          # Client for external CAMS REST API
├── cams-tester.php            # CLI tester utility
├── Constants.php              # Global constants & helpers
└── Exceptions.php             # Custom exception definitions
```

---

## 🚀 Overview

The CAMS Biometric Integrator provides:

- Real-time punch/user data reception from CAMS devices.
- Configurable attendance policy evaluation.
- Tools to manage users and biometric devices.
- Database transactions for consistency.

---

## 📋 Prerequisites

- **PHP**: Version 7.4+  
- **Composer**: PHP dependency manager  
- **Database**: One of the following supported:
  - MySQL / MariaDB (`pdo_mysql`)
  - PostgreSQL (`pdo_pgsql`)
  - MSSQL (`pdo_sqlsrv`)
  - MongoDB (`mongodb` extension)

---

## 🛠️ Setup

### 1. Unzip & Navigate

Unzip the project to your web root (e.g., `htdocs/`, `html/`) and run:

```bash
cd my-cams-app-php
```

### 2. Install PHP Dependencies

```bash
composer install
```

This installs required libraries into the `vendor/` directory.

### 3. Database Setup

#### Create Tables:

You'll need the following basic schema:

```sql
CREATE TABLE camsDevice (
    id INT PRIMARY KEY,
    client_id VARCHAR(255),
    serial_number VARCHAR(255),
    auth_token VARCHAR(255),
    label_name VARCHAR(255),
    status VARCHAR(1)
);

CREATE TABLE camsUser (
    id INT PRIMARY KEY,
    user_id VARCHAR(255),
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    type VARCHAR(1),
    template TEXT,
    status VARCHAR(1)
);

CREATE TABLE camsPunch (
    id INT PRIMARY KEY,
    user_id VARCHAR(255),
    punch_time DATETIME,
    actual_punch_type INT,
    punch_type INT,
    input_type INT,
    device_id INT,
    punch_date_str VARCHAR(10)
);

CREATE TABLE camsConfiguration (
    id INT PRIMARY KEY,
    client_id VARCHAR(255),
    direction INT,
    status VARCHAR(1)
);

CREATE TABLE camsAttendance (
    id INT PRIMARY KEY,
    user_id VARCHAR(255),
    Pdate VARCHAR(10),
    punch_in_time DATETIME,
    punch_out_time DATETIME,
    status VARCHAR(50),
    confirm_status VARCHAR(50),
    shift_id INT,
    day_id INT,
    working_hours DOUBLE,
    overtime_slab_minute INT,
    time_updated DATETIME
);
```

### 4. Configuration Files

- **cams.conf**: Configure `[database]` settings (host, user, password, dbname).
- **.env** *(optional)*: Override sensitive data like DB credentials.
- **shift-attendance.json**: Defines attendance rules. If missing, defaults will be used.

---

## 🏃 Running the Application

### 1. Start Callback Server

#### Development:
```bash
php -S <ip-address>:<port>
```

Open [http://<ip-address>:8008/cams-callback.php](http://localhost:8008/cams-callback.php)

#### Production:
- Deploy under Apache/Nginx with proper PHP setup (e.g., PHP-FPM).
- Point server to `cams-callback.php`.

### 2. CLI Tester

Use the terminal utility for manual testing:

```bash
php cams-tester.php
```

This opens an interactive menu for testing users/devices.

---

## 🧪 Testing: Override Policy with `getModelData`

To test attendance policies without editing `shift-attendance.json`:

1. Open `cams-callback.php`
2. Locate `processPunchLog()`
3. Uncomment the following:

```php
// --- NEW FEATURE: Get model data for the user ---
// $effectivePolicy = $shiftData;
$effectivePolicy = getModelData($pdo, $userId, $shiftData);
// --- END NEW FEATURE ---
```
After uncommenting, cams-callback.php will use the hardcoded policy returned by getModelData for all attendance evaluations, regardless of what's in shift-attendance.json. This is useful for isolated testing scenarios.

Remember to re-comment this line before deploying to a production environment or if you want the application to use the policies defined in shift-attendance.json again.

⚠️ **Reminder**: Comment this back before deploying to production.

---

# 🪵 Logging

### Log Level Configuration
The log level can be set in either:
- **`cams.conf`** under `[log]` section
- Defaults to `info` if not specified

Example in `cams.conf`:
```
[log]
level = info   ; Possible values: error, warning, info, debug
```

Example in `.env`:
```
LOG_LEVEL=debug
```

### Log Output
Logs are timestamped and written to the console in the format:
```
YYYY-MM-DDTHH:mm:ss.sssZ [level]: message
```

### Usage in Code
```php
$logger = require __DIR__ . '/logger.php';
$logger->info('Server started successfully');
$logger->error('Something went wrong');
```

---

## 📄 License

This project is provided as-is. Licensing terms depend on the organization or deployment policy.

---

## 📬 Support

For support, feature requests, or bug reports, please contact your development/infra team or file an issue in your internal repository.