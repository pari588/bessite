# Voucher Overtime Narration System

## Overview
The voucher system has been enhanced to include detailed overtime narration when settling driver payments. Each settlement voucher now displays a row-wise breakdown of all overtime records.

## Files Involved

### 1. Settlement Function
**File:** `/xadmin/mod/driver-management/x-driver-management.inc.php`
**Function:** `settlePayment()`

This function creates the voucher when settling driver overtime payments. It:
- Fetches all overtime records being settled
- Builds detailed narration with date, times, OT hours, and amounts
- Creates the voucher with the narration in `voucherDesc` field

**Narration Format Stored in Database:**
```
06-Dec-2025: 10:00 AM - 01:00 AM | OT: 5.00 hrs | Rs. 625.00
08-Dec-2025: 10:00 AM - 11:00 PM | OT: 3.00 hrs | Rs. 375.00
```

### 2. Voucher Preview/Print Template
**File:** `/xadmin/mod/voucher/inc/x-invoice-priview.php`

This template is used for both preview and PDF print generation. It:
- Parses the `voucherDesc` field using regex
- Displays each overtime record as a separate table row
- Shows 3 columns: PARTICULARS (Date), DETAILS (Time range + OT hrs), AMOUNT

**Regex Pattern Used:**
```php
/^(\d{2}-\w{3}-\d{4}):\s*(.+?)\s*\|\s*OT:\s*([\d.]+)\s*hrs\s*\|\s*Rs\.\s*([\d,.]+)$/
```

### 3. PDF Generation
**File:** `/xadmin/mod/voucher/inc/voucher-print.inc.php`
**Function:** `createPOPDF()`

- Uses `x-invoice-priview.php` template to generate PDF
- Caches PDF in `/uploads/voucher/` directory
- Stores filename in `voucherFile` database field

### 4. CSS Styles
**Files:**
- `/xadmin/mod/voucher/inc/invoice-print.css` - Main styles
- `/xadmin/mod/voucher/inc/invoice-print-pdf.css` - PDF-specific styles

## Database Structure

### Table: `mx_voucher`
| Field | Description |
|-------|-------------|
| `voucherID` | Primary key |
| `voucherNo` | Voucher number (e.g., V/25-26/012) |
| `voucherDate` | Date of voucher |
| `voucherDebitTo` | Driver name(s) |
| `voucherTitle` | "Driver Overtime Settlement" |
| `voucherDesc` | Detailed narration (newline-separated) |
| `voucherAmt` | Total amount |
| `voucherRef` | Reference text |
| `voucherFile` | Cached PDF filename |

## Voucher Layout

```
+------------------+---------------------------+------------+
| PARTICULARS      | DETAILS                   | AMOUNT     |
+------------------+---------------------------+------------+
| Driver Overtime  |                           | 4,039.00   |
| Settlement       |                           |            |
+------------------+---------------------------+------------+
| 06-Dec-2025      | 10:00 AM - 01:00 AM       | Rs. 625.00 |
|                  | (5.00 hrs)                |            |
+------------------+---------------------------+------------+
| 08-Dec-2025      | 10:00 AM - 11:00 PM       | Rs. 375.00 |
|                  | (3.00 hrs)                |            |
+------------------+---------------------------+------------+
| ...              | ...                       | ...        |
+------------------+---------------------------+------------+
|                  | TOTAL                     | 4,039.00   |
+------------------+---------------------------+------------+
```

## Troubleshooting

### Print Shows Old Content
If print shows old content after template changes:
1. Delete cached PDF files:
   ```bash
   rm -f /home/bombayengg/public_html/uploads/voucher/<voucher-filename>.pdf
   ```
2. Clear database cache:
   ```sql
   UPDATE mx_voucher SET voucherFile = '' WHERE voucherID = <id>;
   ```

### Preview Works But Print Doesn't
The print uses the same template as preview (`x-invoice-priview.php`). Check:
1. Cached PDF file exists in `/uploads/voucher/`
2. `voucherFile` field has old filename in database
3. Clear both as shown above

## Modifying the Layout

### To Change Column Widths
Edit `/xadmin/mod/voucher/inc/x-invoice-priview.php`:
```php
<th class="lp0 full-border" width="50%">PARTICULARS</th>
<th class="center full-border" width="30%">DETAILS</th>
<th class="center full-border" width="20%">AMOUNT</th>
```

### To Change Font Size
Edit the inline styles in the PHP loop:
```php
echo '<td class="full-border" style="font-size:11px;">' . $date . '</td>';
```

### To Change Narration Format
Modify `settlePayment()` function in `x-driver-management.inc.php`:
```php
$narrationLines[] = "{$date}: {$fromTime} - {$toTime} | OT: {$otHrs} hrs | Rs. {$totalPay}";
```
**Note:** If you change the format, also update the regex in `x-invoice-priview.php`.

## Related Documentation
- Driver Management Module: `/claudemd/driver-management.md`
- Petty Cash Integration: Vouchers are linked to petty cash book entries

## Change History
- **Dec 2024:** Added detailed overtime narration to settlement vouchers
- **Dec 2024:** Implemented row-wise layout for voucher display
- **Dec 2024:** Removed pincode from voucher template, changed "Telefax" to "Mobile"
