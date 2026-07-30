<?php
/**
 * WhatsApp AI-Powered Pump Matcher
 * 3-layer matching: Model Lookup → Use-Case Dictionary → Spec-Based Scoring
 * No external LLM — pure PHP pattern matching + weighted scoring
 */

/**
 * Use-case → category mapping dictionary
 * Maps common customer queries to product categories + recommended pump IDs
 * Based on Crompton product research: application data, specs, differentiators
 *
 * Categories: 4=Centrifugal Monoset, 24=Mini, 25=DMB-CMB, 26=Shallow Well,
 *   27=3" Borewell, 28=4" Borewell, 29=Openwell, 30=Booster, 31=Control Panel, 32=Agricultural
 *
 * Priority pump IDs ensure best-fit models appear first for each use-case
 */
function getUseCaseDictionary()
{
    return [
        // --- RESIDENTIAL: Pressure Boosting ---
        [
            'keywords' => ['pressure booster', 'pressure boost', 'low pressure', 'pressure problem', 'no pressure',
                           'water pressure', 'tap pressure', 'constant pressure', 'auto pump', 'automatic pump',
                           'pressure pump'],
            'categoryIDs' => [30],
            'label' => 'Pressure Boosting',
            // CFMSMB series (dedicated booster with pressure tank + auto on/off) > Mini Force (budget booster)
            'priorityPumpIDs' => [46, 84, 85, 45], // CFMSMB5D1.00, CFMSMB3D0.50, MINI FORCE II, Mini Force I
        ],
        [
            'keywords' => ['flat', 'apartment', 'society', 'high floor', 'multistory', 'high rise',
                           'top floor', 'upper floor', 'building pressure', 'floor pressure'],
            'categoryIDs' => [30, 24],
            'label' => 'Apartment / High-Floor Pressure',
            // CFMSMB (auto booster) for apartments, Star Plus/Master Dura (high head mini) as backup
            'priorityPumpIDs' => [46, 84, 27, 58], // CFMSMB5D1.00, CFMSMB3D0.50, STAR PLUS I, MASTER DURA I
        ],

        // --- RESIDENTIAL: Overhead Tank / Building Water Lifting ---
        [
            'keywords' => ['overhead tank', 'tank filling', 'terrace tank', 'building tank', 'overhead',
                           'tank pump', 'water tank', 'fill tank', 'roof tank', 'loft tank',
                           'ground to terrace', 'ground to top', 'ground to roof',
                           'sump to terrace', 'sump to overhead', 'sump to top', 'sump to roof',
                           'neeche se upar', 'upar tank', 'terrace pe', 'chhat pe',
                           'building pump', 'building water', 'building ke liye',
                           'pump for building', 'water for building', 'for building',
                           'residential building', 'residential pump', 'residential water',
                           'water lifting', 'water pull', 'pull water', 'lift water',
                           'pani lift', 'pani pull', 'pani upar', 'pani chadana',
                           'bungalow', 'row house', 'duplex', 'penthouse'],
            'categoryIDs' => [24, 25],
            'label' => 'Overhead Tank Filling',
            // Mini pumps (self-priming, designed for sump-to-tank): Master Dura (50m head, 40% faster),
            // Star Plus (45m head), then DMB for larger buildings
            'priorityPumpIDs' => [58, 57, 27, 28, 31], // MASTER DURA I, MASTER PLUS I, STAR PLUS I, STAR DURA I, DMB10D PLUS
        ],
        [
            'keywords' => ['3 floor', 'three floor', '3 storey', 'three storey', '3 story',
                           '4 floor', 'four floor', '5 floor', 'five floor',
                           '2 floor', 'two floor', '2 storey', 'two storey', '2 story',
                           '2 manzil', '3 manzil', '4 manzil', '5 manzil',
                           'multi story', 'multi storey', 'multi floor',
                           'tall building', '3rd floor', '4th floor', '5th floor',
                           '2nd floor', 'top floor'],
            'categoryIDs' => [24],
            'label' => 'Multi-Storey Tank Filling',
            // High-head models: Master Dura I (50m head), Star Plus I (45m), Aquagold 150 (45m)
            'priorityPumpIDs' => [58, 27, 28, 23], // MASTER DURA I, STAR PLUS I, STAR DURA I, AQUAGOLD 150
        ],
        [
            'keywords' => ['7 floor', 'seven floor', '8 floor', 'eight floor',
                           '10 floor', 'ten floor', '9 floor', '6 floor', 'six floor',
                           '7 manzil', '8 manzil', '10 manzil',
                           'high rise building', 'tower', 'commercial building',
                           'large building', 'big building'],
            'categoryIDs' => [30, 25],
            'label' => 'High-Rise Building Water Supply',
            // For 7+ floors need booster systems (CFMSMB) or heavy-duty DMB
            'priorityPumpIDs' => [46, 84, 31, 32], // CFMSMB5D1.00, CFMSMB3D0.50, DMB10D PLUS, DMB10DCSL
        ],
        [
            'keywords' => ['water motor', 'motor pump', 'pamp', 'pump lagana', 'pump lagao',
                           'pump laga do', 'pump chahiye', 'need pump', 'want pump',
                           'suggest pump', 'recommend pump', 'best pump', 'good pump',
                           'which pump', 'konsa pump', 'kaun sa pump', 'kaunsa pump'],
            'categoryIDs' => [24],
            'label' => 'General Pump Recommendation',
            // Versatile best-sellers for general queries
            'priorityPumpIDs' => [52, 57, 53, 26, 27], // MINI MASTERPLUS I, MASTER PLUS I, MINI MARVEL I, ULTIMO I, STAR PLUS I
        ],

        // --- RESIDENTIAL: BMC / Municipal Line ---
        [
            'keywords' => ['bmc line', 'line pulling', 'municipal line', 'corporation line', 'nagar palika',
                           'municipality', 'bmc water', 'corporation water', 'line water', 'main line',
                           'nagar sevak', 'pani line', 'water line', 'municipal water'],
            'categoryIDs' => [25, 24],
            'label' => 'BMC Line / Municipal Water',
            // DMB-CMB (centrifugal, 1440 RPM, quiet, designed for line pulling) > Aquagold (larger pipe)
            'priorityPumpIDs' => [31, 32, 30, 33, 69], // DMB10D PLUS, DMB10DCSL, CMB10NV PLUS, CMB05NV PLUS, AQUAGOLD 50-30
        ],

        // --- RESIDENTIAL: Swimming Pool / Fountain ---
        [
            'keywords' => ['swimming pool', 'pool pump', 'fountain', 'water fountain', 'decorative',
                           'pool filter', 'jacuzzi', 'water feature'],
            'categoryIDs' => [25],
            'label' => 'Swimming Pool / Fountain',
            // DMB series: heavy duty, 1440 RPM, high discharge — ideal for pools/fountains
            'priorityPumpIDs' => [31, 32, 30], // DMB10D PLUS, DMB10DCSL, CMB10NV PLUS
        ],

        // --- RESIDENTIAL: General Home / Domestic ---
        [
            'keywords' => ['home pump', 'house pump', 'domestic pump', 'ghar', 'home water',
                           'house water', 'residential pump', 'family', 'bathroom', 'kitchen',
                           'home use', 'domestic use'],
            'categoryIDs' => [24],
            'label' => 'Home / Domestic Water',
            // General-purpose mini pumps: Mini Masterplus (premium), Mini Marvel (mid), Ultimo (budget)
            'priorityPumpIDs' => [52, 53, 26, 24, 49], // MINI MASTERPLUS I, MINI MARVEL I, ULTIMO I, WIN PLUS I, NILE PLUS I
        ],
        [
            'keywords' => ['small pump', 'mini pump', 'chhota pump', 'cheap pump', 'budget pump',
                           'sasta pump', 'kam price', 'low cost', 'low budget', 'affordable'],
            'categoryIDs' => [24],
            'label' => 'Budget / Mini Pump',
            // Budget-friendly models sorted by price
            'priorityPumpIDs' => [25, 26, 54, 49, 24], // ULTIMO II, ULTIMO I, GLORY PLUS I, NILE PLUS I, WIN PLUS I
        ],
        [
            'keywords' => ['1.5 hp mini', '1.5hp mini', 'powerful mini', 'large mini', 'big mini',
                           'heavy duty mini', 'commercial mini'],
            'categoryIDs' => [24],
            'label' => 'High-Power Mini Pump',
            // Aquagold 1.5HP range — highest power in mini category
            'priorityPumpIDs' => [23, 22, 70], // AQUAGOLD 150, AQUAGOLD DURA 150, AQUAGOLD 100-33
        ],

        // --- RESIDENTIAL: Garden / Lawn ---
        [
            'keywords' => ['garden', 'lawn', 'sprinkler', 'drip irrigation', 'small garden',
                           'gardening', 'plant watering', 'flower', 'lawn pump', 'garden pump',
                           'bagiya', 'bagicha'],
            'categoryIDs' => [24],
            'label' => 'Garden / Lawn Watering',
            // 0.5HP mini pumps — sufficient for garden, cost-effective
            'priorityPumpIDs' => [67, 64, 55, 69], // MINI MARVEL II, MINI MASTER II, MINI CREST I, AQUAGOLD 50-30
        ],

        // --- RESIDENTIAL: Sump / Water Transfer ---
        [
            'keywords' => ['sump', 'basement', 'water transfer', 'underground tank', 'sump pump',
                           'tank to tank', 'water lifting', 'sump to tank'],
            'categoryIDs' => [29, 25, 24],
            'label' => 'Sump / Water Transfer',
            // Openwell submersible (no priming, submerge in sump) > DMB-CMB > Mini
            'priorityPumpIDs' => [43, 44, 31, 52], // OWE12, OWE052, DMB10D PLUS, MINI MASTERPLUS I
        ],

        // --- BOREWELL ---
        [
            'keywords' => ['borewell', 'bore well', 'boring', 'bore pump', 'tubewell', 'tube well',
                           'bore hole', 'borwell', 'bor well', 'borewell pump'],
            'categoryIDs' => [28, 27],
            'label' => 'Borewell / Submersible',
            // 4-inch water-filled (most common residential borewell) > 3-inch (narrow bores)
            'priorityPumpIDs' => [40, 42, 41, 37, 38], // 4W7BU1AU, 4W10BU1AU, 4W14BU2EU, 3W12AP1D, 3W10AP1D
        ],
        [
            'keywords' => ['3 inch', '3"', '75mm bore', '3inch', '3 inch bore', 'narrow bore',
                           'narrow borewell', 'small bore', 'chhota bore'],
            'categoryIDs' => [27],
            'label' => '3-Inch Borewell Submersible',
            'priorityPumpIDs' => [37, 38, 39], // 3W12AP1D, 3W10AP1D, 3W10AK1A
        ],
        [
            'keywords' => ['4 inch', '4"', '100mm bore', 'deep bore', '4inch', '4 inch bore',
                           'standard bore', 'deep borewell', 'deep well'],
            'categoryIDs' => [28],
            'label' => '4-Inch Borewell Submersible',
            // Water-filled first (eco, affordable), oil-filled for heavy-duty
            'priorityPumpIDs' => [40, 42, 76, 77, 41], // 4W7, 4W10, 4W12BF, 4W14BF, 4W14BU2EU
        ],
        [
            'keywords' => ['oil filled', 'oil cooled', 'heavy duty bore', 'continuous bore',
                           'heavy duty submersible', 'non stop bore'],
            'categoryIDs' => [28],
            'label' => 'Oil-Filled Borewell (Heavy Duty)',
            // 4VO series — oil-filled for continuous operation, better cooling
            'priorityPumpIDs' => [78, 79, 80, 81, 82, 83], // 4VO1/7, 4VO1/10, 4VO7BU, 4VO10BU, 4VO1.5/12, 4VO1.5/14
        ],

        // --- OPEN WELL ---
        [
            'keywords' => ['open well', 'openwell', 'kuwa', 'kuan', 'well pump', 'kuaan',
                           'open well pump', 'baav', 'vav', 'well water', 'dewatering',
                           'no priming', 'submersible well'],
            'categoryIDs' => [29],
            'label' => 'Open Well / Sump Submersible',
            // OWE series — submerge directly, no priming, no foot valve
            'priorityPumpIDs' => [43, 44], // OWE12(1PH)Z-28, OWE052(1PH)Z-21FS
        ],

        // --- SHALLOW WELL ---
        [
            'keywords' => ['shallow well', 'shallow borewell', 'shallow bore', 'jet pump',
                           'self priming', 'suction lift', 'surface water', 'shallow'],
            'categoryIDs' => [26],
            'label' => 'Shallow Well / Jet Pump',
            // SWJ series — jet assembly for enhanced suction from shallow wells
            'priorityPumpIDs' => [35, 107, 34, 109, 36], // SWJ100AT-36, SWJ100A-36, SWJ1, SWJ50A-30, SWJ50AT-30
        ],

        // --- AGRICULTURE ---
        [
            'keywords' => ['farm', 'khet', 'kheti', 'agriculture', 'irrigation', 'sinchai',
                           'field', 'crop', 'farming', 'agricultural', 'farm pump',
                           'kisan', 'krishi', 'zameen', 'farm land', 'farm bore',
                           'drip system', 'sprinkler system'],
            'categoryIDs' => [32, 4],
            'label' => 'Agriculture / Farming',
            // Agricultural submersible (MIK/MIN/MIP for large farms), centrifugal monoset for surface
            'priorityPumpIDs' => [98, 90, 89, 91, 92], // MIK22-18, MIK32-27, MIN32-26, MIP52-27, MINH52-30
        ],
        [
            'keywords' => ['5 hp', '5hp', '7.5 hp', '7.5hp', '10 hp', '10hp', 'high hp',
                           'heavy duty agri', 'large farm', 'big farm'],
            'categoryIDs' => [32],
            'label' => 'High-Power Agricultural',
            'priorityPumpIDs' => [91, 92, 93], // MIP52-27 (5HP), MINH52-30 (5HP), MIP7.52-30 (7.5HP)
        ],
        [
            'keywords' => ['small farm', 'kitchen garden', 'nursery', 'horticulture',
                           'small irrigation', 'small field', 'chhota khet'],
            'categoryIDs' => [32, 4],
            'label' => 'Small Farm / Horticulture',
            // Lower HP agricultural: 0.5-1HP monoset/agri pumps
            'priorityPumpIDs' => [97, 104, 94, 100], // MAD052(1PH)Y-14, MAD052(1PH)Y-21+, MBG1.52, MBM12(1PH)
        ],

        // --- CENTRIFUGAL / MONOSET ---
        [
            'keywords' => ['monoblock', 'monoset', 'centrifugal', 'centrifugal pump',
                           'monoblock pump', 'surface pump', 'non submersible'],
            'categoryIDs' => [4, 32],
            'label' => 'Centrifugal / Monoblock',
            'priorityPumpIDs' => [102, 95, 99, 100, 103], // MBG12(1PH), MBG12(3PH), MBK22, MBM12, MAD12
        ],

        // --- SOLAR ---
        [
            'keywords' => ['solar', 'solar pump', 'mnre', 'solar submersible', 'solar borewell',
                           'solar panel pump', 'solar powered', 'govt subsidy', 'solar subsidy'],
            'categoryIDs' => [27, 28, 25, 32],
            'label' => 'Solar / MNRE Approved',
            // MNRE-approved models: 3" borewell, 4" borewell, DMB-CMB, 100W agricultural
            'priorityPumpIDs' => [37, 38, 40, 42, 31, 86, 87, 88], // 3W12, 3W10, 4W7, 4W10, DMB10D, 100W series
        ],

        // --- CONTROL PANEL ---
        [
            'keywords' => ['control panel', 'starter', 'panel', 'pump panel', 'motor starter',
                           'dol starter', 'pump controller'],
            'categoryIDs' => [31],
            'label' => 'Control Panel / Starter',
            'priorityPumpIDs' => [47, 48], // ARMOR1.5-DSU, ARMOR1.0-CQU
        ],

        // --- HINDI/MARATHI KEYWORDS ---
        [
            'keywords' => ['paani', 'pani', 'nali', 'motor pump', 'pamp', 'pump chahiye',
                           'pump lagana', 'pump lagao', 'ghar ka pump'],
            'categoryIDs' => [24],
            'label' => 'Home / Domestic Water',
            'priorityPumpIDs' => [52, 53, 26, 24], // MINI MASTERPLUS I, MINI MARVEL I, ULTIMO I, WIN PLUS I
        ],

        // --- HOTEL / COMMERCIAL ---
        [
            'keywords' => ['hotel', 'hospital', 'club', 'laundry', 'dairy', 'restaurant',
                           'commercial', 'showroom', 'office', 'shop'],
            'categoryIDs' => [24, 30],
            'label' => 'Commercial / Hospitality',
            // Higher-end mini (Aquagold 1.5HP) + booster for commercial
            'priorityPumpIDs' => [23, 22, 46, 84], // AQUAGOLD 150, AQUAGOLD DURA 150, CFMSMB5D1.00, CFMSMB3D0.50
        ],

        // --- RURAL / VILLAGE ---
        [
            'keywords' => ['village', 'gaon', 'rural', 'tier 2', 'tier 3', 'gramin',
                           'voltage problem', 'voltage fluctuation', 'low voltage'],
            'categoryIDs' => [24],
            'label' => 'Rural / Low-Voltage Area',
            // Win Plus (double rust protection, wide voltage 180-260V, newest for rural market)
            'priorityPumpIDs' => [24, 56, 62], // WIN PLUS I, CHAMP PLUS I, CHAMP DURA I
        ],
    ];
}

