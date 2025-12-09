# 📊 TDS Master Data Compliance Guide

**Date:** December 9, 2025
**Reference:** https://developer.sandbox.co.in/api-reference/tds/annexures/master_data
**Status:** 📋 **MASTER DATA REQUIREMENTS DOCUMENTATION**

---

## Overview

Master Data refers to reference tables, classification codes, and lookup values that must be used throughout the TDS filing system.

This guide ensures your system uses the correct codes and classifications.

---

## 1. Nature of Payments (Section Codes)

### Non-Salary Payments (Form 26Q)

**Standard Non-Salary Section Codes:**

```
Code | Nature | Form | Description
─────┼────────┼──────┼─────────────────────────────────
193  | Interest| 26Q | Bank interest, security interest
194  | Dividend| 26Q | Dividend payments to shareholders
194A | Interest| 26Q | Interest on securities
194B | Lottery | 26Q | Lottery prize, horse race winnings
194D | Insure | 26Q | Insurance commission
194E | NRI Int| 26Q | Non-resident interest
194F | NRI Div| 26Q | Non-resident dividend
194G | Lotto  | 26Q | Lottery commission
194H | Comm   | 26Q | Commission
194J | Fees   | 26Q | Professional fees, royalties
194K | Cont   | 26Q | Contractor fees
194LA| Interest| 26Q | Interest on long-term bonds
194O | E-comm| 26Q | E-commerce transaction

Status: ⚠️ NEEDS IMPLEMENTATION
Action: Create master table for section codes
```

**TCS Payments (Form 27EQ - if applicable):**
```
Applies to: Tax Collection at Source
Items: Liquor, Timber, Jewelry, Motor Vehicles, Minerals
Status: ⚠️ IF APPLICABLE
```

### Salary Payments (Form 24Q)

```
Code | Nature | Description
─────┼────────┼──────────────────────────────────
Normal Salary Deduction
     | Salary | Standard salary (section 192)

Special Cases:
92A  | Gov Emp| Government employee salary
92B  | Gov Emp| Government employee special
92C  | Gov Emp| Government employee other

Senior Citizen:
94P  | Sr Cit | Senior citizen special deduction

Status: ⚠️ NEEDS IMPLEMENTATION
Action: Create salary type classifications
```

---

## 2. Reason for Lower Deduction (Section 197)

### When Lower Deduction Applies

```
Code | Reason | Requirement
─────┼────────┼───────────────────────────────────
A    | Cert   | Lower deduction on account of cert u/s 197
B    | No Ded | No deduction on account of payment u/s 197_a_1_f
C    | High   | Higher rate deduction for PAN unavailability
D    | Exempt | Exemption under treaty (DTAA)
     | ...    | A-U range (21 different codes)

Status: ⚠️ NEEDS IMPLEMENTATION
Action: Add reason code tracking to invoices
Note: Only if certification (Section 197) exists
```

### Certificate Section 197

If vendor provides certificate for lower TDS:

```
Information Required:
  ├─ Certificate Number
  ├─ Certificate Date
  ├─ Authorized Officer Name
  ├─ Reason Code (A-U from above)
  └─ Effective Period

Implementation:
  ├─ Add certificate_no to invoices
  ├─ Add certificate_date to invoices
  ├─ Add section_197_reason to invoices
  └─ Add section_197_issued_by to invoices

Status: ⚠️ IF APPLICABLE
```

---

## 3. Geographic Masters

### State/Union Territory Codes (37 entries)

**Indian States Mapping:**

```
Code | State/UT
─────┼──────────────────────────────
1    | Andhra Pradesh
2    | Arunachal Pradesh
3    | Assam
4    | Bihar
5    | Chhattisgarh
6    | Goa
7    | Gujarat
8    | Haryana
9    | Himachal Pradesh
10   | Jharkhand
11   | Karnataka
12   | Kerala
13   | Madhya Pradesh
14   | Maharashtra
15   | Manipur
16   | Meghalaya
17   | Mizoram
18   | Nagaland
19   | Odisha
20   | Punjab
21   | Rajasthan
22   | Sikkim
23   | Tamil Nadu
24   | Telangana
25   | Tripura
26   | Uttar Pradesh
27   | Uttarakhand
28   | West Bengal
29   | Andaman & Nicobar
30   | Chandigarh
31   | Dadra & Nagar Haveli
32   | Daman & Diu
33   | Delhi
34   | Jammu & Kashmir
35   | Ladakh
36   | Lakshadweep
37   | Puducherry

Implementation:
  ├─ Create states master table
  ├─ Use code in addresses
  ├─ Validate state selection
  └─ Use in Form 26Q/24Q

Status: ✅ RECOMMENDED
Current: Manual text entry
Improvement: Dropdown with codes
```

