-- Add Missing Shallow Well Pump Products
-- Source: Crompton.co.in catalog
-- Date: 2025-11-06
-- Total to add: 4 products

-- ============================================================
-- 1. SWJ100AP-36 PLUS (pumpID: 37)
-- ============================================================
INSERT INTO mx_pump 
(pumpTitle, categoryPID, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status)
VALUES 
('SWJ100AP-36 PLUS', 26, 'swj100ap-36-plus.webp', 
'Shallow well jet pump without tank. 1.0 HP (0.75 kW) single phase pump designed for shallow water sources. Features efficient jet pump design with 8-meter suction capability. Suitable for residential applications with adequate suction head. Manual priming required.',
'1.0', '1PH', '-', '-', '-', '-', 'Shallow Well Jet', 'swj100ap-36-plus', 1);

-- Detailed specifications for SWJ100AP-36 PLUS
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
SELECT pumpID, 'SWJ100AP-36 PLUS', 0.75, 1.0, 1, 0, 0, 9.0, '1500-2000 LPM', '15000', '1 Year'
FROM mx_pump WHERE pumpTitle = 'SWJ100AP-36 PLUS';

-- ============================================================
-- 2. SWJ100A-36 PLUS (pumpID: 38)
-- ============================================================
INSERT INTO mx_pump 
(pumpTitle, categoryPID, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status)
VALUES 
('SWJ100A-36 PLUS', 26, 'swj100a-36-plus.webp',
'Shallow well jet pump for residential use. 1.0 HP (0.75 kW) single phase with efficient design. Delivers reliable performance for shallow water extraction. Features standard jet pump configuration with 8-meter suction range. Ideal for small to medium installations.',
'1.0', '1PH', '-', '-', '-', '-', 'Shallow Well Jet', 'swj100a-36-plus', 1);

-- Detailed specifications for SWJ100A-36 PLUS
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
SELECT pumpID, 'SWJ100A-36 PLUS', 0.75, 1.0, 1, 0, 0, 9.0, '1500-2000 LPM', '14000', '1 Year'
FROM mx_pump WHERE pumpTitle = 'SWJ100A-36 PLUS';

-- ============================================================
-- 3. SWJ50AP-30 PLUS (pumpID: 39)
-- ============================================================
INSERT INTO mx_pump 
(pumpTitle, categoryPID, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status)
VALUES 
('SWJ50AP-30 PLUS', 26, 'swj50ap-30-plus.webp',
'Shallow well pump without tank, 0.5 HP (0.37 kW) single phase. Compact and affordable solution for shallow water sources. Features manual priming with efficient jet pump design. Suitable for small installations requiring moderate flow rates. Easy to install and maintain.',
'0.5', '1PH', '-', '-', '-', '-', 'Shallow Well Jet', 'swj50ap-30-plus', 1);

-- Detailed specifications for SWJ50AP-30 PLUS
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
SELECT pumpID, 'SWJ50AP-30 PLUS', 0.37, 0.5, 1, 0, 0, 8.0, '1000-1200 LPM', '10500', '1 Year'
FROM mx_pump WHERE pumpTitle = 'SWJ50AP-30 PLUS';

-- ============================================================
-- 4. SWJ50A-30 PLUS (pumpID: 40)
-- ============================================================
INSERT INTO mx_pump 
(pumpTitle, categoryPID, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status)
VALUES 
('SWJ50A-30 PLUS', 26, 'swj50a-30-plus.webp',
'Shallow well pump, 0.5 HP (0.37 kW) single phase for residential water supply. Efficient jet pump designed for shallow water extraction with 7-9 meter suction capacity. Reliable performance at affordable price point. Suitable for domestic and small agricultural applications.',
'0.5', '1PH', '-', '-', '-', '-', 'Shallow Well Jet', 'swj50a-30-plus', 1);

-- Detailed specifications for SWJ50A-30 PLUS
INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
SELECT pumpID, 'SWJ50A-30 PLUS', 0.37, 0.5, 1, 0, 0, 8.0, '1000-1200 LPM', '9500', '1 Year'
FROM mx_pump WHERE pumpTitle = 'SWJ50A-30 PLUS';

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================
-- Check all shallow well pumps (should be 7 total)
-- SELECT pumpID, pumpTitle, pumpImage, kwhp, supplyPhase FROM mx_pump WHERE categoryPID = 26 ORDER BY pumpID;

-- Check new products details
-- SELECT pumpID, categoryref, powerKw, powerHp, headRange, dischargeRange, mrp FROM mx_pump_detail WHERE pumpID BETWEEN 37 AND 40;