/**
 * Knowledge base article keyword mapping
 */
function getKnowledgeBaseMapping()
{
    return [
        ['keywords' => ['vfd', 'flame proof', 'variable frequency'], 'articleID' => 1, 'title' => 'Use of VFD with Flame-proof Motors'],
        ['keywords' => ['hp to kw', 'kw to hp', 'conversion', 'convert hp'], 'articleID' => 2, 'title' => 'HP to kW Conversion Guide'],
        ['keywords' => ['hazardous', 'gas group', 'iia', 'iib', 'explosion proof'], 'articleID' => 3, 'title' => 'Hazardous-Area Motors Deep Dive'],
        ['keywords' => ['efficiency', 'ie1', 'ie2', 'ie3', 'ie4', 'efficiency class'], 'articleID' => 4, 'title' => 'Motor Efficiency Classes Explained'],
        ['keywords' => ['choose pump', 'which pump', 'pump selection', 'best pump', 'right pump'], 'articleID' => 5, 'title' => 'How to Choose the Best Crompton Pump'],
        ['keywords' => ['nameplate', 'rating', 'read nameplate'], 'articleID' => 7, 'title' => 'How to Read a Motor Nameplate'],
        ['keywords' => ['cooling', 'ic code', 'ic411', 'cooling method'], 'articleID' => 8, 'title' => 'Motor Cooling Methods Explained'],
        ['keywords' => ['motor failure', 'prevent failure', 'motor problem', 'motor issue'], 'articleID' => 9, 'title' => 'Common Motor Failures Prevention'],
        ['keywords' => ['motor for pump', 'pump motor', 'motor selection'], 'articleID' => 10, 'title' => 'Choosing the Right Motor for Pumps'],
        ['keywords' => ['bearing', 'maintenance', 'bearing type'], 'articleID' => 11, 'title' => 'Bearing Types in Electric Motors'],
        ['keywords' => ['energy saving', 'upgrade motor', 'save energy'], 'articleID' => 12, 'title' => 'Energy Savings by Upgrading Motors'],
        ['keywords' => ['insulation', 'class f', 'class h', 'class b'], 'articleID' => 13, 'title' => 'Insulation Classes Explained'],
        ['keywords' => ['duty cycle', 's1', 's2', 's9'], 'articleID' => 14, 'title' => 'Understanding Motor Duty Cycles'],
        ['keywords' => ['synchronous', 'induction', 'motor type', 'motor difference'], 'articleID' => 15, 'title' => 'Synchronous vs Induction Motors'],
        ['keywords' => ['mounting', 'b3', 'b5', 'b14', 'v1', 'foot mount', 'flange'], 'articleID' => 16, 'title' => 'Motor Mounting Types Explained'],
    ];
}

