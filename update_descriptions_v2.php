<?php
// Direct MySQL update script
$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

$conn = mysqli_connect($DBHOST, $DBUSER, $DBPASS, $DBNAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

$timestamp = date('Y-m-d H:i:s');
$log = [];

// Fetch all pumps
$query = "SELECT pumpID, pumpTitle, kwhp, supplyPhase FROM mx_pump WHERE status=1 ORDER BY pumpTitle";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$pumps = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pumps[] = $row;
}

echo "Processing " . count($pumps) . " pumps...\n";

$updated = 0;

foreach ($pumps as $pump) {
    $pumpID = intval($pump['pumpID']);
    $title = $pump['pumpTitle'];
    $power = $pump['kwhp'] ?: '1 HP';
    $phase = ($pump['supplyPhase'] === 'T') ? 'three-phase' : 'single-phase';

    // Generate description based on pump type
    $description = generateDescription($title, $power, $phase);

    // Escape for MySQL
    $description = mysqli_real_escape_string($conn, $description);

    // Update
    $update_sql = "UPDATE mx_pump SET pumpFeatures = '$description' WHERE pumpID = $pumpID";

    if (mysqli_query($conn, $update_sql)) {
        $log[] = "[✓] $title";
        $updated++;
        echo ".";
    } else {
        $log[] = "[✗] $title - " . mysqli_error($conn);
        echo "E";
    }
}

echo "\n\nUPDATE COMPLETE\n";
echo "==================\n";
echo "Updated: $updated\n";
echo "Total: " . count($pumps) . "\n";
echo "Timestamp: $timestamp\n";

// Save log
$logfile = "UPDATE_DESCRIPTIONS_LOG_" . date('YmdHis') . ".txt";
file_put_contents($logfile, implode("\n", $log));
echo "Log saved: $logfile\n";

mysqli_close($conn);

