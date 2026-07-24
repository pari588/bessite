# Fuel Expenses Management Module - Implementation Complete

**Project:** Thesis - Fuel Expenses Management & Reporting System
**Date:** November 29, 2025
**Status:** Implementation Complete - Ready for Testing
**Backend:** Tesseract OCR v4.1.1
**Database:** MySQL with `mx_vehicle` and `mx_fuel_expense` tables

---

## 1. SYSTEM OVERVIEW

The Fuel Expenses Module is a comprehensive system for managing fuel expenses across multiple vehicles with automatic bill OCR processing, payment tracking, and monthly reporting.

### Key Features Implemented:

✓ **Vehicle Management**
- Add/Edit/Delete vehicles
- Track vehicle names and registration numbers
- Default fuel type per vehicle
- Soft-delete capability

✓ **Fuel Expense Tracking**
- Upload fuel bills (JPG, PNG, PDF)
- Automatic OCR extraction (date + amount)
- Confidence score display
- Manual edit capability for extracted data
- Default "Unpaid" status for all new entries
- Payment status tracking (Paid/Unpaid)

✓ **OCR Bill Processing**
- Tesseract integration for automatic field extraction
- Date extraction with multiple format support (DD/MM/YYYY, DD-MM-YYYY, etc.)
- Amount extraction from currency patterns (₹, Rs, रु)
- Confidence scoring algorithm
- Fallback to manual entry if OCR fails

✓ **Payment Status Management**
- Bills default to "Unpaid" when created
- Accounts team can mark bills as "Paid"
- Payment date tracking
- Status filtering in reports

✓ **Monthly Reporting**
- Monthly summary with paid/unpaid breakdown
- Vehicle-wise filtering
- Date range filtering
- Payment status filtering
- CSV export functionality
- Individual transaction view

---

## 2. DATABASE SCHEMA

### Table: `mx_vehicle`

```
vehicleID        - INT, Primary Key, Auto-increment
vehicleName      - VARCHAR(100), Required
registrationNumber - VARCHAR(50), Optional
fuelType         - ENUM('Petrol','Diesel','CNG'), Default: 'Petrol'
notes            - TEXT, Optional
status           - TINYINT(1), Default: 1 (1=active, 0=deleted)
createdDate      - DATETIME, Auto-timestamp
modifiedDate     - DATETIME, Auto-update timestamp
```

### Table: `mx_fuel_expense`

```
fuelExpenseID    - INT, Primary Key, Auto-increment
vehicleID        - INT, Foreign Key to mx_vehicle
billDate         - DATE, Required
expenseAmount    - DECIMAL(10,2), Required
fuelQuantity     - DECIMAL(8,2), Optional
billImage        - VARCHAR(255), Optional (filename)
ocrText          - LONGTEXT, Raw OCR output
extractedData    - JSON, Extracted fields with confidence
confidenceScore  - INT(3), 0-100 percentage
manuallyEdited   - TINYINT(1), Flag if user edited OCR data
paymentStatus    - ENUM('Unpaid','Paid'), Default: 'Unpaid'
paidDate         - DATE, Date when marked as paid
remarks          - TEXT, User notes
status           - TINYINT(1), Default: 1 (1=active, 0=deleted)
createdDate      - DATETIME, Auto-timestamp
modifiedDate     - DATETIME, Auto-update timestamp
```

**Indexes:** vehicleID, billDate, paymentStatus, status
**Foreign Key:** vehicleID → mx_vehicle(vehicleID) ON DELETE CASCADE

---

## 3. FILE STRUCTURE

```
/xadmin/mod/
├── fuel-vehicle/                        # Vehicle Management Module
│   ├── x-fuel-vehicle.inc.php          # AJAX backend (ADD, UPDATE, DELETE)
│   ├── x-fuel-vehicle-list.php         # List view with modal add/edit
│   └── x-fuel-vehicle-add-edit.php     # Redirect (not used, modal-based)
│
└── fuel-expense/                        # Fuel Expense Management Module
    ├── x-fuel-expense.inc.php          # AJAX backend (ADD, UPDATE, DELETE, OCR, MARK_PAID)
    ├── x-fuel-expense-list.php         # Monthly report view with filters
    └── x-fuel-expense-add-edit.php     # Add/edit form with OCR integration

/core/
└── ocr.inc.php                         # Tesseract OCR integration library

/uploads/
└── fuel-expense/                       # Bill image storage

/config.inc.php                         # Added OCR configuration constants
```