// ==========================================
// LAYER 1: Direct Model Name Lookup
// ==========================================
function matchByModelName($text, $DB)
{
    // Extract model-like patterns — must contain at least one digit mixed with letters
    // Examples: DMB10D, CMB05NV, SWJ100A, 4W14BU, 3W10AK1A, AQUAGOLD150
    // Also match known brand prefixes: AQUAGOLD, FLOMAX, GLIDE, CFMSM
    preg_match_all('/\b([A-Z]{1,6}\d+[A-Z0-9\-]*|[0-9]+[A-Z][A-Z0-9\-]{1,15}|(?:AQUAGOLD|FLOMAX|GLIDE|CFMSM|CHAMP|STAR|DURA)[A-Z0-9\-]*)\b/i', $text, $matches);

    if (empty($matches[1])) return null;

    foreach ($matches[1] as $candidate) {
        $candidate = trim($candidate);
        // Skip pure numbers, too short, and common measurement terms
        if (is_numeric($candidate) || strlen($candidate) < 3) continue;
        if (preg_match('/^(hp|kw|lpm|lph|1hp|2hp|3hp|5hp|10hp|15hp|0\.5hp)$/i', $candidate)) continue;

        $searchTerm = '%' . $candidate . '%';

        // Search in pump title
        $DB->vals = array($searchTerm, $searchTerm, 1);
        $DB->types = "ssi";
        $DB->sql = "SELECT p.pumpID, p.pumpTitle, p.seoUri, p.pumpImage, p.categoryPID,
                           pc.categoryTitle, pc.seoUri as catSeoUri
                    FROM " . $DB->pre . "pump p
                    JOIN " . $DB->pre . "pump_category pc ON p.categoryPID = pc.categoryPID
                    WHERE (p.pumpTitle LIKE ? OR p.seoUri LIKE ?) AND p.status = ?
                    LIMIT 3";
        $pumps = $DB->dbRows();

        if (!empty($pumps)) {
            // Get first variant per pump (dedup by pumpID)
            $results = [];
            $seenPumps = [];
            foreach ($pumps as $pump) {
                if (isset($seenPumps[$pump['pumpID']])) continue;
                $seenPumps[$pump['pumpID']] = true;
                $details = getPumpDetails($DB, $pump['pumpID']);
                if (!empty($details)) {
                    $results[] = array_merge($pump, $details[0], ['score' => 100, 'matchType' => 'model']);
                }
            }
            if (!empty($results)) {
                return [
                    'matches' => array_slice($results, 0, 3),
                    'confidence' => 1.0,
                    'matchType' => 'model',
                    'matchedTerm' => $candidate,
                ];
            }
        }

        // Also search in pump_detail categoryref
        $DB->vals = array($searchTerm, 1, 1);
        $DB->types = "sii";
        $DB->sql = "SELECT pd.pumpDID, pd.pumpID, pd.categoryref, pd.powerHp, pd.powerKw,
                           pd.supplyPhaseD, pd.headRange, pd.dischargeRange, pd.mrp, pd.warrenty,
                           p.pumpTitle, p.seoUri, p.pumpImage, p.categoryPID,
                           pc.categoryTitle, pc.seoUri as catSeoUri
                    FROM " . $DB->pre . "pump_detail pd
                    JOIN " . $DB->pre . "pump p ON pd.pumpID = p.pumpID
                    JOIN " . $DB->pre . "pump_category pc ON p.categoryPID = pc.categoryPID
                    WHERE pd.categoryref LIKE ? AND pd.status = ? AND p.status = ?
                    LIMIT 3";
        $results = $DB->dbRows();

        if (!empty($results)) {
            // Deduplicate by pumpID
            $seenPumps2 = [];
            $deduped2 = [];
            foreach ($results as $r) {
                if (isset($seenPumps2[$r['pumpID']])) continue;
                $seenPumps2[$r['pumpID']] = true;
                $r['score'] = 100;
                $r['matchType'] = 'model';
                $deduped2[] = $r;
            }
            return [
                'matches' => $deduped2,
                'confidence' => 1.0,
                'matchType' => 'model',
                'matchedTerm' => $candidate,
            ];
        }
    }

    return null;
}

