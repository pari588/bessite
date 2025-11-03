-- Pump Inquiry Form - Extended Fields Database Schema
-- Execute this script to add new columns to pump_inquiry table

ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `fullName` VARCHAR(100) DEFAULT NULL AFTER `userName`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `companyName` VARCHAR(100) DEFAULT NULL AFTER `fullName`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `city` VARCHAR(50) DEFAULT NULL AFTER `address`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `pinCode` INT(6) DEFAULT NULL AFTER `city`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `preferredContactTime` VARCHAR(50) DEFAULT NULL AFTER `pinCode`;

-- Application Details
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `applicationTypeID` INT(11) DEFAULT NULL AFTER `preferredContactTime`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `purposeOfPump` TEXT DEFAULT NULL AFTER `applicationTypeID`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `installationTypeID` INT(11) DEFAULT NULL AFTER `purposeOfPump`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `operatingMediumID` INT(11) DEFAULT NULL AFTER `installationTypeID`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `waterSourceID` INT(11) DEFAULT NULL AFTER `operatingMediumID`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `requiredHead` DECIMAL(10,2) DEFAULT NULL AFTER `waterSourceID`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `requiredDischarge` VARCHAR(50) DEFAULT NULL AFTER `requiredHead`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `pumpingDistance` DECIMAL(10,2) DEFAULT NULL AFTER `requiredDischarge`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `heightDifference` DECIMAL(10,2) DEFAULT NULL AFTER `pumpingDistance`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `pipeSize` VARCHAR(50) DEFAULT NULL AFTER `heightDifference`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `powerSupplyID` INT(11) DEFAULT NULL AFTER `pipeSize`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `operatingHours` INT(11) DEFAULT NULL AFTER `powerSupplyID`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `automationNeededID` INT(11) DEFAULT NULL AFTER `operatingHours`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `existingPumpModel` VARCHAR(100) DEFAULT NULL AFTER `automationNeededID`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `uploadedFile` VARCHAR(255) DEFAULT NULL AFTER `existingPumpModel`;

-- Product Preferences
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `preferredBrandID` INT(11) DEFAULT NULL AFTER `uploadedFile`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `pumpTypesInterested` TEXT DEFAULT NULL AFTER `preferredBrandID`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `materialPreferenceID` INT(11) DEFAULT NULL AFTER `pumpTypesInterested`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `motorRating` VARCHAR(50) DEFAULT NULL AFTER `materialPreferenceID`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `quantityRequired` INT(11) DEFAULT NULL AFTER `motorRating`;

-- Submission & Security
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `consentGiven` TINYINT(1) DEFAULT 0 AFTER `quantityRequired`;
ALTER TABLE `bombay_pump_inquiry` ADD COLUMN IF NOT EXISTS `gRecaptchaScore` DECIMAL(3,2) DEFAULT NULL AFTER `consentGiven`;

-- Ensure timestamp columns exist
ALTER TABLE `bombay_pump_inquiry` MODIFY COLUMN `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add index for faster queries
ALTER TABLE `bombay_pump_inquiry` ADD INDEX IF NOT EXISTS `idx_status_date` (`status`, `createdDate`);
ALTER TABLE `bombay_pump_inquiry` ADD INDEX IF NOT EXISTS `idx_email` (`userEmail`);
ALTER TABLE `bombay_pump_inquiry` ADD INDEX IF NOT EXISTS `idx_application` (`applicationTypeID`);

