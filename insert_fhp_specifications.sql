-- Insert FHP/Commercial Motors Specifications
-- These specifications are based on industry standards for FHP Commercial Motors

-- SINGLE PHASE MOTORS (Category 102)

-- Capacitor Start Motors (motorID: 48)
-- Typical output range: 370W - 1500W, Single Phase, IEC frames 80-112
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(48, 'Capacitor Start - 370W', '370W', 'Single Phase 230V', 'IEC 80', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(48, 'Capacitor Start - 550W', '550W', 'Single Phase 230V', 'IEC 90', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(48, 'Capacitor Start - 750W', '750W', 'Single Phase 230V', 'IEC 100', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(48, 'Capacitor Start - 1100W', '1100W', 'Single Phase 230V', 'IEC 112', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(48, 'Capacitor Start - 1500W', '1500W', 'Single Phase 230V', 'IEC 112', 'IS 1161, BIS', '4 Pole', '50Hz', 1);

-- Capacitor Run Motors (motorID: 49)
-- Typical output range: 370W - 1500W, Single Phase, Continuous duty
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(49, 'Capacitor Run - 370W', '370W', 'Single Phase 230V', 'IEC 80', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(49, 'Capacitor Run - 550W', '550W', 'Single Phase 230V', 'IEC 90', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(49, 'Capacitor Run - 750W', '750W', 'Single Phase 230V', 'IEC 100', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(49, 'Capacitor Run - 1100W', '1100W', 'Single Phase 230V', 'IEC 112', 'IS 1161, BIS', '4 Pole', '50Hz', 1);

-- Permanent Split Capacitor Motors (motorID: 50)
-- Typical output range: 370W - 1500W, Single Phase, Low vibration
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(50, 'PSC Motor - 370W', '370W', 'Single Phase 230V', 'IEC 80', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(50, 'PSC Motor - 550W', '550W', 'Single Phase 230V', 'IEC 90', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(50, 'PSC Motor - 750W', '750W', 'Single Phase 230V', 'IEC 100', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(50, 'PSC Motor - 1100W', '1100W', 'Single Phase 230V', 'IEC 112', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(50, 'PSC Motor - 1500W', '1500W', 'Single Phase 230V', 'IEC 112', 'IS 1161, BIS', '4 Pole', '50Hz', 1);

-- Split Phase Motors (motorID: 51)
-- Typical output range: 370W - 1100W, Single Phase, Cost-effective
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(51, 'Split Phase - 370W', '370W', 'Single Phase 230V', 'IEC 80', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(51, 'Split Phase - 550W', '550W', 'Single Phase 230V', 'IEC 90', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(51, 'Split Phase - 750W', '750W', 'Single Phase 230V', 'IEC 100', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(51, 'Split Phase - 1100W', '1100W', 'Single Phase 230V', 'IEC 112', 'IS 1161, BIS', '4 Pole', '50Hz', 1);

-- 3 PHASE MOTORS - ROLLED STEEL BODY (Category 103)

-- 3 Phase Rolled Steel Standard Duty (motorID: 52)
-- Typical output range: 1.5kW - 11kW, 3 Phase 230/415V, IEC frames
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(52, '3-Phase Standard - 1.5kW', '1.5kW', '3 Phase 230/415V', 'IEC 90', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(52, '3-Phase Standard - 2.2kW', '2.2kW', '3 Phase 230/415V', 'IEC 100', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(52, '3-Phase Standard - 3.7kW', '3.7kW', '3 Phase 230/415V', 'IEC 112', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(52, '3-Phase Standard - 5.5kW', '5.5kW', '3 Phase 230/415V', 'IEC 132', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(52, '3-Phase Standard - 7.5kW', '7.5kW', '3 Phase 230/415V', 'IEC 132', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(52, '3-Phase Standard - 11kW', '11kW', '3 Phase 230/415V', 'IEC 160', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1);

-- 3 Phase Rolled Steel Heavy Duty (motorID: 53)
-- Enhanced construction for mining and crushing equipment
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(53, '3-Phase Heavy Duty - 2.2kW', '2.2kW', '3 Phase 230/415V', 'IEC 100', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(53, '3-Phase Heavy Duty - 3.7kW', '3.7kW', '3 Phase 230/415V', 'IEC 112', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(53, '3-Phase Heavy Duty - 5.5kW', '5.5kW', '3 Phase 230/415V', 'IEC 132', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(53, '3-Phase Heavy Duty - 7.5kW', '7.5kW', '3 Phase 230/415V', 'IEC 132', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(53, '3-Phase Heavy Duty - 11kW', '11kW', '3 Phase 230/415V', 'IEC 160', 'IS 1161:2014, IEC 60034-1, BIS', '4 Pole', '50Hz', 1);

-- 3 Phase Rolled Steel Premium Efficiency (motorID: 54)
-- Energy-efficient IE2 standard motors
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(54, '3-Phase Premium IE2 - 1.5kW', '1.5kW', '3 Phase 230/415V', 'IEC 90', 'IS 1161:2014 (IE2), IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(54, '3-Phase Premium IE2 - 2.2kW', '2.2kW', '3 Phase 230/415V', 'IEC 100', 'IS 1161:2014 (IE2), IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(54, '3-Phase Premium IE2 - 3.7kW', '3.7kW', '3 Phase 230/415V', 'IEC 112', 'IS 1161:2014 (IE2), IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(54, '3-Phase Premium IE2 - 5.5kW', '5.5kW', '3 Phase 230/415V', 'IEC 132', 'IS 1161:2014 (IE2), IEC 60034-1, BIS', '4 Pole', '50Hz', 1),
(54, '3-Phase Premium IE2 - 7.5kW', '7.5kW', '3 Phase 230/415V', 'IEC 132', 'IS 1161:2014 (IE2), IEC 60034-1, BIS', '4 Pole', '50Hz', 1);

-- 3 Phase Rolled Steel Explosion Proof (motorID: 55)
-- Safety certified for hazardous areas
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(55, 'Explosion Proof - 1.1kW', '1.1kW', '3 Phase 230/415V', 'IEC 90', 'ATEX II 2G/3G, IEC 60034-1, CCOE Certified', '4 Pole', '50Hz', 1),
(55, 'Explosion Proof - 1.5kW', '1.5kW', '3 Phase 230/415V', 'IEC 90', 'ATEX II 2G/3G, IEC 60034-1, CCOE Certified', '4 Pole', '50Hz', 1),
(55, 'Explosion Proof - 2.2kW', '2.2kW', '3 Phase 230/415V', 'IEC 100', 'ATEX II 2G/3G, IEC 60034-1, CCOE Certified', '4 Pole', '50Hz', 1),
(55, 'Explosion Proof - 3.7kW', '3.7kW', '3 Phase 230/415V', 'IEC 112', 'ATEX II 2G/3G, IEC 60034-1, CCOE Certified', '4 Pole', '50Hz', 1),
(55, 'Explosion Proof - 5.5kW', '5.5kW', '3 Phase 230/415V', 'IEC 132', 'ATEX II 2G/3G, IEC 60034-1, CCOE Certified', '4 Pole', '50Hz', 1),
(55, 'Explosion Proof - 7.5kW', '7.5kW', '3 Phase 230/415V', 'IEC 132', 'ATEX II 2G/3G, IEC 60034-1, CMRI Certified', '4 Pole', '50Hz', 1);

-- APPLICATION SPECIFIC MOTORS (Category 104)

-- Huller Motors (motorID: 56)
-- Specialized for rice/grain processing - high torque
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(56, 'Huller Motor - 1.5kW', '1.5kW', '3 Phase 230/415V', 'IEC 90', 'IS 1161, BIS', '6 Pole', '50Hz', 1),
(56, 'Huller Motor - 2.2kW', '2.2kW', '3 Phase 230/415V', 'IEC 100', 'IS 1161, BIS', '6 Pole', '50Hz', 1),
(56, 'Huller Motor - 3.7kW', '3.7kW', '3 Phase 230/415V', 'IEC 112', 'IS 1161, BIS', '6 Pole', '50Hz', 1);

-- Cooler Motors (motorID: 57)
-- For textile mills and cooling applications - high airflow
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(57, 'Cooler Motor - 0.37kW', '0.37kW', 'Single Phase 230V', 'IEC 80', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(57, 'Cooler Motor - 0.55kW', '0.55kW', 'Single Phase 230V', 'IEC 90', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(57, 'Cooler Motor - 0.75kW', '0.75kW', '3 Phase 230/415V', 'IEC 90', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(57, 'Cooler Motor - 1.1kW', '1.1kW', '3 Phase 230/415V', 'IEC 100', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(57, 'Cooler Motor - 1.5kW', '1.5kW', '3 Phase 230/415V', 'IEC 100', 'IS 1161, BIS', '4 Pole', '50Hz', 1);

-- Flange Motors (motorID: 58)
-- Direct drive coupling - compact design
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(58, 'Flange Motor - 0.37kW', '0.37kW', 'Single Phase 230V', 'IEC 80-F', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(58, 'Flange Motor - 0.55kW', '0.55kW', 'Single Phase 230V', 'IEC 90-F', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(58, 'Flange Motor - 0.75kW', '0.75kW', '3 Phase 230/415V', 'IEC 90-F', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(58, 'Flange Motor - 1.1kW', '1.1kW', '3 Phase 230/415V', 'IEC 100-F', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(58, 'Flange Motor - 1.5kW', '1.5kW', '3 Phase 230/415V', 'IEC 112-F', 'IS 1161, BIS', '4 Pole', '50Hz', 1);

-- Textile Industry Motors (motorID: 59)
-- High-speed for looms and spinners
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(59, 'Textile Motor - 0.37kW', '0.37kW', 'Single Phase 230V', 'IEC 80', 'IS 1161, BIS', '2 Pole', '50Hz', 1),
(59, 'Textile Motor - 0.55kW', '0.55kW', 'Single Phase 230V', 'IEC 90', 'IS 1161, BIS', '2 Pole', '50Hz', 1),
(59, 'Textile Motor - 0.75kW', '0.75kW', '3 Phase 230/415V', 'IEC 90', 'IS 1161, BIS', '2 Pole', '50Hz', 1),
(59, 'Textile Motor - 1.1kW', '1.1kW', '3 Phase 230/415V', 'IEC 100', 'IS 1161, BIS', '2 Pole', '50Hz', 1);

-- Agricultural Equipment Motors (motorID: 60)
-- Robust for farm equipment - dust resistant
INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, specPoles, specFrequency, status) VALUES
(60, 'Agricultural Motor - 1.5kW', '1.5kW', '3 Phase 230/415V', 'IEC 90', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(60, 'Agricultural Motor - 2.2kW', '2.2kW', '3 Phase 230/415V', 'IEC 100', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(60, 'Agricultural Motor - 3.7kW', '3.7kW', '3 Phase 230/415V', 'IEC 112', 'IS 1161, BIS', '4 Pole', '50Hz', 1),
(60, 'Agricultural Motor - 5.5kW', '5.5kW', '3 Phase 230/415V', 'IEC 132', 'IS 1161, BIS', '4 Pole', '50Hz', 1);
