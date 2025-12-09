<?php
/**
 * Database Migration Script - TDS AutoFile Redesign Phase 1
 * Creates new tables for API integration and multi-firm filing
 */

function run_migrations($pdo) {
  $migrations = [
    'create_api_credentials_table',
    'create_tds_filing_jobs_table',
    'create_tds_filing_logs_table',
    'create_deductees_table',
    'create_challan_linkages_table',
    'create_analytics_jobs_table',
    'alter_firms_table',
    'alter_invoices_table',
    'alter_challans_table',
  ];

  foreach ($migrations as $migration) {
    try {
      $migration($pdo);
      echo "✓ $migration\n";
    } catch (Exception $e) {
      echo "✗ $migration: " . $e->getMessage() . "\n";
    }
  }
}

function create_api_credentials_table($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS api_credentials (
      id INT PRIMARY KEY AUTO_INCREMENT,
      firm_id INT NOT NULL UNIQUE,
      api_key VARCHAR(255) NOT NULL,
      api_secret VARCHAR(255) NOT NULL,
      environment ENUM('sandbox','production') DEFAULT 'sandbox',
      access_token VARCHAR(500),
      token_generated_at TIMESTAMP NULL,
      token_expires_at TIMESTAMP NULL,
      is_active BOOLEAN DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE,
      INDEX idx_firm_active (firm_id, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
}

function create_tds_filing_jobs_table($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS tds_filing_jobs (
      id BIGINT PRIMARY KEY AUTO_INCREMENT,
      firm_id INT NOT NULL,
      fy VARCHAR(9) NOT NULL COMMENT '2025-26',
      quarter ENUM('Q1','Q2','Q3','Q4') NOT NULL,

      -- File generation stages
      txt_file_path VARCHAR(255),
      txt_generated_at TIMESTAMP NULL,

      -- CSI download stage
      csi_file_path VARCHAR(255),
      csi_downloaded_at TIMESTAMP NULL,

      -- FVU generation stage
      fvu_job_id VARCHAR(100),
      fvu_status ENUM('pending','submitted','processing','succeeded','failed') DEFAULT 'pending',
      fvu_file_path VARCHAR(255),
      form27a_file_path VARCHAR(255),
      fvu_generated_at TIMESTAMP NULL,
      fvu_error_message TEXT,

      -- Filing stage
      filing_job_id VARCHAR(100),
      filing_status ENUM('pending','submitted','processing','acknowledged','rejected','accepted') DEFAULT 'pending',
      filing_ack_no VARCHAR(30),
      filing_date TIMESTAMP NULL,
      filing_error_message TEXT,

      -- Control totals
      control_total_records INT DEFAULT 0,
      control_total_amount DECIMAL(14,2) DEFAULT 0,
      control_total_tds DECIMAL(14,2) DEFAULT 0,

      -- Job metadata
      created_by INT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

      FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE,
      FOREIGN KEY (created_by) REFERENCES users(id),
      UNIQUE KEY unique_filing (firm_id, fy, quarter),
      INDEX idx_firm_status (firm_id, filing_status),
      INDEX idx_fvu_status (fvu_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
}

function create_tds_filing_logs_table($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS tds_filing_logs (
      id BIGINT PRIMARY KEY AUTO_INCREMENT,
      job_id BIGINT NOT NULL,
      stage VARCHAR(50) NOT NULL COMMENT 'txt_generation,csi_download,fvu_generation,e_filing',
      status VARCHAR(50) NOT NULL COMMENT 'pending,processing,completed,failed',
      message TEXT,
      api_request LONGTEXT,
      api_response LONGTEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

      FOREIGN KEY (job_id) REFERENCES tds_filing_jobs(id) ON DELETE CASCADE,
      INDEX idx_job_stage (job_id, stage)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
}

function create_deductees_table($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS deductees (
      id BIGINT PRIMARY KEY AUTO_INCREMENT,
      job_id BIGINT NOT NULL,
      vendor_id INT NOT NULL,
      pan CHAR(10) NOT NULL,
      name VARCHAR(200) NOT NULL,
      section_code VARCHAR(16) NOT NULL,
      total_gross DECIMAL(14,2) DEFAULT 0,
      total_tds DECIMAL(14,2) DEFAULT 0,
      payment_count INT DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

      FOREIGN KEY (job_id) REFERENCES tds_filing_jobs(id) ON DELETE CASCADE,
      FOREIGN KEY (vendor_id) REFERENCES vendors(id),
      UNIQUE KEY unique_deductee_per_job (job_id, vendor_id, section_code),
      INDEX idx_job_pan (job_id, pan)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
}

function create_challan_linkages_table($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS challan_linkages (
      id BIGINT PRIMARY KEY AUTO_INCREMENT,
      deductee_id BIGINT NOT NULL,
      challan_id BIGINT NOT NULL,
      allocated_tds DECIMAL(14,2) NOT NULL,
      bsr_code CHAR(7) NOT NULL,
      challan_date DATE NOT NULL,
      challan_serial_no VARCHAR(20) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

      FOREIGN KEY (deductee_id) REFERENCES deductees(id) ON DELETE CASCADE,
      FOREIGN KEY (challan_id) REFERENCES challans(id),
      INDEX idx_deductee (deductee_id),
      INDEX idx_challan (challan_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
}

function create_analytics_jobs_table($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS analytics_jobs (
      id BIGINT PRIMARY KEY AUTO_INCREMENT,
      filing_job_id BIGINT NOT NULL,
      firm_id INT NOT NULL,
      job_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'Sandbox job_id UUID',
      job_type ENUM('potential_notices','risk_assessment','form_validation') DEFAULT 'potential_notices',
      fy VARCHAR(9) NOT NULL COMMENT '2025-26',
      quarter ENUM('Q1','Q2','Q3','Q4') NOT NULL,
      form VARCHAR(10) COMMENT 'e.g., 26Q, 27Q, 24Q',
      status ENUM('submitted','queued','processing','succeeded','failed') DEFAULT 'submitted',
      report_url VARCHAR(500),
      error_message TEXT,
      potential_risks INT COMMENT 'Number of potential notices identified',
      risk_level VARCHAR(20) COMMENT 'LOW, MEDIUM, HIGH, CRITICAL',
      initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      completed_at TIMESTAMP NULL,
      last_polled_at TIMESTAMP NULL,
      poll_count INT DEFAULT 0,
      created_by INT,

      FOREIGN KEY (filing_job_id) REFERENCES tds_filing_jobs(id) ON DELETE CASCADE,
      FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE,
      FOREIGN KEY (created_by) REFERENCES users(id),
      INDEX idx_filing_job (filing_job_id),
      INDEX idx_firm_status (firm_id, status),
      INDEX idx_job_id (job_id),
      INDEX idx_initiated (initiated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
}

function alter_firms_table($pdo) {
  $columns = $pdo->query("SHOW COLUMNS FROM firms WHERE Field IN ('tin_fc_status', 'fy_closure_date', 'filing_reminder_days', 'auto_file_enabled', 'sandbox_mode')")->fetchAll();

  if (count($columns) < 5) {
    $pdo->exec("
      ALTER TABLE firms
      ADD COLUMN IF NOT EXISTS tin_fc_status ENUM('pending','registered','verified') DEFAULT 'pending' COMMENT 'TIN Facilitation Center registration status',
      ADD COLUMN IF NOT EXISTS fy_closure_date DATE COMMENT 'Expected FY closure for scheduling',
      ADD COLUMN IF NOT EXISTS filing_reminder_days INT DEFAULT 7,
      ADD COLUMN IF NOT EXISTS auto_file_enabled BOOLEAN DEFAULT 0,
      ADD COLUMN IF NOT EXISTS sandbox_mode BOOLEAN DEFAULT 1
    ");
  }
}

function alter_invoices_table($pdo) {
  $columns = $pdo->query("SHOW COLUMNS FROM invoices WHERE Field IN ('is_reconciled', 'reconciled_at', 'total_allocated_tds', 'allocation_status', 'validation_errors')")->fetchAll();

  if (count($columns) < 5) {
    $pdo->exec("
      ALTER TABLE invoices
      ADD COLUMN IF NOT EXISTS is_reconciled BOOLEAN DEFAULT 0,
      ADD COLUMN IF NOT EXISTS reconciled_at TIMESTAMP NULL,
      ADD COLUMN IF NOT EXISTS total_allocated_tds DECIMAL(14,2) DEFAULT 0,
      ADD COLUMN IF NOT EXISTS allocation_status ENUM('unallocated','partial','complete') DEFAULT 'unallocated',
      ADD COLUMN IF NOT EXISTS validation_errors JSON
    ");
  }
}

function alter_challans_table($pdo) {
  $columns = $pdo->query("SHOW COLUMNS FROM challans WHERE Field IN ('is_validated', 'validation_errors', 'validated_at')")->fetchAll();

  if (count($columns) < 3) {
    $pdo->exec("
      ALTER TABLE challans
      ADD COLUMN IF NOT EXISTS is_validated BOOLEAN DEFAULT 0,
      ADD COLUMN IF NOT EXISTS validation_errors JSON,
      ADD COLUMN IF NOT EXISTS validated_at TIMESTAMP NULL
    ");
  }
}

// Run migrations if called directly
if (php_sapi_name() === 'cli' && basename($argv[0] ?? '') === basename(__FILE__)) {
  $config = require __DIR__ . '/../config.php';
  $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
    $config['db']['host'], $config['db']['name'], $config['db']['charset']);
  $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);
  echo "Running migrations...\n";
  run_migrations($pdo);
  echo "Migrations complete!\n";
}
?>
