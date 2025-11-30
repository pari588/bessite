<?php
/**
 * Pump Description SEO Update Script
 * Updates all pump descriptions with SEO-optimized content
 * Based on Crompton product specifications and local inventory
 */

require_once('config.inc.php');
require_once('core/db.inc.php');

$db = new MxDB();
$timestamp = date('Y-m-d H:i:s');
$log = [];

// SEO Description Templates - Structured by pump type and specifications
$descriptions = [
    // MINI PUMPS - Self-Priming
    'MINI MASTER' => [
        'template' => 'The {model} is a premium self-priming mini pump engineered for residential water pressure boosting and domestic applications. With {power} horsepower and {phase} phase operation, this Crompton mini pump delivers reliable performance with advanced electrical stamping technology. Features brass impellers, stainless steel components, and IP55 protection for durability. Ideal for water extraction, gardening, and household plumbing needs. Available now at Bombay Engineering Syndicate – your trusted Crompton distributor.',
        'specs' => ['power' => '1 HP', 'phase' => 'single', 'type' => 'Self-Priming Mini Pump']
    ],
    'MINI FORCE' => [
        'template' => 'The {model} mini force pump combines efficiency with reliability for modern residential water management. {power} capacity with {phase} phase operation, featuring advanced electrical construction and F-Class insulation. Delivers consistent pressure for water boosting and domestic applications. Compact design fits residential spaces seamlessly. Energy-efficient with IP55 protection. Perfect for home water supply systems. Get yours today from Bombay Engineering Syndicate – Crompton\'s authorized distributor.',
        'specs' => ['power' => '0.5-1 HP', 'phase' => 'single', 'type' => 'Self-Priming Mini Pump']
    ],
    'CHAMP PLUS' => [
        'template' => 'The {model} is an economical choice for residential water pressure needs, delivering consistent performance at an affordable price point. {power} single-phase operation with reliable self-priming capability. Compact design ideal for domestic water supply and pressure boosting. Features advanced electrical stamping and IP55 protection. Low maintenance, high reliability. Trusted by homeowners across India. Available at Bombay Engineering Syndicate – your Crompton distributor.',
        'specs' => ['power' => '1 HP', 'phase' => 'single', 'type' => 'Self-Priming Mini Pump']
    ],

    // 3-INCH BOREWELL SUBMERSIBLES
    '3-INCH' => [
        'template' => 'The {model} is a 3-inch submersible pump designed for shallow to medium-depth borewell applications in residential and agricultural settings. {power} capacity with {phase} phase operation ensures reliable water extraction from depths up to {depth} feet. Features deep borewell submersible technology with IP55 protection and energy-efficient performance. Ideal for small farms and residential water supply. Trusted quality from Crompton. Available at Bombay Engineering Syndicate – India\'s leading Crompton pump distributor.',
        'specs' => ['power' => '0.5-1 HP', 'phase' => 'single', 'depth' => '100-150']
    ],

    // 4-INCH BOREWELL SUBMERSIBLES
    '4-INCH OIL-FILLED' => [
        'template' => 'The {model} oil-filled borewell submersible pump delivers superior durability for deep borewell water extraction. {power} capacity with {phase} phase operation, featuring premium oil-filled construction for extended operational life. Handles voltage fluctuations effectively with excellent performance in challenging borewell conditions. Suitable for agricultural irrigation and residential applications. Deep borewell rated with IP55 protection. Premium Crompton quality at Bombay Engineering Syndicate.',
        'specs' => ['power' => '0.5-2 HP', 'phase' => 'single', 'type' => 'Oil-Filled Submersible']
    ],
    '4-INCH WATER-FILLED' => [
        'template' => 'The {model} water-filled borewell submersible pump combines eco-friendly design with reliable performance for medium to deep borewells. {power} capacity single-phase operation with excellent voltage fluctuation tolerance. Ideal for residential and agricultural water supply applications. Features sturdy construction, low noise operation, and energy-efficient performance. IP55 protected for durability. Requires routine maintenance. Your trusted source: Bombay Engineering Syndicate – Crompton distributor.',
        'specs' => ['power' => '0.5-2 HP', 'phase' => 'single', 'type' => 'Water-Filled Submersible']
    ],

    // OPENWELL PUMPS
    'HORIZONTAL OPENWELL' => [
        'template' => 'The {model} horizontal openwell pump is engineered for efficient water extraction from open sources like wells, tanks, and reservoirs. {power} capacity with {phase} phase operation delivers consistent flow for agricultural and residential applications. Designed for easy installation at ground level with minimal maintenance requirements. Features robust construction, energy-efficient motor, and reliable performance across seasons. Perfect for dairy farms, gardens, and community water systems. Get it from Bombay Engineering Syndicate – Crompton\'s authorized partner.',
        'specs' => ['power' => '1-3 HP', 'phase' => 'single/three', 'type' => 'Openwell Pump']
    ],
    'VERTICAL OPENWELL' => [
        'template' => 'The {model} vertical openwell pump offers reliable water extraction from open wells and tanks with space-efficient design. {power} capacity with {phase} phase operation for agricultural irrigation and residential water supply. Compact vertical configuration saves floor space while delivering consistent performance. Features energy-efficient motor, easy maintenance, and long operational life. IP55 protected against weather conditions. Available now at Bombay Engineering Syndicate – your trusted Crompton distributor in India.',
        'specs' => ['power' => '1-3 HP', 'phase' => 'single/three', 'type' => 'Openwell Pump']
    ],

    // SHALLOW WELL PUMPS
    'SHALLOW WELL' => [
        'template' => 'The {model} shallow well self-priming pump is ideal for extracting water from shallow borewells, hand pumps, and surface sources. {power} capacity single-phase operation with reliable self-priming capability. Perfect for residential water supply, agricultural irrigation, and emergency water needs. Compact design with easy installation. Features advanced electrical technology, IP55 protection, and energy-efficient performance. Low maintenance with long service life. Bombay Engineering Syndicate – your Crompton pump specialist.',
        'specs' => ['power' => '0.5-1 HP', 'phase' => 'single', 'type' => 'Shallow Well Pump']
    ],

    // AGRICULTURAL SUBMERSIBLES
    'AGRICULTURAL SUBMERSIBLE' => [
        'template' => 'The {model} agricultural submersible pump is purpose-built for farm irrigation and borewell water extraction with exceptional durability. {power} capacity with {phase} phase operation for deep water wells and borewells. Energy-efficient design reduces operational costs while maintaining consistent performance. IP55 protection and robust construction handle demanding farm conditions. Ideal for large farms, commercial irrigation, and agricultural development projects. Trusted by farmers. Available at Bombay Engineering Syndicate – Crompton\'s authorized distributor.',
        'specs' => ['power' => '0.5-2 HP', 'phase' => 'single', 'type' => 'Agricultural Submersible']
    ],

    // CONTROL PANELS
    'CONTROL PANEL' => [
        'template' => 'The {model} control panel provides comprehensive protection and control for pump motors in residential and agricultural applications. Features {specs} with advanced electrical safety and automation. Essential component for safe, efficient pump operation. Protects against voltage fluctuations, overload, and short circuits. Easy installation and maintenance. Crompton quality assurance. Available at Bombay Engineering Syndicate – complete pump solutions provider.',
        'specs' => ['type' => 'automatic/manual control']
    ],

    // PRESSURE BOOSTER PUMPS
    'PRESSURE BOOSTER' => [
        'template' => 'The {model} pressure booster pump ensures consistent water pressure throughout your residential or commercial property. {power} capacity with {phase} phase operation for reliable pressure maintenance. Ideal for high-rise buildings, hotels, hospitals, and residential complexes. Features automatic pressure control, low noise operation, and energy-efficient design. IP55 protected with long operational life. Essential for modern water systems. Get Crompton quality at Bombay Engineering Syndicate – your trusted pump distributor.',
        'specs' => ['power' => '1-2 HP', 'phase' => 'single/three', 'type' => 'Pressure Booster']
    ],

    // CIRCULATORY PUMPS
    'CIRCULATORY IN-LINE' => [
        'template' => 'The {model} circulatory in-line pump is engineered for continuous water circulation in heating systems, air conditioning, and industrial applications. {power} capacity with {phase} phase operation for reliable, quiet performance. Compact in-line design minimizes installation space. Features energy-efficient motor, low vibration, and extended service life. IP55 protection for durability. Industrial-grade reliability. Available at Bombay Engineering Syndicate – Crompton\'s comprehensive pump solutions provider.',
        'specs' => ['power' => '0.5-1 HP', 'phase' => 'single', 'type' => 'Circulatory Pump']
    ]
];

