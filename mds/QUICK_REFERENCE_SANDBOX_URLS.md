# Quick Reference: Sandbox Host URLs

## 🔗 Host URLs

| Environment | Host URL | Use Case |
|---|---|---|
| **Test** | `https://test-api.sandbox.co.in` | Development & Testing |
| **Production** | `https://api.sandbox.co.in` | Live Operations |

## 🔑 API Credentials

### Test Environment
```
API Key:  key_test_86c17301347d4699bded915f03fb6f28
Secret:   secret_test_5aa240aac21d4074ba6b041b76a5d059
Host:     https://test-api.sandbox.co.in
```

### Production Environment
```
API Key:  key_live_d6fe3991cf45411bb21504de5fcc013c
Secret:   secret_live_af21219571174b959cb8da9648dd970e
Host:     https://api.sandbox.co.in
```

## 📝 PHP Usage

### Use Test Environment (Default)
```php
$api = new SandboxTDSAPI($firm_id, $pdo);
// Automatically uses: https://test-api.sandbox.co.in
```

### Use Production Environment
```php
$api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');
// Automatically uses: https://api.sandbox.co.in
```

## 🔐 Authentication Headers

```
Authorization: Bearer {JWT_TOKEN}
x-api-key: {API_KEY}
x-api-version: 1.0
Content-Type: application/json
```

## 📍 Example API Endpoints

**Authenticate:**
```
POST https://test-api.sandbox.co.in/authenticate
POST https://api.sandbox.co.in/authenticate
```

**Submit TDS Report:**
```
POST https://test-api.sandbox.co.in/tds/reports/txt
POST https://api.sandbox.co.in/tds/reports/txt
```

**Poll Report Status:**
```
GET https://test-api.sandbox.co.in/tds/reports/txt?job_id={jobId}
GET https://api.sandbox.co.in/tds/reports/txt?job_id={jobId}
```

## ✅ Current Status

- ✅ Test environment: Ready
- ✅ Production environment: Configured
- ✅ Automatic URL switching: Working
- ✅ Credentials: Stored and secure
- ⚠️ Features: Pending activation

## 📚 Full Documentation

See [SANDBOX_HOST_URLS_AND_ENVIRONMENTS.md](SANDBOX_HOST_URLS_AND_ENVIRONMENTS.md) for complete details.

---

**Last Updated:** December 9, 2025
