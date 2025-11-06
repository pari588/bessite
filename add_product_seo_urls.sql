-- Add SEO URLs (seoUri) for 12 New Mini Pump Products
-- This creates clickable product detail page URLs
-- Date: 2025-11-06

UPDATE mx_pump SET seoUri = 'mini-master-ii' WHERE pumpID = 64;
UPDATE mx_pump SET seoUri = 'champ-plus-ii' WHERE pumpID = 65;
UPDATE mx_pump SET seoUri = 'mini-masterplus-ii' WHERE pumpID = 66;
UPDATE mx_pump SET seoUri = 'mini-marvel-ii' WHERE pumpID = 67;
UPDATE mx_pump SET seoUri = 'mini-crest-ii' WHERE pumpID = 68;
UPDATE mx_pump SET seoUri = 'aquagold-50-30' WHERE pumpID = 69;
UPDATE mx_pump SET seoUri = 'aquagold-100-33' WHERE pumpID = 70;
UPDATE mx_pump SET seoUri = 'flomax-plus-ii' WHERE pumpID = 71;
UPDATE mx_pump SET seoUri = 'master-dura-ii' WHERE pumpID = 72;
UPDATE mx_pump SET seoUri = 'master-plus-ii' WHERE pumpID = 73;
UPDATE mx_pump SET seoUri = 'star-plus-ii' WHERE pumpID = 74;
UPDATE mx_pump SET seoUri = 'champ-dura-ii' WHERE pumpID = 75;

-- Verify the updates
-- SELECT pumpID, pumpTitle, seoUri FROM mx_pump WHERE pumpID BETWEEN 64 AND 75;