---

## 4. CORE COMPONENTS

### 4.1 OCR Integration (`core/ocr.inc.php`)

**Main Functions:**

1. **processBillOCR($imagePath, $vehicleID = 0)**
   - Executes Tesseract on uploaded bill image
   - Returns raw text and structured extraction
   - Handles errors gracefully

2. **extractBillFields($ocrText)**
   - Parses OCR text for date and amount
   - Uses regex patterns for common formats
   - Calculates confidence scores

3. **extractDate(&$lines, &$fields)**
   - Finds dates in multiple formats
   - Validates year and date ranges
   - Returns confidence 0-100

4. **extractAmount(&$lines, &$fields)**
   - Searches for currency amounts
   - Pattern matching for ₹, Rs, रु
   - Validates amount ranges (0-100,000)

5. **validateBillData($extractedData)**
   - Validates extracted data
   - Returns validation errors

**Confidence Scoring:**
- Date perfect match: 95%
- Date partial match: 70%
- Date not found: 0%
- Amount with currency symbol: 90%
- Amount without currency: 60%
- Amount not found: 0%
- Overall: Average of date + amount

### 4.2 Vehicle Module (`fuel-vehicle/`)

**Backend (x-fuel-vehicle.inc.php):**
- AJAX actions: ADD, UPDATE, DELETE
- Input validation and sanitization
- Soft delete (status = 0)
- Module initialization via setModVars()

**List Page (x-fuel-vehicle-list.php):**
- Search by: vehicle name, registration, fuel type
- Modal-based add/edit form
- AJAX form submission
- Real-time list updates

**Key Features:**
- Required fields: Vehicle Name, Fuel Type
- Optional fields: Registration Number, Notes
- Status indicators for active vehicles
- Delete confirmation dialog

### 4.3 Fuel Expense Module (`fuel-expense/`)

**Backend (x-fuel-expense.inc.php):**
- AJAX actions:
  - **ADD**: Insert new expense (defaults to Unpaid)
  - **UPDATE**: Update expense details
  - **DELETE**: Soft delete (status = 0)
  - **OCR**: Process bill image via Tesseract
  - **MARK_PAID**: Set paymentStatus to Paid with date
  - **MARK_UNPAID**: Revert to Unpaid
  - **mxDelFile**: Delete bill image

- File handling:
  - Upload validation (JPG, PNG, PDF)
  - Unique filename generation
  - Directory creation if needed
  - File cleanup on errors

**List/Report Page (x-fuel-expense-list.php):**

*Search Filters:*
- Vehicle (dropdown)
- Payment Status (All/Paid/Unpaid)
- From Date, To Date (date range)

*Monthly Summary Report:*
- Grouped by Month/Year
- Paid amount, unpaid amount, total
- Summary of total paid and unpaid amounts in the table footer, respects search filters
- Transaction count per month
- Paid vs unpaid breakdown
- Yearly totals in footer
- CSV export button

*Individual Transactions Table:*
- Date, Vehicle, Amount, Quantity
- Payment status with color coding
- Paid date when applicable
- Edit, Mark Paid/Unpaid, Delete buttons

**Add/Edit Form (x-fuel-expense-add-edit.php):**

*Bill Upload Section:*
- File input (JPG, PNG, PDF)
- "Extract from Bill" button (shows after file selected)
- OCR processing with progress indicator

*OCR Results Display:*
- Extracted date with confidence %
- Extracted amount with confidence %
- Overall confidence score
- Clear OCR data button
- All fields editable by user

*Manual Entry Fields:*
- Bill Date (required)
- Expense Amount (required)
- Vehicle (required dropdown)
- Fuel Quantity (optional)
- Remarks (optional textarea)

*Payment Information (Edit Mode):*
- Current payment status display
- Paid date display
- "Mark as Paid" button (for unpaid expenses)

---

## 5. USER WORKFLOWS

### Workflow A: Add Expense via Bill Upload

1. Admin → Fuel Expenses → Add Expense
2. Upload bill image (JPG/PNG/PDF)
3. Click "Extract from Bill"
4. Tesseract processes image
5. System displays extracted date & amount with confidence scores
6. User verifies/edits extracted values
7. Select vehicle, enter quantity (optional)
8. Add remarks (optional)
9. Click "Add Expense"
10. Record saved as "Unpaid"

