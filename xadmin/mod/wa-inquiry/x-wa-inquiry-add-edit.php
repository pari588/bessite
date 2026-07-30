<?php
$id = 0;
$D = array();
if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"]);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM " . $DB->pre . $MXMOD["TBL"] . " WHERE status=? AND `" . $MXMOD["PK"] . "` =?";
    $D = $DB->dbRow();
}

if (empty($D)) {
    echo '<div class="wrap-right"><p>Inquiry not found.</p></div>';
    return;
}

// Status dropdown
$statusOpts = '<option value="">-- Select --</option>';
$statuses = ['new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted', 'converted' => 'Converted', 'closed' => 'Closed'];
foreach ($statuses as $k => $v) {
    $sel = ($D['inquiryStatus'] === $k) ? ' selected' : '';
    $statusOpts .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
}

// Assigned to dropdown
$assignedOpts = '<option value="">-- Select --</option>';
$offices = ['mumbai' => 'Mumbai', 'ahmedabad' => 'Ahmedabad'];
foreach ($offices as $k => $v) {
    $sel = ($D['assignedTo'] === $k) ? ' selected' : '';
    $assignedOpts .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
}

// Format phone
$phone = $D['fromNumber'] ?? '';
if (strlen($phone) >= 10) {
    $last10 = substr($phone, -10);
    $phoneFormatted = '+91 ' . substr($last10, 0, 5) . ' ' . substr($last10, 5);
} else {
    $phoneFormatted = $phone;
}

// Decode JSON fields
$parsedParams = json_decode($D['parsedParams'] ?? '{}', true);
$matchedProducts = json_decode($D['matchedProducts'] ?? '[]', true);
?>

