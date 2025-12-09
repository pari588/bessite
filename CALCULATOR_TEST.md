# Calculator Testing Guide

## Testing the Calculator Fix

The calculator has been fixed to properly handle section codes and display results.

### What Was Fixed

1. **Section Code Parsing** - The dropdown now correctly extracts `section_code` from the getAllTDSRates() response
2. **Result Display** - Fixed to handle both `tds_rate`/`tcs_rate` and `tds_amount`/`tcs_amount` field names
3. **Error Prevention** - Added fallback values to prevent undefined variable errors

### How to Test

#### Method 1: Using the Web Interface

1. Navigate to: `/tds/admin/calculator.php`
2. Select Calculation Type: **TDS (Deduction)**
3. Enter Base Amount: **100000** (₹1,00,000)
4. Select Section Code: **194C - Contractor/Sub-contractor (5%)**
5. Leave Custom Rate empty
6. Click **Calculate**

**Expected Result:**
```
Base Amount: ₹100,000.00
Rate: 5%
TDS Amount: ₹5,000.00
Net Amount: ₹95,000.00
Section Code: 194C
```

#### Method 2: Test with Different Types

**Test Case 1: Rent (194A)**
- Base Amount: 50000
- Section: 194A - Rent/License fees (10%)
- Expected TDS: 5000

**Test Case 2: Professional Fees (194J)**
- Base Amount: 75000
- Section: 194J - Fee for professional services (10%)
- Expected TDS: 7500

**Test Case 3: TCS Calculation**
1. Select Type: **TCS (Collection)**
2. Base Amount: **100000**
3. Section: **206C-1H** (0.1%)
4. Expected TCS: 100 (if above threshold)

### Available Section Codes

**TDS Sections:**
| Code | Description | Rate |
|------|-------------|------|
| 194A | Rent/License fees | 10% |
| 194C | Contractor/Sub-contractor | 5% |
| 194D | Insurance Commission | 10% |
| 194E | Mutual Fund | 20% |
| 194F | Dividend | 20% |
| 194G | Commission/Brokerage | 10% |
| 194H | Commission/Remuneration | 5% |
| 194I | Search/Fishing vessels | 10% |
| 194J | Fee for professional services | 10% |
| 194K | Brokerage/Commission | 10% |
| 194LA | Life Insurance Premium | 10% |
| 194LB | Life Insurance Premium (non-individual) | 10% |

**TCS Sections:**
| Code | Description | Rate |
|------|-------------|------|
| 206C | Sale of goods (Motor vehicle) | 1% |
| 206C-1H | Sale of goods (other) | 0.1% |

### Debugging

If calculations aren't working:

1. **Check Section Code** - Use only valid codes from the dropdown
2. **Check Amount** - Must be a positive number
3. **Browser Console** - Look for JavaScript errors
4. **Server Logs** - Check PHP error logs

---

**Status: ✅ Calculator Fixed and Ready to Use**
