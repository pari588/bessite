<?php
/**
 * TDS AutoFile - Test Data Prefill Script
 * Creates comprehensive test data for all TDS forms
 * Date: December 9, 2025
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

// Disable output buffering to see progress
if (ob_get_level()) {
    ob_end_flush();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    echo "=== TDS AutoFile Test Data Prefill ===\n\n";

    // ============================================
    // 1. ADD VENDORS
    // ============================================
    echo "1. Adding Vendors...\n";

    $vendors = [
        ['name' => 'ABC Corporation', 'pan' => 'ABCDE1234F', 'category' => 'company'],
        ['name' => 'XYZ Traders', 'pan' => 'XYZAB5678G', 'category' => 'individual'],
        ['name' => 'DEF Industries Ltd', 'pan' => 'DEFGH0987K', 'category' => 'company'],
        ['name' => 'MNO Services', 'pan' => 'MNOIJ2345L', 'category' => 'individual'],
        ['name' => 'PQR Manufacturing', 'pan' => 'PQRST6789M', 'category' => 'company'],
        ['name' => 'UVW Consultants', 'pan' => 'UVWXY3456N', 'category' => 'individual'],
    ];

    $vendor_ids = [];
    foreach ($vendors as $vendor) {
        $stmt = $pdo->prepare("
            INSERT INTO vendors (firm_id, name, pan, category, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([1, $vendor['name'], $vendor['pan'], $vendor['category']]);
        $vendor_ids[$vendor['pan']] = $pdo->lastInsertId();
        echo "   ✓ Added: {$vendor['name']} ({$vendor['pan']})\n";
    }

    // ============================================
    // 2. ADD INVOICES FOR Q2 (Jul-Sep 2025)
    // ============================================
    echo "\n2. Adding Q2 Invoices (July-September 2025)...\n";

    $invoices = [
        // Vendor 1: ABC Corporation (194A - 10%)
        ['vendor_pan' => 'ABCDE1234F', 'invoice_no' => 'INV-2025-001', 'date' => '2025-07-15', 'base_amount' => 100000, 'section' => '194A', 'tds_rate' => 10],
        ['vendor_pan' => 'ABCDE1234F', 'invoice_no' => 'INV-2025-002', 'date' => '2025-08-20', 'base_amount' => 150000, 'section' => '194A', 'tds_rate' => 10],

        // Vendor 2: XYZ Traders (194C - 1%)
        ['vendor_pan' => 'XYZAB5678G', 'invoice_no' => 'INV-2025-003', 'date' => '2025-07-22', 'base_amount' => 500000, 'section' => '194C', 'tds_rate' => 1],

        // Vendor 3: DEF Industries (194H - 5%)
        ['vendor_pan' => 'DEFGH0987K', 'invoice_no' => 'INV-2025-004', 'date' => '2025-08-10', 'base_amount' => 200000, 'section' => '194H', 'tds_rate' => 5],

        // Vendor 4: MNO Services (194J - 10%)
        ['vendor_pan' => 'MNOIJ2345L', 'invoice_no' => 'INV-2025-005', 'date' => '2025-09-05', 'base_amount' => 300000, 'section' => '194J', 'tds_rate' => 10],

        // Vendor 5: PQR Manufacturing (194Q - 0.1%)
        ['vendor_pan' => 'PQRST6789M', 'invoice_no' => 'INV-2025-006', 'date' => '2025-09-18', 'base_amount' => 1000000, 'section' => '194Q', 'tds_rate' => 0.1],
    ];

    foreach ($invoices as $invoice) {
        $tds_amount = $invoice['base_amount'] * $invoice['tds_rate'] / 100;
        $vendor_id = $vendor_ids[$invoice['vendor_pan']];

        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                firm_id, vendor_id, invoice_no, invoice_date,
                base_amount, section_code, tds_rate, tds_amount, total_tds, fy, quarter,
                allocation_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            1,                          // firm_id
            $vendor_id,                 // vendor_id
            $invoice['invoice_no'],     // invoice_no
            $invoice['date'],           // invoice_date
            $invoice['base_amount'],    // base_amount
            $invoice['section'],        // section_code
            $invoice['tds_rate'],       // tds_rate
            $tds_amount,                // tds_amount
            $tds_amount,                // total_tds
            '2025-26',                  // fy
            'Q2',                       // quarter
            'unallocated'               // allocation_status (will update after challan allocation)
        ]);

        echo "   ✓ Invoice {$invoice['invoice_no']}: ₹{$invoice['base_amount']} (TDS: ₹{$tds_amount})\n";
    }

    // ============================================
    // 3. ADD CHALLANS FOR Q2
    // ============================================
    echo "\n3. Adding Q2 Challans...\n";

    $challans = [
        ['bsr' => '0021', 'date' => '2025-08-10', 'serial' => '1001', 'amount' => 15000],    // 10k + 5k
        ['bsr' => '0021', 'date' => '2025-08-15', 'serial' => '1002', 'amount' => 10000],    // 10k
        ['bsr' => '0021', 'date' => '2025-09-20', 'serial' => '1003', 'amount' => 40000],    // 1k + 30k + 5k + 4k
    ];

    $challan_ids = [];
    foreach ($challans as $challan) {
        $stmt = $pdo->prepare("
            INSERT INTO challans (
                firm_id, bsr_code, challan_date, challan_serial_no, amount_total, amount_tds,
                fy, quarter, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            1,                      // firm_id
            $challan['bsr'],        // bsr_code
            $challan['date'],       // challan_date
            $challan['serial'],     // challan_serial_no
            $challan['amount'],     // amount_total
            $challan['amount'],     // amount_tds
            '2025-26',              // fy
            'Q2'                    // quarter
        ]);

        $challan_ids[] = $pdo->lastInsertId();
        echo "   ✓ Challan BSR {$challan['bsr']}-{$challan['serial']}: ₹{$challan['amount']}\n";
    }

    // ============================================
    // 4. CREATE ALLOCATIONS (Link Invoices to Challans)
    // ============================================
    echo "\n4. Creating Invoice-to-Challan Allocations...\n";

    // Get the invoice IDs we just created
    $stmt = $pdo->prepare("SELECT id, invoice_no, total_tds FROM invoices WHERE fy='2025-26' AND quarter='Q2' ORDER BY id");
    $stmt->execute();
    $invoice_list = $stmt->fetchAll();

    // Manual allocation mapping
    $allocations = [
        0 => ['challan_id' => $challan_ids[0], 'amount' => 10000],     // INV-2025-001: 10k to challan 1
        1 => ['challan_id' => $challan_ids[1], 'amount' => 10000],     // INV-2025-002: 15k → 10k to challan 2 + 5k to challan 0
        2 => ['challan_id' => $challan_ids[2], 'amount' => 5000],      // INV-2025-003: 5k to challan 3
        3 => ['challan_id' => $challan_ids[2], 'amount' => 10000],     // INV-2025-004: 10k to challan 3
        4 => ['challan_id' => $challan_ids[2], 'amount' => 30000],     // INV-2025-005: 30k to challan 3
        5 => ['challan_id' => $challan_ids[2], 'amount' => 1000],      // INV-2025-006: 1k to challan 3
    ];

    foreach ($invoice_list as $idx => $invoice) {
        $alloc = $allocations[$idx];

        $stmt = $pdo->prepare("
            INSERT INTO challan_allocations (
                invoice_id, challan_id, allocated_tds, created_at
            ) VALUES (?, ?, ?, NOW())
        ");

        $stmt->execute([
            $invoice['id'],
            $alloc['challan_id'],
            $alloc['amount']
        ]);

        // Update invoice allocation status to complete
        $stmt = $pdo->prepare("
            UPDATE invoices SET allocation_status = 'complete'
            WHERE id = ?
        ");
        $stmt->execute([$invoice['id']]);

        echo "   ✓ {$invoice['invoice_no']}: ₹{$alloc['amount']} allocated\n";
    }

    // ============================================
    // 5. ADD INVOICES FOR Q3 (Oct-Dec 2025)
    // ============================================
    echo "\n5. Adding Q3 Invoices (October-December 2025)...\n";

    $invoices_q3 = [
        ['vendor_pan' => 'ABCDE1234F', 'invoice_no' => 'INV-2025-101', 'date' => '2025-10-12', 'base_amount' => 75000, 'section' => '194A', 'tds_rate' => 10],
        ['vendor_pan' => 'XYZAB5678G', 'invoice_no' => 'INV-2025-102', 'date' => '2025-11-08', 'base_amount' => 400000, 'section' => '194C', 'tds_rate' => 1],
        ['vendor_pan' => 'DEFGH0987K', 'invoice_no' => 'INV-2025-103', 'date' => '2025-12-05', 'base_amount' => 250000, 'section' => '194H', 'tds_rate' => 5],
    ];

    foreach ($invoices_q3 as $invoice) {
        $tds_amount = $invoice['base_amount'] * $invoice['tds_rate'] / 100;
        $vendor_id = $vendor_ids[$invoice['vendor_pan']];

        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                firm_id, vendor_id, invoice_no, invoice_date,
                base_amount, section_code, tds_rate, tds_amount, total_tds, fy, quarter,
                allocation_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            1, $vendor_id, $invoice['invoice_no'],
            $invoice['date'], $invoice['base_amount'], $invoice['section'],
            $invoice['tds_rate'], $tds_amount, $tds_amount, '2025-26', 'Q3', 'unallocated'
        ]);

        echo "   ✓ Invoice {$invoice['invoice_no']}: ₹{$invoice['base_amount']} (TDS: ₹{$tds_amount})\n";
    }

    // ============================================
    // 6. ADD CHALLANS FOR Q3
    // ============================================
    echo "\n6. Adding Q3 Challans...\n";

    $challans_q3 = [
        ['bsr' => '0021', 'date' => '2025-10-25', 'serial' => '2001', 'amount' => 15000],
        ['bsr' => '0021', 'date' => '2025-11-30', 'serial' => '2002', 'amount' => 20000],
    ];

    $challan_ids_q3 = [];
    foreach ($challans_q3 as $challan) {
        $stmt = $pdo->prepare("
            INSERT INTO challans (
                firm_id, bsr_code, challan_date, challan_serial_no, amount_total, amount_tds,
                fy, quarter, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([1, $challan['bsr'], $challan['date'], $challan['serial'], $challan['amount'], $challan['amount'], '2025-26', 'Q3']);
        $challan_ids_q3[] = $pdo->lastInsertId();
        echo "   ✓ Challan BSR {$challan['bsr']}-{$challan['serial']}: ₹{$challan['amount']}\n";
    }

    // ============================================
    // 7. ALLOCATE Q3 INVOICES
    // ============================================
    echo "\n7. Allocating Q3 Invoices to Challans...\n";

    $stmt = $pdo->prepare("SELECT id, invoice_no, total_tds FROM invoices WHERE fy='2025-26' AND quarter='Q3' ORDER BY id");
    $stmt->execute();
    $invoice_list_q3 = $stmt->fetchAll();

    $allocations_q3 = [
        0 => ['challan_id' => $challan_ids_q3[0], 'amount' => 7500],    // 7.5k
        1 => ['challan_id' => $challan_ids_q3[1], 'amount' => 4000],    // 4k
        2 => ['challan_id' => $challan_ids_q3[1], 'amount' => 12500],   // 12.5k
    ];

    foreach ($invoice_list_q3 as $idx => $invoice) {
        $alloc = $allocations_q3[$idx];

        $stmt = $pdo->prepare("
            INSERT INTO challan_allocations (
                invoice_id, challan_id, allocated_tds, created_at
            ) VALUES (?, ?, ?, NOW())
        ");

        $stmt->execute([$invoice['id'], $alloc['challan_id'], $alloc['amount']]);

        $stmt = $pdo->prepare("UPDATE invoices SET allocation_status = 'complete' WHERE id = ?");
        $stmt->execute([$invoice['id']]);

        echo "   ✓ {$invoice['invoice_no']}: ₹{$alloc['amount']} allocated\n";
    }

    // ============================================
    // 8. SUMMARY
    // ============================================
    echo "\n8. Summary Statistics:\n";

    $stmt = $pdo->prepare("
        SELECT 'Q2' as quarter, COUNT(*) as inv_count, SUM(total_tds) as total_tds
        FROM invoices WHERE fy='2025-26' AND quarter='Q2'
        UNION ALL
        SELECT 'Q3', COUNT(*), SUM(total_tds)
        FROM invoices WHERE fy='2025-26' AND quarter='Q3'
    ");
    $stmt->execute();
    $summaries = $stmt->fetchAll();

    foreach ($summaries as $summary) {
        echo "   {$summary['quarter']}: {$summary['inv_count']} invoices, TDS: ₹{$summary['total_tds']}\n";
    }

    $stmt = $pdo->prepare("
        SELECT 'Q2' as quarter, COUNT(*) as ch_count, SUM(amount_tds) as total_paid
        FROM challans WHERE fy='2025-26' AND quarter='Q2'
        UNION ALL
        SELECT 'Q3', COUNT(*), SUM(amount_tds)
        FROM challans WHERE fy='2025-26' AND quarter='Q3'
    ");
    $stmt->execute();
    $challan_summaries = $stmt->fetchAll();

    foreach ($challan_summaries as $summary) {
        echo "   {$summary['quarter']} Challans: {$summary['ch_count']} records, Total Paid: ₹{$summary['total_paid']}\n";
    }

    // Commit transaction
    $pdo->commit();

    echo "\n✅ Test data prefill completed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Login to /tds/admin/\n";
    echo "2. View invoices, challans, and reconciliation\n";
    echo "3. Run compliance checks\n";
    echo "4. Generate forms (26Q, 24Q, 16)\n";
    echo "5. Submit for e-filing\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    die(1);
}
?>
