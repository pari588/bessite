# HRMS WhatsApp Automation Plan
**Date:** 2026-02-23
**Status:** Planning — Not yet implemented
**Depends on:** Real WhatsApp Business number verified + Phone Number ID

## API Credentials (Verified)

| Item | Value |
|------|-------|
| Phone Number ID | `1046604245192095` |
| WABA ID | `3195920597256889` |
| Access Token | `EAANUKgxSVcEBQ...` (stored separately) |
| API Version | v21.0 |

---

## Current HRMS State

### What Exists
- Full attendance system with CAMS biometric integration
- Leave management (9 leave types, balance tracking, FY-based, comp-off, encashment)
- Salary slips with PDF generation and email delivery
- Shift management with late/early detection (15-min grace period)
- Cron jobs: auto-attendance, monthly emails, leave accrual, comp-off expiry
- Email automation via Brevo API

### What's Missing
- **No `whatsappNumber` field on employees** — only `emergencyContact` and `email`
- **No notification queue** — no system to queue and send messages
- **No WhatsApp API integration** — no code to call Meta Cloud API
- **No inbound message handling** — no webhook to receive employee messages

---

## Architecture Overview

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  HRMS Events     │────▶│  Notification     │────▶│  WhatsApp Cloud │
│  (Cron/Admin/    │     │  Queue            │     │  API (Meta)     │
│   Employee)      │     │  mx_wa_queue      │     │                 │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                           │
┌─────────────────┐     ┌──────────────────┐              │
│  Employee        │◀────│  Webhook Handler  │◀─────────────┘
│  Self-Service    │     │  /core/wa-webhook │  (Inbound messages)
│  (Leave balance, │     │  .php             │
│   attendance)    │     └──────────────────┘
└─────────────────┘
```

**Two flows:**
1. **Outbound** — HRMS triggers → queue → cron sends via Meta API → employee receives on WhatsApp
2. **Inbound** — Employee sends WhatsApp message → Meta webhook → parse intent → query HRMS data → auto-reply

---

## Phase 1: Foundation (Build First)

### 1.1 Add WhatsApp Number to Employee Table

```sql
ALTER TABLE mx_x_admin_user
ADD COLUMN whatsappNumber VARCHAR(15) NULL AFTER emergencyContact,
ADD COLUMN whatsappOptIn TINYINT(1) DEFAULT 0 AFTER whatsappNumber,
ADD COLUMN whatsappOptInDate DATETIME NULL AFTER whatsappOptIn;
```

- `whatsappNumber` — 91XXXXXXXXXX format (with country code, no +)
- `whatsappOptIn` — employee must consent to receive messages (Meta requirement)
- `whatsappOptInDate` — when they opted in (compliance audit trail)

Admin UI: Add field to employee add/edit form in xadmin.
Employee portal: Add opt-in toggle in profile page.

### 1.2 WhatsApp API Config

Create `/home/bombayengg/whatsapp-config.php` (OUTSIDE web root for security):

```php
<?php
define('WA_PHONE_NUMBER_ID', 'XXXXXXXXX');  // New ID after real number verified
define('WA_WABA_ID', '927271706367429');
define('WA_ACCESS_TOKEN', '...');           // System user token
define('WA_API_VERSION', 'v21.0');
define('WA_API_BASE', 'https://graph.facebook.com/' . WA_API_VERSION);
define('WA_WEBHOOK_VERIFY_TOKEN', 'bes_hrms_wa_2026');  // Random string for webhook verification
define('WA_FROM_NUMBER', '91XXXXXXXXXX');   // Display reference
```

### 1.3 WhatsApp API Core Library

Create `/home/bombayengg/public_html/core/whatsapp-api.inc.php`:

```php
class WhatsAppAPI {
    // Send template message (pre-approved by Meta)
    public function sendTemplate($to, $templateName, $language, $components = [])

    // Send free-form text (only within 24-hour window)
    public function sendText($to, $message)

    // Send document (PDF salary slip, reports)
    public function sendDocument($to, $documentUrl, $caption, $filename)

    // Send interactive buttons (yes/no, menu options)
    public function sendInteractiveButtons($to, $body, $buttons)

