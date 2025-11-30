<?php
/**
 * Get Motor Specifications from Database
 * Helper function to retrieve and display motor specifications
 * Can be used in motor detail pages and comparison tools
 */

include '/home/bombayengg/public_html/config.inc.php';

/**
 * Get all specifications for a specific motor
 * @param int $motorID - Motor product ID
 * @return array - Array of specifications
 */
function getMotorSpecifications($motorID) {
    global $DBHOST, $DBUSER, $DBPASS, $DBNAME;

    $mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    if ($mysqli->connect_error) {
        return [];
    }

    $stmt = $mysqli->prepare("
        SELECT
            motorSpecID,
            specTitle,
            specOutput,
            specVoltage,
            specFrameSize,
            specStandard,
            specPoles,
            specFrequency,
            status
        FROM mx_motor_specification
        WHERE motorID = ? AND status = 1
        ORDER BY
            CASE
                WHEN specOutput REGEXP '^[0-9]+(\\.[0-9]+)?W$' THEN CAST(SUBSTRING_INDEX(specOutput, 'W', 1) AS DECIMAL)
                WHEN specOutput REGEXP '^[0-9]+(\\.[0-9]+)?kW$' THEN CAST(SUBSTRING_INDEX(specOutput, 'kW', 1) AS DECIMAL) * 1000
                ELSE 9999
            END
    ");

    $stmt->bind_param('i', $motorID);
    $stmt->execute();

    $result = $stmt->get_result();
    $specifications = [];

    while ($row = $result->fetch_assoc()) {
        $specifications[] = $row;
    }

    $stmt->close();
    $mysqli->close();

    return $specifications;
}

/**
 * Get specifications for multiple motors (for comparison)
 * @param array $motorIDs - Array of motor IDs
 * @return array - Associative array with motorID => specifications
 */
function getMultipleMotorSpecifications($motorIDs) {
    global $DBHOST, $DBUSER, $DBPASS, $DBNAME;

    if (empty($motorIDs)) {
        return [];
    }

    $mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    if ($mysqli->connect_error) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($motorIDs), '?'));
    $types = str_repeat('i', count($motorIDs));

    $stmt = $mysqli->prepare("
        SELECT
            motorID,
            motorSpecID,
            specTitle,
            specOutput,
            specVoltage,
            specFrameSize,
            specStandard,
            specPoles,
            specFrequency
        FROM mx_motor_specification
        WHERE motorID IN ($placeholders) AND status = 1
        ORDER BY motorID, specOutput
    ");

    $stmt->bind_param($types, ...$motorIDs);
    $stmt->execute();

    $result = $stmt->get_result();
    $allSpecifications = [];

    while ($row = $result->fetch_assoc()) {
        $motorID = $row['motorID'];
        if (!isset($allSpecifications[$motorID])) {
            $allSpecifications[$motorID] = [];
        }
        $allSpecifications[$motorID][] = $row;
    }

    $stmt->close();
    $mysqli->close();

    return $allSpecifications;
}

/**
 * Get unique output values for a motor category
 * @param int $categoryID - Motor category ID
 * @return array - Unique output values
 */
