<?php
require_once("config.inc.php");
require_once(COREPATH . "/db.class.inc.php");

$DB = new dbClass();

// Helper function to generate SEO URI
function makeSeoUri($str = "") {
    $str = trim($str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9]+/i', '-', $str);
    $str = trim($str, '-');
    return $str;
}

// 1. Create Parent Category: High / Low Voltage AC & DC Motors
$parentData = array(
    'categoryTitle' => 'High / Low Voltage AC & DC Motors',
    'seoUri' => 'high-low-voltage-ac-dc-motors',
    'synopsis' => 'Comprehensive range of high and low voltage AC & DC motors for industrial applications, including energy-efficient designs, hazardous area motors, and specialized applications.',
    'imageName' => '',
    'xOrder' => 1,
    'parentID' => 0,
    'status' => 1,
    'langCode' => '',
    'langChild' => '',
    'parentLID' => 0,
    'templateFile' => ''
);

$DB->table = "mx_motor_category";
$DB->data = $parentData;
if ($DB->dbInsert()) {
    $parentID = $DB->insertID;
    echo "✓ Parent category created (ID: $parentID)\n";
} else {
    echo "✗ Failed to create parent category\n";
    exit(1);
}

// 2. Define 7 sub-categories with their content
$categories = array(
    array(
        'title' => 'High Voltage Motors',
        'image' => 'HV-Motors-High-Voltage-Bombay-Engineering.webp',
        'synopsis' => 'High voltage motors from Bombay Engineering are engineered for industrial applications requiring superior performance and reliability. These motors deliver exceptional efficiency across heavy-duty operations, from manufacturing plants to power generation facilities. Our HV motors feature advanced cooling systems, reinforced insulation, and robust construction to handle demanding environments while maintaining consistent power output and operational safety.',
        'xOrder' => 1
    ),
    array(
        'title' => 'Low Voltage Motors',
        'image' => 'LV-Motors-Low-Voltage-Bombay-Engineering.webp',
        'synopsis' => 'Low voltage motors from Bombay Engineering combine efficiency with versatility for commercial and residential applications. Suitable for water pumps, HVAC systems, and industrial machinery, these motors offer reliable performance in diverse operational scenarios. Our LV motors feature energy-saving designs, compact profiles, and easy integration capabilities, making them ideal for cost-conscious businesses seeking dependable motor solutions.',
        'xOrder' => 2
    ),
    array(
        'title' => 'Energy Efficient Motors',
        'image' => 'Energy-Efficient-Motors-IE3-IE4-Bombay.webp',
        'synopsis' => 'Energy-efficient motors from Bombay Engineering meet international IE3 and IE4 standards, significantly reducing operational costs and carbon footprint. Ideal for environmentally-conscious industries and businesses targeting sustainability goals, these motors deliver superior efficiency without sacrificing performance. Our IE-rated motors provide lower operating temperatures, extended lifespan, and reduced maintenance requirements.',
        'xOrder' => 3
    ),
    array(
        'title' => 'Motors for Hazardous Area (LV)',
        'image' => 'Safety-Motors-Hazardous-Area-LV-Bombay.webp',
        'synopsis' => 'Safety-certified motors for hazardous environments from Bombay Engineering ensure operational protection in explosive atmospheres. Compliant with international safety standards, these LV hazardous area motors feature flame-proof construction, enhanced cooling, and specialized insulation for zones requiring maximum safety. Ideal for chemical plants, oil refineries, and mining operations.',
        'xOrder' => 4
    ),
    array(
        'title' => 'DC Motors',
        'image' => 'DC-Motors-Industrial-Machine-Bombay.webp',
        'synopsis' => 'DC motors from Bombay Engineering provide precise speed control and exceptional torque for specialized industrial applications. From mining equipment to steel mills, our DC motors deliver consistent performance in demanding environments. Featuring robust construction, advanced cooling systems, and customizable configurations, these motors are engineered for applications requiring variable speed operation and high torque output.',
        'xOrder' => 5
    ),
    array(
        'title' => 'Motors for Hazardous Areas (HV)',
        'image' => 'Flame-Proof-Motors-HV-Hazardous-Bombay.webp',
        'synopsis' => 'High voltage flame-proof motors from Bombay Engineering are designed for the most demanding hazardous environments. These motors meet stringent international safety standards for explosive atmospheres and provide maximum protection in high-risk industrial settings. With advanced thermal management, reinforced construction, and specialized insulation systems, our HV hazardous area motors ensure safe, reliable operation in critical applications.',
        'xOrder' => 6
    ),
    array(
        'title' => 'Special Application Motors',
        'image' => 'Special-Application-Motors-Cement-Mill-Bombay.webp',
        'synopsis' => 'Specialized motors from Bombay Engineering are engineered for unique industrial requirements including cement mills, sugar plants, and textile machinery. Our custom-designed motors feature application-specific configurations, enhanced durability, and superior load-handling capabilities. Whether for twin-drive systems, brake motors, or re-rolling mill applications, our specialized motors deliver optimized performance.',
        'xOrder' => 7
    )
);

// 3. Insert all sub-categories
$subCategoryIDs = array();
foreach ($categories as $cat) {
    $catData = array(
        'categoryTitle' => $cat['title'],
        'seoUri' => 'high-low-voltage-ac-dc-motors/' . makeSeoUri($cat['title']),
        'imageName' => $cat['image'],
        'synopsis' => $cat['synopsis'],
        'xOrder' => $cat['xOrder'],
        'parentID' => $parentID,
        'status' => 1,
        'langCode' => '',
        'langChild' => '',
        'parentLID' => 0,
        'templateFile' => ''
    );

    $DB->table = "mx_motor_category";
    $DB->data = $catData;
    if ($DB->dbInsert()) {
        $catID = $DB->insertID;
        $subCategoryIDs[] = $catID;
        echo "✓ Sub-category created: {$cat['title']} (ID: $catID)\n";
    } else {
        echo "✗ Failed to create sub-category: {$cat['title']}\n";
    }
}

echo "\n========================================\n";
echo "Motor Categories Creation Summary:\n";
echo "========================================\n";
echo "Parent Category ID: $parentID\n";
echo "Sub-Categories Created: " . count($subCategoryIDs) . "\n";
echo "Total Categories: " . (1 + count($subCategoryIDs)) . "\n";
echo "Status: SUCCESS\n";
echo "========================================\n\n";

// Verify the categories were created
$DB->vals = array($parentID);
$DB->types = "i";
$DB->sql = "SELECT COUNT(*) as cnt FROM mx_motor_category WHERE parentID=? OR categoryMID=?";
$DB->vals = array($parentID, $parentID);
$DB->types = "ii";
$DB->sql = "SELECT categoryMID, categoryTitle, seoUri FROM mx_motor_category WHERE parentID=? ORDER BY xOrder";
$results = $DB->dbAll();

echo "Created Categories:\n";
echo "==================\n";
foreach ($results as $row) {
    echo "- {$row['categoryTitle']} ({$row['seoUri']})\n";
}

?>
