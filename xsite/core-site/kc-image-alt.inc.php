<?php
/**
 * Content-descriptive ALT text for Knowledge Center article images.
 * Describes what each image actually shows (better image SEO than a generic title).
 * Falls back to the article title + brand for any article not in the map.
 */
if (!function_exists('kcImageAlt')) {
    function kcImageAlt($slug, $title)
    {
        static $map = [
            // --- Original articles (1-16, images regenerated 2026-07) ---
            'use-of-vfd-with-flame-proof-motors-implications-and-mitigation-1' => 'Cast-iron flameproof motor in a petrochemical plant fed by a VFD through armoured conduit',
            'hp-to-kw-conversion-made-easy-a-practical-guide-for-motor-users-1' => 'Technician using a calculator beside an industrial electric motor to convert HP to kW',
            'hazardous-area-motors-a-deep-dive-into-gas-groups-iia-iib' => 'Flameproof Ex-d electric motor installed among pipes in a petrochemical plant',
            'motor-efficiency-classes-ie1-to-ie4-explained-1' => 'Row of industrial electric motors from small to large representing IE1 to IE4 efficiency classes',
            'how-to-choose-the-best-crompton-pump-for-your-home-0-5-1-hp-1' => 'Compact domestic monoblock water pump installed beside a residential water storage tank',
            'motor-cooling-methods-ic-codes-explained-1' => 'Electric motor with fan cowl removed exposing the cooling fan and cooling fins',
            'energy-savings-by-upgrading-to-ie3ie4-motors' => 'Premium efficiency electric motor connected to a digital power meter on a test bench',
            'understanding-motor-duty-cycles-s1-s9' => 'Electric motor running on an instrumented dynamometer test bench',
            'synchronous-vs-induction-motors-key-differences-explained' => 'Synchronous motor with slip rings beside a squirrel cage induction motor in a workshop',
            'motor-mounting-types-explained-b3-b5-b14-v1-etc' => 'Foot-mounted and flange-mounted electric motors side by side showing mounting types',
            // --- First batch (17-31) ---
            'starting-methods-comparison' => 'DOL, star-delta, soft starter and VFD motor starters compared in an industrial control panel',
            'npsh-net-positive-suction-head' => 'Cutaway of a centrifugal pump impeller showing cavitation vapour bubbles forming at the impeller eye',
            'pump-cavitation-causes-prevention' => 'Close-up of a centrifugal pump impeller damaged by cavitation pitting and erosion',
            'pump-head-calculation-guide' => 'Diagram of a pump lifting water to an overhead tank with head measurement markings',
            'single-phase-vs-three-phase-motors' => 'Single-phase electric motor with capacitor beside a larger three-phase industrial motor',
            'motor-protection-devices-mpcb-overload-relay' => 'MPCB, contactor and thermal overload relay mounted on a DIN rail for motor protection',
            'nema-vs-iec-motor-standards' => 'NEMA frame motor and IEC frame motor shown side by side for standards comparison',
            'solar-pump-systems-india' => 'Solar panels powering a submersible borewell pump irrigating crops in an Indian field',
            'power-factor-in-motors' => 'Industrial electric motor connected to a power-factor-correction capacitor bank',
            'motor-rewinding-vs-replacement' => 'Electric motor stator being rewound with copper windings beside a new replacement motor',
            'pump-maintenance-checklist' => 'Technician inspecting a centrifugal pump with a maintenance checklist',
            'borewell-pump-installation-guide' => 'Workers lowering a submersible pump into a borewell casing during installation',
            'agricultural-irrigation-pumps' => 'Water pump feeding irrigation channels through an Indian crop field',
            'motor-vibration-analysis' => 'Engineer measuring motor bearing vibration with a handheld vibration analyzer',
            'atex-iecex-peso-certifications' => 'Flameproof Ex-rated hazardous area electric motor installed in a petrochemical plant',
            // --- Second batch (32-64) ---
            'crompton-vs-kirloskar-pumps' => 'Two domestic centrifugal water pumps compared side by side',
            'best-water-pump-for-multistorey-building' => 'Cutaway of a multi-storey building showing a pump supplying water to a rooftop overhead tank',
            'submersible-vs-monoblock-pump' => 'Slim stainless steel submersible borewell pump beside a compact surface monoblock pump',
            'borewell-pump-cost-india-guide' => 'Stainless steel 4-inch borewell submersible pump shown with a measuring tape',
            'water-pump-not-pumping-water-causes' => 'Hand checking the priming plug on a domestic water pump that runs but is not pumping',
            'motor-tripping-mcb-causes-solutions' => 'Electrical distribution board with one MCB circuit breaker tripped to the off position',
            'submersible-pump-runs-but-no-water' => 'Submersible borewell pump lifted from its casing pipe for inspection',
            'motor-overheating-causes-diagnosis' => 'Technician checking an electric motor for overheating with an infrared thermometer',
            'low-water-pressure-house-solutions' => 'Weak versus strong shower flow solved with a pressure booster pump',
            'calculate-water-tank-pump-capacity' => 'Rooftop overhead water tank fed by a pump, illustrating tank capacity sizing',
            'pump-selection-farmhouse-weekend-home' => 'Farmhouse borewell pump and overhead tank supplying a garden',
            'vfd-energy-savings-calculator-payback' => 'Variable frequency drive connected to an industrial motor for energy savings',
            'pm-kusum-solar-pump-subsidy-guide' => 'Solar panels powering an agricultural irrigation pump under the PM-KUSUM scheme',
            'monsoon-motor-pump-care' => 'Electric motor and pump sheltered from monsoon rain under a canopy',
            'bee-star-rating-pumps-explained' => 'Water pump displayed with a five-star BEE energy efficiency rating label',
            'crompton-cg-abb-siemens-motor-brands-compared' => 'Four industrial electric motors lined up in a row for a brand comparison',
            'how-to-choose-right-motor-hp' => 'Electric motors arranged from small to large by horsepower rating',
            'ie3-vs-ie4-motors-worth-it' => 'IE3 and IE4 premium efficiency electric motors compared side by side',
            'aluminium-vs-cast-iron-motor-body' => 'Aluminium body electric motor beside a cast iron body motor showing the material difference',
            'flameproof-motor-buying-guide-india' => 'Flameproof Ex-d explosion-proof electric motor installed in a chemical plant',
            'motor-hums-but-wont-start' => 'Technician testing a single-phase motor capacitor when the motor hums but will not start',
            'motor-tripping-overload-relay-diagnosis' => 'Motor starter with thermal overload relay and contactor mounted on a DIN rail',
            'motor-drawing-high-current-causes' => 'Clamp meter measuring high current on an industrial electric motor cable',
            'single-phasing-motor-killer' => 'Three-phase motor terminal box with one phase cable disconnected, illustrating single phasing',
            'diagnose-motor-problems-by-sound' => 'Technician using a stethoscope to diagnose an electric motor fault by sound',
            'why-motor-bearings-fail-early' => 'A new ball bearing beside a worn, pitted bearing showing premature failure',
            'best-motors-textile-industry' => 'Electric drive motors on textile spinning machines in an Indian mill',
            'motor-selection-air-compressors' => 'Belt-driven electric motor on an industrial air compressor',
            'brake-motors-cranes-hoists-guide' => 'Electric brake motor mounted on an overhead crane hoist mechanism',
            'motors-cement-plants-heavy-industry' => 'Large high-voltage electric motor driving a cement plant ball mill',
            'star-vs-delta-motor-wiring' => 'Electric motor terminal box showing star and delta winding connections with brass links',
            'motor-service-factor-explained' => 'Close-up of an electric motor nameplate showing the service factor rating',
            'motor-greasing-guide' => 'Technician greasing an electric motor bearing with a grease gun',
        ];
        $slug = trim($slug, '/');
        if (isset($map[$slug])) {
            return $map[$slug] . ' — Bombay Engineering Syndicate';
        }
        // Fallback for original articles: keyword-rich title + brand
        return $title . ' — Bombay Engineering Syndicate, Mumbai & Ahmedabad';
    }
}
