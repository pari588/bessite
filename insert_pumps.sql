-- Crompton Residential Pumps Bulk Insert
-- Date: 2025-11-05

-- Ensure category exists
INSERT IGNORE INTO mx_pump_category (categoryTitle, seoUri, parentID, status, addDate) 
VALUES ('Residential Pumps', 'residential-pumps', 0, 1, NOW());

-- Get category ID (assuming it's the last inserted or existing)
SET @cat_id = (SELECT categoryPID FROM mx_pump_category WHERE categoryTitle='Residential Pumps' LIMIT 1);

-- Insert 27 Crompton Residential Pumps
INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate)
VALUES 
('Mini Everest Mini Pump', @cat_id, 'Compact pump for gardening, lawn sprinkling', '1.1kW', 'Single Phase', '25mm x 25mm', '', 'B.I.S. Compliant', '', 'Mini Pump', 'mini-everest-mini-pump', 1, NOW()),
('AQUAGOLD DURA 150', @cat_id, 'Durable aquagold pump for household use', '1.5HP / 1.1kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'aquagold-dura-150', 1, NOW()),
('AQUAGOLD 150', @cat_id, 'Standard aquagold pump', '1.5HP / 1.1kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'aquagold-150', 1, NOW()),
('WIN PLUS I', @cat_id, 'Window pump series', '1.0HP / 0.75kW', 'Single Phase', '25mm x 25mm', '', '', '', 'Mini Pump', 'win-plus-i', 1, NOW()),
('ULTIMO II', @cat_id, 'Entry-level pump', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'ultimo-ii', 1, NOW()),
('ULTIMO I', @cat_id, 'Basic pump model', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'ultimo-i', 1, NOW()),
('STAR PLUS I', @cat_id, 'Star series pump', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'star-plus-i', 1, NOW()),
('STAR DURA I', @cat_id, 'Durable star series', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'star-dura-i', 1, NOW()),
('PRIMO I', @cat_id, 'Premium pump', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'primo-i', 1, NOW()),
('CMB10NV PLUS', @cat_id, 'Centrifugal monoblock pump, 0.5 HP', '0.5HP / 0.37kW', 'Single Phase', '', '', 'B.I.S. Compliant', '', 'Monoblock Pump', 'cmb10nv-plus', 1, NOW()),
('DMB10D PLUS', @cat_id, 'Centrifugal monoblock pump, 1.0 HP, max head 54m', '1.0HP / 0.75kW', 'Single Phase', '', '', 'B.I.S. Compliant', '', 'Monoblock Pump', 'dmb10d-plus', 1, NOW()),
('DMB10DCSL', @cat_id, 'Centrifugal monoblock pump, 1440 RPM', '1.0HP / 0.75kW', 'Single Phase', '', '', 'B.I.S. Compliant', '', 'Monoblock Pump', 'dmb10dcsl', 1, NOW()),
('CMB05NV PLUS', @cat_id, 'Centrifugal monoblock pump with brass impeller', '0.5HP / 0.37kW', 'Single Phase', '', '', 'B.I.S. Compliant', '', 'Monoblock Pump', 'cmb05nv-plus', 1, NOW()),
('SWJ1', @cat_id, 'Shallow well jet pump with 8m suction', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Shallow Well Pump', 'swj1', 1, NOW()),
('SWJ100AT-36 PLUS', @cat_id, 'Shallow well jet pump with tank', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Shallow Well Pump', 'swj100at-36-plus', 1, NOW()),
('SWJ50AT-30 PLUS', @cat_id, 'Shallow well pump with tank, 0.5 HP', '0.5HP / 0.37kW', 'Single Phase', '', '', '', '', 'Shallow Well Pump', 'swj50at-30-plus', 1, NOW()),
('3W12AP1D', @cat_id, '3-inch water-filled submersible pump', '1.0HP / 0.75kW', 'Single Phase', '3 inch (75mm)', '', '', '', 'Borewell Submersible', '3w12ap1d', 1, NOW()),
('3W10AP1D', @cat_id, '3-inch water-filled submersible pump', '1.0HP / 0.75kW', 'Single Phase', '3 inch (75mm)', '', '', '', 'Borewell Submersible', '3w10ap1d', 1, NOW()),
('3W10AK1A', @cat_id, '3-inch water-filled submersible pump', '1.0HP / 0.75kW', 'Single Phase', '3 inch (75mm)', '', '', '', 'Borewell Submersible', '3w10ak1a', 1, NOW()),
('4W7BU1AU', @cat_id, '4-inch water-filled submersible, 7 stages', '1.0HP / 0.75kW', 'Single Phase', '4 inch (100mm)', '7', '', '', 'Borewell Submersible', '4w7bu1au', 1, NOW()),
('4W14BU2EU', @cat_id, '4-inch water-filled submersible, 14 stages, 301ft head', '2.0HP / 1.5kW', 'Single Phase', '4 inch (100mm)', '14', '', '', 'Borewell Submersible', '4w14bu2eu', 1, NOW()),
('4W10BU1AU', @cat_id, '4-inch water-filled submersible, 10 stages', '1.0HP / 0.75kW', 'Single Phase', '4 inch (100mm)', '10', '', '', 'Borewell Submersible', '4w10bu1au', 1, NOW()),
('OWE12(1PH)Z-28', @cat_id, 'Centrifugal openwell pump with anti-rust coating', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Openwell Submersible', 'owe121phz-28', 1, NOW()),
('OWE052(1PH)Z-21FS', @cat_id, 'Centrifugal openwell submersible pump', '0.5HP / 0.37kW', 'Single Phase', '', '', '', '', 'Openwell Submersible', 'owe0521phz-21fs', 1, NOW()),
('Mini Force I', @cat_id, 'Automatic pressure booster pump with dry run protection', '0.5HP / 0.37kW', 'Single Phase', '', '', '', '', 'Booster Pump', 'mini-force-i', 1, NOW()),
('CFMSMB5D1.00-V24', @cat_id, 'Centrifugal booster pump, single stage', '1.0HP / 0.75kW', 'Single Phase', '', '1', '', '', 'Booster Pump', 'cfmsmb5d1.00-v24', 1, NOW()),
('ARMOR1.5-DSU', @cat_id, 'Control panel with settable OFF-timers, 1.5 HP', '1.5HP / 1.1kW', 'Single Phase', '', '', '', '', 'Control Panel', 'armor1.5-dsu', 1, NOW()),
('ARMOR1.0-CQU', @cat_id, 'Control panel compatible with submersible pumps, 1.0 HP', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Control Panel', 'armor1.0-cqu', 1, NOW());

