<?php
/**
 * Enhanced OCR Integration Module - PaddleOCR + Tesseract Fallback
 * Handles bill image processing with improved accuracy for financial documents
 *
 * Date: December 2025
 * Project: Fuel Expenses Management System
 * Features: PaddleOCR primary, Tesseract fallback, confidence scoring
 */

/**
 * Process fuel bill image using PaddleOCR (with Tesseract fallback)
 *
 * @param string $imagePath - Full path to bill image file
 * @param int $vehicleID - Optional vehicle ID for context
 * @return array - Array with status, raw text, extracted fields, and confidence scores
 */
function processBillOCR($imagePath = "", $vehicleID = 0) {
    $response = array(
        "status" => "error",
        "message" => "",
        "rawText" => "",
        "extractedData" => array(
            "date" => "",
            "amount" => "",
            "dateConfidence" => 0,
            "amountConfidence" => 0
        ),
        "overallConfidence" => 0,
        "ocrEngine" => "none",
        "debug" => array()
    );

    // Create a custom log file for debugging
    $logFile = sys_get_temp_dir() . '/ocr_debug.log';
    $logFn = function($msg) use ($logFile) {
        $timestamp = date('Y-m-d H:i:s');
        $fullMsg = "[$timestamp] $msg";
        @file_put_contents($logFile, "$fullMsg\n", FILE_APPEND);
        error_log("[OCR] $msg");
        @syslog(LOG_INFO, "[OCR] $msg");
    };

    $logFn("=== processBillOCR START ===");
    $logFn("File: $imagePath, VehicleID: $vehicleID");

    // Validate image path
    if (!file_exists($imagePath)) {
        $response["message"] = "Image file not found: " . $imagePath;
        $logFn("ERROR: File not found: $imagePath");
        return $response;
    }

    if (filesize($imagePath) > 5242880) {
        $response["message"] = "File size exceeds 5MB limit";
        return $response;
    }

    $tempDir = sys_get_temp_dir();
    $fileExt = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    $fileType = mime_content_type($imagePath);
    $isPDF = ($fileExt === 'pdf' || strpos($fileType, 'pdf') !== false);

    $processPath = $imagePath;
    $tempImageFile = null;

    // Convert PDF to image if needed
    if ($isPDF) {
        $logFn("PDF detected, attempting conversion...");
        $processPath = convertPDFToImage($imagePath, $tempDir, $logFn);
        if (!$processPath) {
            $response["message"] = "Failed to convert PDF to image";
            return $response;
        }
        $tempImageFile = $processPath;
        $logFn("PDF converted to: $processPath");
    }

    // Try PaddleOCR first (preferred - better accuracy)
    $ocrResult = processBillOCRWithPaddle($processPath, $logFn);

    if ($ocrResult && $ocrResult['status'] === 'success') {
        $logFn("PaddleOCR successful, text length: " . strlen($ocrResult['rawText']));
        $response["status"] = "success";
        $response["rawText"] = $ocrResult['rawText'];
        $response["ocrEngine"] = "paddle";

        // Extract fields from OCR text
        $extracted = extractBillFields($ocrResult['rawText']);
        $response["extractedData"] = $extracted["fields"];
        $response["overallConfidence"] = $extracted["overallConfidence"];

        // Validate extracted data
        validateExtractedData($response, $logFn);
    } else {
        // TESTING MODE: Tesseract fallback disabled - force PaddleOCR testing only
        // (Uncomment below to re-enable Tesseract fallback)
        $logFn("PaddleOCR failed or not available");
        $logFn("TESTING MODE: Tesseract fallback is DISABLED - only testing PaddleOCR");

        /*
        // DISABLED FOR TESTING - Re-enable by uncommenting
        // Fallback to Tesseract if PaddleOCR fails
        $logFn("Falling back to Tesseract");
        $ocrResult = processBillOCRWithTesseract($processPath, $logFn);

        if ($ocrResult && $ocrResult['status'] === 'success') {
            $logFn("Tesseract successful");
            $response["status"] = "success";
            $response["rawText"] = $ocrResult['rawText'];
            $response["ocrEngine"] = "tesseract";

            // Extract fields
            $extracted = extractBillFields($ocrResult['rawText']);
            $response["extractedData"] = $extracted["fields"];
            $response["overallConfidence"] = $extracted["overallConfidence"];

            // Add fallback warning
            $response["fallbackWarning"] = "Using Tesseract OCR due to PaddleOCR unavailability - accuracy may be lower";

            // Validate extracted data
            validateExtractedData($response, $logFn);
        } else {
            $response["message"] = "Both PaddleOCR and Tesseract failed";
            $logFn("ERROR: Both OCR engines failed");
        }
        */

        // Force error when PaddleOCR fails (for testing)
        $response["message"] = "PaddleOCR failed - Tesseract fallback is disabled during testing";
        $logFn("PaddleOCR-only mode: Returning error to force investigation");
    }

    // Clean up temporary files
    if ($tempImageFile && file_exists($tempImageFile)) {
        @unlink($tempImageFile);
        $logFn("Cleaned up temp file: $tempImageFile");
    }

    $logFn("=== processBillOCR END ===");
    return $response;
}

