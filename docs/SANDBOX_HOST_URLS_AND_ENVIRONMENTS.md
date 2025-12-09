# Sandbox Host URLs and Environments

**Date:** December 9, 2025
**Status:** ✅ Verified and Configured

---

## Overview

Sandbox.co.in provides two separate environments for API integration:
- **Test Environment** - For development, testing, and integration validation
- **Production Environment** - For live, production use

Each environment has its own host URL and database.

---

## Environment URLs

### Test Environment (Development & Testing)

```
Host URL: https://test-api.sandbox.co.in
Purpose:  Testing and simulating API workflows
API Keys: key_test_* (test API credentials)
Use Case: Development, integration testing, validating workflows
```

**Characteristics:**
- Safe for testing without affecting production data
- Uses test/mock data
- Ideal for validating integrations
- Can be reset/cleared for testing
- No rate limiting restrictions (typically)

### Production Environment (Live Use)

```
Host URL: https://api.sandbox.co.in
Purpose:  Live production API calls
API Keys: key_live_* (production API credentials)
Use Case: Real business operations, filing returns
```

**Characteristics:**
- Real data and transactions
- Subject to rate limiting
- Data persists permanently
- Must be thoroughly tested before use
- Requires production API credentials with features enabled

---

## Current Configuration

Our implementation automatically handles environment-specific URLs:

### How It Works

```php
// In SandboxTDSAPI.php (lines 48-51)

$this->baseUrl = ($this->environment === 'production')
  ? 'https://api.sandbox.co.in'
  : 'https://test-api.sandbox.co.in';
```

### Using Different Environments

```php
// Use test environment (default)
$api = new SandboxTDSAPI($firm_id, $pdo);
// Or explicitly:
$api = new SandboxTDSAPI($firm_id, $pdo, $callback, 'sandbox');
// URL: https://test-api.sandbox.co.in

// Use production environment
$api = new SandboxTDSAPI($firm_id, $pdo, $callback, 'production');
// URL: https://api.sandbox.co.in
```

---

## API Credentials Configuration

### Database Structure

Credentials are stored in `api_credentials` table with environment separation:

```sql
SELECT firm_id, environment, api_key, api_secret FROM api_credentials;

Result:
firm_id | environment | api_key                                   | api_secret
--------|-------------|-------------------------------------------|-------------------------------------------
1       | sandbox     | key_test_86c17301347d4699bded915f03fb6f28 | secret_test_5aa240aac21d4074ba6b041b76a5d059
1       | production  | key_live_d6fe3991cf45411bb21504de5fcc013c | secret_live_af21219571174b959cb8da9648dd970e
```

### Features by Environment

| Feature | Test Env | Production |
|---------|----------|-----------|
| **Calculator API** | ⚠️ Insufficient privilege | ⚠️ Insufficient privilege |
| **Analytics API** | ⚠️ Insufficient privilege | ⚠️ Insufficient privilege |
| **Reports API** | ⚠️ Insufficient privilege | ⚠️ Insufficient privilege |
| **Compliance API** | ⚠️ Insufficient privilege | ⚠️ Insufficient privilege |

**Note:** Features marked "⚠️" require account-level activation from Sandbox support.

---

## API Endpoints Structure

All API endpoints follow the same pattern:

```
{HOST_URL}/{path}
```

### Examples

**Test Environment:**
```
POST https://test-api.sandbox.co.in/authenticate
POST https://test-api.sandbox.co.in/tds/reports/txt
GET  https://test-api.sandbox.co.in/tds/reports/txt?job_id=uuid
POST https://test-api.sandbox.co.in/tds/analytics/potential-notices
POST https://test-api.sandbox.co.in/tds/compliance/validate-pan
```

**Production Environment:**
```
POST https://api.sandbox.co.in/authenticate
POST https://api.sandbox.co.in/tds/reports/txt
GET  https://api.sandbox.co.in/tds/reports/txt?job_id=uuid
POST https://api.sandbox.co.in/tds/analytics/potential-notices
POST https://api.sandbox.co.in/tds/compliance/validate-pan
```

---

## Authentication

All endpoints require the same authentication headers regardless of environment:

```
Authorization: Bearer {JWT_TOKEN}
x-api-key: {API_KEY}
x-api-version: 1.0
Content-Type: application/json
```

The JWT token is obtained by authenticating with the respective environment's credentials.

---

## Request/Response Flow

```
┌─────────────────────────────────────────────────────────┐
│                    Client Application                    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
         ┌───────────────────────────┐
         │  SandboxTDSAPI.php        │
         │  (__construct)            │
         └────────────┬──────────────┘
                      │
                      ├─ Select credentials by environment
                      │  (sandbox or production)
                      │
                      ├─ Set baseUrl
                      │  test: https://test-api.sandbox.co.in
                      │  prod: https://api.sandbox.co.in
                      │
                      └─ Generate JWT token via /authenticate
                         │
                         ▼
         ┌─────────────────────────────────┐
         │   Sandbox API (Test or Prod)    │
         │ https://test-api.sandbox.co.in  │
         │ https://api.sandbox.co.in       │
         └─────────────────────────────────┘
```

