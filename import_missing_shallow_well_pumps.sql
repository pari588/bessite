-- Import missing Crompton Shallow Well Pumps (4 products)
-- Category: Shallow Well Pumps (categoryPID: 26)

-- 1. SWJ100AP-36 PLUS - 1 HP Shallow Well Pump
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    26,
    'SWJ100AP-36 PLUS',
    'swj100ap-36-plus',
    'swj100ap-36-plus.webp',
    'SWJ100AP-36 PLUS High Suction Shallow Well Pump 1 HP. Advanced model with high suction capability up to 8 metres. Suitable for residential applications and irrigation systems. Voltage range 180V-260V. Energy-efficient operation with reliable performance.',
    '1 HP',
    '1-Phase',
    'Shallow Well',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'SWJ100AP-36 PLUS', 0.75, 1, 1, '12,025/-', '12 months', 1);

-- 2. SWJ100A-36 PLUS - 1 HP Shallow Well Pump
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    26,
    'SWJ100A-36 PLUS',
    'swj100a-36-plus',
    'swj100a-36-plus.webp',
    'SWJ100A-36 PLUS High Suction Shallow Well Pump 1 HP. Premium shallow well pump with extended suction lift up to 8 metres. Designed for residential water supply and irrigation. Operating voltage 180V-260V. Efficient and durable construction.',
    '1 HP',
    '1-Phase',
    'Shallow Well',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'SWJ100A-36 PLUS', 0.75, 1, 1, '13,125/-', '12 months', 1);

-- 3. SWJ50AP-30 PLUS - 0.5 HP Shallow Well Pump
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    26,
    'SWJ50AP-30 PLUS',
    'swj50ap-30-plus',
    'swj50ap-30-plus.webp',
    'SWJ50AP-30 PLUS High Suction Shallow Well Pump 0.5 HP. Compact and energy-efficient model for small residential applications. High suction capability up to 8 metres. Voltage range 180V-260V. Perfect for domestic water supply and small irrigation systems.',
    '0.5 HP',
    '1-Phase',
    'Shallow Well',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'SWJ50AP-30 PLUS', 0.37, 0.5, 1, '10,225/-', '12 months', 1);

-- 4. SWJ50A-30 PLUS - 0.5 HP Shallow Well Pump
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
VALUES (
    26,
    'SWJ50A-30 PLUS',
    'swj50a-30-plus',
    'swj50a-30-plus.webp',
    'SWJ50A-30 PLUS High Suction Shallow Well Pump 0.5 HP. Advanced shallow well pump offering excellent suction lift up to 8 metres. Ideal for residential water needs and small agricultural applications. Wide voltage compatibility 180V-260V. Reliable and cost-effective.',
    '0.5 HP',
    '1-Phase',
    'Shallow Well',
    1
);

SET @pump_id = LAST_INSERT_ID();
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, mrp, warrenty, status)
VALUES (@pump_id, 'SWJ50A-30 PLUS', 0.37, 0.5, 1, '11,050/-', '12 months', 1);