/**
 * Process image with PaddleOCR via Python script
 */
function processBillOCRWithPaddle($imagePath, $logFn) {
    $result = array(
        "status" => "error",
        "rawText" => "",
        "message" => ""
    );

    // Path to Python script
    $pythonScript = dirname(__FILE__) . '/paddleocr_processor.py';
    if (!file_exists($pythonScript)) {
        $logFn("PaddleOCR script not found: $pythonScript");
        return $result;
    }

    // Execute Python script
    $command = '/bin/python3 ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($imagePath) . ' 2>&1';
    $logFn("Executing: $command");

    $output = shell_exec($command);
    if (!$output) {
        $logFn("PaddleOCR script returned empty output");
        return $result;
    }

    // Extract JSON from output (may contain warnings/noise before JSON)
    // Find the first { character which marks the start of JSON
    $jsonStart = strpos($output, '{');
    if ($jsonStart === false) {
        $logFn("No JSON found in PaddleOCR output: " . substr($output, 0, 200));
        return $result;
    }

    // Extract from first { to end
    $jsonOutput = substr($output, $jsonStart);

    // Parse JSON output
    $jsonData = json_decode($jsonOutput, true);
    if (!$jsonData || !is_array($jsonData)) {
        $logFn("Failed to parse PaddleOCR JSON output: " . substr($jsonOutput, 0, 200));
        return $result;
    }

    if (isset($jsonData['error'])) {
        $logFn("PaddleOCR error: " . $jsonData['error']);
        return $result;
    }

    if ($jsonData['status'] !== 'success') {
        $logFn("PaddleOCR status not success: " . ($jsonData['message'] ?? 'unknown'));
        return $result;
    }

    $result["status"] = "success";
    $result["rawText"] = $jsonData['text'] ?? '';

    $logFn("PaddleOCR extracted " . count($jsonData['blocks'] ?? []) . " text blocks");
    $logFn("Average confidence: " . ($jsonData['avg_confidence'] ?? 0) . "%");

    return $result;
}

/**
 * Fallback: Process image with Tesseract OCR
 */
function processBillOCRWithTesseract($imagePath, $logFn) {
    $result = array(
        "status" => "error",
        "rawText" => "",
        "message" => ""
    );

    $tesseractPath = defined('TESSERACT_PATH') ? TESSERACT_PATH : '/usr/bin/tesseract';

    if (!file_exists($tesseractPath)) {
        $logFn("Tesseract not found at: $tesseractPath");
        return $result;
    }

    $tempDir = sys_get_temp_dir();
    $tempOutputFile = $tempDir . '/' . 'ocr_' . uniqid();

    // Run Tesseract
    $command = $tesseractPath . ' ' .
               escapeshellarg($imagePath) . ' ' .
               escapeshellarg($tempOutputFile) . ' ' .
               '-l eng 2>&1';

    $logFn("Running Tesseract: " . $command);

    ob_start();
    passthru($command, $returnCode);
    $passthruOutput = ob_get_clean();

    if ($returnCode !== 0) {
        $logFn("Tesseract failed with code $returnCode: $passthruOutput");
        return $result;
    }

    $outputFile = $tempOutputFile . '.txt';
    if (!file_exists($outputFile)) {
        $logFn("Tesseract output file not created: $outputFile");
        return $result;
    }

    $ocrText = file_get_contents($outputFile);
    @unlink($outputFile);
    @unlink($tempOutputFile);

    $result["status"] = "success";
    $result["rawText"] = trim($ocrText);

    $logFn("Tesseract extraction successful, text length: " . strlen($ocrText));

    return $result;
}

/**
 * Convert PDF to image
 */
