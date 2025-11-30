-- Import missing 4-inch Borewell Submersible Pumps
-- Category ID: 28 (4-Inch Borewell)

-- 1. 4W12BF1.5E - Water-filled 1.5 HP
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, pumpType, status)
VALUES (
    28,
    '4W12BF1.5E',
    '4w12bf1-5e',
    '4w12bf1-5e.webp',
    '4 Inch Water-filled Borewell Submersible Pump. 1.5 HP capacity with voltage fluctuation handling. Suitable for residential and agricultural applications. Requires routine maintenance every 6-12 months. Perfect for deep borewells with extended head range capacity.',
    '1.5 HP',
    '1-Phase',
    '100',
    '5',
    'Water-filled',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
VALUES (@pump_id, '4W12BF1.5E', 1.1, 1.5, 1, 100, 5, 60, '1000-1200 LPH', '17,700/-', '12 months', 1);

-- 2. 4W14BF1.5E - Water-filled 1.5 HP Extended
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, pumpType, status)
VALUES (
    28,
    '4W14BF1.5E',
    '4w14bf1-5e',
    '4w14bf1-5e.webp',
    '4 Inch Water-filled Borewell Submersible Pump. 1.5 HP capacity with extended head range for deeper borewells. Voltage fluctuation tolerant design. Ideal for challenging borewell conditions. Eco-friendly water-filled construction.',
    '1.5 HP',
    '1-Phase',
    '100',
    '7',
    'Water-filled',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
VALUES (@pump_id, '4W14BF1.5E', 1.1, 1.5, 1, 100, 7, 85, '900-1100 LPH', '19,750/-', '12 months', 1);

-- 3. 4VO1/7-BUE(U4S) - Oil-filled 1 HP
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, pumpType, status)
VALUES (
    28,
    '4VO1/7-BUE(U4S)',
    '4vo1-7-bue-u4s',
    '4vo1-7-bue-u4s.webp',
    '4 Inch Oil-filled Borewell Submersible Pump. 1 HP capacity with superior longevity. Oil-filled design provides excellent voltage fluctuation handling and extended operational life. Black and silver finish. Compact 7-inch housing for various borewell configurations.',
    '1 HP',
    '1-Phase',
    '100',
    '4',
    'Oil-filled',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
VALUES (@pump_id, '4VO1/7-BUE(U4S)', 0.75, 1, 1, 100, 4, 50, '800-1000 LPH', '12,850/-', '12 months', 1);

-- 4. 4VO1/10-BUE(U4S) - Oil-filled 1 HP Extended
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, pumpType, status)
VALUES (
    28,
    '4VO1/10-BUE(U4S)',
    '4vo1-10-bue-u4s',
    '4vo1-10-bue-u4s.webp',
    '4 Inch Oil-filled Borewell Submersible Pump. 1 HP capacity with extended head range. Robust oil-filled construction for improved durability and performance in challenging conditions. Superior voltage fluctuation tolerance. Long operational life.',
    '1 HP',
    '1-Phase',
    '100',
    '5',
    'Oil-filled',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
VALUES (@pump_id, '4VO1/10-BUE(U4S)', 0.75, 1, 1, 100, 5, 65, '700-900 LPH', '13,650/-', '12 months', 1);

-- 5. 4VO7BU1EU - Oil-filled 1 HP Compact
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, pumpType, status)
VALUES (
    28,
    '4VO7BU1EU',
    '4vo7bu1eu',
    '4vo7bu1eu.webp',
    '4 Inch Oil-filled Borewell Submersible Pump. 1 HP variant with compact 7-inch housing. Oil-filled design ensures excellent voltage fluctuation tolerance and long operational life. Ideal for residential and small agricultural applications.',
    '1 HP',
    '1-Phase',
    '100',
    '4',
    'Oil-filled',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
VALUES (@pump_id, '4VO7BU1EU', 0.75, 1, 1, 100, 4, 50, '800-1000 LPH', '12,850/-', '12 months', 1);

-- 6. 4VO10BU1EU - Oil-filled 1 HP
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, pumpType, status)
VALUES (
    28,
    '4VO10BU1EU',
    '4vo10bu1eu',
    '4vo10bu1eu.webp',
    '4 Inch Oil-filled Borewell Submersible Pump. 1 HP with 10-inch configuration for deeper borewells. Premium oil-filled construction for superior durability in residential and agricultural applications. Excellent voltage fluctuation handling.',
    '1 HP',
    '1-Phase',
    '100',
    '5',
    'Oil-filled',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
VALUES (@pump_id, '4VO10BU1EU', 0.75, 1, 1, 100, 5, 65, '700-900 LPH', '13,650/-', '12 months', 1);

-- 7. 4VO1.5/12-BUE(U4S) - Oil-filled 1.5 HP
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, pumpType, status)
VALUES (
    28,
    '4VO1.5/12-BUE(U4S)',
    '4vo1-5-12-bue-u4s',
    '4vo1-5-12-bue-u4s.webp',
    '4 Inch Oil-filled Borewell Submersible Pump. 1.5 HP capacity with 12-inch configuration. Excellent for deeper borewells with consistent voltage fluctuation handling. Oil-filled design ensures superior durability and extended life. Premium construction.',
    '1.5 HP',
    '1-Phase',
    '100',
    '6',
    'Oil-filled',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
VALUES (@pump_id, '4VO1.5/12-BUE(U4S)', 1.1, 1.5, 1, 100, 6, 75, '1000-1200 LPH', '16,450/-', '12 months', 1);

-- 8. 4VO1.5/14-BUE(U4S) - Oil-filled 1.5 HP Extended
INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, pumpType, status)
VALUES (
    28,
    '4VO1.5/14-BUE(U4S)',
    '4vo1-5-14-bue-u4s',
    '4vo1-5-14-bue-u4s.webp',
    '4 Inch Oil-filled Borewell Submersible Pump. 1.5 HP with 14-inch configuration for extended head range. Ideal for challenging borewell conditions with superior durability. Premium oil-filled construction with excellent voltage fluctuation tolerance.',
    '1.5 HP',
    '1-Phase',
    '100',
    '7',
    'Oil-filled',
    1
);

SET @pump_id = LAST_INSERT_ID();

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
VALUES (@pump_id, '4VO1.5/14-BUE(U4S)', 1.1, 1.5, 1, 100, 7, 90, '900-1100 LPH', '17,200/-', '12 months', 1);
