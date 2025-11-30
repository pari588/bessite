<?php
/**
 * Google reCAPTCHA v3 Verification Code
 * Add this code to your form processing handler
 */

// Configuration
$RECAPTCHA_SECRET_KEY = '6LeVCf0rAAAAABhzHVTK76BApoP66LDaXdYaUBN-';
$RECAPTCHA_THRESHOLD = 0.5; // Score threshold (0.0 to 1.0)

/**
 * Verify reCAPTCHA Response
 * This function should be called in your form processing script
 *
 * @param string $recaptcha_token The token from the form submission
 * @return array Array with 'success' => bool, 'score' => float, 'action' => string
 */
function verify_recaptcha($recaptcha_token) {
    global $RECAPTCHA_SECRET_KEY, $RECAPTCHA_THRESHOLD;

    // Verify that token is not empty
    if (empty($recaptcha_token)) {
        return array(
            'success' => false,
            'message' => 'reCAPTCHA token not provided',
            'score' => 0
        );
    }

    // Prepare verification request
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';

    $post_data = array(
        'secret' => $RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha_token
    );

    // Use cURL to send verification request to Google
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $verify_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post_data),
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ));

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Check for cURL errors
    if ($curl_error) {
        return array(
            'success' => false,
            'message' => 'cURL Error: ' . $curl_error,
            'score' => 0
        );
    }

    // Decode the response
    $response_data = json_decode($response, true);

    // Check if response is valid
    if (!isset($response_data['success'])) {
        return array(
            'success' => false,
            'message' => 'Invalid response from Google reCAPTCHA',
            'score' => 0
        );
    }

    // Check success flag
    if (!$response_data['success']) {
        $error_codes = isset($response_data['error-codes']) ? implode(', ', $response_data['error-codes']) : 'Unknown error';
        return array(
            'success' => false,
            'message' => 'reCAPTCHA verification failed: ' . $error_codes,
            'score' => 0
        );
    }

    // Get the score
    $score = isset($response_data['score']) ? floatval($response_data['score']) : 0;
    $action = isset($response_data['action']) ? $response_data['action'] : 'unknown';

    // Check if score meets threshold
    if ($score < $RECAPTCHA_THRESHOLD) {
        return array(
            'success' => false,
            'message' => 'Suspiciously low score detected. Please try again.',
            'score' => $score,
            'action' => $action
        );
    }

    // Verification successful
    return array(
        'success' => true,
        'message' => 'reCAPTCHA verification passed',
        'score' => $score,
        'action' => $action
    );
}

/**
 * USAGE EXAMPLE - Add this to your contact form processing handler:
 *
 * if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 *
 *     // Get the reCAPTCHA token from the form
 *     $recaptcha_token = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
 *
 *     // Verify reCAPTCHA
 *     $captcha_result = verify_recaptcha($recaptcha_token);
 *
 *     if (!$captcha_result['success']) {
 *         // reCAPTCHA verification failed
 *         $response = array(
 *             'err' => 1,
 *             'msg' => $captcha_result['message'],
 *             'captcha_score' => $captcha_result['score']
 *         );
 *         header('Content-Type: application/json');
 *         echo json_encode($response);
 *         exit;
 *     }
 *
 *     // Log the reCAPTCHA score for monitoring
 *     // (Optional) You can track scores for analysis
 *     // log_recaptcha_score($captcha_result['score'], $captcha_result['action']);
 *
 *     // Continue with form processing...
 *     // Process the contact form data
 *     // Send emails, save to database, etc.
 *
 *     $response = array(
 *         'err' => 0,
 *         'msg' => 'Thank you! Your message has been sent.',
 *         'captcha_score' => $captcha_result['score']
 *     );
 *     header('Content-Type: application/json');
 *     echo json_encode($response);
 *     exit;
 * }
 */

?>
