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

// Define motor products for each category
$motorProducts = array(
    // Category ID 20: High Voltage Motors
    20 => array(
        array(
            'title' => 'High Voltage Industrial Motor HV-500KW',
            'subtitle' => '500 KW Industrial Grade Motor',
            'features' => 'High voltage industrial motor engineered for heavy-duty manufacturing plants and power generation facilities. Features advanced cooling systems, reinforced insulation, and robust construction. Suitable for continuous operation in demanding industrial environments.',
            'content' => 'This high voltage motor delivers exceptional efficiency and reliability. Designed for manufacturing plants, power generation facilities, and heavy industrial applications. Advanced thermal management and specialized construction ensure consistent performance in the most demanding conditions.',
            'image' => 'HV-Motors-High-Voltage-Bombay-Engineering.webp'
        ),
        array(
            'title' => 'High Voltage Motor HV-250KW',
            'subtitle' => '250 KW High Voltage Motor',
            'features' => 'Compact yet powerful high voltage motor for industrial machinery and power applications. Delivers consistent torque and efficiency across continuous operation. Built with superior materials for extended lifespan.',
            'content' => 'Mid-range high voltage motor suitable for various industrial applications. Combines power with efficiency for manufacturing operations requiring reliable motor performance.',
            'image' => 'HV-Motors-High-Voltage-Bombay-Engineering.webp'
        )
    ),

    // Category ID 21: Low Voltage Motors
    21 => array(
        array(
            'title' => 'Low Voltage Motor LV-15KW',
            'subtitle' => '15 KW Commercial Grade Motor',
            'features' => 'Versatile low voltage motor suitable for water pumps, HVAC systems, and commercial machinery. Energy-saving design with compact profile. Easy integration with existing systems.',
            'content' => 'Reliable low voltage motor for commercial and residential applications. Perfect for water supply systems, cooling systems, and general industrial machinery. Combines efficiency with affordability.',
            'image' => 'LV-Motors-Low-Voltage-Bombay-Engineering.webp'
        ),
        array(
            'title' => 'Low Voltage Motor LV-7KW',
            'subtitle' => '7 KW Compact Motor',
            'features' => 'Compact low voltage motor for smaller applications. Ideal for residential water pumps and light commercial machinery. Low power consumption with reliable performance.',
            'content' => 'Efficient low voltage motor for residential and small commercial applications. Designed for cost-conscious users seeking reliable motor solutions.',
            'image' => 'LV-Motors-Low-Voltage-Bombay-Engineering.webp'
        )
    ),

    // Category ID 22: Energy Efficient Motors
    22 => array(
        array(
            'title' => 'Energy Efficient Motor IE4-Premium 20KW',
            'subtitle' => 'IE4 Premium Efficiency 20 KW',
            'features' => 'Meets international IE4 standards for premium efficiency. Significantly reduces operational costs and carbon footprint. Lower operating temperatures, extended lifespan, and reduced maintenance requirements.',
            'content' => 'Premium efficiency motor meeting IE4 international standards. Ideal for environmentally-conscious industries and businesses targeting sustainability goals. Superior efficiency without compromising performance.',
            'image' => 'Energy-Efficient-Motors-IE3-IE4-Bombay.webp'
        ),
        array(
            'title' => 'Energy Efficient Motor IE3-High Efficiency 10KW',
            'subtitle' => 'IE3 High Efficiency 10 KW',
            'features' => 'Meets IE3 international efficiency standards. Delivers superior efficiency for cost-conscious operations. Extended motor life with reduced energy consumption.',
            'content' => 'High efficiency motor meeting IE3 standards. Perfect balance between cost-effectiveness and energy savings for industrial applications.',
            'image' => 'Energy-Efficient-Motors-IE3-IE4-Bombay.webp'
        )
    ),

    // Category ID 23: Motors for Hazardous Area (LV)
    23 => array(
        array(
            'title' => 'Safety Certified Hazardous Area Motor LV-15KW',
            'subtitle' => 'Flame-Proof LV Motor - ATEX Certified',
            'features' => 'Safety-certified for hazardous environments (Zone 1, 2). Flame-proof construction with enhanced cooling. Specialized insulation for explosive atmosphere protection. Compliant with international safety standards.',
            'content' => 'Designed for chemical plants, oil refineries, and mining operations where explosive atmospheres are present. Maximum safety with reliable performance in critical applications.',
            'image' => 'Safety-Motors-Hazardous-Area-LV-Bombay.webp'
        )
    ),

    // Category ID 24: DC Motors
    24 => array(
        array(
            'title' => 'DC Motor Industrial Series DC-50KW',
            'subtitle' => '50 KW Industrial DC Motor',
            'features' => 'Provides precise speed control and exceptional torque for specialized applications. From mining equipment to steel mills. Robust construction with advanced cooling systems.',
            'content' => 'Industrial grade DC motor perfect for applications requiring variable speed operation and high torque output. Suitable for steel mills, mining equipment, and specialty machinery.',
            'image' => 'DC-Motors-Industrial-Machine-Bombay.webp'
        ),
        array(
            'title' => 'DC Motor Series DC-25KW',
            'subtitle' => '25 KW DC Motor',
            'features' => 'Mid-range DC motor for controlled speed applications. Excellent for hoists, cranes, and machinery requiring variable speed. Custom configurations available.',
            'content' => 'Versatile DC motor suitable for applications where precise speed control is essential.',
            'image' => 'DC-Motors-Industrial-Machine-Bombay.webp'
        )
    ),

    // Category ID 25: Motors for Hazardous Areas (HV)
    25 => array(
        array(
            'title' => 'High Voltage Flame-Proof Motor HV-Hazard-75KW',
            'subtitle' => 'Flame-Proof HV Motor - ATEX Certified',
            'features' => 'Designed for the most demanding hazardous environments. Meets stringent international safety standards for explosive atmospheres. Advanced thermal management with specialized insulation systems.',
            'content' => 'Maximum protection for critical industrial settings including chemical plants, oil refineries, and similar high-risk applications. Combines safety with reliability.',
            'image' => 'Flame-Proof-Motors-HV-Hazardous-Bombay.webp'
        )
    ),

    // Category ID 26: Special Application Motors
    26 => array(
        array(
            'title' => 'Cement Mill Twin Drive Motor Special-150KW',
            'subtitle' => 'Twin-Drive Motor for Cement Mill',
            'features' => 'Specifically engineered for cement mill twin-drive applications. Enhanced durability for heavy continuous duty. Superior load-handling capabilities for specialized industrial processes.',
            'content' => 'Custom-designed motor for cement mill twin-drive systems. Optimized for the unique requirements of cement manufacturing with superior reliability.',
            'image' => 'Special-Application-Motors-Cement-Mill-Bombay.webp'
        ),
        array(
            'title' => 'Special Application Sugar Plant Motor-75KW',
            'subtitle' => 'Custom Motor for Sugar Processing',
            'features' => 'Engineered for sugar plant processing machinery. Handles the demanding requirements of sugar manufacturing with reliability and efficiency. Custom configurations for specific plant requirements.',
            'content' => 'Purpose-built motor for sugar plant applications. Delivers optimized performance for sugar processing machinery.',
            'image' => 'Special-Application-Motors-Cement-Mill-Bombay.webp'
        )
    )
);

