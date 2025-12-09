# Sandbox Analytics API - Quick Start Guide

## TL;DR

The Sandbox Analytics API is now integrated. It allows you to check compliance risks and get Potential Notice Analysis from Sandbox.

---

## Quick Setup (5 minutes)

### 1. Create Database Table
```bash
php /home/bombayengg/public_html/tds/lib/migrations.php
```

### 2. Test in Compliance Page
1. Go to Admin → Compliance
2. Scroll to "Analytics & Risk Assessment" section
3. Enter a job ID and click "Poll Status"

That's it! ✓

---

## How to Use

### Use Case 1: Poll Existing Job

**In Compliance UI:**
1. Enter Job ID (from Sandbox)
2. Click "Poll Status"
3. See result + download link if ready

**Via cURL:**
```bash
curl -X POST http://bombayengg.net/tds/api/poll_analytics_job.php \
  -d "job_id=550e8400-e29b-41d4-a716-446655440000"
```

### Use Case 2: Track New Job

**When you initiate analytics in Sandbox:**
```bash
curl -X POST http://bombayengg.net/tds/api/initiate_analytics_job.php \
  -d "filing_job_id=5&job_id=550e8400-e29b-41d4-a716-446655440000"
```

**Response:**
```json
{
  "ok": true,
  "data": {
    "analytics_job_id": 42,
    "status": "submitted"
  }
}
```

Then poll periodically to check status.

### Use Case 3: Get All Jobs

**Get recent analytics jobs:**
```bash
curl -X POST http://bombayengg.net/tds/api/get_analytics_jobs.php \
  -d "limit=10"
```

**Filter by filing job:**
```bash
curl -X POST http://bombayengg.net/tds/api/get_analytics_jobs.php \
  -d "filing_job_id=5"
```

**Filter by status:**
```bash
curl -X POST http://bombayengg.net/tds/api/get_analytics_jobs.php \
  -d "status=succeeded"
```

---

## Status Codes

| Status | Meaning |
|--------|---------|
| **submitted** | Waiting to start |
| **queued** | In queue |
| **processing** | Running analysis |
| **succeeded** | Done! Report ready |
| **failed** | Failed, check error |

---

## API Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/tds/api/poll_analytics_job.php` | POST | Check job status |
| `/tds/api/initiate_analytics_job.php` | POST | Create tracking record |
| `/tds/api/get_analytics_jobs.php` | POST | List jobs |

---

## What Was Added

### Backend
- ✅ `SandboxTDSAPI::pollAnalyticsJob()` - Call Sandbox API
- ✅ `analytics_jobs` table - Track jobs locally
- ✅ 3 new API endpoints - Manage jobs

### Frontend
- ✅ "Analytics & Risk Assessment" card - In compliance page
- ✅ Job status display - Show recent jobs
- ✅ Manual poll control - Check status anytime
- ✅ Report download links - Get results when ready

---

## Files

### New Files
```
/tds/api/poll_analytics_job.php
/tds/api/initiate_analytics_job.php
/tds/api/get_analytics_jobs.php
```

### Modified Files
```
/tds/lib/SandboxTDSAPI.php (added method)
/tds/lib/migrations.php (added migration)
/tds/admin/compliance.php (added section + UI)
```

### Documentation
```
/ANALYTICS_API_INTEGRATION.md (full docs)
/ANALYTICS_QUICK_START.md (this file)
/ANALYTICS_API_INTEGRATION_STATUS.md (status report)
```

---

## Troubleshooting

**Q: Analytics section not showing**
- A: Run migration: `php /tds/lib/migrations.php`

**Q: "Table doesn't exist" error**
- A: Same as above - run migration

**Q: Job ID not found**
- A: Verify job_id is correct UUID from Sandbox

**Q: Report URL is null**
- A: Job might still be processing - poll again later

**Q: Authorization failed**
- A: Check API credentials are set in database

---

## Next: Integration Ideas

You can further integrate by:

1. **Auto-initiate jobs** when FVU is generated
2. **Auto-poll jobs** periodically in background
3. **Show report preview** on compliance page
4. **Alert on failures** via email/notification
5. **Archive reports** locally when done

See full documentation for implementation details.

---

## Important Notes

- ⚠️ **Must run migration** before using
- ⚠️ **API credentials required** in database
- ⚠️ **Jobs are async** - check status periodically
- ⚠️ **Reports available 30min-2hrs** after submission

---

## Help

- Full docs: `/ANALYTICS_API_IMPLEMENTATION.md`
- Status report: `/ANALYTICS_API_INTEGRATION_STATUS.md`
- Sandbox docs: https://developer.sandbox.co.in/api-reference/tds/analytics/

---

**Integration Status:** ✅ Complete & Ready to Use

Happy analyzing! 📊
