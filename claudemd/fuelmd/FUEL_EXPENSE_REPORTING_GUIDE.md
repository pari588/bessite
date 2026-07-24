# Fuel Expenses Report - User Guide

**Date Updated:** December 1, 2025
**Version:** 1.1 (Enhanced Filtering & Standard Buttons)
**Status:** ✅ Complete & Ready to Use

---

## Overview

The Fuel Expenses Module now includes an enhanced **Detailed Reporting System** with dynamic filtering and standard xadmin UI/UX for search, print, and export functionalities. This report allows admins and managers to review individual fuel expense transactions, filter them by vehicle, payment status, and date ranges, and export or print the results.

---

## Features

### Feature: Detailed Transaction View (Primary View)
- All individual expense records are displayed.
- Shows: Date, Vehicle, Amount, Quantity, Status, Paid Date, Remarks.
- Sortable by date (newest first).
- Displays subtotals for Total, Paid, and Unpaid amounts in the footer.
- Provides full transaction-level visibility.

**Use When:** You need detailed transaction records to audit, verify, or review all individual fuel spending.

---

## Filtering Options

The reporting system provides the following filters. The search filter UI is initially hidden and toggled by clicking the "Search" button.

### 1. **Vehicle Filter**
- **All Vehicles** - Default (shows all vehicles).
- Select a specific vehicle to filter by that vehicle only.
- Dropdown populated from active vehicles.

### 2. **Payment Status Filter**
- **All Status** - Default (shows paid and unpaid).
- **Paid** - Shows only marked as paid.
- **Unpaid** - Shows only marked as unpaid.

### 3. **Date Range Filter** ("From Date" & "To Date")
- **From Date** - Start date for filtering.
- **To Date** - End date for filtering.
- **Default UI State:** Both "From Date" and "To Date" fields are empty when the page first loads.
- **Backend Query Default:** On initial load (when UI fields are empty), the report dynamically filters results from the 1st of the current month up to the current day.
- **Use When:** You need to view transactions within a specific date range.

### How to Use Filters:
1.  Click the "Search" button in the top right to open the filter panel.
2.  Select your desired Vehicle, Payment Status, and/or enter a "From Date" and "To Date".
3.  Click the "Search" button (within the filter panel) to apply the filters.
4.  To reset filters, clear all selected options/dates and click "Search" again.

---

## How to Access the Report

### From Admin Panel:
1. Navigate to: `/xadmin/`
2. Go to: **Fuel Expenses → Report** (in the sidebar menu).
3. Or access directly: `https://www.bombayengg.net/xadmin/fuel-expense-report/`

---

## Using the Report

### Step 1: Open and Apply Filters
1.  On the report page, the filter panel is hidden by default.
2.  Click the "Search" button (magnifying glass icon) in the top right to reveal the filter panel.
3.  Select your desired Vehicle, Payment Status, and/or enter a "From Date" and "To Date".
4.  Click the "Search" button (within the filter panel) to apply the filters.
5.  The report data will update based on your selected criteria.

### Step 2: Analyze Data
- Review the detailed transaction records in the main table.
- Totals for paid and unpaid amounts are displayed in the table footer.

### Step 3: Print the Report
1.  Click the "Print" button (printer icon) in the top right.
2.  A new popup window will open, displaying a formatted version of the current report data suitable for printing.
3.  Use your browser's print function (e.g., Ctrl+P or Cmd+P) from within that popup window.

### Step 4: Export to Excel
1.  Click the "Export" button (excel icon) in the top right.
2.  A popup will appear with options to specify record range and export format (CSV or XLSX).
3.  Choose your desired options and click "EXPORT".
4.  A file (`fuel-expenses-report.csv` or `.xlsx`) will download to your computer.

---

## Standard Buttons

The report page features the following standard xadmin buttons in the top right (`page-nav`) area:

### 1. Search Button
- **Appearance:** Magnifying glass icon (`fa-search`) and text "Search".
- **Functionality:** Toggles the visibility of the filter panel (`div.search-data`) using a smooth slide animation.
- **Behavior:** The filter panel is hidden by default on page load. Clicking this button will show it, and clicking it again will hide it.

### 2. Print Button
- **Appearance:** Printer icon (`fa-print`) and text "Print".
- **Functionality:** Opens a new popup window with a print-friendly version of the current report data. This popup leverages `xadmin`'s `x-print.php` utility.
- **Behavior:** Only visible if there are records (`MXTOTREC > 0`) in the report.

### 3. Export to Excel Button
- **Appearance:** Excel file icon (`fa-file-excel-o`) and text "Export".
- **Functionality:** Opens a popup window ("Export Details") that allows you to specify the record range and choose the export format (CSV or XLSX).
- **Behavior:** Only visible if there are records (`MXTOTREC > 0`) in the report. Select your options and click "EXPORT" to download the file.

---

## Summary Statistics

The report footer displays **summary statistics** for the currently filtered data:
- **Total Unpaid:** Sum of all unpaid expense amounts.
- **Total Paid:** Sum of all paid expense amounts.

---

## Use Cases

### For Managers:
✅ **Spending Trends** - Easily review fuel expenses over time.
✅ **Vehicle Performance** - Analyze fuel consumption per vehicle.
✅ **Payment Status** - Track paid and unpaid bills for follow-up.

### For Accountants:
✅ **Detailed Audit Trail** - Verify each transaction.
✅ **Reconciliation** - Match with financial records.
✅ **Reporting** - Generate reports for financial analysis.

### For Fleet Managers:
✅ **Cost Analysis** - Monitor fuel costs per vehicle.
✅ **Budgeting** - Aid in projecting future fuel expenses.

---

## File Locations

```
/xadmin/mod/fuel-expense/
├── x-fuel-expense-report.php          [Main report page, now enhanced]
├── x-fuel-expense-report-add-edit.php [Empty - view only]
├── x-fuel-expense-list.php            [List with bulk actions]
└── x-fuel-expense.inc.php             [AJAX handler]
```

---

## Troubleshooting

### Q: No data showing in report?
**A:** Check your filter selections. Click the "Search" button to open the filter panel and ensure the date range or other criteria are broad enough to include existing records.

### Q: Print button does not open a popup?
**A:** Ensure your browser does not block popups for `bombayengg.net`.

### Q: Export to Excel popup does not open?
**A:** Similar to the print function, ensure your browser allows popups.

---

## Future Enhancements (Phase 2)

- 📋 Planned: Charts/Graphs for visual trend analysis.
- 📋 Planned: Email Reports for automated monthly delivery.
- 📋 Planned: Budget Alerts for notifications on spending limits.
- 📋 Planned: Fuel Efficiency metrics (e.g., cost per km/liter).
- 📋 Planned: Driver Assignment for driver-wise analysis.

---

## Summary

The Fuel Expenses Report provides:
- ✅ A single, detailed transaction view.
- ✅ Standard xadmin filter for Vehicle, Payment Status, From Date, To Date.
- ✅ Standard xadmin "Search" button to toggle the filter panel.
- ✅ Standard xadmin "Print" button for report popups.
- ✅ Standard xadmin "Export" button for CSV/XLSX downloads.
- ✅ Backend query logic with default date range application.
- ✅ Real-time data retrieval with efficient filtering.
- ✅ Professional xadmin styling and layout.
- ✅ Color-coded payment status tracking.
- ✅ Detailed financial summaries and totals.

**Status:** 🟢 **COMPLETE & PRODUCTION READY**

---

**Last Updated:** December 1, 2025
**Created By:** Development Team
**Ready for Use:** ✅ YES