// Insert motor products
$totalProducts = 0;
foreach ($motorProducts as $categoryID => $products) {
    foreach ($products as $product) {
        $motorData = array(
            'categoryMID' => $categoryID,
            'motorTitle' => $product['title'],
            'motorSubTitle' => $product['subtitle'],
            'motorFeatures' => $product['features'],
            'motorContent' => $product['content'],
            'motorImage' => $product['image'],
            'seoUri' => makeSeoUri($product['title']),
            'status' => 1
        );

        $DB->table = "mx_motor";
        $DB->data = $motorData;
        if ($DB->dbInsert()) {
            $motorID = $DB->insertID;
            $totalProducts++;
            echo "✓ Product added: {$product['title']} (ID: $motorID)\n";
        } else {
            echo "✗ Failed to add product: {$product['title']}\n";
        }
    }
}

echo "\n========================================\n";
echo "Motor Products Creation Summary:\n";
echo "========================================\n";
echo "Total Products Created: $totalProducts\n";
echo "Status: SUCCESS\n";
echo "========================================\n\n";

// Verify the products were created
echo "Products by Category:\n";
echo "====================\n";
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT c.categoryTitle, COUNT(m.motorID) as product_count
            FROM mx_motor_category c
            LEFT JOIN mx_motor m ON c.categoryMID = m.categoryMID AND m.status = 1
            WHERE c.parentID > 0 AND c.status = 1
            GROUP BY c.categoryMID, c.categoryTitle
            ORDER BY c.xOrder";
$results = $DB->dbRows();
foreach ($results as $row) {
    echo "- {$row['categoryTitle']}: {$row['product_count']} products\n";
}

?>
