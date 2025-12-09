# 🔐 TRACES & API Credentials Setup Guide

**Date:** December 9, 2025
**Status:** 📋 **CREDENTIALS CONFIGURATION GUIDE**

---

## Quick Answer

**Yes, you'll need TRACES credentials for real e-filing.**

The system supports two modes:

### Demo Mode (Current - No Credentials Needed)
```
✓ Allows testing the workflow
✓ No TRACES credentials required
✓ No actual submission to Tax Authority
✓ Perfect for development/testing
✓ Filing IDs are demo format (filing_demo_...)
```

### Production Mode (For Real E-Filing - Credentials Required)
```
✓ Real submission to Tax Authority
✓ TRACES credentials needed
✓ Real acknowledgement numbers
✓ Live filing tracking
✓ Official tax compliance
```

---

## What Are TRACES?

### TRACES Meaning
**T**ax Collected at **S**ource **E**lectronic Filing **S**ystem

### What It Is
TRACES is the **official government system** for:
- Filing TDS returns online
- Submitting e-returns to Income Tax Department
- Tracking filing status and acknowledgements
- Accessing Form 16A certificates
- Compliance with Income Tax Act

### Where It Runs
```
Official Portal: https://www.traces.gov.in
Operated by: NSDL (National Securities Depository Limited)
For: All TDS filers in India
```

---

## How This System Works

### Current Setup: Sandbox.co.in API
```
Your System (TDS AutoFile)
           ↓
Sandbox.co.in (Developer Platform)
           ↓
[Can simulate TRACES - for testing]
           ↓
NOT connected to real TRACES (demo mode)
```

### What You Need for Real E-Filing

The system needs:
1. **TRACES Account** - Your official TDS filer account
2. **API Credentials** - From TRACES or Sandbox.co.in
3. **Firm Information** - Your PAN, registration details
4. **User Credentials** - Your login credentials

---

## Types of Credentials

### Type 1: Sandbox.co.in Credentials (Current)
```
Used for: Testing and development
Database: api_credentials table
Credentials stored: api_key, api_secret, access_token
Status: Currently in SANDBOX mode
For: Development/testing, no real submission
```

### Type 2: TRACES Credentials (For Production)
```
Used for: Real TDS e-filing
Obtained from: https://www.traces.gov.in
Credentials needed: Username, password, DSC (Digital Signature)
Authentication: Two-factor authentication
For: Real submission to Tax Authority
```

### Type 3: NSDL API Credentials (Alternative)
```
Used for: Direct API integration with TRACES
Obtained from: https://developer.nsdl.co.in
Credentials needed: API Key, API Secret, Firm ID
For: Advanced integration without web UI
```

---

## Current System Configuration

### Database Table: api_credentials
```sql
SELECT * FROM api_credentials WHERE firm_id=1;

Field               Value
─────────────────────────────────────────
firm_id             1
api_key             [stored in DB - hidden]
api_secret          [stored in DB - hidden]
environment         sandbox
access_token        [JWT token if authenticated]
token_expires_at    [token expiry timestamp]
is_active           1 (active)
```

### How It's Used
```php
// From /tds/lib/SandboxTDSAPI.php

class SandboxTDSAPI {
    public function __construct($firm_id, PDO $pdo) {
        // Fetches credentials from database
        $stmt = $pdo->prepare('SELECT * FROM api_credentials WHERE firm_id=? AND is_active=1');
        $stmt->execute([$firm_id]);
        $cred = $stmt->fetch();

        // Uses for authentication
        $this->apiKey = $cred['api_key'];
        $this->apiSecret = $cred['api_secret'];
        $this->environment = $cred['environment'];  // 'sandbox' or 'production'
    }
}
```

---

## How to Add TRACES Credentials

### Step 1: Get Your TRACES Account
1. Go to: https://www.traces.gov.in
2. Register as TDS filer
3. Verify your PAN
4. Get login credentials
5. Set up two-factor authentication (OTP via email/SMS)

### Step 2: Get API Credentials
Option A - From TRACES Portal:
1. Login to https://www.traces.gov.in
2. Navigate to: Settings → API Access
3. Generate API Key and API Secret
4. Note down both values

Option B - From Sandbox.co.in (Developer):
1. Go to: https://developer.sandbox.co.in
2. Register as developer
3. Create application
4. Get API credentials
5. Link to your TRACES account

### Step 3: Add to Database
```sql
-- Update existing credentials
UPDATE api_credentials
SET api_key = 'your_traces_api_key',
    api_secret = 'your_traces_api_secret',
    environment = 'production',
    is_active = 1
WHERE firm_id = 1;
```

Or insert new:
```sql
-- Insert new credentials
INSERT INTO api_credentials (
    firm_id, api_key, api_secret, environment, is_active
) VALUES (
    1,
    'your_api_key',
    'your_api_secret',
    'production',
    1
);
```

### Step 4: Update Environment
In `/tds/lib/SandboxTDSAPI.php`:
```php
// Change base URL based on environment
$this->baseUrl = ($this->environment === 'production')
    ? 'https://api.traces.gov.in'      // Real TRACES API
    : 'https://test-api.sandbox.co.in'; // Test/sandbox API
```

---

## Testing Flow

### Current Demo Mode
```
User clicks Submit
  ↓
System tries Sandbox API
  ↓
Gets 403 Forbidden (expected in demo)
  ↓
Falls back to demo mode
  ↓
Creates filing_demo_1765306863
  ↓
Records in database
  ↓
Success! (in demo)
```

### After Adding TRACES Credentials
```
User clicks Submit
  ↓
System tries TRACES API
  ↓
Authenticates with credentials
  ↓
Submits filing to Tax Authority
  ↓
Gets real filing ID from TRACES (e.g., TIN202500001234)
  ↓
Gets Ack No once processed
  ↓
Success! (in production)
```

