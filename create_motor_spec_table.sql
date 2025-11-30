-- Create motor_specification table to store multiple specifications per product
CREATE TABLE IF NOT EXISTS `mx_motor_specification` (
  `motorSpecID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `motorID` INT NOT NULL,
  `specTitle` VARCHAR(255),
  `specOutput` VARCHAR(100),
  `specVoltage` VARCHAR(100),
  `specFrameSize` VARCHAR(100),
  `specStandard` VARCHAR(255),
  `specPoles` VARCHAR(50),
  `specFrequency` VARCHAR(50),
  `status` INT DEFAULT 1,
  `createdDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`motorID`) REFERENCES `mx_motor`(`motorID`) ON DELETE CASCADE,
  INDEX idx_motorID (motorID),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