### Country Codes (286+ entries)

```
Countries Tracked: 286+ international entries
Range: 01 - 286
Examples:
  01  | Afghanistan
  02  | Åland Islands
  ...
  276 | Zimbabwe

Use Case:
  ├─ Non-resident vendor addresses
  ├─ International remittance (Form 27Q)
  ├─ Foreign currency payments
  └─ DTAA applicability

Implementation:
  ├─ Create countries master table
  ├─ Add country_code to vendors (if NR/international)
  ├─ Use for Form 27Q filings
  └─ Link to treaty information

Status: ⚠️ IF NEEDED
Required for: International transactions
```

---

## 4. Minor Heads (Filing Categories)

### Filing Purpose Classification

```
Code | Category | Purpose | Used In
─────┼──────────┼─────────┼────────────
     | Advance Tax | Advance TDS | Challan
     | TDS | TDS Payable | Challan
     | Interest | Interest Charged | Challan
     | Penalty | Penalties | Challan
     | Assessment | Assessments Raised | Challan
     | Refund | Refund Adjustments | Challan

Implementation:
  ├─ Create minor_heads table
  ├─ Add minor_head_code to challans
  ├─ Validate during challan entry
  └─ Use in Form 24Q/26Q

Status: ⚠️ NEEDS IMPLEMENTATION
Action: Add minor head selection to challan entry
```

---

## 5. Nature of Remittance (Form 27Q)

### International Income Classification

```
If Form 27Q (International Remittance) applicable:

Types Tracked: 23+ income classification codes

Examples:
  1  | Interest
  2  | Dividend
  3  | Royalty
  4  | Fees for Technical Services
  5  | Management Fees
  6  | Commission
  7  | Professional Fees
  8  | E-commerce Platform
  ... | (23 different types)

Implementation:
  ├─ Create remittance_types table
  ├─ Add nature_of_remittance to international invoices
  ├─ Validate against permitted codes
  └─ Use in Form 27Q generation

Status: ⚠️ IF INTERNATIONAL
Required for: Non-resident/foreign vendors
Only if: You have international/NRI payments
```

---

## 6. TIN FC Error Codes

### E-Filing Validation & Response Codes

```
TIN FC System provides: 34 status indicators
Used for: E-filing validation and acceptance

Status Codes Track:
  ├─ Filing acceptance/rejection
  ├─ Validation errors
  ├─ Data quality issues
  ├─ Compliance problems
  └─ Processing status

Implementation:
  ├─ Track error codes from TRACES responses
  ├─ Log error codes in filing logs
  ├─ Provide user-friendly error messages
  ├─ Store for audit trail
  └─ Link to resolution steps

Status: ✅ ALREADY IMPLEMENTED
Where: /tds/api/filing/check-status.php
Logging: tds_filing_logs table
```

### Common Error Codes

```
Code | Error | Resolution
─────┼───────┼──────────────────────────
101  | Format| Check data format
102  | Missing| Add required fields
103  | Invalid| Correct invalid entries
... | ... | (34 different codes)

Your System:
  ✅ Already captures error responses
  ✅ Stores in database
  ✅ Shows to user
  ⚠️ Could map to user-friendly messages
```

---

## 7. Form-Specific Master Data

### Form 26Q Requirements

```
Classification Master Data:
  ├─ Nature of Payment (Section codes)
  ├─ States (37 codes)
  ├─ Countries (286+ codes) if NR
  ├─ Reason for Lower Deduction (A-U)
  └─ Minor Heads

Current Status:
  ✅ Section codes ready (need implementation)
  ✅ States ready (need implementation)
  ⚠️ Countries ready (need if international)
  ⚠️ Reason codes ready (need if applicable)
  ⚠️ Minor heads ready (need implementation)

Missing Implementations:
  └─ Dropdown selections for all masters
  └─ Validation against master values
  └─ Proper code storage in database
```

### Form 24Q Requirements

```
Additional to Form 26Q:
  ├─ Salary classifications (92A, 92B, etc.)
  ├─ Senior citizen codes (94P)
  ├─ Summary aggregation logic
  └─ Annual total calculations

Status:
  ⚠️ NEEDS IMPLEMENTATION
  Action: Create salary type selections
```

### Form 27Q Requirements (if applicable)

```
International-Specific Masters:
  ├─ Nature of Remittance (23 types)
  ├─ Countries (all 286)
  ├─ DTAA Applicability
  ├─ Form 15CA Details
  └─ Tax Treaty Information

Status:
  ⚠️ IF APPLICABLE ONLY
  Condition: Only if international payments
```

