<?php
/**
 * CAMS Biometric Callback (Root Level)
 * This file exists at root to bypass SSL redirect
 *
 * Device URL: http://www.bombayengg.net/cams-callback.php
 * Port: 80
 */

// Simply include the main callback handler
require_once __DIR__ . '/core/cams-biometric-callback.php';
