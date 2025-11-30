-- Import missing Pressure Booster Pumps
-- Category ID: 30 (Booster Pumps)

-- 1. CFMSMB3D0.50-V24 - Pressure Booster Pump 0.5 HP
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    30,
    'CFMSMB3D0.50-V24',
    'cfmsmb3d0-50-v24',
    'cfmsmb3d0-50-v24.webp',
    'Compact Pressure Booster Pump with 0.5 HP capacity. Features energy-efficient design with dry run protection. Operates at less than 60dB for noiseless operation. Ideal for residential applications requiring consistent water pressure. Blue and black finish with compact design.',
    '0.5 HP',
    '1-Phase',
    'Pressure Booster',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'CFMSMB3D0.50-V24', 0.37, 0.5, 1, '26,075/-', '12 months', 1);

-- 2. MINI FORCE II - Pressure Booster Pump 0.5 HP
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    30,
    'MINI FORCE II',
    'mini-force-ii',
    'mini-force-ii.webp',
    'Compact Mini Force II Pressure Booster Pump with 0.5 HP capacity. Energy-efficient design with centrifugal pump technology. Dry run protection and noiseless operation (less than 60dB). Perfect for small residential installations. Blue and black finish.',
    '0.5 HP',
    '1-Phase',
    'Pressure Booster',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'MINI FORCE II', 0.37, 0.5, 1, '13,225/-', '12 months', 1);