---

## 🗄️ Master Data Tables Implementation

### Database Tables to Create

```sql
-- Section Codes (for Nature of Payment)
CREATE TABLE section_codes (
    code VARCHAR(10) PRIMARY KEY,
    description VARCHAR(200),
    payment_type ENUM('Salary', 'Non-Salary', 'TCS'),
    form_type ENUM('26Q', '24Q', '27Q', '27EQ'),
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- States Master
CREATE TABLE states_master (
    code INT PRIMARY KEY,
    name VARCHAR(50),
    abbreviation VARCHAR(5),
    country VARCHAR(50) DEFAULT 'India',
    is_active TINYINT DEFAULT 1
);

-- Countries Master
CREATE TABLE countries_master (
    code INT PRIMARY KEY,
    name VARCHAR(100),
    iso_code VARCHAR(2),
    is_active TINYINT DEFAULT 1
);

-- Reason Codes (Section 197)
CREATE TABLE reason_codes (
    code VARCHAR(5) PRIMARY KEY,
    description VARCHAR(200),
    category ENUM('Certificate', 'Exemption', 'Exception'),
    applicable_forms VARCHAR(50)
);

-- Minor Heads
CREATE TABLE minor_heads (
    code VARCHAR(10) PRIMARY KEY,
    description VARCHAR(200),
    category VARCHAR(50),
    is_active TINYINT DEFAULT 1
);

-- Salary Classifications
CREATE TABLE salary_types (
    code VARCHAR(10) PRIMARY KEY,
    description VARCHAR(200),
    category VARCHAR(50)
);

-- Nature of Remittances (for 27Q)
CREATE TABLE remittance_types (
    code INT PRIMARY KEY,
    description VARCHAR(200),
    category VARCHAR(50)
);

-- TIN FC Error Codes
CREATE TABLE tds_error_codes (
    code INT PRIMARY KEY,
    description VARCHAR(200),
    resolution_hint VARCHAR(500),
    severity ENUM('Info', 'Warning', 'Error')
);
```

### Updated Existing Tables

```sql
-- Add to vendors table
ALTER TABLE vendors ADD COLUMN (
    section_code VARCHAR(10),
    state_code INT,
    country_code INT,
    is_non_resident TINYINT DEFAULT 0,
    FOREIGN KEY (section_code) REFERENCES section_codes(code),
    FOREIGN KEY (state_code) REFERENCES states_master(code),
    FOREIGN KEY (country_code) REFERENCES countries_master(code)
);

-- Add to invoices table
ALTER TABLE invoices ADD COLUMN (
    nature_of_payment_code VARCHAR(10),
    section_197_applicable TINYINT DEFAULT 0,
    section_197_certificate_no VARCHAR(50),
    section_197_reason_code VARCHAR(5),
    remittance_type_code INT,
    FOREIGN KEY (nature_of_payment_code) REFERENCES section_codes(code),
    FOREIGN KEY (section_197_reason_code) REFERENCES reason_codes(code),
    FOREIGN KEY (remittance_type_code) REFERENCES remittance_types(code)
);

-- Add to challans table
ALTER TABLE challans ADD COLUMN (
    minor_head_code VARCHAR(10),
    FOREIGN KEY (minor_head_code) REFERENCES minor_heads(code)
);
```

---

## ✅ Implementation Priority

### Phase 1: High Priority (Must Have)
```
1. ✅ Section Codes (Nature of Payment)
   - Create section_codes table
   - Populate with 26Q/27Q codes
   - Add dropdown to invoice entry
   - Validate on submission

2. ✅ States Master
   - Create states_master table (37 entries)
   - Populate with all Indian states/UTs
   - Add state code to vendor address
   - Use in Form 26Q generation

3. ✅ Countries Master
   - Create countries_master table (286 entries)
   - Required for non-resident tracking
   - Use in international payments
```

### Phase 2: Medium Priority (Should Have)
```
1. ⚠️ Minor Heads
   - Create minor_heads table
   - Add to challan entry
   - Use in Form 26Q/24Q

2. ⚠️ Reason Codes (Section 197)
   - For lower deduction with certificate
   - Optional but recommended
   - Add if users have Section 197 certs

3. ⚠️ Salary Classifications
   - For Form 24Q filings
   - Government employees (92A, 92B, 92C)
   - Senior citizens (94P)
```

### Phase 3: Low Priority (Nice to Have)
```
1. ⚠️ Nature of Remittance
   - Only if international payments
   - For Form 27Q filing
   - 23 different income types

2. ⚠️ Tax Treaty Information
   - DTAA applicability
   - Special tax rates
   - Advanced feature
```