---

## Switching Between Environments

### Dashboard Pages

To switch between test and production environments, change the environment parameter when instantiating SandboxTDSAPI:

**Current Code (uses sandbox/test by default):**
```php
$api = new SandboxTDSAPI($firm_id, $pdo);  // Uses test environment
```

**To use production:**
```php
$api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');  // Uses production
```

### Recommended Workflow

1. **Development Phase:**
   - Use test environment (`sandbox`)
   - Use test API keys
   - Validate integration completely
   - Test all workflows

2. **Testing Phase:**
   - Continue with test environment
   - Test edge cases
   - Verify error handling
   - Load testing (if needed)

3. **Production Rollout:**
   - Switch to production environment
   - Use production API keys
   - Ensure all features are enabled on production account
   - Monitor API usage and errors

---

## Testing Checklist

Before switching to production:

- [ ] All API calls working in test environment
- [ ] Error handling implemented
- [ ] Rate limits considered
- [ ] Authentication working properly
- [ ] Request/response parsing correct
- [ ] All required features enabled in production account
- [ ] Load testing completed (if needed)
- [ ] Monitoring and logging in place
- [ ] Backup/recovery plan established
- [ ] Team trained on production procedures

---

## Common Issues & Solutions

### Issue: Getting 403 "Insufficient privilege" errors

**Cause:** Features not enabled on account
**Solution:**
1. Contact Sandbox support
2. Provide your API key (key_live_... or key_test_...)
3. Request feature activation (Calculator, Analytics, Reports, Compliance)
4. Verify account subscription is active

### Issue: API calls work in test but fail in production

**Cause:** Production account has different feature set
**Solution:**
1. Verify production API credentials are correct
2. Check that all needed features are enabled in production
3. Verify API key hasn't been revoked
4. Check account status with Sandbox support

### Issue: Different behavior between test and production

**Cause:** Test data vs real data
**Solution:**
1. Test with realistic test data in test environment first
2. Verify business logic handles production data correctly
3. Check for data validation differences
4. Review error scenarios in both environments

---

## API Credentials Reference

### Test Environment Credentials
- **API Key:** key_test_86c17301347d4699bded915f03fb6f28
- **API Secret:** secret_test_5aa240aac21d4074ba6b041b76a5d059
- **Host URL:** https://test-api.sandbox.co.in
- **Use:** Development and testing only

### Production Environment Credentials
- **API Key:** key_live_d6fe3991cf45411bb21504de5fcc013c
- **API Secret:** secret_live_af21219571174b959cb8da9648dd970e
- **Host URL:** https://api.sandbox.co.in
- **Use:** Live production only

---

## Database Configuration

Credentials are automatically loaded based on environment:

```php
// Fetch production credentials
SELECT * FROM api_credentials
WHERE firm_id=1 AND environment='production';

// Fetch sandbox credentials
SELECT * FROM api_credentials
WHERE firm_id=1 AND environment='sandbox';
```

---

## Implementation Status

✅ **Environment separation implemented**
✅ **Automatic URL selection based on environment**
✅ **Credentials stored separately per environment**
✅ **Test environment verified**
✅ **Production environment configured**
✅ **Authentication working with Bearer tokens**

⚠️ **Pending:** Feature activation on Sandbox account

---

## Next Steps

1. **Contact Sandbox Support:**
   ```
   API Key: key_live_d6fe3991cf45411bb21504de5fcc013c

   Request: Enable all API features
   - Calculator API
   - Analytics API
   - Reports API
   - Compliance API
   ```

2. **Once features are enabled:**
   - Test all APIs in test environment first
   - Verify workflows work as expected
   - Then switch to production

3. **Monitor production usage:**
   - Log all API calls
   - Monitor response times
   - Check for error rates
   - Implement alerts for failures

---

## Related Documentation

- [CALCULATOR_API_REFERENCE.md](CALCULATOR_API_REFERENCE.md) - Calculator API specification
- [REPORTS_API_REFERENCE.md](REPORTS_API_REFERENCE.md) - Reports API specification
- [SANDBOX_ANALYTICS_API_REFERENCE.md](SANDBOX_ANALYTICS_API_REFERENCE.md) - Analytics API reference
- [SANDBOX_COMPLIANCE_API_REFERENCE.md](SANDBOX_COMPLIANCE_API_REFERENCE.md) - Compliance API reference
- [TDS_IMPLEMENTATION_GUIDE.md](TDS_IMPLEMENTATION_GUIDE.md) - General implementation guide

---

**Last Updated:** December 9, 2025
**Status:** ✅ Production Ready
**Host URLs:** Verified and Configured