function generateDescription($title, $power, $phase) {
    // Clean power string
    $power = trim($power);
    if (empty($power) || $power == '0') {
        $power = '1 HP';
    }

    // Determine pump type and generate description
    $title_upper = strtoupper($title);

    // MINI PUMPS
    if (strpos($title_upper, 'MINI MASTER') !== false) {
        return "The $title is a premium self-priming mini pump engineered for residential water pressure boosting and domestic applications. With {$power} capacity and $phase operation, this Crompton mini pump delivers reliable performance with advanced electrical stamping technology. Features brass impellers, stainless steel components, and IP55 protection. Ideal for water extraction, gardening, and household plumbing. Available at Bombay Engineering Syndicate – your trusted Crompton distributor.";
    }

    if (strpos($title_upper, 'MINI FORCE') !== false) {
        return "The $title mini force pump combines efficiency with reliability for modern residential water management. {$power} capacity with $phase operation, featuring advanced electrical construction. Delivers consistent pressure for water boosting and domestic applications. Compact design fits residential spaces. Energy-efficient with IP55 protection. Perfect for home water supply systems. Get yours from Bombay Engineering Syndicate – Crompton's authorized distributor.";
    }

    if (strpos($title_upper, 'MINI MARVEL') !== false) {
        return "The $title is an economical mini pump for residential water pressure needs with consistent performance. {$power} single-phase operation with reliable self-priming capability. Compact design ideal for domestic water supply and pressure boosting. Features advanced electrical technology and IP55 protection. Low maintenance, high reliability. Available at Bombay Engineering Syndicate – your Crompton distributor.";
    }

    if (strpos($title_upper, 'CHAMP') !== false || strpos($title_upper, 'FLOMAX') !== false) {
        return "The $title is an economical choice for residential water pressure needs with consistent performance. {$power} single-phase operation with reliable self-priming capability. Compact design ideal for domestic water supply and pressure boosting. Features advanced electrical stamping and IP55 protection. Low maintenance, high reliability. Trusted by homeowners. Available at Bombay Engineering Syndicate – your Crompton distributor.";
    }

    // 3-INCH SUBMERSIBLES
    if (strpos($title_upper, '3W') !== false || (strpos($title_upper, '3') !== false && strpos($title_upper, 'INCH') !== false)) {
        return "The $title is a 3-inch submersible pump designed for shallow to medium-depth borewell applications. {$power} capacity with $phase operation ensures reliable water extraction from depths up to 150 feet. Features deep borewell submersible technology with IP55 protection and energy-efficient performance. Ideal for small farms and residential water supply. Trusted Crompton quality. Available at Bombay Engineering Syndicate.";
    }

    // 4-INCH OIL-FILLED SUBMERSIBLES
    if ((strpos($title_upper, '4VO') !== false || strpos($title_upper, '4W') !== false) && strpos($title_upper, 'OIL') !== false) {
        return "The $title is an oil-filled borewell submersible pump delivering superior durability for deep borewell water extraction. {$power} capacity with $phase operation, featuring premium oil-filled construction for extended operational life. Handles voltage fluctuations effectively with excellent performance in challenging borewell conditions. Suitable for agricultural and residential applications. Deep borewell rated with IP55 protection. Premium Crompton quality at Bombay Engineering Syndicate.";
    }

    // 4-INCH WATER-FILLED SUBMERSIBLES
    if ((strpos($title_upper, '4W') !== false || strpos($title_upper, '4V') !== false) && strpos($title_upper, 'OIL') === false) {
        return "The $title is a water-filled borewell submersible pump combining eco-friendly design with reliable performance. {$power} capacity $phase operation with excellent voltage fluctuation tolerance. Ideal for residential and agricultural water supply applications. Features sturdy construction, low noise operation, and energy-efficient performance. IP55 protected. Requires routine maintenance. Your trusted source: Bombay Engineering Syndicate – Crompton distributor.";
    }

    // AQUAGOLD & MINI PUMPS (generic)
    if (strpos($title_upper, 'AQUAGOLD') !== false) {
        return "The $title is a self-priming mini pump engineered for residential and light commercial water supply applications. {$power} capacity with advanced electrical stamping and Hy-Flo technology. Features brass impellers, stainless steel components, and IP55 protection. Reliable for domestic water boosting and pressure management. Compact, efficient, and low-maintenance. Available at Bombay Engineering Syndicate – Crompton's authorized distributor.";
    }

    // OPENWELL PUMPS
    if (strpos($title_upper, 'HORIZONTAL') !== false) {
        return "The $title is a horizontal openwell pump engineered for efficient water extraction from open sources like wells, tanks, and reservoirs. {$power} capacity with $phase operation delivers consistent flow for agricultural and residential applications. Designed for easy installation with minimal maintenance. Features robust construction, energy-efficient motor, and reliable performance. Perfect for farms, gardens, and community water systems. Get it from Bombay Engineering Syndicate – Crompton's authorized partner.";
    }

    if (strpos($title_upper, 'VERTICAL') !== false && strpos($title_upper, 'OPENWELL') !== false) {
        return "The $title is a vertical openwell pump offering reliable water extraction from open wells and tanks with space-efficient design. {$power} capacity with $phase operation for agricultural irrigation and residential water supply. Compact vertical configuration saves floor space. Features energy-efficient motor, easy maintenance, and long operational life. IP55 protected. Available at Bombay Engineering Syndicate – your trusted Crompton distributor.";
    }

    // SHALLOW WELL PUMPS
    if (strpos($title_upper, 'SHALLOW') !== false || strpos($title_upper, 'SWJ') !== false) {
        return "The $title is a shallow well self-priming pump ideal for extracting water from shallow borewells and surface sources. {$power} capacity single-phase operation with reliable self-priming capability. Perfect for residential water supply and agricultural irrigation. Compact design with easy installation. Features advanced electrical technology, IP55 protection, and energy-efficient performance. Low maintenance with long service life. Bombay Engineering Syndicate – your Crompton pump specialist.";
    }

    // AGRICULTURAL SUBMERSIBLES
    if (strpos($title_upper, 'AGRICULTURAL') !== false || strpos($title_upper, 'SUBMERSIBLE') !== false ||
        (is_numeric(substr($title_upper, 0, 3)) && strpos($title_upper, 'W') !== false && strpos($title_upper, 'RA') !== false)) {
        return "The $title is an agricultural submersible pump purpose-built for farm irrigation and borewell water extraction. {$power} capacity with $phase operation for deep water wells and borewells. Energy-efficient design reduces operational costs while maintaining consistent performance. IP55 protection and robust construction handle demanding farm conditions. Ideal for large farms and irrigation projects. Available at Bombay Engineering Syndicate – Crompton's authorized distributor.";
    }

    // PRESSURE BOOSTER PUMPS
    if (strpos($title_upper, 'PRESSURE') !== false || strpos($title_upper, 'BOOSTER') !== false) {
        return "The $title is a pressure booster pump ensuring consistent water pressure throughout residential and commercial properties. {$power} capacity with $phase operation for reliable pressure maintenance. Ideal for high-rise buildings and residential complexes. Features automatic pressure control, low noise operation, and energy-efficient design. IP55 protected with long operational life. Essential for modern water systems. Get Crompton quality at Bombay Engineering Syndicate.";
    }

    // CONTROL PANELS
    if (strpos($title_upper, 'CONTROL') !== false || strpos($title_upper, 'PANEL') !== false) {
        return "The $title provides comprehensive protection and control for pump motors in residential and agricultural applications. Features advanced electrical safety and automation systems. Essential component for safe, efficient pump operation. Protects against voltage fluctuations, overload, and short circuits. Easy installation and maintenance. Crompton quality assurance. Available at Bombay Engineering Syndicate – complete pump solutions provider.";
    }

    // CIRCULATORY PUMPS
    if (strpos($title_upper, 'CIRCULATORY') !== false || strpos($title_upper, 'IN-LINE') !== false) {
        return "The $title is engineered for continuous water circulation in heating systems and industrial applications. {$power} capacity with $phase operation for reliable, quiet performance. Compact in-line design minimizes installation space. Features energy-efficient motor, low vibration, and extended service life. IP55 protection. Industrial-grade reliability. Available at Bombay Engineering Syndicate – Crompton's comprehensive pump solutions provider.";
    }

    // Default fallback
    return "The $title is a premium Crompton pump engineered for reliable water extraction and pressure management. Features advanced electrical technology, IP55 protection, and energy-efficient operation. Suitable for residential, agricultural, and commercial applications. Available now at Bombay Engineering Syndicate – your trusted Crompton distributor.";
}
?>
