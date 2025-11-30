<?php
require_once("config.inc.php");
require_once("core/core.inc.php");

echo "<pre>";
echo "=== RENAMING PUMP INQUIRY TABLE ===\n\n";

// Step 1: Rename the table
echo "Step 1: Renaming table bombay_pump_inquiry to mx_pump_inquiry...\n";
$DB->sql = "RENAME TABLE `bombay_pump_inquiry` TO `mx_pump_inquiry`";
if ($DB->dbQuery()) {
    echo "✓ Table renamed successfully\n\n";
} else {
    echo "✗ Failed to rename table: " . $DB->con->error . "\n";
    exit;
}

// Step 2: Verify the table exists
echo "Step 2: Verifying table exists...\n";
$DB->sql = "SHOW TABLES LIKE 'mx_pump_inquiry'";
$DB->dbRows();
if ($DB->numRows > 0) {
    echo "✓ mx_pump_inquiry table verified\n\n";
} else {
    echo "✗ Table not found after rename!\n";
    exit;
}

// Step 3: Update setModVars in x-pump-inquiry.inc.php
echo "Step 3: Updating setModVars in x-pump-inquiry.inc.php...\n";
$inc_file = "/home/bombayengg/public_html/xadmin/mod/pump-inquiry/x-pump-inquiry.inc.php";
$content = file_get_contents($inc_file);
$new_content = str_replace(
    '"TBL" => "bombay_pump_inquiry"',
    '"TBL" => "pump_inquiry"',
    $content
);

if ($new_content !== $content) {
    file_put_contents($inc_file, $new_content);
    echo "✓ Updated x-pump-inquiry.inc.php\n\n";
} else {
    echo "⚠ No changes needed in x-pump-inquiry.inc.php\n\n";
}

// Step 4: Update references in x-pump-inquiry-list.php
echo "Step 4: Updating references in x-pump-inquiry-list.php...\n";
$list_file = "/home/bombayengg/public_html/xadmin/mod/pump-inquiry/x-pump-inquiry-list.php";
$content = file_get_contents($list_file);
$new_content = str_replace(
    '`bombay_pump_inquiry`',
    '`' . $DB->pre . 'pump_inquiry`',
    $content
);

// Also remove the comment about not using mx_ prefix
$new_content = str_replace(
    "// Use bombay_pump_inquiry table directly (not mx_pump_inquiry)\n",
    "// Using mx_pump_inquiry table with standard database prefix\n",
    $new_content
);

if ($new_content !== $content) {
    file_put_contents($list_file, $new_content);
    echo "✓ Updated x-pump-inquiry-list.php\n\n";
} else {
    echo "⚠ No significant changes in x-pump-inquiry-list.php\n\n";
}

// Step 5: Update references in frontend file
echo "Step 5: Checking frontend pump-inquiry file...\n";
$frontend_file = "/home/bombayengg/public_html/xsite/mod/pump-inquiry/x-pump-inquiry-inc.php";
if (file_exists($frontend_file)) {
    $content = file_get_contents($frontend_file);
    $new_content = str_replace(
        'bombay_pump_inquiry',
        $DB->pre . 'pump_inquiry',
        $content
    );

    if ($new_content !== $content) {
        file_put_contents($frontend_file, $new_content);
        echo "✓ Updated x-pump-inquiry-inc.php\n\n";
    } else {
        echo "⚠ No changes needed in x-pump-inquiry-inc.php\n\n";
    }
} else {
    echo "⚠ Frontend file not found at $frontend_file\n\n";
}

echo "=== COMPLETE ===\n";
echo "All references have been updated to use mx_pump_inquiry table.\n";
echo "</pre>";
?>