// ==========================================
// LAYER 2: Use-Case Dictionary Matching
// ==========================================
function matchByUseCase($text, $DB)
{
    $text = strtolower($text);
    $dictionary = getUseCaseDictionary();

    $bestMatch = null;
    $bestScore = 0;

    foreach ($dictionary as $entry) {
        $score = 0;
        foreach ($entry['keywords'] as $kw) {
            if (strpos($text, $kw) !== false) {
                // Weight by keyword length — longer/more specific keywords score higher
                $score += strlen($kw);
            }
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $entry;
        }
    }

    if (!$bestMatch || $bestScore === 0) return null;

    // If priority pump IDs are specified, fetch those specific pumps first
    $priorityIDs = $bestMatch['priorityPumpIDs'] ?? [];
    $results = [];

    if (!empty($priorityIDs)) {
        $pIDs = array_slice($priorityIDs, 0, 5); // Max 5 priority pumps
        $placeholders = implode(',', array_fill(0, count($pIDs), '?'));
        $types = str_repeat('i', count($pIDs));

        $DB->vals = array_merge($pIDs, [1, 1]);
        $DB->types = $types . "ii";
        $DB->sql = "SELECT pd.pumpDID, pd.pumpID, pd.categoryref, pd.powerHp, pd.powerKw,
                           pd.supplyPhaseD, pd.headRange, pd.dischargeRange, pd.mrp, pd.warrenty,
                           p.pumpTitle, p.seoUri, p.pumpImage, p.categoryPID,
                           pc.categoryTitle, pc.seoUri as catSeoUri
                    FROM " . $DB->pre . "pump_detail pd
                    JOIN " . $DB->pre . "pump p ON pd.pumpID = p.pumpID
                    JOIN " . $DB->pre . "pump_category pc ON p.categoryPID = pc.categoryPID
                    WHERE p.pumpID IN ($placeholders) AND pd.status = ? AND p.status = ?";
        $rows = $DB->dbRows();

        // Sort results to match priority order
        $byPumpID = [];
        foreach ($rows as $r) {
            if (!isset($byPumpID[$r['pumpID']])) {
                $byPumpID[$r['pumpID']] = $r;
            }
        }
        foreach ($pIDs as $pid) {
            if (isset($byPumpID[$pid])) {
                $byPumpID[$pid]['score'] = 95;
                $byPumpID[$pid]['matchType'] = 'usecase';
                $results[] = $byPumpID[$pid];
            }
        }
    }

    // If no priority results, fall back to category-based fetch
    if (empty($results)) {
        $catIDs = $bestMatch['categoryIDs'];
        $placeholders = implode(',', array_fill(0, count($catIDs), '?'));
        $types = str_repeat('i', count($catIDs));

        $DB->vals = array_merge($catIDs, [1, 1]);
        $DB->types = $types . "ii";
        $DB->sql = "SELECT pd.pumpDID, pd.pumpID, pd.categoryref, pd.powerHp, pd.powerKw,
                           pd.supplyPhaseD, pd.headRange, pd.dischargeRange, pd.mrp, pd.warrenty,
                           p.pumpTitle, p.seoUri, p.pumpImage, p.categoryPID,
                           pc.categoryTitle, pc.seoUri as catSeoUri
                    FROM " . $DB->pre . "pump_detail pd
                    JOIN " . $DB->pre . "pump p ON pd.pumpID = p.pumpID
                    JOIN " . $DB->pre . "pump_category pc ON p.categoryPID = pc.categoryPID
                    WHERE p.categoryPID IN ($placeholders) AND pd.status = ? AND p.status = ?
                    ORDER BY pd.powerHp ASC, pd.mrp ASC
                    LIMIT 5";
        $rows = $DB->dbRows();

        // Deduplicate by pumpID
        $seen = [];
        foreach ($rows as $r) {
            if (!isset($seen[$r['pumpID']])) {
                $r['score'] = 90;
                $r['matchType'] = 'usecase';
                $results[] = $r;
                $seen[$r['pumpID']] = true;
            }
        }
    }

    if (empty($results)) return null;

    return [
        'matches' => array_slice($results, 0, 3),
        'confidence' => 0.9,
        'matchType' => 'usecase',
        'matchedUseCase' => $bestMatch['label'],
    ];
}