function convertPDFToImage($pdfPath, $tempDir, $logFn) {
    // Try pdftoppm first
    $pdftoppmPaths = array('/bin/pdftoppm', '/usr/bin/pdftoppm', '/usr/local/bin/pdftoppm');
    foreach ($pdftoppmPaths as $path) {
        if (file_exists($path)) {
            $tempImageFile = $tempDir . '/pdf_' . uniqid() . '.png';
            $outputPrefix = substr($tempImageFile, 0, -4);
            $command = escapeshellcmd($path) . ' -singlefile -png ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg($outputPrefix) . ' 2>&1';

            $logFn("Trying pdftoppm: $command");
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($tempImageFile)) {
                @chmod($tempImageFile, 0644);
                $logFn("PDF converted with pdftoppm: $tempImageFile");
                return $tempImageFile;
            }
            $logFn("pdftoppm failed with code $returnCode");
        }
    }

    // Try ImageMagick convert as fallback
    $convertPaths = array('/bin/convert', '/usr/bin/convert', '/usr/local/bin/convert');
    foreach ($convertPaths as $path) {
        if (file_exists($path)) {
            $tempImageFile = $tempDir . '/pdf_' . uniqid() . '.png';
            $command = escapeshellcmd($path) . ' -density 150 ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg($tempImageFile) . ' 2>&1';

            $logFn("Trying ImageMagick: $command");
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($tempImageFile)) {
                @chmod($tempImageFile, 0644);
                $logFn("PDF converted with ImageMagick: $tempImageFile");
                return $tempImageFile;
            }
            $logFn("ImageMagick failed with code $returnCode");
        }
    }

    $logFn("PDF conversion failed - no tools available");
    return null;
}

/**
 * Extract bill fields (date and amount) from OCR text
 *
 * @param string $ocrText - Raw OCR extracted text
 * @return array - Extracted fields with confidence scores
 */
function extractBillFields($ocrText = "") {
    $result = array(
        "fields" => array(
            "date" => "",
            "amount" => "",
            "dateConfidence" => 0,
            "amountConfidence" => 0
        ),
        "overallConfidence" => 0
    );

    if (empty($ocrText)) {
        return $result;
    }

    $lines = explode("\n", strtolower($ocrText));

    // Extract DATE
    $dateConfidence = extractDate($lines, $result["fields"]);
    $result["fields"]["dateConfidence"] = $dateConfidence;

    // Extract AMOUNT
    $amountConfidence = extractAmount($lines, $result["fields"]);
    $result["fields"]["amountConfidence"] = $amountConfidence;

    // Calculate overall confidence (average of both fields)
    $result["overallConfidence"] = intval(($dateConfidence + $amountConfidence) / 2);

    return $result;
}

/**
 * Extract date from OCR text
 */
function extractDate(&$lines, &$fields) {
    $confidence = 0;

    // Common date patterns (Indian bills use DD/MM/YYYY or DD-MM-YYYY)
    $datePatterns = array(
        '/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/', // Direct DD/MM/YYYY
        '/[^\d](\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/', // With non-digit prefix
        '/(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/', // YYYY/MM/DD format
        '/(0[1-9]|[12]\d|3[01])\s+(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\s+(\d{4})/i', // DD Mon YYYY
        '/(\d{1,2})\s+(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{4})/i', // DD Month YYYY
        '/\b(\d{1,2})\s*[-\/]\s*(\d{1,2})\s*[-\/]\s*(\d{4})\b/', // DD-MM-YYYY with spaces
    );

    foreach ($lines as $line) {
        if (strlen($line) < 5) continue;

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                $day = null;
                $month = null;
                $year = null;

                if (count($matches) >= 4) {
                    if (strlen($matches[1]) === 4) {
                        // YYYY/MM/DD format
                        $year = intval($matches[1]);
                        $month = intval($matches[2]);
                        $day = intval($matches[3]);
                    } else if (strlen($matches[3]) === 4) {
                        // DD/MM/YYYY format
                        $day = intval($matches[1]);
                        $month = intval($matches[2]);
                        $year = intval($matches[3]);
                    } else if (isset($matches[4]) && strlen($matches[4]) === 4) {
                        // DD/MM/YYYY with non-digit prefix
                        $day = intval($matches[2]);
                        $month = intval($matches[3]);
                        $year = intval($matches[4]);
                    } else if (is_numeric($matches[1]) && is_numeric($matches[2]) && strlen($matches[3]) === 4) {
                        $day = intval($matches[1]);
                        $month = intval($matches[2]);
                        $year = intval($matches[3]);
                    }
                }

                if ($day && $month && $year) {
                    $currentYear = intval(date('Y'));
                    if ($year >= 2000 && $year <= ($currentYear + 2)) {
                        if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12) {
                            $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
                            if (strtotime($dateStr) !== false) {
                                $fields["date"] = $dateStr;
                                return 95; // High confidence
                            }
                        }
                    }
                }
            }
        }
    }

    // Fallback: look for any reasonable date pattern
    foreach ($lines as $line) {
        if (preg_match_all('/\d{1,4}/', $line, $numbers)) {
            $nums = $numbers[0];
            if (count($nums) >= 3) {
                $possibleDate = interpretAsDate($nums);
                if ($possibleDate !== false) {
                    $fields["date"] = $possibleDate;
                    return 60; // Lower confidence for guessed date
                }
            }
        }
    }

    return $confidence;
}