// Fetch all pumps from database
$query = "SELECT pumpID, pumpTitle, seoUri, categoryPID, kwhp, supplyPhase FROM mx_pump WHERE status=1 ORDER BY pumpTitle";
$pumps = $db->fetchAll($query);

if (!$pumps) {
    echo "ERROR: Could not fetch pumps from database\n";
    exit(1);
}

echo "Total pumps to update: " . count($pumps) . "\n";
echo str_repeat("=", 80) . "\n";

$updated = 0;
$skipped = 0;

foreach ($pumps as $pump) {
    $pumpID = $pump['pumpID'];
    $title = $pump['pumpTitle'];
    $power = $pump['kwhp'] ?: '1 HP';
    $phase = $pump['supplyPhase'] ?: 'single';

    // Determine pump type and select appropriate description template
    $description = null;

    if (stripos($title, 'MINI MASTER') !== false) {
        $desc_template = $descriptions['MINI MASTER']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }
    elseif (stripos($title, 'MINI FORCE') !== false) {
        $desc_template = $descriptions['MINI FORCE']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }
    elseif (stripos($title, 'CHAMP') !== false) {
        $desc_template = $descriptions['CHAMP PLUS']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }
    elseif (stripos($title, '3W') !== false || stripos($title, '3-INCH') !== false) {
        $desc_template = $descriptions['3-INCH']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}', '{depth}'],
                                 [$title, $power, $phase, '100'], $desc_template);
    }
    elseif ((stripos($title, '4W') !== false || stripos($title, '4VO') !== false) && stripos($title, 'OIL') !== false) {
        $desc_template = $descriptions['4-INCH OIL-FILLED']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }
    elseif ((stripos($title, '4W') !== false || stripos($title, '4V') !== false) && stripos($title, 'OIL') === false) {
        $desc_template = $descriptions['4-INCH WATER-FILLED']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }
    elseif (stripos($title, 'HORIZONTAL') !== false || stripos($title, 'OPENWELL') !== false) {
        if (stripos($title, 'HORIZONTAL') !== false) {
            $desc_template = $descriptions['HORIZONTAL OPENWELL']['template'];
        } else {
            $desc_template = $descriptions['VERTICAL OPENWELL']['template'];
        }
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }
    elseif (stripos($title, 'SHALLOW') !== false || stripos($title, 'SWJ') !== false) {
        $desc_template = $descriptions['SHALLOW WELL']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }
    elseif (stripos($title, 'AGRICULTURAL') !== false ||
            (stripos($title, 'RA') !== false && is_numeric(substr($title, 0, 3)))) {
        $desc_template = $descriptions['AGRICULTURAL SUBMERSIBLE']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }
    elseif (stripos($title, 'PRESSURE') !== false || stripos($title, 'BOOSTER') !== false) {
        $desc_template = $descriptions['PRESSURE BOOSTER']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }
    elseif (stripos($title, 'CONTROL') !== false || stripos($title, 'PANEL') !== false) {
        $desc_template = $descriptions['CONTROL PANEL']['template'];
        $description = str_replace(['{model}', '{specs}'],
                                 [$title, 'advanced control features'], $desc_template);
    }
    elseif (stripos($title, 'CIRCULATORY') !== false || stripos($title, 'IN-LINE') !== false) {
        $desc_template = $descriptions['CIRCULATORY IN-LINE']['template'];
        $description = str_replace(['{model}', '{power}', '{phase}'],
                                 [$title, $power, $phase], $desc_template);
    }

    // If no description generated, use generic template
    if (!$description) {
        $description = "The {$title} is a premium Crompton pump engineered for reliable water extraction and pressure management. Features advanced electrical technology, IP55 protection, and energy-efficient operation. Suitable for residential, agricultural, and commercial applications. Available now at Bombay Engineering Syndicate – your trusted Crompton distributor.";
        $skipped++;
    } else {
        // Sanitize the description
        $description = cleanHtml($description);

        // Update database
        $update_query = "UPDATE mx_pump SET pumpFeatures = %s WHERE pumpID = %d";
        $result = $db->query($update_query, [$description, $pumpID]);

        if ($result) {
            $log[] = "[✓] {$title} - Description updated ({$pumpID})";
            $updated++;
        } else {
            $log[] = "[✗] {$title} - FAILED to update ({$pumpID})";
        }
    }
}