### Workflow B: Manual Entry (OCR Failed)

1. Admin → Fuel Expenses → Add Expense
2. Skip file upload or OCR fails
3. Manually enter:
   - Bill Date
   - Amount
   - Vehicle
   - Quantity (optional)
4. Click "Add Expense"
5. Record saved with no OCR data

### Workflow C: Payment Tracking

1. Accounts team → Fuel Expenses
2. Filter by "Unpaid" status
3. Review outstanding bills
4. Click "Mark Paid" on bill
5. System records current date as payment date
6. Status changes to "Paid"
7. Report automatically updates

### Workflow D: Monthly Reporting

1. Admin → Fuel Expenses (default view)
2. Monthly summary displayed automatically
3. Optional filters: Vehicle, Date Range, Payment Status
4. View shows paid vs unpaid breakdown per month
5. Click "View" to drill down into transactions
6. Click "Export to CSV" for reports

---

## 6. CONFIGURATION

### config.inc.php Constants Added:

```php
define("TESSERACT_PATH", "/usr/bin/tesseract");
define("TESSERACT_LANG", "eng");
define("FUEL_EXPENSE_UPLOAD_DIR", "fuel-expense");
```

### Tesseract Installation:

**Verify Installation:**
```bash
tesseract --version
# Should return: tesseract 4.1.1
```

**Current Status:** ✓ Installed and verified

---

## 7. BACKUP INFORMATION

**Database Backup Created:**
- Timestamp: Nov 29, 2025 15:18:41
- Filename: `bombayengg_backup_fuel_expenses_20251129_151841.sql`
- Location: `/home/bombayengg/public_html/database_backups/`
- Size: ~1.1 MB
- Contains: Full database snapshot before fuel expense tables

**Files Backed Up (Implicitly):**
- All existing xadmin modules
- config.inc.php (original in git)
- core files

---

## 8. API ENDPOINTS

### Vehicle Module AJAX (`/xadmin/mod/fuel-vehicle/x-fuel-vehicle.inc.php`)

```
POST /xadmin/mod/fuel-vehicle/x-fuel-vehicle.inc.php

Actions:
- xAction=ADD      → Add new vehicle
- xAction=UPDATE   → Update vehicle
- xAction=DELETE   → Delete vehicle

Parameters:
- vehicleName     (required for ADD/UPDATE)
- registrationNumber
- fuelType        (required, default: Petrol)
- notes
- vehicleID       (required for UPDATE/DELETE)
```

### Expense Module AJAX (`/xadmin/mod/fuel-expense/x-fuel-expense.inc.php`)

```
POST /xadmin/mod/fuel-expense/x-fuel-expense.inc.php

Actions:
- xAction=ADD      → Add new expense
- xAction=UPDATE   → Update expense
- xAction=DELETE   → Delete expense
- xAction=OCR      → Process bill image
- xAction=MARK_PAID   → Mark as paid
- xAction=MARK_UNPAID → Mark as unpaid

Parameters:
- vehicleID        (required)
- billDate         (required)
- expenseAmount    (required)
- fuelQuantity     (optional)
- remarks          (optional)
- billImage        (file upload for OCR)
- fuelExpenseID    (required for UPDATE/DELETE/MARK_*)
```

---

## 9. TESTING CHECKLIST

### Vehicle Management Tests:
- [ ] Add vehicle with required fields
- [ ] Edit existing vehicle
- [ ] Delete vehicle (soft delete)
- [ ] Search vehicles by name/registration/fuel type
- [ ] Verify pagination works
- [ ] Modal closes after save

### OCR Processing Tests:
- [ ] Upload JPG bill image
- [ ] Upload PNG bill image
- [ ] Upload PDF bill image
- [ ] Verify OCR extracts date correctly
- [ ] Verify OCR extracts amount correctly
- [ ] Confidence scores display properly
- [ ] Manual edit of extracted fields works
- [ ] Large files (>5MB) rejected
- [ ] Invalid file types rejected

### Expense Management Tests:
- [ ] Add expense via OCR
- [ ] Add expense via manual entry
- [ ] Edit expense
- [ ] Delete expense
- [ ] Verify new expenses default to "Unpaid"
- [ ] Mark expense as Paid
- [ ] Mark paid expense back to Unpaid
- [ ] Payment date recorded correctly