function getCategoryOutputRange($categoryID) {
    global $DBHOST, $DBUSER, $DBPASS, $DBNAME;

    $mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    if ($mysqli->connect_error) {
        return [];
    }

    $stmt = $mysqli->prepare("
        SELECT DISTINCT s.specOutput
        FROM mx_motor_specification s
        JOIN mx_motor m ON s.motorID = m.motorID
        WHERE m.categoryMID = ? AND s.status = 1
        ORDER BY
            CASE
                WHEN s.specOutput REGEXP '^[0-9]+(\\.[0-9]+)?W$' THEN CAST(SUBSTRING_INDEX(s.specOutput, 'W', 1) AS DECIMAL)
                WHEN s.specOutput REGEXP '^[0-9]+(\\.[0-9]+)?kW$' THEN CAST(SUBSTRING_INDEX(s.specOutput, 'kW', 1) AS DECIMAL) * 1000
                ELSE 9999
            END
    ");

    $stmt->bind_param('i', $categoryID);
    $stmt->execute();

    $result = $stmt->get_result();
    $outputs = [];

    while ($row = $result->fetch_assoc()) {
        $outputs[] = $row['specOutput'];
    }

    $stmt->close();
    $mysqli->close();

    return $outputs;
}

/**
 * Get unique standards for a motor category
 * @param int $categoryID - Motor category ID
 * @return array - Unique standards
 */
function getCategoryStandards($categoryID) {
    global $DBHOST, $DBUSER, $DBPASS, $DBNAME;

    $mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    if ($mysqli->connect_error) {
        return [];
    }

    $stmt = $mysqli->prepare("
        SELECT DISTINCT s.specStandard
        FROM mx_motor_specification s
        JOIN mx_motor m ON s.motorID = m.motorID
        WHERE m.categoryMID = ? AND s.status = 1
        ORDER BY s.specStandard
    ");

    $stmt->bind_param('i', $categoryID);
    $stmt->execute();

    $result = $stmt->get_result();
    $standards = [];

    while ($row = $result->fetch_assoc()) {
        if (!empty($row['specStandard'])) {
            $standards[] = $row['specStandard'];
        }
    }

    $stmt->close();
    $mysqli->close();

    return array_unique($standards);
}

/**
 * Format specification for display
 * @param array $spec - Specification record
 * @return string - Formatted HTML display
 */
function formatSpecificationDisplay($spec) {
    $html = '<div class="motor-specification">';
    $html .= '<h4>' . htmlspecialchars($spec['specTitle']) . '</h4>';
    $html .= '<ul class="spec-details">';
    $html .= '<li><strong>Output:</strong> ' . htmlspecialchars($spec['specOutput']) . '</li>';
    $html .= '<li><strong>Voltage:</strong> ' . htmlspecialchars($spec['specVoltage']) . '</li>';
    $html .= '<li><strong>Frame Size:</strong> ' . htmlspecialchars($spec['specFrameSize']) . '</li>';
    $html .= '<li><strong>Poles:</strong> ' . htmlspecialchars($spec['specPoles']) . '</li>';
    $html .= '<li><strong>Frequency:</strong> ' . htmlspecialchars($spec['specFrequency']) . '</li>';
    $html .= '<li><strong>Standards:</strong> ' . htmlspecialchars($spec['specStandard']) . '</li>';
    $html .= '</ul>';
    $html .= '</div>';
    return $html;
}

// Example usage demonstration
if (php_sapi_name() === 'cli') {
    // Command line demonstration
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "MOTOR SPECIFICATIONS RETRIEVAL EXAMPLES\n";
    echo str_repeat("=", 80) . "\n\n";

    // Example 1: Get specs for single motor
    echo "Example 1: Get all specifications for Capacitor Start Motors (ID: 48)\n";
    echo str_repeat("-", 80) . "\n";
    $specs = getMotorSpecifications(48);
    echo "Found " . count($specs) . " specifications:\n";
    foreach ($specs as $spec) {
        echo "  - " . $spec['specTitle'] . ": " . $spec['specOutput'] . " @ " . $spec['specVoltage'] . " (" . $spec['specFrameSize'] . ")\n";
    }

    // Example 2: Get output range for category
    echo "\n\nExample 2: Get output range for Single Phase Motors (Category 102)\n";
    echo str_repeat("-", 80) . "\n";
    $outputs = getCategoryOutputRange(102);
    echo "Available outputs: " . implode(", ", $outputs) . "\n";

    // Example 3: Get standards for category
    echo "\n\nExample 3: Get standards for 3 Phase Motors (Category 103)\n";
    echo str_repeat("-", 80) . "\n";
    $standards = getCategoryStandards(103);
    foreach ($standards as $standard) {
        echo "  - " . $standard . "\n";
    }

    echo "\n" . str_repeat("=", 80) . "\n";
}

?>
