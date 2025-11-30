-- Import Crompton Agricultural Pumps
-- Categories: Borewell (3), Centrifugal (4), Open Well (5)

-- BOREWELL CATEGORY (categoryPID: 3)
-- 1. 100W25RA5TP-50 - 100W Submersible
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    3,
    '100W25RA5TP-50',
    '100w25ra5tp-50',
    '100w25ra5tp-50.webp',
    '100W Submersible Agricultural Pump (25RA5TP-50). Deep borewell rated submersible pump designed for agricultural irrigation and water extraction. High performance with low energy consumption. IP55 protection for dust and water resistance. Suitable for deep water extraction applications.',
    '100W',
    '1-Phase',
    'Submersible',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, '100W25RA5TP-50', 0.1, 0.13, 1, '48,750/-', '12 months', 1);

-- 2. 100W15RA3TP-50 - 100W Submersible
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    3,
    '100W15RA3TP-50',
    '100w15ra3tp-50',
    '100w15ra3tp-50.webp',
    '100W Submersible Agricultural Pump (15RA3TP-50). Borewell extraction submersible designed for agricultural use. Energy-efficient operation with IP55 rated protection for outdoor durability. Reliable performance for irrigation and water supply applications.',
    '100W',
    '1-Phase',
    'Submersible',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, '100W15RA3TP-50', 0.1, 0.13, 1, '37,725/-', '12 months', 1);

-- 3. 100W12RA3TP-50 - 100W Submersible
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    3,
    '100W12RA3TP-50',
    '100w12ra3tp-50',
    '100w12ra3tp-50.webp',
    '100W Submersible Agricultural Pump (12RA3TP-50). Compact submersible design for agricultural irrigation. Deep borewell rated with high performance characteristics. Energy-efficient operation suitable for small to medium farms.',
    '100W',
    '1-Phase',
    'Submersible',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, '100W12RA3TP-50', 0.1, 0.13, 1, '35,525/-', '12 months', 1);

-- 4. MIN32-26 - 3HP Submersible
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    3,
    'MIN32-26',
    'min32-26',
    'min32-26.webp',
    '3HP Submersible Agricultural Pump (MIN32-26). Medium capacity submersible pump designed for agricultural irrigation. Borewell extraction capability with reliable performance. Suitable for medium-sized farm operations.',
    '3 HP',
    '3-Phase',
    'Submersible',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MIN32-26', 2.2, 3, 3, '33,375/-', '12 months', 1);

-- 5. MIK32-27 - 3HP Submersible
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    3,
    'MIK32-27',
    'mik32-27',
    'mik32-27.webp',
    '3HP Submersible Agricultural Pump (MIK32-27). Professional grade submersible for borewell extraction. Designed for agricultural and irrigation applications. Robust construction for long-term reliability.',
    '3 HP',
    '3-Phase',
    'Submersible',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MIK32-27', 2.2, 3, 3, '33,375/-', '12 months', 1);

-- 6. MIP52-27 - 5HP Submersible
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    3,
    'MIP52-27',
    'mip52-27',
    'mip52-27.webp',
    '5HP Submersible Agricultural Pump (MIP52-27). High-capacity submersible for agricultural duty. Designed for reliable irrigation and water extraction. IP55 protected with energy-efficient operation.',
    '5 HP',
    '3-Phase',
    'Submersible',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MIP52-27', 3.7, 5, 3, '43,775/-', '12 months', 1);

-- 7. MINH52-30 - 5HP Submersible
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    3,
    'MINH52-30',
    'minh52-30',
    'minh52-30.webp',
    '5HP Submersible Agricultural Pump (MINH52-30). Irrigation-focused submersible designed for deep water extraction. High performance and energy efficiency for large agricultural operations.',
    '5 HP',
    '3-Phase',
    'Submersible',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MINH52-30', 3.7, 5, 3, '44,100/-', '12 months', 1);

-- 8. MIP7.52-30 - 7.5HP Submersible
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    3,
    'MIP7.52-30',
    'mip7-52-30',
    'mip7-52-30.webp',
    '7.5HP Submersible Agricultural Pump (MIP7.52-30). High-capacity deep water extraction pump for large-scale agricultural operations. Professional-grade performance with energy-efficient design.',
    '7.5 HP',
    '3-Phase',
    'Submersible',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MIP7.52-30', 5.5, 7.5, 3, '58,300/-', '12 months', 1);

-- CENTRIFUGAL CATEGORY (categoryPID: 4)
-- 9. MBG1.52 - 1HP Centrifugal
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    4,
    'MBG1.52',
    'mbg1-52',
    'mbg1-52.webp',
    '1HP Centrifugal Agricultural Pump (MBG1.52). Entry-level centrifugal pump for agricultural irrigation. Reliable performance for small farm operations. Single-phase design for easy installation.',
    '1 HP',
    '1-Phase',
    'Centrifugal',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MBG1.52', 0.75, 1, 1, '18,750/-', '12 months', 1);

-- 10. MBG12(3PHASE) - 1HP Centrifugal 3-Phase
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    4,
    'MBG12(3PHASE)',
    'mbg12-3phase',
    'mbg12-3phase.webp',
    '1HP Three-Phase Centrifugal Agricultural Pump (MBG12). Professional centrifugal pump for agricultural applications. Three-phase motor design for industrial/commercial farm operations.',
    '1 HP',
    '3-Phase',
    'Centrifugal',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MBG12(3PHASE)', 0.75, 1, 3, '15,375/-', '12 months', 1);

-- OPEN WELL CATEGORY (categoryPID: 5)
-- 11. MBQ22-1PH-14 - 1HP Centrifugal Open Well
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    5,
    'MBQ22-1PH-14',
    'mbq22-1ph-14',
    'mbq22-1ph-14.webp',
    '1HP Centrifugal Open Well Pump (MBQ22-1PH-14). Single-phase centrifugal pump designed for open well applications. Reliable water extraction for irrigation and agricultural use. Proven performance in field conditions.',
    '1 HP',
    '1-Phase',
    'Centrifugal',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MBQ22-1PH-14', 0.75, 1, 1, '21,550/-', '12 months', 1);

-- 12. MAD052(1PH)Y-14 - 0.5HP Domestic/Agricultural
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    5,
    'MAD052(1PH)Y-14',
    'mad052-1ph-y-14',
    'mad052-1ph-y-14.webp',
    '0.5HP Single-Phase Domestic/Agricultural Pump (MAD052). Compact, efficient pump for small farm use and domestic water supply. Entry-level solution for water extraction from open wells.',
    '0.5 HP',
    '1-Phase',
    'Domestic/Agricultural',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MAD052(1PH)Y-14', 0.37, 0.5, 1, '7,325/-', '12 months', 1);
