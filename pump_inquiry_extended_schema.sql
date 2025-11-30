-- ============================================================================
-- EXTENDED PUMP INQUIRY FORM - DATABASE SCHEMA
-- Created: 2025-11-05
-- Purpose: Extended form with comprehensive fields for pump inquiries
-- ============================================================================

-- Drop existing table if exists (optional - comment out if table exists with data)
-- DROP TABLE IF EXISTS `bombay_pump_inquiry`;

-- Create the main pump inquiry table
CREATE TABLE IF NOT EXISTS `bombay_pump_inquiry` (
  `pumpInquiryID` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique inquiry ID',

  -- ========== CUSTOMER DETAILS SECTION ==========
  `fullName` VARCHAR(100) NOT NULL COMMENT 'Full name of customer',
  `companyName` VARCHAR(150) COMMENT 'Company/Organization name',
  `userEmail` VARCHAR(100) NOT NULL COMMENT 'Customer email address',
  `userMobile` VARCHAR(20) NOT NULL COMMENT 'Customer mobile number (Indian format)',
  `address` TEXT COMMENT 'Installation address/location',
  `city` VARCHAR(50) COMMENT 'City dropdown: Mumbai, Pune, Ahmedabad, Other',
  `pinCode` VARCHAR(6) COMMENT '6-digit pin code',
  `preferredContactTime` VARCHAR(20) COMMENT 'Preferred contact time: Morning, Afternoon, Evening',

  -- ========== APPLICATION DETAILS SECTION ==========
  `applicationTypeID` VARCHAR(50) COMMENT 'Type of application: Domestic, Industrial, Agricultural, Commercial, Sewage, HVAC, Firefighting, Other',
  `purposeOfPump` TEXT COMMENT 'Purpose/use of pump',
  `installationTypeID` VARCHAR(50) COMMENT 'Installation type: Surface, Submersible, Booster, Dewatering, Openwell, Borewell',
  `operatingMediumID` VARCHAR(50) COMMENT 'Operating medium: Clean water, Muddy water, Sewage, Chemical, Hot water, Other',
  `waterSourceID` VARCHAR(50) COMMENT 'Water source: Overhead tank, Underground tank, Borewell, River, Sump, Other',
  `requiredHead` DECIMAL(8,2) COMMENT 'Required head in meters',
  `requiredDischarge` VARCHAR(50) COMMENT 'Required discharge (LPM or m³/hr)',
  `pumpingDistance` DECIMAL(8,2) COMMENT 'Total pumping distance in meters',
  `heightDifference` DECIMAL(8,2) COMMENT 'Height difference in meters',
  `pipeSize` VARCHAR(20) COMMENT 'Pipe size in inches',
  `powerSupplyID` VARCHAR(20) COMMENT 'Power supply: Single Phase, Three Phase',
  `operatingHours` DECIMAL(5,2) COMMENT 'Operating hours per day',
  `automationNeeded` VARCHAR(10) COMMENT 'Automation needed: Yes, No',
  `existingPumpModel` VARCHAR(100) COMMENT 'Existing pump model (if any)',
  `uploadedFile` VARCHAR(255) COMMENT 'Path to uploaded file (jpg, png, pdf)',

  -- ========== PRODUCT PREFERENCES SECTION ==========
  `preferredBrand` VARCHAR(100) COMMENT 'Preferred brand: Crompton, CG Power, Kirloskar, Open to suggestion',
  `pumpTypesInterested` VARCHAR(255) COMMENT 'Pump types (comma-separated): Centrifugal, Jet, Submersible, Monoblock, Borewell, Booster, Self-Priming, Others',
  `materialPreference` VARCHAR(100) COMMENT 'Material preference: Cast Iron, Stainless Steel, Bronze, Plastic, Open to suggestion',
  `motorRating` VARCHAR(50) COMMENT 'Motor HP/kW rating',
  `quantityRequired` INT COMMENT 'Quantity of pumps required',

  -- ========== SUBMISSION & TRACKING ==========
  `consentGiven` BOOLEAN DEFAULT 0 COMMENT 'Consent checkbox status',
  `gRecaptchaScore` DECIMAL(3,2) COMMENT 'Google reCAPTCHA v3 score',
  `status` INT DEFAULT 1 COMMENT 'Status: 1=Active, 0=Inactive',
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updatedDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',

  -- ========== INDEXES FOR PERFORMANCE ==========
  KEY `idx_email` (`userEmail`),
  KEY `idx_mobile` (`userMobile`),
  KEY `idx_city` (`city`),
  KEY `idx_applicationType` (`applicationTypeID`),
  KEY `idx_installationType` (`installationTypeID`),
  KEY `idx_status_date` (`status`, `createdDate`),
  KEY `idx_created_date` (`createdDate`),
  KEY `idx_full_name` (`fullName`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Extended pump inquiry form submissions';

-- ============================================================================
-- Create dedicated table for pump types (for better normalization)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bombay_pump_types` (
  `pumpTypeID` INT AUTO_INCREMENT PRIMARY KEY,
  `pumpTypeName` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Type name: Centrifugal, Jet, Submersible, etc',
  `description` TEXT,
  `status` INT DEFAULT 1,
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Create junction table for pump types (many-to-many relationship)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bombay_inquiry_pump_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `inquiryID` INT NOT NULL,
  `pumpTypeID` INT NOT NULL,
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`inquiryID`) REFERENCES `bombay_pump_inquiry`(`pumpInquiryID`) ON DELETE CASCADE,
  FOREIGN KEY (`pumpTypeID`) REFERENCES `bombay_pump_types`(`pumpTypeID`) ON DELETE CASCADE,
  UNIQUE KEY `unique_inquiry_type` (`inquiryID`, `pumpTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Insert default pump types
-- ============================================================================
INSERT IGNORE INTO `bombay_pump_types` (`pumpTypeName`, `description`) VALUES
('Centrifugal', 'Centrifugal pump for general purpose applications'),
('Jet', 'Jet pump for shallow well applications'),
('Submersible', 'Submersible pump for deep well/borewell installations'),
('Monoblock', 'Monoblock pump with integrated motor'),
('Borewell', 'Dedicated borewell submersible pump'),
('Booster', 'Booster pump for pressure enhancement'),
('Self-Priming', 'Self-priming pump for high suction lift applications'),
('Others', 'Other pump types');

-- ============================================================================
-- Create table for application types (for dropdown options)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bombay_application_types` (
  `applicationTypeID` INT AUTO_INCREMENT PRIMARY KEY,
  `applicationName` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `status` INT DEFAULT 1,
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `bombay_application_types` (`applicationName`, `description`) VALUES
('Domestic', 'Domestic/residential use'),
('Industrial', 'Industrial applications'),
('Agricultural', 'Agricultural/farming use'),
('Commercial', 'Commercial properties'),
('Sewage', 'Sewage/wastewater handling'),
('HVAC', 'HVAC systems'),
('Firefighting', 'Firefighting systems'),
('Other', 'Other applications');

-- ============================================================================
-- Create table for installation types
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bombay_installation_types` (
  `installationTypeID` INT AUTO_INCREMENT PRIMARY KEY,
  `installationName` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `status` INT DEFAULT 1,
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `bombay_installation_types` (`installationName`, `description`) VALUES
('Surface', 'Surface installation'),
('Submersible', 'Submersible installation'),
('Booster', 'Booster pump installation'),
('Dewatering', 'Dewatering installation'),
('Openwell', 'Open well installation'),
('Borewell', 'Borewell installation');

-- ============================================================================
-- Create table for operating mediums
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bombay_operating_mediums` (
  `operatingMediumID` INT AUTO_INCREMENT PRIMARY KEY,
  `mediumName` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `status` INT DEFAULT 1,
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `bombay_operating_mediums` (`mediumName`, `description`) VALUES
('Clean water', 'Clean water'),
('Muddy water', 'Water with suspended particles'),
('Sewage', 'Sewage/wastewater'),
('Chemical', 'Chemical fluids'),
('Hot water', 'Hot water applications'),
('Other', 'Other mediums');

-- ============================================================================
-- Create table for water sources
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bombay_water_sources` (
  `waterSourceID` INT AUTO_INCREMENT PRIMARY KEY,
  `sourceName` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `status` INT DEFAULT 1,
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `bombay_water_sources` (`sourceName`, `description`) VALUES
('Overhead tank', 'Overhead water tank'),
('Underground tank', 'Underground/sump tank'),
('Borewell', 'Borewell/tube well'),
('River', 'River/natural water source'),
('Sump', 'Sump/collection pit'),
('Other', 'Other water sources');

-- ============================================================================
-- Create table for brands
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bombay_brands` (
  `brandID` INT AUTO_INCREMENT PRIMARY KEY,
  `brandName` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `status` INT DEFAULT 1,
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `bombay_brands` (`brandName`, `description`) VALUES
('Crompton', 'Crompton Greaves Electric Limited'),
('CG Power', 'CG Power and Industrial Solutions'),
('Kirloskar', 'Kirloskar Electric Company'),
('Open to suggestion', 'Customer open to suggestions');

-- ============================================================================
-- Create table for material preferences
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bombay_materials` (
  `materialID` INT AUTO_INCREMENT PRIMARY KEY,
  `materialName` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `status` INT DEFAULT 1,
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `bombay_materials` (`materialName`, `description`) VALUES
('Cast Iron', 'Cast iron construction'),
('Stainless Steel', 'Stainless steel construction'),
('Bronze', 'Bronze construction'),
('Plastic', 'Plastic/composite materials'),
('Open to suggestion', 'Open to suggestion');

-- ============================================================================
-- Summary of schema changes
-- ============================================================================
-- Main Table: bombay_pump_inquiry (31 fields)
--   - Customer Details: 8 fields
--   - Application Details: 16 fields
--   - Product Preferences: 5 fields
--   - Submission: 2 fields
--
-- Supporting Tables:
--   - bombay_pump_types: Pump type options
--   - bombay_inquiry_pump_types: Many-to-many junction table
--   - bombay_application_types: Application type options
--   - bombay_installation_types: Installation type options
--   - bombay_operating_mediums: Operating medium options
--   - bombay_water_sources: Water source options
--   - bombay_brands: Brand options
--   - bombay_materials: Material options
-- ============================================================================