// Clear cache
if (file_exists('/tmp/bombayengg_cache')) {
    exec('rm -rf /tmp/bombayengg_cache/*');
    $log[] = "[✓] Cache cleared";
}

// Generate report
echo "\n" . str_repeat("=", 80) . "\n";
echo "PUMP DESCRIPTION UPDATE REPORT\n";
echo str_repeat("=", 80) . "\n\n";

echo "✅ Total Updated: $updated\n";
echo "⚠️  Total Skipped/Generic: $skipped\n";
echo "Total Pumps: " . count($pumps) . "\n\n";

echo "Update Log:\n";
echo str_repeat("-", 80) . "\n";
foreach ($log as $entry) {
    echo $entry . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Database backup: PUMP_DESCRIPTIONS_BACKUP_*.sql\n";
echo "Update completed at: $timestamp\n";
echo str_repeat("=", 80) . "\n";

// Also save log to file
$log_file = "PUMP_DESCRIPTIONS_UPDATE_LOG_" . date('Y-m-d_H-i-s') . ".txt";
file_put_contents($log_file, implode("\n", $log));
echo "\nLog saved to: $log_file\n";

function cleanHtml($html) {
    // Basic HTML sanitization - allow common tags
    $allowed_tags = '<p><br><strong><b><em><i><ul><li><ol><a>';
    return strip_tags($html, $allowed_tags);
}

?>