/**
 * Interpret array of numbers as date
 */
function interpretAsDate($numbers = array()) {
    if (empty($numbers)) return false;

    $currentYear = intval(date('Y'));

    // Strategy 1: Look for 4-digit year
    foreach ($numbers as $k => $num) {
        if (strlen($num) === 4) {
            $year = intval($num);
            if ($year >= 2000 && $year <= ($currentYear + 2)) {
                // Try numbers before year
                if ($k >= 2) {
                    $month = intval($numbers[$k - 2]);
                    $day = intval($numbers[$k - 1]);

                    if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
                        if (strtotime($dateStr) !== false) {
                            return $dateStr;
                        }
                    }
                }
                // Try numbers after year
                if ($k + 2 < count($numbers)) {
                    $month = intval($numbers[$k + 1]);
                    $day = intval($numbers[$k + 2]);

                    if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
                        if (strtotime($dateStr) !== false) {
                            return $dateStr;
                        }
                    }
                }
            }
        }
    }

    // Strategy 2: DD-MM-YYYY pattern in consecutive numbers
    for ($i = 0; $i < count($numbers) - 2; $i++) {
        $num1 = intval($numbers[$i]);
        $num2 = intval($numbers[$i + 1]);
        $num3 = intval($numbers[$i + 2]);

        if (strlen($numbers[$i]) <= 2 && strlen($numbers[$i + 1]) <= 2 && strlen($numbers[$i + 2]) === 4) {
            if ($num1 >= 1 && $num1 <= 31 && $num2 >= 1 && $num2 <= 12) {
                $dateStr = sprintf("%04d-%02d-%02d", $num3, $num2, $num1);
                if (strtotime($dateStr) !== false) {
                    return $dateStr;
                }
            }
        }
    }

    return false;
}

/**
 * Extract amount from OCR text (Improved for better accuracy)
 */
