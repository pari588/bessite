-- Create database if not exists
CREATE DATABASE IF NOT EXISTS camsBiometrics;
USE camsBiometrics;

-- Table: camsPunch
CREATE TABLE `camsPunch` (
        id BIGSERIAL PRIMARY KEY COMMENT 'Primary key ID',
        user_id VARCHAR(64) NOT NULL COMMENT 'Employee/User ID',
        punch_date_str VARCHAR(10) NOT NULL COMMENT 'Date part of punch_time in YYYY-MM-DD format',
        punch_time TIMESTAMP WITH TIME ZONE NOT NULL COMMENT 'Exact time of the punch',
        actual_punch_type SMALLINT NOT NULL COMMENT '0-Check In, 1-Check Out, 3-Break Out, 4-Break In, 5-Intermediate (as reported by device)',
        punch_type SMALLINT NOT NULL COMMENT '0-Check In, 1-Check Out, 3-Break Out, 4-Break In, 5-Intermediate (as recalculated by system)',
        input_type SMALLINT NOT NULL COMMENT '0-Fingerprint, 1-Face, 3-Card, 4-Password, 5-Others (method of punch)',
        device_id BIGINT NOT NULL COMMENT 'ID of the biometric device that recorded the punch',
        status VARCHAR(1) NOT NULL DEFAULT 'A' COMMENT 'Status of the punch record (e.g., A=Active)',
        time_updated TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Last update timestamp'
);



-- Table: camsUser
CREATE TABLE `camsUser` (
        id BIGSERIAL PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL UNIQUE,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        type VARCHAR(1) NOT NULL,
        template JSONB,
        status VARCHAR(1) NOT NULL DEFAULT 'A',
        time_updated TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);



-- Table: camsDevice
CREATE TABLE `camsDevice` (
        id BIGSERIAL PRIMARY KEY,
        client_id VARCHAR(255) NOT NULL,
        serial_number VARCHAR(255) NOT NULL UNIQUE,
        label_name VARCHAR(255) NOT NULL,
        auth_token VARCHAR(255) NOT NULL,
        status VARCHAR(1) NOT NULL DEFAULT 'A',
        time_updated TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);



-- Table: camsConfiguration
CREATE TABLE `camsConfiguration` (
        id SERIAL PRIMARY KEY,
        client_id VARCHAR(255) NOT NULL UNIQUE,
        direction SMALLINT NOT NULL,
        status VARCHAR(1) NOT NULL DEFAULT 'A',
        time_updated TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);



-- Table: camsAttendance
CREATE TABLE `camsAttendance` (
        id BIGSERIAL PRIMARY KEY,
        pdate VARCHAR(10),
        user_id VARCHAR(64) NOT NULL,
        punch_in_time TIMESTAMP WITH TIME ZONE,
        punch_out_time TIMESTAMP WITH TIME ZONE,
        status VARCHAR(2) NOT NULL DEFAULT 'A',
        confirm_status VARCHAR(1) NOT NULL DEFAULT 'I',
        shift_id SMALLINT NOT NULL DEFAULT 9,
        day_id SMALLINT NOT NULL DEFAULT 9,
        working_hours DOUBLE PRECISION NOT NULL DEFAULT 0.00,
        overtime_slab_minute INTEGER NOT NULL DEFAULT 0,
        time_updated TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);