<div class="wrap-right">
    <?php echo getPageNav(); ?>

    <div class="wrap-data" style="padding: 20px;">
        <!-- Header with reference and status -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:15px; border-bottom:2px solid #25D366;">
            <div>
                <h2 style="margin:0; color:#333;">
                    <?php echo htmlspecialchars($D['referenceNumber']); ?>
                    <span style="font-size:14px; background:#25D366; color:#fff; padding:3px 12px; border-radius:12px; margin-left:10px;">
                        via WhatsApp
                    </span>
                </h2>
                <p style="margin:5px 0 0; color:#666;">
                    <?php echo ucfirst($D['productType']); ?> Inquiry |
                    <?php echo date('d M Y, h:i A', strtotime($D['createdAt'])); ?>
                </p>
            </div>
            <div>
                <?php
                $colors = ['new' => '#28a745', 'contacted' => '#17a2b8', 'quoted' => '#ffc107', 'converted' => '#007bff', 'closed' => '#6c757d'];
                $color = $colors[$D['inquiryStatus']] ?? '#333';
                ?>
                <span style="background:<?php echo $color; ?>; color:#fff; padding:6px 16px; border-radius:20px; font-size:14px; font-weight:bold;">
                    <?php echo ucfirst($D['inquiryStatus']); ?>
                </span>
            </div>
        </div>

        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <!-- Left Column: Customer + Product Info -->
            <div style="flex:1; min-width:400px;">
                <!-- Customer Information -->
                <div style="background:#f9f9f9; border-left:4px solid #25D366; padding:15px; margin-bottom:15px;">
                    <h3 style="margin:0 0 10px; color:#25D366;">Customer Information</h3>
                    <table style="width:100%;">
                        <tr><td style="padding:5px; width:30%; font-weight:bold; color:#555;">Name:</td><td><?php echo htmlspecialchars($D['customerName'] ?: 'N/A'); ?></td></tr>
                        <tr><td style="padding:5px; font-weight:bold; color:#555;">Phone:</td><td><a href="https://wa.me/<?php echo $phone; ?>" target="_blank"><?php echo $phoneFormatted; ?></a></td></tr>
                        <?php if ($D['companyName']) { ?>
                        <tr><td style="padding:5px; font-weight:bold; color:#555;">Company:</td><td><?php echo htmlspecialchars($D['companyName']); ?></td></tr>
                        <?php } ?>
                        <tr><td style="padding:5px; font-weight:bold; color:#555;">City:</td><td><?php echo htmlspecialchars($D['city'] ?: 'N/A'); ?></td></tr>
                        <?php if ($D['email']) { ?>
                        <tr><td style="padding:5px; font-weight:bold; color:#555;">Email:</td><td><?php echo htmlspecialchars($D['email']); ?></td></tr>
                        <?php } ?>
                        <tr><td style="padding:5px; font-weight:bold; color:#555;">Assigned To:</td><td><?php echo ucfirst($D['assignedTo'] ?: 'N/A'); ?> Office</td></tr>
                    </table>
                </div>

                <!-- Requirement -->
                <div style="background:#f9f9f9; border-left:4px solid #157bba; padding:15px; margin-bottom:15px;">
                    <h3 style="margin:0 0 10px; color:#157bba;">Requirement</h3>
                    <p style="margin:0; white-space:pre-wrap;"><?php echo htmlspecialchars($D['requirementText'] ?: 'No requirement text captured'); ?></p>
                </div>

                <!-- Selected Product -->
                <?php if ($D['selectedProductTitle']) { ?>
                <div style="background:#f9f9f9; border-left:4px solid #ff9800; padding:15px; margin-bottom:15px;">
                    <h3 style="margin:0 0 10px; color:#ff9800;">Selected Product</h3>
                    <p style="margin:0; font-size:16px; font-weight:bold;"><?php echo htmlspecialchars($D['selectedProductTitle']); ?></p>
                    <?php if ($D['selectedProductID']) { ?>
                    <p style="margin:5px 0 0; color:#666;">Pump ID: <?php echo intval($D['selectedProductID']); ?></p>
                    <?php } ?>
                </div>
                <?php } ?>

                <!-- AI Matching Details -->
                <?php if (!empty($parsedParams) || !empty($matchedProducts)) { ?>
                <div style="background:#f9f9f9; border-left:4px solid #9c27b0; padding:15px; margin-bottom:15px;">
                    <h3 style="margin:0 0 10px; color:#9c27b0;">AI Matching Details</h3>
                    <?php if (!empty($parsedParams)) { ?>
                    <p style="margin:0 0 8px; font-weight:bold; color:#555;">Parsed Parameters:</p>
                    <table style="width:100%; font-size:13px;">
                        <?php foreach ($parsedParams as $k => $v) {
                            if ($v !== null && $k !== 'rawText') { ?>
                            <tr><td style="padding:3px 5px; width:40%; color:#666;"><?php echo htmlspecialchars($k); ?>:</td><td><?php echo htmlspecialchars(is_array($v) ? json_encode($v) : $v); ?></td></tr>
                        <?php } } ?>
                    </table>
                    <?php } ?>
                    <?php if (!empty($matchedProducts)) { ?>
                    <p style="margin:10px 0 5px; font-weight:bold; color:#555;">Matched Products:</p>
                    <table style="width:100%; font-size:13px;">
                        <tr style="background:#eee;"><th style="padding:5px;">Pump ID</th><th>Detail ID</th><th>Score</th></tr>
                        <?php foreach ($matchedProducts as $mp) { ?>
                        <tr>
                            <td style="padding:5px;"><?php echo intval($mp['pumpID'] ?? 0); ?></td>
                            <td style="padding:5px;"><?php echo intval($mp['pumpDID'] ?? 0); ?></td>
                            <td style="padding:5px;"><?php echo intval($mp['score'] ?? 0); ?>/100</td>
                        </tr>
                        <?php } ?>
                    </table>
                    <?php } ?>
                </div>
                <?php } ?>

                <!-- Email Sent -->
                <?php if ($D['emailSentAt']) { ?>
                <p style="color:#28a745; font-size:13px;">Lead email sent: <?php echo date('d M Y, h:i A', strtotime($D['emailSentAt'])); ?></p>
                <?php } ?>
            </div>

            <!-- Right Column: Status Management -->
            <div style="flex:0 0 350px;">
                <form id="frmStatusUpdate" onsubmit="return updateInquiryStatus();">
                    <div style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px;">
                        <h3 style="margin:0 0 15px; color:#333;">Manage Inquiry</h3>

                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-weight:bold; margin-bottom:5px; color:#555;">Status:</label>
                            <select id="inquiryStatus" name="inquiryStatus" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                                <?php echo $statusOpts; ?>
                            </select>
                        </div>

                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-weight:bold; margin-bottom:5px; color:#555;">Follow-up Date:</label>
                            <input type="date" id="followUpDate" name="followUpDate" value="<?php echo $D['followUpDate'] ?? ''; ?>" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>

                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-weight:bold; margin-bottom:5px; color:#555;">Sales Notes:</label>
                            <textarea id="salesNotes" name="salesNotes" rows="5" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"><?php echo htmlspecialchars($D['salesNotes'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" style="width:100%; background:#25D366; color:#fff; border:none; padding:10px 20px; border-radius:4px; cursor:pointer; font-size:14px; font-weight:bold;">
                            Update Status
                        </button>
                    </div>
                </form>

                <!-- Quick Actions -->
                <div style="margin-top:15px; background:#fff; border:1px solid #ddd; border-radius:8px; padding:15px;">
                    <h4 style="margin:0 0 10px;">Quick Actions</h4>
                    <a href="https://wa.me/<?php echo $phone; ?>" target="_blank" style="display:block; padding:8px 15px; background:#25D366; color:#fff; text-decoration:none; border-radius:4px; text-align:center; margin-bottom:8px;">
                        Open WhatsApp Chat
                    </a>
                    <?php if ($D['selectedProductID']) { ?>
                    <a href="<?php echo SITEURL; ?>/xadmin/?m=pump&p=edit&id=<?php echo intval($D['selectedProductID']); ?>" target="_blank" style="display:block; padding:8px 15px; background:#157bba; color:#fff; text-decoration:none; border-radius:4px; text-align:center;">
                        View Product
                    </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateInquiryStatus() {
    var data = {
        xAction: 'updateStatus',
        inquiryID: <?php echo $id; ?>,
        inquiryStatus: document.getElementById('inquiryStatus').value,
        salesNotes: document.getElementById('salesNotes').value,
        followUpDate: document.getElementById('followUpDate').value
    };

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.status === 'success') {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (resp.message || 'Unknown error'));
                }
            } catch (e) {
                alert('Status updated!');
                location.reload();
            }
        }
    };
    var params = Object.keys(data).map(function(k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
    }).join('&');
    xhr.send(params);
    return false;
}
</script>