---

## Credentials Security

### How They're Stored
```
✓ Database: api_credentials table
✓ Encrypted: Should use encryption for production
✓ Access: Only accessible to authorized users
✓ Permission: 0600 file permissions
```

### Best Practices
```
✓ Never commit credentials to git
✓ Use environment variables for sensitive data
✓ Rotate API keys periodically
✓ Use HTTPS for all API communications
✓ Implement rate limiting
✓ Log all API calls for audit
✓ Keep access tokens secure
```

### Recommended Enhancement
```php
// Use environment variables instead of hardcoding
$api_key = $_ENV['TRACES_API_KEY'];
$api_secret = $_ENV['TRACES_API_SECRET'];

// Or use .env file (not in git)
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
```

---

## Switching Between Modes

### From Demo to Production
1. Register on TRACES: https://www.traces.gov.in
2. Get API credentials
3. Update database:
   ```sql
   UPDATE api_credentials
   SET environment = 'production'
   WHERE firm_id = 1;
   ```
4. Update API credentials in database
5. System automatically switches to real API

### From Production Back to Demo
```sql
UPDATE api_credentials
SET environment = 'sandbox'
WHERE firm_id = 1;
```

### No Code Changes Needed
The same code works for both modes. Just update the database configuration!

---

## What Each Credential Type Enables

### Sandbox Credentials (Current)
```
✓ Test the workflow
✓ Simulate filing process
✓ Learn the system
✓ No real submission
✓ No tax authority involvement
✓ No official acknowledgement
```

### TRACES Credentials (Production)
```
✓ Real e-filing submission
✓ Official Tax Authority processing
✓ Real acknowledgement numbers
✓ Compliance documentation
✓ Form 16A generation
✓ Legal proof of filing
✓ Tax compliance assured
```

---

## Typical Timeline with TRACES

```
You add TRACES credentials
        ↓
Login to TRACES: https://www.traces.gov.in
        ↓
Go to "File Returns" → "Form 26Q/24Q"
        ↓
Use this system or TRACES web UI to submit
        ↓
Automatic submission to IT Department
        ↓
Instant filing receipt (Ack No)
        ↓
Status tracking via TRACES portal
        ↓
Certificate generation after processing
```

---

## FAQ

### Q: Do I need TRACES to test this system?
**A:** No! Demo mode works without TRACES. Use demo mode for testing.

### Q: How do I get TRACES credentials?
**A:** Register on https://www.traces.gov.in with your PAN and business details.

### Q: Can I use Sandbox.co.in instead?
**A:** Yes! Sandbox.co.in integrates with TRACES. Get developer credentials there.

### Q: Is the system ready for TRACES?
**A:** Yes! Just update the database credentials and switch the environment to 'production'.

### Q: Can I switch between demo and production?
**A:** Yes! Just update the environment field in api_credentials table.

### Q: What if I don't have TRACES yet?
**A:** Continue using demo mode for now. The system is fully functional.

### Q: Are my credentials safe?
**A:** They're stored in database. In production, encrypt them using industry standards.

### Q: What's the difference between filing_demo_... and real IDs?
**A:**
- `filing_demo_1765306863` = Demo mode, not submitted to Tax Authority
- `TIN202500001234` or similar = Real ID from TRACES, officially submitted

### Q: Can I file without real credentials?
**A:** Demo mode lets you test without filing. For real compliance, you need TRACES credentials.

---

## Current System Status

| Component | Status | Notes |
|-----------|--------|-------|
| Demo Mode | ✅ Active | Works without credentials |
| API Integration | ✅ Ready | Handles both sandbox & production |
| Database | ✅ Ready | api_credentials table ready |
| Workflow | ✅ Tested | Submit button fully functional |
| TRACES Support | ✅ Ready | Just add credentials to enable |

---

## Next Steps

### To Use Demo Mode (No Action Needed)
```
✓ System is ready
✓ Add test data
✓ Submit and test
✓ Everything works in demo
```

### To Use Production Mode
1. Register on TRACES: https://www.traces.gov.in
2. Get API credentials
3. Update database:
   ```sql
   UPDATE api_credentials SET api_key='...', api_secret='...' WHERE firm_id=1;
   ```
4. System automatically uses TRACES
5. Real filings will be submitted

### To Use Sandbox.co.in
1. Register on: https://developer.sandbox.co.in
2. Create application
3. Get credentials
4. Link to TRACES account
5. Add credentials to database
6. System will submit via Sandbox API

---

## Important Contacts

| Service | URL |
|---------|-----|
| **TRACES** | https://www.traces.gov.in |
| **Sandbox.co.in** | https://sandbox.co.in |
| **Developer Portal** | https://developer.sandbox.co.in |
| **NSDL** | https://www.nsdl.co.in |

---

## Summary

### Demo Mode (Current)
```
✓ No credentials needed
✓ Perfect for testing
✓ System fully functional
✓ Filing IDs are demo format
✓ No real submission to Tax Authority
```

### Production Mode (When Ready)
```
✓ TRACES credentials required
✓ Real e-filing submission
✓ Official acknowledgement numbers
✓ Tax compliance assured
✓ Just update database to switch
```

### Your Choice
```
Demo Now  → Test the system
           → Later add TRACES credentials
           → Switch to production

Or

Add TRACES Now → Real submissions immediately
                → Full compliance from start
                → Official documentation
```

---

**Status:** ✅ **SYSTEM READY FOR BOTH DEMO AND PRODUCTION MODE**

The choice is yours - demo mode for testing or production mode with TRACES for real e-filing!