function extractAmount(&$lines, &$fields) {
    $confidence = 0;
    $foundAmounts = array();

    // Enhanced patterns for currency amounts - handles various formats
    $amountPatterns = array(
        // Currency symbol first (Rs., ₹, रु, INR)
        '/(?:rs|inr|रु|₹)\s*\.?\s*(\d+(?:[,.\s]\d{2})?)/i', // Rs. 500, INR 500, रु 500, ₹500

        // Number followed by currency
        '/(\d+(?:[,.\s]\d{2})?)\s*(?:rs|inr|रु|₹)/i', // 500 Rs, 500 INR, 500 रु

        // Keywords: Total, Amount, Paid, Price, Cost, Base, Bill
        '/total\s*[\:\=\s]*(?:rs|inr|₹)?\s*(\d+(?:[,.\s]\d{2})?)/i', // Total: 500
        '/amount\s*[\:\=\s]*(?:rs|inr|₹)?\s*(\d+(?:[,.\s]\d{2})?)/i', // Amount: 500
        '/(?:base\s*)?amt\.?\s*[\:\=\s]*(?:rs|inr|₹)?\s*(\d+(?:[,.\s]\d{2})?)/i', // Amt: 500 or Base Amt: 500
        '/(?:base\s*)?ant\.?\s*[\:\=\s]*(?:rs|inr|₹)?\s*(\d+(?:[,.\s]\d{2})?)/i', // Ant: 500 or Base Ant: 500 (typo in bill) - FIXED to include INR
        '/paid\s*[\:\=\s]*(?:rs|inr|₹)?\s*(\d+(?:[,.\s]\d{2})?)/i', // Paid: 500
        '/price\s*[\:\=\s]*(?:rs|inr|₹)?\s*(\d+(?:[,.\s]\d{2})?)/i', // Price: 500
        '/cost\s*[\:\=\s]*(?:rs|inr|₹)?\s*(\d+(?:[,.\s]\d{2})?)/i', // Cost: 500
        '/bill\s*[\:\=\s]*(?:rs|inr|₹)?\s*(\d+(?:[,.\s]\d{2})?)/i', // Bill: 500

        // Direct number patterns (for cards/payments)
        '/:\s*(?:rs|inr)\s*(\d+(?:[,.\s]\d{2})?)/i', // :Rs 500, :INR 500
        '/(?:rs|inr)\s+(\d+(?:[,.\s]\d{2})?)(?:\s|$)/i', // Rs 500, INR 500 (with space after)
    );

    foreach ($lines as $line) {
        foreach ($amountPatterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                if (isset($matches[1])) {
                    $amountStr = $matches[1];
                    $amountStr = str_replace(array(',', ' '), '', $amountStr);

                    if (preg_match('/\.(\d{2})$/', $amountStr)) {
                        $amount = floatval($amountStr);
                    } else {
                        $amount = floatval($amountStr);
                    }

                    // Validate amount (> 0 and < 100000 for fuel)
                    if ($amount > 0 && $amount < 100000) {
                        $foundAmounts[] = array(
                            "amount" => $amount,
                            "confidence" => 90,
                            "pattern" => "currency"
                        );
                    }
                }
            }
        }
    }

    // Special handling for "Amount(Rs.):" format (found in some bills)
    foreach ($lines as $line) {
        if (preg_match('/amount\s*\(\s*(?:rs|inr)\.?\s*\)\s*:\s*(\d+(?:[,.\s]\d{2})?)/i', $line, $matches)) {
            $amountStr = $matches[1];
            $amountStr = str_replace(array(',', ' '), '', $amountStr);
            $amount = floatval($amountStr);
            if ($amount > 0 && $amount < 100000) {
                $foundAmounts[] = array(
                    "amount" => $amount,
                    "confidence" => 92,
                    "pattern" => "amount_rs"
                );
            }
        }
    }

    if (!empty($foundAmounts)) {
        usort($foundAmounts, function($a, $b) {
            return $b['confidence'] - $a['confidence'];
        });

        $fields["amount"] = number_format($foundAmounts[0]["amount"], 2, '.', '');
        return $foundAmounts[0]["confidence"];
    }

    // Fallback: look for large numbers
    $allNumbers = array();
    foreach ($lines as $line) {
        if (preg_match_all('/(\d+(?:[.,]\d{2})?)/', $line, $matches)) {
            foreach ($matches[1] as $num) {
                $num = str_replace(',', '.', $num);
                $amount = floatval($num);
                if ($amount > 100 && $amount < 100000) {
                    $allNumbers[] = $amount;
                }
            }
        }
    }

    if (!empty($allNumbers)) {
        $counts = array_count_values(array_map('intval', $allNumbers));
        arsort($counts);
        $mostCommon = key($counts);
        $fields["amount"] = number_format($mostCommon, 2, '.', '');
        return 50; // Lower confidence
    }

    return $confidence;
}

/**
 * Validate and enhance extracted data
 */
function validateExtractedData(&$response, $logFn) {
    $extracted = &$response["extractedData"];

    // Warn if date is extracted with low confidence
    if (!empty($extracted["date"]) && $extracted["dateConfidence"] < 85) {
        $response["dateWarning"] = "Date extracted with low confidence (" . $extracted["dateConfidence"] . "%) - please verify";
        $logFn("Date warning: low confidence");
    }

    // Check if date is in the future
    if (!empty($extracted["date"])) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $extracted["date"]);
        if ($dateObj && $dateObj->format('Y') > date('Y') + 1) {
            $response["dateWarning"] = "Extracted date is in the future - likely OCR error, please correct";
            $extracted["date"] = "";
            $logFn("Date warning: future date");
        }
    }

    // Log final results
    $logFn("Final extraction - Date: " . ($extracted["date"] ?: "NONE") .
           " (confidence: " . $extracted["dateConfidence"] . "%), Amount: " .
           ($extracted["amount"] ?: "NONE") . " (confidence: " . $extracted["amountConfidence"] . "%)");
}

/**
 * Validate extracted bill data
 */
function validateBillData($extractedData = array()) {
    $errors = array();

    if (empty($extractedData["date"])) {
        $errors["date"] = "Date not found in bill";
    } else {
        if (strtotime($extractedData["date"]) === false) {
            $errors["date"] = "Invalid date format";
        }
    }

    if (empty($extractedData["amount"])) {
        $errors["amount"] = "Amount not found in bill";
    } else {
        $amount = floatval($extractedData["amount"]);
        if ($amount <= 0) {
            $errors["amount"] = "Amount must be greater than 0";
        }
        if ($amount > 100000) {
            $errors["amount"] = "Amount seems too high for fuel purchase";
        }
    }

    return array(
        "valid" => empty($errors),
        "errors" => $errors
    );
}

?>