// ==========================================
// LAYER 3: Spec-Based Scoring
// ==========================================

/**
 * Extract pump requirements from natural language text
 */
function parsePumpRequirement($text)
{
    $text = strtolower($text);
    $params = [
        'powerHp' => null,
        'powerKw' => null,
        'headMeters' => null,
        'phase' => null,
        'pumpType' => null,
        'application' => null,
        'confidence' => 0,
        'rawText' => $text,
    ];

    $extracted = 0;

    // Power HP
    if (preg_match('/(\d+\.?\d*)\s*(hp|bhp)/i', $text, $m)) {
        $params['powerHp'] = floatval($m[1]);
        $extracted++;
    }

    // Power kW
    if (preg_match('/(\d+\.?\d*)\s*(kw|kilowatt)/i', $text, $m)) {
        $params['powerKw'] = floatval($m[1]);
        $extracted++;
    }

    // Head/Depth in feet → convert to meters
    if (preg_match('/(\d+\.?\d*)\s*(ft|feet|foot)/i', $text, $m)) {
        $params['headMeters'] = round(floatval($m[1]) * 0.3048, 1);
        $extracted++;
    }

    // Head in meters
    if (!$params['headMeters'] && preg_match('/(\d+\.?\d*)\s*(m|meter|metres|meters)/i', $text, $m)) {
        $params['headMeters'] = floatval($m[1]);
        $extracted++;
    }

    // Phase
    if (preg_match('/single\s*phase|1\s*phase|1ph|single|1-phase/i', $text)) {
        $params['phase'] = 1;
        $extracted++;
    } elseif (preg_match('/three\s*phase|3\s*phase|3ph|three|3-phase/i', $text)) {
        $params['phase'] = 3;
        $extracted++;
    }

    // Pump type keywords
    $typeMap = [
        'borewell' => ['borewell', 'bore', 'submersible', 'boring', 'tubewell'],
        'booster' => ['booster', 'pressure boost'],
        'openwell' => ['openwell', 'open well', 'kuwa'],
        'mini' => ['mini', 'small', 'domestic', 'home'],
        'centrifugal' => ['monoblock', 'centrifugal', 'monoset'],
        'agricultural' => ['agriculture', 'farm', 'irrigation', 'khet'],
        'shallow' => ['shallow', 'self priming'],
        'dmb' => ['dmb', 'cmb', 'bmc', 'tank filling', 'line pulling'],
    ];

    foreach ($typeMap as $type => $keywords) {
        foreach ($keywords as $kw) {
            if (strpos($text, $kw) !== false) {
                $params['pumpType'] = $type;
                $extracted++;
                break 2;
            }
        }
    }

    // Application
    $appMap = [
        'residential' => ['home', 'house', 'flat', 'domestic', 'ghar', 'apartment', 'society'],
        'agricultural' => ['farm', 'agriculture', 'khet', 'kheti', 'sinchai', 'field'],
        'industrial' => ['factory', 'industrial', 'plant', 'commercial'],
    ];

    foreach ($appMap as $app => $keywords) {
        foreach ($keywords as $kw) {
            if (strpos($text, $kw) !== false) {
                $params['application'] = $app;
                break 2;
            }
        }
    }

    $params['confidence'] = min(1.0, $extracted * 0.25);
    return $params;
}