### Reporting Tests:
- [ ] Monthly summary displays correctly
- [ ] Paid/unpaid amounts calculated correctly
- [ ] Filter by vehicle works
- [ ] Filter by date range works
- [ ] Filter by payment status works
- [ ] CSV export includes all data
- [ ] Totals in footer are correct
- [ ] Individual transactions view works

### Integration Tests:
- [ ] Expense linked to vehicle correctly
- [ ] Foreign key constraint works (cascade delete)
- [ ] Status filtering works (active/deleted)
- [ ] CSRF token validation works
- [ ] Session validation works

---

## 10. KNOWN LIMITATIONS & FUTURE ENHANCEMENTS

### Current Limitations:
1. OCR processes synchronously (user waits for result)
2. Supports only English OCR (TESSERACT_LANG = "eng")
3. Maximum file size 5MB
4. No expense categorization (all are fuel)
5. No mileage/efficiency tracking
6. No recurring expenses
7. No multi-bill upload

### Recommended Future Enhancements:
1. Async OCR processing with background jobs
2. Multi-language OCR support
3. GST/Tax extraction from bills
4. Cost per kilometer analysis
5. Mileage tracking
6. Expense categories
7. Bulk upload capability
8. Email notifications on payment status
9. Mobile app integration
10. Dashboard widget

---

## 11. TROUBLESHOOTING

### Issue: OCR not extracting date/amount
**Solution:**
1. Verify Tesseract installation: `tesseract --version`
2. Check TESSERACT_PATH in config.inc.php
3. Ensure bill image is clear and readable
4. Try manual entry as fallback

### Issue: Upload fails
**Solution:**
1. Check `/uploads/fuel-expense/` directory exists and is writable
2. Verify file size < 5MB
3. Check file format is JPG, PNG, or PDF
4. Check PHP upload_max_filesize setting

### Issue: Payment status not updating
**Solution:**
1. Verify expense exists in database
2. Check paymentStatus field value (should be 'Paid' or 'Unpaid')
3. Verify MySQL connection and permissions
4. Check application logs

### Issue: Report totals incorrect
**Solution:**
1. Verify SQL query includes correct filters
2. Check for deleted expenses (status = 0)
3. Ensure amounts are stored as DECIMAL, not TEXT
4. Run COUNT and SUM manually in MySQL

---

## 12. NEXT STEPS FOR USER

1. **Test the modules:**
   - Add 2-3 vehicles
   - Add expenses with OCR processing
   - Test payment status updates
   - Run CSV export

2. **Customize if needed:**
   - Modify report fields
   - Add expense categories
   - Adjust OCR confidence thresholds
   - Add email notifications

3. **Deploy to production:**
   - Test with real bills
   - Train users on workflow
   - Set up backup schedule
   - Monitor OCR accuracy

4. **Thesis documentation:**
   - Document OCR performance metrics
   - Track accuracy rates
   - Measure processing time
   - Analyze false positives/negatives

---

## 13. SUPPORT & DOCUMENTATION

**Files Modified:**
- `/config.inc.php` - Added OCR configuration

**Files Created:**
- `/core/ocr.inc.php` - OCR integration (290+ lines)
- `/xadmin/mod/fuel-vehicle/x-fuel-vehicle.inc.php` - Backend (140+ lines)
- `/xadmin/mod/fuel-vehicle/x-fuel-vehicle-list.php` - List view (220+ lines)
- `/xadmin/mod/fuel-expense/x-fuel-expense.inc.php` - Backend (280+ lines)
- `/xadmin/mod/fuel-expense/x-fuel-expense-list.php` - Report view (350+ lines)
- `/xadmin/mod/fuel-expense/x-fuel-expense-add-edit.php` - Form (480+ lines)

**Total Code Lines:** ~1,750+ lines of production code

**Database Tables Created:**
- `mx_vehicle` (8 columns, 2 indexes)
- `mx_fuel_expense` (16 columns, 4 indexes, 1 FK)

**Upload Directory:**
- `/uploads/fuel-expense/` - Bill image storage

---

**Implementation completed successfully on November 29, 2025**
**Status: Ready for Testing and Deployment**

---

*This documentation is part of the Fuel Expenses Management thesis project.*
