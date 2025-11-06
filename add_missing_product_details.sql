-- Add Missing Product Details for 12 New Mini Pump Products
-- Source: Crompton.co.in extraction
-- Date: 2025-11-06

-- ============================================================
-- 1. MINI MASTER II (pumpID: 64, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Compact Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, thermal overload protection, IP 55 protection and F-Class insulation. Lift capacity up to 8.0 metres. Perfect for smaller installations and low-flow applications.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 64;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (64, 'MINI MASTER II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '7850', '1 Year');

-- ============================================================
-- 2. CHAMP PLUS II (pumpID: 65, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Affordable Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, thermal overload protection, IP 55 protection and F-Class insulation. Reliable performance for small applications. Ideal for budget-conscious customers.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 65;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (65, 'CHAMP PLUS II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '4650', '1 Year');

-- ============================================================
-- 3. MINI MASTERPLUS II (pumpID: 66, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Premium Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features 40% faster filling capability with excellent performance. IP 55 protection and F-Class insulation. Lift capacity up to 8.0 metres. Advanced electrical stamping and Hy-Flo Max technology.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 66;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (66, 'MINI MASTERPLUS II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '8375', '2 Years');

-- ============================================================
-- 4. MINI MARVEL II (pumpID: 67, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Compact Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, thermal overload protection, IP 55 protection and F-Class insulation. Lift capacity up to 8.0 metres. Lightweight and easy to install for small applications.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 67;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (67, 'MINI MARVEL II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '6375', '1 Year');

-- ============================================================
-- 5. MINI CREST II (pumpID: 68, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Reliable Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, 1-year warranty, thermal overload protection, and lift capacity up to 8.0 metres. IP 55 protection and F-Class insulation. Durable construction for small installations.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 68;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (68, 'MINI CREST II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '4950', '1 Year');

-- ============================================================
-- 6. AQUAGOLD 50-30 (pumpID: 69, 0.5 HP)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Economical Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Affordable AQUAGOLD series pump with reliable performance. Features include IP 55 protection, F-Class insulation, and lift capacity up to 7.0 metres. Ideal for budget-conscious customers.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 69;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (69, 'AQUAGOLD 50-30', 0.375, 0.5, 1, 20, 0, 7.0, '500-600 LPM', '7750', '1 Year');

-- ============================================================
-- 7. AQUAGOLD 100-33 (pumpID: 70, 1.0 HP)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Efficient Mini Self Priming Regenerative Pump 1.0 HP (0.75 kW). AQUAGOLD series pump with reliable self-priming design. Features include IP 55 protection, F-Class insulation, and lift capacity up to 8.0 metres. Good performance at affordable price.',
  kwhp = '1',
  supplyPhase = '1PH',
  deliveryPipe = '25',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 70;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (70, 'AQUAGOLD 100-33', 0.75, 1.0, 1, 25, 0, 8.0, '800-1000 LPM', '10175', '1 Year');

-- ============================================================
-- 8. FLOMAX PLUS II (pumpID: 71, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Compact Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features anti-rust technology, anti-jam winding, wide voltage design. Ideal for small applications. Advanced electrical stamping and Hy-Flo Max technology for reliable performance.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 71;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (71, 'FLOMAX PLUS II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '7925', '1 Year');

-- ============================================================
-- 9. MASTER DURA II (pumpID: 72, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Premium Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). DURA series model with anti-jam winding, thermal overload protection, wide voltage protection, high suction capacity, and drip-proof design. Black & Silver color variant. Advanced construction.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 72;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (72, 'MASTER DURA II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '8350', '1 Year');

-- ============================================================
-- 10. MASTER PLUS II (pumpID: 73, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Compact Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features anti-jam winding technology, drip-proof adaptor, wide voltage design. Reliable self-priming regenerative design for small applications. High-quality construction.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 73;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (73, 'MASTER PLUS II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '8050', '1 Year');

-- ============================================================
-- 11. STAR PLUS II (pumpID: 74, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Reliable Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, thermal overload protection, IP 55 protection and F-Class insulation. Good performance for small applications at affordable price.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 74;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (74, 'STAR PLUS II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '6700', '1 Year');

-- ============================================================
-- 12. CHAMP DURA II (pumpID: 75, 0.5 HP variant)
-- ============================================================
UPDATE mx_pump SET
  pumpFeatures = 'Durable Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). DURA series model with anti-jam winding, thermal overload protection, wide voltage protection, high suction capacity, and drip-proof design. Black & Silver color variant. Advanced construction.',
  kwhp = '0.5',
  supplyPhase = '1PH',
  deliveryPipe = '20',
  noOfStage = 'Regen',
  isi = 'Yes',
  pumpType = 'Mini Self-Priming'
WHERE pumpID = 75;

INSERT INTO mx_pump_detail (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
VALUES (75, 'CHAMP DURA II', 0.375, 0.5, 1, 20, 0, 8.0, '500-600 LPM', '4950', '1 Year');

-- ============================================================
-- VERIFICATION QUERIES (run after execution)
-- ============================================================
-- SELECT pumpID, pumpTitle, kwhp, supplyPhase, deliveryPipe FROM mx_pump WHERE pumpID BETWEEN 64 AND 75;
-- SELECT pumpID, categoryref, powerKw, powerHp, headRange FROM mx_pump_detail WHERE pumpID BETWEEN 64 AND 75;
