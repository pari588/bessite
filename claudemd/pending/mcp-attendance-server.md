# Plan: MCP Server — HRMS Attendance Management

**Status**: Pending
**Created**: 2026-04-03
**Estimated effort**: 2–3 hours

---

## Goal

Build an MCP (Model Context Protocol) server that exposes HRMS attendance data and operations to Claude Code (desktop/mobile). Enables natural language attendance management without needing a dev session.

**Example interactions once built:**
- "Show me Sakshi's attendance for March"
- "Mark Ganesh as leave on April 2nd"
- "Who came in late this week?"
- "Correct Manish's check-in on March 14 to 10:30"
- "Give me a summary of all employees for March"

---

## Architecture

```
Claude (web / desktop / iOS / Android)
        │
        │  HTTPS (Streamable HTTP transport)
        ▼
https://www.bombayengg.net/mcp/attendance/
        │  (PHP endpoint — no separate Node process needed)
        ▼
  MySQL (bombayengg DB)
  mx_attendance / mx_x_admin_user / camsPunch
```

**MCP Server location**: `/home/bombayengg/public_html/mcp/attendance/`
**Language**: PHP (keeps it simple — no Node.js process to manage, runs under existing Apache/PHP setup)
**Transport**: Streamable HTTP over HTTPS (SSE is being deprecated)
**Auth**: Authless with a secret token checked in the request header (internal tool)
**DB**: Same credentials as the rest of the site

---

## Tools to Expose

### Read Operations
| Tool | Description |
|------|-------------|
| `get_employees` | List all employees with userID, name, biometricID, schedule |
| `get_attendance` | Query attendance by employee + date range. Returns checkIn, checkOut, status, hours |
| `get_monthly_summary` | Present/absent/leave/late counts per employee for a month |
| `get_late_report` | Who came in late in a given period |
| `get_missing_checkout` | Employees with check-in but no check-out for a date |

### Write Operations
| Tool | Description |
|------|-------------|
| `update_checkin` | Correct check-in time for a specific employee + date |
| `update_checkout` | Correct check-out time for a specific employee + date |
| `mark_leave` | Mark one or more days as leave for an employee |
| `mark_holiday` | Mark a date as holiday for all/specific employees |
| `bulk_mark_present` | Mark a date range as present for specified employees (for device downtime scenarios) |

---

## MCP Server File Structure

```
/home/bombayengg/public_html/mcp/attendance/
├── index.php         # MCP endpoint — handles initialize, tools/list, tools/call
├── tools.php         # Tool definitions (name, description, inputSchema)
├── handlers/
│   ├── read.php      # get_employees, get_attendance, summaries
│   └── write.php     # update_checkin, mark_leave, etc.
└── auth.php          # Secret token validation
```

---

## Claude Code / claude.ai Config (once built)

Add via **claude.ai → Settings → Integrations → Add custom connector**:

```
URL:  https://www.bombayengg.net/mcp/attendance/
```

Once added on claude.ai, it's automatically available on:
- Claude web
- Claude desktop
- Claude iOS & Android (read-only add — configured via web first)

---

## Key DB Tables Reference

```sql
mx_attendance         -- attendanceID, userID, attendanceDate, checkIn, checkOut,
                      -- workingHours, isLate, lateMinutes, isEarlyCheckout, earlyMinutes,
                      -- attendanceStatus (present/absent/leave/half_day/holiday),
                      -- source (biometric/manual/auto)

mx_x_admin_user       -- userID, displayName, biometricID,
                      -- workStartTime, workEndTime, lateGraceMinutes

camsPunch             -- user_id (biometricID), punch_time, punch_type (0=in, 1=out)
```

---

## Implementation Steps

1. **Setup** — `npm init`, install `@modelcontextprotocol/sdk` and `mysql2`
2. **DB layer** — connection pool, parameterised query helper, employee name lookup
3. **Read tools** — `get_attendance`, `get_employees`, `get_monthly_summary`, `get_late_report`
4. **Write tools** — `update_checkin`, `update_checkout`, `mark_leave` with auto-recalculation of `workingHours`, `isLate`, `lateMinutes`
5. **MCP server bootstrap** — register all tools, start stdio transport
6. **Test** — connect from Claude Code desktop, run a few natural language queries
7. **Document** — add connection instructions to this file

---

## Considerations

- **Mobile supported**: iOS & Android work with remote MCP servers as of July 2025. Configure once on claude.ai, available everywhere.
- **No SSH tunnel needed**: Anthropic's cloud calls our HTTPS endpoint directly. Server is already public.
- **PHP is fine**: Streamable HTTP transport is just JSON over HTTP POST — PHP handles this natively.
- **Auth**: Simple secret token in `Authorization: Bearer <token>` header. Token stored outside web root.
- **Safety**: Write tools must validate employee exists before update. No deletes exposed.
- **Recalculation**: All write tools must recalculate `workingHours`, `isLate`, `lateMinutes`, `isEarlyCheckout`, `earlyMinutes` using the employee's `workStartTime`/`lateGraceMinutes`.
- **SSE deprecation**: Article notes SSE may be deprecated — build with Streamable HTTP from the start.
