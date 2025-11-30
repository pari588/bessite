-- Import missing Crompton Agricultural Pumps (8 products)
-- Category: Agricultural Pumps (categoryPID: 32)

-- 1. MIK22-18 - 2.2HP Submersible
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    32,
    'MIK22-18',
    'mik22-18',
    'mik22-18.webp',
    '2.2HP Submersible Agricultural Pump (MIK22-18). Professional-grade pump for agricultural irrigation and water extraction. 18m head range with reliable performance. Designed for medium-scale farm operations.',
    '2.2 HP',
    '3-Phase',
    'Submersible',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MIK22-18', 1.5, 2.2, 3, '25,775/-', '12 months', 1);

-- 2. MBK22(1PH)-24 - 2.2HP Centrifugal
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    32,
    'MBK22(1PH)-24',
    'mbk22-1ph-24',
    'mbk22-1ph-24.webp',
    '2.2HP Single-Phase Centrifugal Agricultural Pump (MBK22). 24m head range suitable for open well and shallow borewell applications. Energy-efficient design for reliable agricultural irrigation.',
    '2.2 HP',
    '1-Phase',
    'Centrifugal',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MBK22(1PH)-24', 1.5, 2.2, 1, '22,200/-', '12 months', 1);

-- 3. MBM12(1PH) - 1.2HP Centrifugal
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    32,
    'MBM12(1PH)',
    'mbm12-1ph',
    'mbm12-1ph.webp',
    '1.2HP Single-Phase Centrifugal Agricultural Pump (MBM12). Compact design for small to medium farm operations. Reliable water extraction for irrigation applications.',
    '1.2 HP',
    '1-Phase',
    'Centrifugal',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MBM12(1PH)', 0.9, 1.2, 1, '15,425/-', '12 months', 1);

-- 4. MBQ22(1PH)-12U - 2.2HP Centrifugal
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    32,
    'MBQ22(1PH)-12U',
    'mbq22-1ph-12u',
    'mbq22-1ph-12u.webp',
    '2.2HP Single-Phase Centrifugal Agricultural Pump (MBQ22). 12m head range designed for open well water extraction. Efficient operation for agricultural and domestic use.',
    '2.2 HP',
    '1-Phase',
    'Centrifugal',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MBQ22(1PH)-12U', 1.5, 2.2, 1, '21,025/-', '12 months', 1);

-- 5. MBG12(1PH)-21 - 1.2HP Centrifugal
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    32,
    'MBG12(1PH)-21',
    'mbg12-1ph-21',
    'mbg12-1ph-21.webp',
    '1.2HP Single-Phase Centrifugal Agricultural Pump (MBG12). 21m head range for deeper well applications. Reliable performance for small farm water extraction and irrigation.',
    '1.2 HP',
    '1-Phase',
    'Centrifugal',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MBG12(1PH)-21', 0.9, 1.2, 1, '15,425/-', '12 months', 1);

-- 6. MAD12(1PH)Y-30 - 1.2HP Domestic/Agricultural
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    32,
    'MAD12(1PH)Y-30',
    'mad12-1ph-y-30',
    'mad12-1ph-y-30.webp',
    '1.2HP Single-Phase Domestic/Agricultural Pump (MAD12). 30m head range for deeper water extraction. Compact, efficient pump for small farm use and domestic water supply.',
    '1.2 HP',
    '1-Phase',
    'Domestic/Agricultural',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MAD12(1PH)Y-30', 0.9, 1.2, 1, '13,875/-', '12 months', 1);

-- 7. MAD052(1PH)Y-21+ - 0.5HP Domestic/Agricultural
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    32,
    'MAD052(1PH)Y-21+',
    'mad052-1ph-y-21-plus',
    'mad052-1ph-y-21-plus.webp',
    '0.5HP Single-Phase Domestic/Agricultural Pump (MAD052). 21m+ head range for shallow to medium depth wells. Economical solution for small farm and domestic water supply applications.',
    '0.5 HP',
    '1-Phase',
    'Domestic/Agricultural',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MAD052(1PH)Y-21+', 0.37, 0.5, 1, '8,300/-', '12 months', 1);

-- 8. MAD052(1PH)Y-18+ - 0.5HP Domestic/Agricultural
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    32,
    'MAD052(1PH)Y-18+',
    'mad052-1ph-y-18-plus',
    'mad052-1ph-y-18-plus.webp',
    '0.5HP Single-Phase Domestic/Agricultural Pump (MAD052). 18m+ head range designed for shallow well water extraction. Budget-friendly pump for small farm and domestic water needs.',
    '0.5 HP',
    '1-Phase',
    'Domestic/Agricultural',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MAD052(1PH)Y-18+', 0.37, 0.5, 1, '7,625/-', '12 months', 1);