    // Send interactive list (menu with multiple options)
    public function sendInteractiveList($to, $body, $sections)

    // Mark message as read
    public function markRead($messageId)

    // Log all API calls
    private function logApiCall($method, $to, $payload, $response)
}
```

### 1.4 Notification Queue Table

```sql
CREATE TABLE mx_wa_queue (
    queueID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    toNumber VARCHAR(15) NOT NULL,
    messageType ENUM('template', 'text', 'document', 'interactive') NOT NULL,
    templateName VARCHAR(100) NULL,
    templateLanguage VARCHAR(10) DEFAULT 'en',
    templateParams JSON NULL,
    textBody TEXT NULL,
    documentUrl VARCHAR(500) NULL,
    documentCaption VARCHAR(255) NULL,
    interactivePayload JSON NULL,
    triggerEvent VARCHAR(50) NOT NULL,
    priority ENUM('high', 'normal', 'low') DEFAULT 'normal',
    queueStatus ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    waMessageID VARCHAR(100) NULL,
    errorMessage TEXT NULL,
    attempts INT DEFAULT 0,
    maxAttempts INT DEFAULT 3,
    scheduledAt DATETIME NULL,
    sentAt DATETIME NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (queueStatus),
    INDEX idx_scheduled (scheduledAt, queueStatus),
    INDEX idx_user (userID),
    INDEX idx_event (triggerEvent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 1.5 Inbound Message Log Table

```sql
CREATE TABLE mx_wa_inbound (
    inboundID INT AUTO_INCREMENT PRIMARY KEY,
    fromNumber VARCHAR(15) NOT NULL,
    userID INT NULL,
    waMessageID VARCHAR(100) NOT NULL,
    messageType ENUM('text', 'image', 'document', 'interactive_reply', 'button_reply') NOT NULL,
    messageBody TEXT NULL,
    buttonPayload VARCHAR(255) NULL,
    listPayload VARCHAR(255) NULL,
    intent VARCHAR(50) NULL,
    responseQueueID INT NULL,
    processedAt DATETIME NULL,
    receivedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from (fromNumber),
    INDEX idx_user (userID),
    INDEX idx_intent (intent),
    UNIQUE KEY uk_wa_msg (waMessageID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 1.6 Queue Processing Cron

Create `/home/bombayengg/public_html/cron/wa-queue-processor.php`:

- Runs every 2 minutes via cron
- Picks up `pending` messages where `scheduledAt <= NOW()` or `scheduledAt IS NULL`
- Processes in priority order (high → normal → low)
- Rate limit: max 80 messages/second (Meta Cloud API limit, but we'll do 1/sec to be safe)
- Retry failed messages up to `maxAttempts`
- Logs all outcomes

Crontab entry:
```
*/2 * * * * php /home/bombayengg/public_html/cron/wa-queue-processor.php >> /home/bombayengg/logs/wa-queue.log 2>&1
```

### 1.7 Webhook Endpoint for Inbound Messages

Create `/home/bombayengg/public_html/core/wa-webhook.php`:

- Handles Meta webhook verification (GET request with hub.challenge)
- Receives inbound messages (POST from Meta)
- Parses message type and content
- Matches `fromNumber` to `mx_x_admin_user.whatsappNumber` to identify employee
- Logs in `mx_wa_inbound`
- Routes to intent handler

Add to `.htaccess` (exempt from main site rewrite):
```apache
RewriteRule ^core/wa-webhook\.php$ core/wa-webhook.php [L]
```

---

## Phase 2: Outbound Notifications (HRMS → Employee)

### Meta Template Messages Required

You need to create these templates in Meta Business Manager and get them approved before use. Templates support variables like {{1}}, {{2}} etc.

#### 2.1 Late Arrival Notification
**Template name:** `late_arrival_alert`
**Category:** Utility
**Trigger:** Cron at 10:30 AM daily — check today's attendance where `isLate = 1`
**Message:**
```
Hi {{1}},

Your check-in today was recorded at {{2}}, which is {{3}} minutes after your scheduled time of {{4}}.

If you have a reason, please submit a remark via the HRMS portal.

— Bombay Engineering Syndicate HR
```
**Variables:** employee name, check-in time, late minutes, scheduled time

#### 2.2 Absent Notification (No Check-In)
**Template name:** `absent_notification`
**Category:** Utility
**Trigger:** Cron at 11:00 AM daily — employees with no check-in and no approved leave
**Message:**
```
Hi {{1}},

We haven't received your attendance for today ({{2}}). If you're on leave, please apply through the HRMS portal.

If this is an error, please contact HR.

— Bombay Engineering Syndicate HR
```

#### 2.3 Leave Request Status Update
**Template name:** `leave_status_update`
**Category:** Utility
**Trigger:** When manager approves/rejects a leave request (real-time from admin action)
**Message:**
```
Hi {{1}},

Your leave request for {{2}} to {{3}} ({{4}}) has been {{5}}.

{{6}}

Current balance: {{7}} days remaining.

— Bombay Engineering Syndicate HR
```
**Variables:** name, from date, to date, leave type, approved/rejected, rejection reason (or empty), remaining balance

#### 2.4 Salary Slip Ready
**Template name:** `salary_slip_ready`
**Category:** Utility
**Trigger:** When salary slip status changes to `slip_generated` or `paid`
**Message:**
```
Hi {{1}},

Your salary slip for {{2}} {{3}} is ready.

Net Salary: ₹{{4}}
Status: {{5}}

View and download from the HRMS portal.

— Bombay Engineering Syndicate HR
```

#### 2.5 Monthly Attendance Summary
**Template name:** `monthly_attendance_summary`
**Category:** Utility
**Trigger:** 1st of each month (alongside existing email cron)
**Message:**
```
Hi {{1}},

Your attendance summary for {{2}} {{3}}:

Present: {{4}} days
Absent: {{5}} days
Leaves: {{6}} days
Late: {{7}} times
Working Hours: {{8}} hrs

Full report available on the HRMS portal.

— Bombay Engineering Syndicate HR
```

#### 2.6 Leave Balance Reminder (Monthly)
**Template name:** `leave_balance_reminder`
**Category:** Utility
**Trigger:** 1st of each month
**Message:**
```
Hi {{1}},

Your leave balance as of {{2}}:

{{3}}

Apply for leaves through the HRMS portal.

— Bombay Engineering Syndicate HR
```
**Variable {{3}}:** Formatted list like "CL: 5 | SL: 8 | EL: 12.5"

#### 2.7 Holiday Reminder (Day Before)
**Template name:** `holiday_reminder`
**Category:** Utility
**Trigger:** Cron at 6:00 PM, day before a holiday
**Message:**
```
Hi {{1}},

Reminder: Tomorrow ({{2}}) is {{3}}.

The office will be closed. Enjoy your holiday!

— Bombay Engineering Syndicate HR
```

#### 2.8 Salary Advance Status
**Template name:** `salary_advance_update`
**Category:** Utility
**Trigger:** When advance request is approved/rejected/disbursed
**Message:**
```
Hi {{1}},

Your salary advance request of ₹{{2}} has been {{3}}.

{{4}}

— Bombay Engineering Syndicate HR
```
**Variable {{4}}:** Repayment details if approved, rejection reason if rejected

---

## Phase 3: Inbound Self-Service (Employee → HRMS)

### How It Works

Employee sends a WhatsApp message → webhook receives it → parse intent → query database → auto-reply within 24-hour service window (FREE).

### 3.1 Intent Detection

Simple keyword matching (no AI needed):

| Employee Types | Intent | Action |
|---------------|--------|--------|
| "leave", "leave balance", "how many leaves" | `leave_balance` | Query `mx_employee_leave_balance` |
| "attendance", "my attendance", "late" | `attendance_today` | Query today's `mx_attendance` |
| "salary", "salary slip", "payslip" | `salary_info` | Query latest `mx_salary_slip` |
| "apply leave", "leave request" | `apply_leave` | Start interactive leave flow |
| "holiday", "holidays", "next holiday" | `next_holiday` | Query `mx_holiday_master` |
| "help", "menu", "hi", "hello" | `main_menu` | Send interactive menu |
| Anything else | `unknown` | Reply with menu options |

### 3.2 Main Menu (Interactive List)

When employee sends "hi" or "menu":

```
Welcome to BES HRMS! Choose an option:

📋 My Attendance
  → Today's Status
  → This Month Summary

🏖️ Leave
  → Check Balance
  → Apply Leave
  → My Leave Requests

💰 Salary
  → Latest Salary Slip
  → Salary Structure

📅 Holidays
  → Upcoming Holidays

ℹ️ Help
  → Contact HR
```

Sent as WhatsApp Interactive List message (native UI, no typing needed).

### 3.3 Leave Balance Response

Employee asks "leave balance" or selects from menu:

```
Your Leave Balance (FY 2025-26):

Casual Leave: 5 / 12
Sick Leave: 10 / 12
Earned Leave: 12.5 / 15
Emergency Leave: 3 / 3
Comp-Off: 1.5 available

Total Available: 32 days

Need to apply? Type "apply leave" or visit the HRMS portal.
```

Query: `mx_employee_leave_balance` WHERE `userID` and current FY, joined with `mx_leave_type` for names.

### 3.4 Today's Attendance Response

Employee asks "attendance" or "am I late":

```
Today's Attendance (23 Feb 2026):

Check-in: 9:12 AM
Check-out: — (still working)
Status: Present
Late: No (within grace period)

Working hours so far: 7h 23m
```

Or if late:
```
Today's Attendance (23 Feb 2026):

Check-in: 9:42 AM ⚠️
Status: Present (Late)
Late by: 27 minutes

Submit a remark via HRMS portal if needed.
```

Query: `mx_attendance` WHERE `userID` and `attendanceDate = CURDATE()`.

### 3.5 Monthly Attendance Summary

Employee asks "this month attendance":

```
February 2026 Attendance:

Working Days: 18 (so far)
Present: 16
Absent: 0
Leaves: 2 (1 CL, 1 SL)
Late: 3 times
Early Checkout: 1

Pending days: 3 (24, 25, 26 Feb)
```

Query: `mx_attendance` WHERE `userID` and current month aggregate.

### 3.6 Apply Leave (Interactive Flow)

Employee types "apply leave" → multi-step interactive conversation:

**Step 1:** Send interactive buttons:
```
What type of leave?
[Casual Leave] [Sick Leave] [Earned Leave]
```

**Step 2:** After selection:
```
From which date? (Reply with date, e.g., 25-02-2026)
```

**Step 3:** After from date:
```
To which date? (Reply with date, or type "same" for single day)
```

**Step 4:** Confirmation with buttons:
```
Confirm leave request:

Type: Casual Leave
From: 25 Feb 2026
To: 26 Feb 2026
Days: 2

Your CL balance: 5 days

[Confirm] [Cancel]
```

**Step 5:** On confirm → INSERT into `mx_leave_request`, notify manager via WhatsApp.

State management: Track conversation state in `mx_wa_inbound` or a simple `mx_wa_conversation_state` table:

```sql
CREATE TABLE mx_wa_conversation_state (
    stateID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    currentFlow VARCHAR(50) NULL,
    currentStep INT DEFAULT 0,
    flowData JSON NULL,
    expiresAt DATETIME NOT NULL,
    UNIQUE KEY uk_user (userID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Expires after 10 minutes of inactivity (prevents stale conversations).

### 3.7 Salary Info Response

Employee asks "salary" or "salary slip":

```
Latest Salary: January 2026

Gross: ₹XX,XXX
Deductions: ₹X,XXX
Net Salary: ₹XX,XXX
Status: Paid (15 Jan 2026)

Reply "download" for PDF slip.
```

If they reply "download" → send the PDF via `sendDocument()` if `slipPDF` path exists.

### 3.8 Holiday List Response

Employee asks "holidays" or "next holiday":

```
Upcoming Holidays:

🗓️ 14 Mar 2026 — Holi (National)
🗓️ 02 Apr 2026 — Good Friday (National)
🗓️ 14 Apr 2026 — Ambedkar Jayanti (National)

Total remaining holidays in FY: 8
```

Query: `mx_holiday_master` WHERE `holidayDate >= CURDATE()` ORDER BY date LIMIT 5.

### 3.9 Unknown Message Fallback

```
Sorry, I didn't understand that. Here's what I can help with:

1️⃣ Leave Balance — type "leave balance"
2️⃣ Today's Attendance — type "attendance"
3️⃣ Salary Slip — type "salary"
4️⃣ Apply Leave — type "apply leave"
5️⃣ Holidays — type "holidays"

Or type "menu" for full options.
```

---

## Phase 4: Manager Notifications

### 4.1 New Leave Request Alert (to Manager)

**Template name:** `manager_leave_request`
**Trigger:** When employee submits leave request
**To:** Manager's WhatsApp (via `managerID` → manager's `whatsappNumber`)
**Message:**
```
Hi {{1}},

{{2}} has requested {{3}} leave:

From: {{4}}
To: {{5}}
Days: {{6}}
Reason: {{7}}

Please approve or reject via the HRMS admin panel.

— BES HRMS
```

### 4.2 Absenteeism Alert (to Manager)

**Template name:** `manager_absent_alert`
**Trigger:** Cron at 11:00 AM — if employee absent with no leave
**To:** Manager's WhatsApp
**Message:**
```
Hi {{1}},

The following team members have no attendance today ({{2}}):

{{3}}

— BES HRMS
```

### 4.3 Late Pattern Alert (to HR/Manager)

**Template name:** `late_pattern_alert`
**Trigger:** Weekly cron (Monday) — employees late 3+ times in past week
**To:** HR admin + manager
**Message:**
```
Weekly Late Report:

{{1}} was late {{2}} times last week:
{{3}}

— BES HRMS
```

---

## Phase 5: Admin Controls

### 5.1 HRMS Settings Page — WhatsApp Tab

Add a new tab in `/xadmin/mod/hrms-settings/` for WhatsApp configuration:

| Setting | Default | Description |
|---------|---------|-------------|
| `wa_enabled` | 0 | Master on/off switch |
| `wa_late_notification` | 1 | Send late alerts to employees |
| `wa_absent_notification` | 1 | Send absent alerts |
| `wa_leave_status_notification` | 1 | Send leave approval/rejection |
| `wa_salary_notification` | 1 | Send salary slip alerts |
| `wa_monthly_summary` | 1 | Send monthly attendance summary |
| `wa_holiday_reminder` | 1 | Send holiday eve reminders |
| `wa_manager_alerts` | 1 | Send alerts to managers |
| `wa_late_notify_time` | 10:30 | When to check for late arrivals |
| `wa_absent_notify_time` | 11:00 | When to check for absences |
| `wa_self_service` | 1 | Allow employee self-service queries |
| `wa_apply_leave` | 1 | Allow leave application via WhatsApp |

### 5.2 WhatsApp Log Viewer

New admin module: `/xadmin/mod/whatsapp-log/`

- View all sent/received messages
- Filter by employee, date, event type, status
- Resend failed messages
- View conversation threads per employee
- Export logs

### 5.3 Bulk WhatsApp Number Import

In HRMS Settings → WhatsApp tab:
- Upload CSV with `employeeCode, whatsappNumber`
- Bulk opt-in/opt-out
- Preview before applying

---

## Implementation Priority

| Priority | Item | Effort | Impact |
|----------|------|--------|--------|
| P0 | 1.1 Add whatsappNumber field | Low | Foundation |
| P0 | 1.2-1.3 API config + core library | Medium | Foundation |
| P0 | 1.4-1.5 Queue + inbound tables | Low | Foundation |
| P0 | 1.6 Queue processor cron | Medium | Foundation |
| P0 | 1.7 Webhook endpoint | Medium | Foundation |
| P1 | 2.1 Late arrival notification | Low | High — immediate value |
| P1 | 2.2 Absent notification | Low | High |
| P1 | 2.3 Leave status update | Low | High |
| P1 | 3.1-3.2 Intent detection + main menu | Medium | High — employee self-service |
| P1 | 3.3 Leave balance query | Low | High — most asked question |
| P1 | 3.4 Today's attendance query | Low | High |
| P2 | 2.4 Salary slip notification | Low | Medium |
| P2 | 2.5 Monthly summary | Low | Medium |
| P2 | 3.5-3.8 Other self-service queries | Medium | Medium |
| P2 | 3.6 Apply leave via WhatsApp | High | High — but complex |
| P2 | 4.1-4.3 Manager notifications | Medium | Medium |
| P3 | 5.1 Admin settings UI | Medium | Low — can use DB directly initially |
| P3 | 5.2 Log viewer | Medium | Low — check DB/logs initially |
| P3 | 5.3 Bulk import | Low | Low |

---

## Meta Template Approval Strategy

Submit templates in this order (approval takes 24-48 hours each):

**Batch 1 (submit immediately after number verification):**
1. `late_arrival_alert` — Utility
2. `absent_notification` — Utility
3. `leave_status_update` — Utility

**Batch 2 (submit after Batch 1 approved):**
4. `salary_slip_ready` — Utility
5. `monthly_attendance_summary` — Utility
6. `leave_balance_reminder` — Utility

**Batch 3:**
7. `holiday_reminder` — Utility
8. `manager_leave_request` — Utility
9. `salary_advance_update` — Utility
10. `late_pattern_alert` — Utility

All are **Utility** category (₹0.13/msg) — none are Marketing.

---

## Cost Estimate

Assuming 20 employees:

| Notification | Frequency | Messages/Month | Cost/Month |
|-------------|-----------|----------------|------------|
| Late alerts | ~5/day avg | ~100 | ₹13 |
| Absent alerts | ~2/day avg | ~40 | ₹5.20 |
| Leave status | ~15/month | ~15 | ₹1.95 |
| Salary slips | 1/month/employee | ~20 | ₹2.60 |
| Monthly summary | 1/month/employee | ~20 | ₹2.60 |
| Holiday reminder | ~1/month | ~20 | ₹2.60 |
| Manager alerts | ~20/month | ~20 | ₹2.60 |
| **Total outbound** | | **~235** | **~₹30/month** |
| Self-service replies | Free (24hr window) | Unlimited | **₹0** |

**Total estimated cost: ₹30-50/month** for 20 employees.

---

## Files to Create/Modify

### New Files
| File | Purpose |
|------|---------|
| `/home/bombayengg/whatsapp-config.php` | API credentials (outside web root) |
| `core/whatsapp-api.inc.php` | WhatsApp Cloud API wrapper class |
| `core/wa-webhook.php` | Inbound message webhook handler |
| `core/wa-intent-handler.inc.php` | Parse and respond to employee queries |
| `cron/wa-queue-processor.php` | Send queued outbound messages |
| `cron/wa-late-absent-notify.php` | Daily late/absent check and queue |
| `cron/wa-holiday-reminder.php` | Day-before holiday notification |
| `database_migrations/hrms_whatsapp_tables.sql` | All new tables + column additions |

### Modified Files
| File | Change |
|------|--------|
| `xadmin/mod/admin-user/x-admin-user-add-edit.php` | Add whatsappNumber + optIn fields |
| `xadmin/mod/admin-user/x-admin-user.inc.php` | Handle whatsappNumber in save/update |
| `xadmin/mod/employee-leave/x-employee-leave.inc.php` | Queue leave status notification on approve/reject |
| `xadmin/mod/salary-slip/x-salary-slip.inc.php` | Queue salary notification on generate/pay |
| `xadmin/mod/hrms-settings/x-hrms-settings.inc.php` | Add WhatsApp settings tab |
| `xsite/mod/hrms/x-profile.php` | Add opt-in toggle for employees |
| `cron/hrms-monthly-attendance-email.php` | Add WhatsApp summary alongside email |
| `.htaccess` | Exempt webhook URL from site rewrite |

---

## Security Considerations

1. **Webhook verification** — Meta sends a verify token on setup; validate it matches our config
2. **Signature validation** — Verify `X-Hub-Signature-256` header on every webhook POST using app secret
3. **Rate limiting** — Cap inbound processing to prevent abuse
4. **Employee matching** — Only respond to registered WhatsApp numbers; ignore unknown numbers
5. **Data sensitivity** — Never send full salary amounts in templates; use "view on portal" for details
6. **Opt-in compliance** — Meta requires explicit opt-in before sending; track in DB
7. **Token storage** — Access token stored outside web root, never in version control
8. **Webhook URL** — Use HTTPS (already enforced), exempt from CSP frame restrictions if needed