/**
 * Find matching pumps using weighted scoring
 */
function findMatchingPumps($params, $DB)
{
    // Map pump type to categories
    $typeToCat = [
        'borewell' => [27, 28],
        'booster' => [30],
        'openwell' => [29],
        'mini' => [24],
        'centrifugal' => [4],
        'agricultural' => [32, 4],
        'shallow' => [26],
        'dmb' => [25],
    ];

    // Get all active pump details
    $DB->vals = array(1, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT pd.pumpDID, pd.pumpID, pd.categoryref, pd.powerHp, pd.powerKw,
                       pd.supplyPhaseD, pd.headRange, pd.dischargeRange, pd.mrp, pd.warrenty,
                       p.pumpTitle, p.seoUri, p.pumpImage, p.categoryPID,
                       pc.categoryTitle, pc.seoUri as catSeoUri
                FROM " . $DB->pre . "pump_detail pd
                JOIN " . $DB->pre . "pump p ON pd.pumpID = p.pumpID
                JOIN " . $DB->pre . "pump_category pc ON p.categoryPID = pc.categoryPID
                WHERE pd.status = ? AND p.status = ?";
    $allPumps = $DB->dbRows();

    $scored = [];

    foreach ($allPumps as $pump) {
        $score = 0;

        // Category score (30 pts)
        if ($params['pumpType'] && isset($typeToCat[$params['pumpType']])) {
            $targetCats = $typeToCat[$params['pumpType']];
            if (in_array($pump['categoryPID'], $targetCats)) {
                $score += 30;
            }
        } else {
            $score += 15; // No type specified, neutral
        }

        // Power score (25 pts)
        $pumpHp = floatval($pump['powerHp']);
        if ($params['powerHp'] && $pumpHp > 0) {
            $diff = abs($pumpHp - $params['powerHp']) / max($params['powerHp'], 0.1);
            if ($diff <= 0.3) {
                $score += round(25 - ($diff * 25));
            }
        } elseif ($params['powerKw'] && floatval($pump['powerKw']) > 0) {
            $diff = abs(floatval($pump['powerKw']) - $params['powerKw']) / max($params['powerKw'], 0.1);
            if ($diff <= 0.3) {
                $score += round(25 - ($diff * 25));
            }
        } else {
            $score += 15; // No power specified, neutral
        }

        // Head score (25 pts)
        $pumpHead = floatval($pump['headRange']);
        if ($params['headMeters'] && $pumpHead > 0) {
            if ($pumpHead >= $params['headMeters']) {
                $overshoot = ($pumpHead - $params['headMeters']) / max($params['headMeters'], 1);
                if ($overshoot <= 0.2) {
                    $score += 25;
                } elseif ($overshoot <= 0.5) {
                    $score += 15;
                } else {
                    $score += 5;
                }
            } else {
                // Head too low — eliminate
                continue;
            }
        } else {
            $score += 15; // No head specified, neutral
        }

        // Phase score (15 pts)
        if ($params['phase']) {
            if (intval($pump['supplyPhaseD']) === $params['phase']) {
                $score += 15;
            } else {
                // Phase mismatch — eliminate
                continue;
            }
        } else {
            $score += 10; // No phase specified, neutral
        }

        $pump['score'] = $score;
        $pump['matchType'] = 'spec';
        $scored[] = $pump;
    }

    // Sort by score descending
    usort($scored, function ($a, $b) { return $b['score'] - $a['score']; });

    // Deduplicate by pumpID (keep highest scoring variant per pump)
    $seen = [];
    $deduped = [];
    foreach ($scored as $s) {
        if (!isset($seen[$s['pumpID']])) {
            $deduped[] = $s;
            $seen[$s['pumpID']] = true;
        }
    }

    $top = array_slice($deduped, 0, 3);

    if (empty($top)) return null;

    return [
        'matches' => $top,
        'confidence' => min(0.85, $params['confidence'] + 0.3),
        'matchType' => 'spec',
        'parsedParams' => $params,
    ];
}

// ==========================================
// MASTER MATCHING FUNCTION
// ==========================================
function matchPump($text, $DB)
{
    // Layer 1: Direct model name lookup (highest priority)
    $result = matchByModelName($text, $DB);
    if ($result) return $result;

    // Layer 2: Use-case dictionary
    $result = matchByUseCase($text, $DB);
    if ($result) return $result;

    // Layer 3: Spec extraction + scoring
    $params = parsePumpRequirement($text);
    if ($params['confidence'] >= 0.25) {
        $result = findMatchingPumps($params, $DB);
        if ($result) return $result;
    }

    // No confident match
    return null;
}

// ==========================================
// HELPER: Get pump detail rows for a pump
// ==========================================
function getPumpDetails($DB, $pumpID)
{
    $DB->vals = array($pumpID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT pumpDID, categoryref, powerHp, powerKw, supplyPhaseD,
                       headRange, dischargeRange, mrp, warrenty
                FROM " . $DB->pre . "pump_detail
                WHERE pumpID = ? AND status = ?
                ORDER BY powerHp ASC
                LIMIT 3";
    return $DB->dbRows();
}

// ==========================================
// FORMAT: Build WhatsApp message for a pump result
// ==========================================
function formatPumpResult($pump)
{
    $phase = (intval($pump['supplyPhaseD'] ?? 0) === 3) ? 'Three Phase' : 'Single Phase';
    $hp = $pump['powerHp'] ?? '';
    $kw = $pump['powerKw'] ?? '';
    $head = $pump['headRange'] ?? '';
    $discharge = $pump['dischargeRange'] ?? '';
    $mrp = $pump['mrp'] ?? '';
    $warranty = $pump['warrenty'] ?? '';
    $catSeoUri = $pump['catSeoUri'] ?? '';
    $pumpSeoUri = $pump['seoUri'] ?? '';

    $powerStr = '';
    if ($hp) $powerStr = "{$hp} HP";
    if ($hp && $kw) $powerStr .= " / {$kw} kW";
    elseif ($kw) $powerStr = "{$kw} kW";

    $caption = "*{$pump['pumpTitle']}*\n";
    if ($powerStr) $caption .= "Power: {$powerStr}\n";
    if ($head) {
        $headFt = round($head / 0.3048);
        $caption .= "Head: {$head}m (~{$headFt}ft)\n";
    }
    $caption .= "Supply: {$phase}\n";
    if ($discharge) $caption .= "Discharge: {$discharge} LPM\n";
    if ($mrp) $caption .= "MRP: {$mrp}\n";
    if ($warranty) $caption .= "Warranty: {$warranty}\n";
    if ($catSeoUri && $pumpSeoUri) {
        $caption .= "View: bombayengg.net/{$catSeoUri}/{$pumpSeoUri}/";
    }

    $imageUrl = '';
    if (!empty($pump['pumpImage'])) {
        // Use PNG version for WhatsApp (Cloud API rejects webp with error 131053)
        $imgFile = $pump['pumpImage'];
        $pngFile = preg_replace('/\.webp$/i', '.png', $imgFile);
        $basePath = (defined('ROOTPATH') ? ROOTPATH : $_SERVER['DOCUMENT_ROOT']) . '/uploads/pump/530_530_crop_100/';
        if ($pngFile !== $imgFile && file_exists($basePath . $pngFile)) {
            $imageUrl = WA_SITE_URL . '/uploads/pump/530_530_crop_100/' . $pngFile;
        } else {
            $imageUrl = WA_SITE_URL . '/uploads/pump/530_530_crop_100/' . $imgFile;
        }
    }

    return [
        'imageUrl' => $imageUrl,
        'caption' => $caption,
        'pumpTitle' => $pump['pumpTitle'],
        'pumpID' => $pump['pumpID'],
        'pumpDID' => $pump['pumpDID'] ?? null,
    ];
}

/**
 * Check if text matches a knowledge base article
 */
function matchKnowledgeBase($text)
{
    $text = strtolower($text);
    $mapping = getKnowledgeBaseMapping();

    foreach ($mapping as $entry) {
        foreach ($entry['keywords'] as $kw) {
            if (strpos($text, $kw) !== false) {
                return $entry;
            }
        }
    }

    return null;
}

/**
 * Generate reference number for inquiry
 */
function generateReferenceNumber($DB)
{
    $year = date('Y');
    $DB->vals = array($year . '%');
    $DB->types = "s";
    $DB->sql = "SELECT COUNT(*) as cnt FROM " . $DB->pre . "wa_inquiry WHERE referenceNumber LIKE ?";
    $row = $DB->dbRow();
    $seq = ($row['cnt'] ?? 0) + 1;
    return sprintf('WA-%s-%04d', $year, $seq);
}
