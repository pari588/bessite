<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();

header('Content-Type: application/json');

// NOTE: Vendor autocomplete feature has been removed.
// This endpoint is no longer used. Vendors must be entered manually in the invoice form.

http_response_code(410);
echo json_encode([
    'ok' => false,
    'message' => 'Vendor autocomplete is no longer available. Please enter vendor details manually in the invoice form.'
]);

