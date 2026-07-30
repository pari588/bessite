<?php
/**
 * CAMS API Response Decryptor
 */

$encrypted = "MWa8TYbMwtw7O4MsoGqACDWcs9oafRhfwrYh+VPosWkaQdtDvhi3fXYVOyLEvU4pPX43rd2C5wHeuNrmmcYuYoOFmx30RXV3qmqShYKItds=";
$securityKey = "pJR1U92U5ZavbgA8leRPBsr2XAuJqxQg";

// Method 1: Standard AES-256-CBC
function decrypt1($encrypted, $key) {
    $data = base64_decode($encrypted);
    $keyHash = substr(hash('sha256', $key, true), 0, 32);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encryptedData = substr($data, $ivLength);
    return openssl_decrypt($encryptedData, 'aes-256-cbc', $keyHash, OPENSSL_RAW_DATA, $iv);
}

// Method 2: Direct key
function decrypt2($encrypted, $key) {
    $data = base64_decode($encrypted);
    $iv = substr($data, 0, 16);
    $encryptedData = substr($data, 16);
    return openssl_decrypt($encryptedData, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

// Method 3: Key as-is, padded
function decrypt3($encrypted, $key) {
    $data = base64_decode($encrypted);
    $iv = substr($data, 0, 16);
    $encryptedData = substr($data, 16);
    $paddedKey = str_pad($key, 32, "\0");
    return openssl_decrypt($encryptedData, 'aes-256-cbc', $paddedKey, OPENSSL_RAW_DATA, $iv);
}

// Method 4: AES-128-CBC
function decrypt4($encrypted, $key) {
    $data = base64_decode($encrypted);
    $iv = substr($data, 0, 16);
    $encryptedData = substr($data, 16);
    return openssl_decrypt($encryptedData, 'aes-128-cbc', substr($key, 0, 16), OPENSSL_RAW_DATA, $iv);
}

// Method 5: ECB mode
function decrypt5($encrypted, $key) {
    $data = base64_decode($encrypted);
    return openssl_decrypt($data, 'aes-256-ecb', substr(hash('sha256', $key, true), 0, 32), OPENSSL_RAW_DATA);
}

echo "Encrypted: $encrypted\n\n";
echo "Security Key: $securityKey\n\n";

echo "Method 1 (SHA256 + CBC): " . var_export(decrypt1($encrypted, $securityKey), true) . "\n";
echo "Method 2 (Direct Key): " . var_export(decrypt2($encrypted, $securityKey), true) . "\n";
echo "Method 3 (Padded Key): " . var_export(decrypt3($encrypted, $securityKey), true) . "\n";
echo "Method 4 (AES-128-CBC): " . var_export(decrypt4($encrypted, $securityKey), true) . "\n";
echo "Method 5 (ECB): " . var_export(decrypt5($encrypted, $securityKey), true) . "\n";

// Try without encryption (maybe it's just base64)
echo "\nBase64 decode only: " . var_export(base64_decode($encrypted), true) . "\n";
?>