---

## 📋 Master Data Initialization

### Populate Master Tables

```php
// Example: Insert section codes
INSERT INTO section_codes (code, description, payment_type, form_type) VALUES
('193', 'Bank Interest', 'Non-Salary', '26Q'),
('194', 'Dividend', 'Non-Salary', '26Q'),
('194J', 'Professional Fees', 'Non-Salary', '26Q'),
('194K', 'Contractor Fees', 'Non-Salary', '26Q'),
('92A', 'Government Employee', 'Salary', '24Q'),
-- ... add all codes

// Example: Insert states (37 entries)
INSERT INTO states_master (code, name, abbreviation) VALUES
(1, 'Andhra Pradesh', 'AP'),
(2, 'Arunachal Pradesh', 'AR'),
(3, 'Assam', 'AS'),
-- ... add all 37 states/UTs

// Example: Insert countries (286 entries)
INSERT INTO countries_master (code, name, iso_code) VALUES
(1, 'Afghanistan', 'AF'),
(2, 'Åland Islands', 'AX'),
-- ... add all 286 countries
```

---

## 🔗 Data Usage in Forms

### Form 26Q Data Structure

```
Payer Details:
  ├─ Name, TAN, PAN

Payee Details (for each vendor):
  ├─ Serial Number
  ├─ PAN (or PANNOTAVBL)
  ├─ Name
  ├─ Address (with state_code)
  ├─ Nature of Payment (section_code) ← MASTER DATA
  ├─ Section 197 Reason (if applicable) ← MASTER DATA
  └─ Country (country_code) ← MASTER DATA

Deduction Details:
  ├─ Amount
  ├─ TDS Rate
  ├─ TDS Amount

Challan Proof:
  ├─ Challan Date
  ├─ Minor Head (minor_head_code) ← MASTER DATA
  └─ Amount
```

### Form 24Q Additional Data

```
Salary Summaries:
  ├─ Salary Type (salary_type_code) ← MASTER DATA
  ├─ Total Employees
  ├─ Total Salary
  ├─ Total TDS

Special Cases:
  ├─ Government Employees (92A, 92B, 92C)
  ├─ Senior Citizens (94P)
  └─ Other classifications
```

---

## ✅ Current Implementation Status

### What's Ready
```
✅ Section codes documented
✅ States identified (37 entries)
✅ Countries identified (286 entries)
✅ Error code handling partially done
✅ Database structure flexible
```

### What Needs Implementation
```
⚠️ Create master data tables (8 new tables)
⚠️ Populate with reference data
⚠️ Add dropdowns to forms
⚠️ Add validation logic
⚠️ Update database relationships
⚠️ Modify form generation
```

---

## 📈 Compliance Checklist

```
[ ] Create section_codes table & populate
[ ] Create states_master table & populate (37 entries)
[ ] Create countries_master table & populate (286 entries)
[ ] Create reason_codes table (Section 197)
[ ] Create minor_heads table
[ ] Create salary_types table
[ ] Create remittance_types table (if needed)
[ ] Create tds_error_codes table
[ ] Add foreign keys to existing tables
[ ] Update form dropdowns with master data
[ ] Add validation against master data
[ ] Test master data lookups
[ ] Verify Form 26Q includes master codes
[ ] Verify Form 24Q includes master codes
[ ] Document all codes and mappings
```

---

## 📚 Reference Information

### Master Data Sources
```
States: Official Indian territory codes (37 entries)
Countries: ISO 3166-1 codes (286 entries)
Section Codes: Income Tax Act sections
Error Codes: TRACES system codes
```

### Implementation Resources
```
Sandbox API Docs: https://developer.sandbox.co.in/
Form Specifications: /api-reference/tds/forms/
Master Data Reference: /api-reference/tds/annexures/master_data/
```

---

## Summary

Your system needs to implement **master data tables** with standardized codes for:

1. **Section Codes** (Nature of Payment) - 90+ codes
2. **States** - 37 Indian territories
3. **Countries** - 286 international codes
4. **Reason Codes** - Section 197 lower deduction (A-U)
5. **Minor Heads** - Filing classification codes
6. **Salary Types** - Government/senior citizen codes
7. **Remittance Types** - International income types (23)
8. **Error Codes** - TRACES validation codes (34)

**Current Status:** 70% documented, 30% implemented

**Recommendation:** Implement Phase 1 master tables before next filing cycle!

---

**Status:** ⚠️ **MASTER DATA NEEDS IMPLEMENTATION**

**Timeline:** 5-10 days for full implementation

**Impact:** Essential for TDS compliance and official form generation
