# TDS AutoFile - Complete Redesign Plan
## Aligned with Sandbox TDS API & Income Tax Act 1961

---

## **Executive Summary**

**Current State**: Single-firm TDS app with manual 26Q generation
**Target State**: Multi-firm, Sandbox API-integrated TDS filing platform with automated FVU generation and e-filing

**Key Changes**:
1. ✅ Multi-firm architecture (each firm independent)
2. ✅ API-compliant TDS return generation
3. ✅ Sandbox integration for FVU & e-filing
4. ✅ Job-based async workflow for large datasets
5. ✅ Real-time compliance status tracking
6. ✅ Digital signature & form submission automation

---

## **Phase 1: Database Schema Redesign**

### **New Tables to Add**

#### **1. api_credentials** — Store Sandbox API keys per firm
```sql
CREATE TABLE api_credentials (
  id INT PRIMARY KEY AUTO_INCREMENT,
  firm_id INT NOT NULL UNIQUE,
  api_key VARCHAR(255) NOT NULL,
  api_secret VARCHAR(255) NOT NULL,
  environment ENUM('sandbox','production') DEFAULT 'sandbox',
  access_token VARCHAR(500),
  token_generated_at TIMESTAMP,
  token_expires_at TIMESTAMP,
  is_active BOOLEAN DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (firm_id) REFERENCES firms(id)
);
```

#### **2. tds_filing_jobs** — Track TDS filing workflow
```sql
CREATE TABLE tds_filing_jobs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  firm_id INT NOT NULL,
  fy VARCHAR(9) NOT NULL,
  quarter ENUM('Q1','Q2','Q3','Q4') NOT NULL,

  -- File generation stages
  txt_file_path VARCHAR(255),
  txt_generated_at TIMESTAMP,

  -- CSI download stage
  csi_file_path VARCHAR(255),
  csi_downloaded_at TIMESTAMP,

  -- FVU generation stage
  fvu_job_id VARCHAR(100),
  fvu_status ENUM('pending','submitted','processing','succeeded','failed') DEFAULT 'pending',
  fvu_file_path VARCHAR(255),
  form27a_file_path VARCHAR(255),
  fvu_generated_at TIMESTAMP,
  fvu_error_message TEXT,

  -- Filing stage
  filing_job_id VARCHAR(100),
  filing_status ENUM('pending','submitted','processing','acknowledged','rejected','accepted') DEFAULT 'pending',
  filing_ack_no VARCHAR(30),
  filing_date TIMESTAMP,
  filing_error_message TEXT,

  -- Control totals
  control_total_records INT,
  control_total_amount DECIMAL(14,2),
  control_total_tds DECIMAL(14,2),

  -- Job metadata
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (firm_id) REFERENCES firms(id),
  FOREIGN KEY (created_by) REFERENCES users(id),
  UNIQUE KEY unique_filing (firm_id, fy, quarter)
);
```

#### **3. tds_filing_logs** — Audit trail for each step
```sql
CREATE TABLE tds_filing_logs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  job_id BIGINT NOT NULL,
  stage VARCHAR(50) NOT NULL,
  status VARCHAR(50) NOT NULL,
  message TEXT,
  api_request TEXT,
  api_response TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES tds_filing_jobs(id)
);
```

#### **4. deductees** — Consolidated deductee details per return
```sql
CREATE TABLE deductees (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  job_id BIGINT NOT NULL,
  vendor_id INT NOT NULL,
  pan CHAR(10) NOT NULL,
  name VARCHAR(200) NOT NULL,
  section_code VARCHAR(16) NOT NULL,
  total_gross DECIMAL(14,2),
  total_tds DECIMAL(14,2),
  payment_count INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES tds_filing_jobs(id),
  FOREIGN KEY (vendor_id) REFERENCES vendors(id),
  UNIQUE KEY unique_deductee_per_job (job_id, vendor_id, section_code)
);
```

#### **5. challan_linkages** — Map deductees to challans
```sql
CREATE TABLE challan_linkages (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  deductee_id BIGINT NOT NULL,
  challan_id BIGINT NOT NULL,
  allocated_tds DECIMAL(14,2) NOT NULL,
  bsr_code CHAR(7) NOT NULL,
  challan_date DATE NOT NULL,
  challan_serial_no VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (deductee_id) REFERENCES deductees(id),
  FOREIGN KEY (challan_id) REFERENCES challans(id)
);
```

### **Modified Tables**

#### **firms** — Add API configuration fields
```sql
ALTER TABLE firms ADD COLUMN (
  tin_fc_status ENUM('pending','registered','verified') DEFAULT 'pending',
  fy_closure_date DATE COMMENT 'Expected FY closure for scheduling',
  filing_reminder_days INT DEFAULT 7,
  auto_file_enabled BOOLEAN DEFAULT 0,
  sandbox_mode BOOLEAN DEFAULT 1
);
```

#### **invoices** — Add reconciliation tracking
```sql
ALTER TABLE invoices ADD COLUMN (
  is_reconciled BOOLEAN DEFAULT 0,
  reconciled_at TIMESTAMP NULL,
  total_allocated_tds DECIMAL(14,2) DEFAULT 0,
  allocation_status ENUM('unallocated','partial','complete') DEFAULT 'unallocated',
  validation_errors JSON
);
```

#### **challans** — Add validation tracking
```sql
ALTER TABLE challans ADD COLUMN (
  is_validated BOOLEAN DEFAULT 0,
  validation_errors JSON,
  validated_at TIMESTAMP NULL
);
```

---

## **Phase 2: Core API Integration Layer**

### **New Library: `SandboxTDSAPI.php`**

```php
<?php
class SandboxTDSAPI {
  private $apiKey;
  private $apiSecret;
  private $environment;
  private $baseUrl;
  private $accessToken;
  private $pdo;

  public function __construct($firm_id, PDO $pdo) {
    $this->pdo = $pdo;
    $stmt = $pdo->prepare('SELECT * FROM api_credentials WHERE firm_id=? AND is_active=1');
    $stmt->execute([$firm_id]);
    $cred = $stmt->fetch();

    if (!$cred) throw new Exception('No API credentials found');

    $this->apiKey = $cred['api_key'];
    $this->apiSecret = $cred['api_secret'];
    $this->environment = $cred['environment'];
    $this->baseUrl = ($this->environment === 'production')
      ? 'https://api.sandbox.co.in'
      : 'https://test-api.sandbox.co.in';
  }

  // Authenticate and get access token
  public function authenticate() {
    $response = $this->makeRequest('POST', '/authenticate', [], [
      'x-api-key' => $this->apiKey,
      'x-api-secret' => $this->apiSecret
    ]);

    if (!isset($response['data']['access_token'])) {
      throw new Exception('Failed to authenticate: ' . json_encode($response));
    }

    $this->accessToken = $response['data']['access_token'];
    return $this->accessToken;
  }

  // Download CSI file (Challan Status Information)
  public function downloadCSI($fy, $quarter) {
    // Requires OTP verification (bank statement)
    // Returns: CSI file content
    $response = $this->makeAuthenticatedRequest('GET', '/tds/compliance/csi/download', [
      'fy' => $fy,
      'quarter' => $quarter
    ]);

    return $response['data'];
  }

  // Generate FVU (File Validation Utility) - Async job
  public function submitFVUGenerationJob($txtContent, $csiContent) {
    $payload = [
      'txt_file' => base64_encode($txtContent),
      'csi_file' => base64_encode($csiContent)
    ];

    $response = $this->makeAuthenticatedRequest('POST', '/tds/compliance/fvu/generate', $payload);

    return [
      'job_id' => $response['data']['job_id'],
      'status' => 'submitted'
    ];
  }

  // Poll FVU job status
  public function pollFVUJobStatus($job_id) {
    $response = $this->makeAuthenticatedRequest('GET', '/tds/compliance/e-file/poll', [
      'job_id' => $job_id
    ]);

    return [
      'status' => $response['data']['status'],
      'fvu_url' => $response['data']['fvu_url'] ?? null,
      'form27a_url' => $response['data']['form27a_url'] ?? null,
      'error' => $response['data']['error'] ?? null
    ];
  }

  // Submit TDS return for e-filing
  public function submitEFilingJob($fvuZipPath, $form27aPath) {
    $payload = [
      'fvu_zip' => base64_encode(file_get_contents($fvuZipPath)),
      'form27a' => base64_encode(file_get_contents($form27aPath))
    ];

    $response = $this->makeAuthenticatedRequest('POST', '/tds/compliance/tin-fc/deductors/e-file/fvu', $payload);

    return [
      'job_id' => $response['data']['job_id'],
      'status' => 'submitted'
    ];
  }

  // Poll e-filing status
  public function pollEFilingStatus($job_id) {
    $response = $this->makeAuthenticatedRequest('GET', '/tds/compliance/e-file/poll', [
      'job_id' => $job_id
    ]);

    return [
      'status' => $response['data']['status'],
      'ack_no' => $response['data']['ack_no'] ?? null,
      'error' => $response['data']['error'] ?? null
    ];
  }

  private function makeRequest($method, $endpoint, $data = [], $headers = []) {
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $this->baseUrl . $endpoint,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HTTPHEADER => $this->prepareHeaders($headers),
      CURLOPT_TIMEOUT => 30
    ]);

    if (!empty($data) && $method !== 'GET') {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
      throw new Exception("API Error ($httpCode): $response");
    }

    return json_decode($response, true);
  }

  private function makeAuthenticatedRequest($method, $endpoint, $data = []) {
    if (!$this->accessToken) {
      $this->authenticate();
    }

    $headers = [
      'Authorization' => $this->accessToken,
      'Content-Type' => 'application/json'
    ];

    return $this->makeRequest($method, $endpoint, $data, $headers);
  }

  private function prepareHeaders($headers = []) {
    $default = [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json'
    ];

    $merged = array_merge($default, $headers);
    $formatted = [];
    foreach ($merged as $key => $value) {
      $formatted[] = "$key: $value";
    }

    return $formatted;
  }
}
?>
```

---

## **Phase 3: TDS Return Generation Engine**

### **New Library: `TDS26QGenerator.php`**

```php
<?php
class TDS26QGenerator {
  private $pdo;
  private $firm;
  private $fy;
  private $quarter;
  private $job_id;

  public function __construct($pdo, $firm_id, $fy, $quarter, $job_id) {
    $this->pdo = $pdo;
    $this->fy = $fy;
    $this->quarter = $quarter;
    $this->job_id = $job_id;

    $this->firm = $pdo->query("SELECT * FROM firms WHERE id=" . (int)$firm_id)->fetch();
    if (!$this->firm) throw new Exception('Firm not found');
  }

  // Generate Form 26Q TXT file per Income Tax Act 1961
  public function generateTXT() {
    // Fetch validated invoices
    $invoices = $this->getValidatedInvoices();
    if (empty($invoices)) {
      throw new Exception('No invoices found for filing');
    }

    // Build deductee summary
    $deductees = $this->aggregateDeductees($invoices);

    // Generate NS1 format (^ delimited)
    $txt = $this->buildFormContent($deductees, $invoices);

    // Save to file
    $filePath = $this->saveFile('txt', $txt);

    // Update job
    $stmt = $this->pdo->prepare('
      UPDATE tds_filing_jobs
      SET txt_file_path=?, txt_generated_at=NOW()
      WHERE id=?
    ');
    $stmt->execute([$filePath, $this->job_id]);

    return $filePath;
  }

  private function getValidatedInvoices() {
    $stmt = $this->pdo->prepare('
      SELECT i.*, v.pan, v.name, v.category
      FROM invoices i
      JOIN vendors v ON v.id=i.vendor_id
      WHERE i.firm_id=? AND i.fy=? AND i.quarter=? AND i.allocation_status="complete"
      ORDER BY i.invoice_date
    ');
    $stmt->execute([$this->firm['id'], $this->fy, $this->quarter]);
    return $stmt->fetchAll();
  }

  private function aggregateDeductees($invoices) {
    $deductees = [];
    foreach ($invoices as $inv) {
      $key = $inv['pan'] . '|' . $inv['section_code'];
      if (!isset($deductees[$key])) {
        $deductees[$key] = [
          'pan' => $inv['pan'],
          'name' => $inv['name'],
          'section' => $inv['section_code'],
          'category' => $inv['category'],
          'gross' => 0,
          'tds' => 0,
          'count' => 0,
          'payments' => []
        ];
      }
      $deductees[$key]['gross'] += (float)$inv['base_amount'];
      $deductees[$key]['tds'] += (float)$inv['total_tds'];
      $deductees[$key]['count']++;
      $deductees[$key]['payments'][] = $inv;
    }
    return $deductees;
  }

  private function buildFormContent($deductees, $invoices) {
    $lines = [];
    $lineNo = 1;

    // FH - File Header (18 fields)
    $fh = [$lineNo++, 'FH', 'NS1', 'R', date('dmY'), '1', 'D',
           strtoupper($this->firm['tan']), '1', 'TDS-AutoFile', '', '', '', '', '', '', '', ''];
    $lines[] = $this->formatLine($fh, 18);

    // BH - Batch Header (72 fields)
    $totalDeductees = count($deductees);
    $totalAmount = array_sum(array_map(fn($d) => $d['gross'], $deductees));
    $totalTDS = array_sum(array_map(fn($d) => $d['tds'], $deductees));

    $bh = [$lineNo++, 'BH', '1', strtoupper($this->firm['tan']),
           strtoupper($this->firm['pan']), $this->firm['display_name'], '', '', '', '',
           '', '', '', '', '', '', $totalDeductees, $totalAmount, $totalTDS, '', ...array_fill(0, 52, '')];
    $lines[] = $this->formatLine($bh, 72);

    // DR - Deductor Record (deductee summary)
    foreach ($deductees as $deductee) {
      $dr = [$lineNo++, 'DR', strtoupper($deductee['pan']), $deductee['name'],
             $deductee['section'], $deductee['gross'], $deductee['tds'], $deductee['count'],
             ...array_fill(0, 64, '')];
      $lines[] = $this->formatLine($dr, 72);
    }

    // CH - Challan Header (challan details linked to deductees)
    // [Implementation for challan links]

    // TL - Total Line
    $tl = [$lineNo++, 'TL', $totalDeductees, $totalAmount, $totalTDS, '', '', '', '', '',
           ...array_fill(0, 62, '')];
    $lines[] = $this->formatLine($tl, 72);

    return implode('', $lines);
  }

  private function formatLine($fields, $expectedCount) {
    // Pad or trim to expected field count
    $fields = array_slice(array_pad($fields, $expectedCount, ''), 0, $expectedCount);
    return implode('^', $fields) . "\r\n";
  }

  private function saveFile($type, $content) {
    $dir = __DIR__ . '/../uploads/filings/' . $this->job_id;
    if (!is_dir($dir)) mkdir($dir, 0750, true);

    $filename = $type === 'txt' ? 'form26q.txt' : 'form26q_' . $type;
    $path = $dir . '/' . $filename;
    file_put_contents($path, $content);

    return $path;
  }
}
?>
```

---

## **Phase 4: Workflow Orchestration**

### **New Endpoint: `api/filing/initiate.php`**

```php
<?php
// POST /tds/api/filing/initiate
// Initiates complete TDS filing workflow for a FY/Quarter
// 1. Validate invoices & challans
// 2. Generate TXT
// 3. Download CSI
// 4. Submit FVU job
// 5. Store job tracking

require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/SandboxTDSAPI.php';
require_once __DIR__.'/../lib/TDS26QGenerator.php';
require_once __DIR__.'/../lib/ajax_helpers.php';

$firm_id = (int)($_POST['firm_id'] ?? 0);
$fy = $_POST['fy'] ?? '';
$quarter = $_POST['quarter'] ?? '';

try {
  // Validate inputs
  if (!preg_match('/^\d{4}-\d{2}$/', $fy)) json_err('Invalid FY format');
  if (!in_array($quarter, ['Q1','Q2','Q3','Q4'])) json_err('Invalid quarter');

  // Create filing job record
  $stmt = $pdo->prepare('
    INSERT INTO tds_filing_jobs (firm_id, fy, quarter, created_by)
    VALUES (?, ?, ?, ?)
  ');
  $stmt->execute([$firm_id, $fy, $quarter, $_SESSION['uid']]);
  $job_id = $pdo->lastInsertId();

  // Phase 1: Validate and generate TXT
  $generator = new TDS26QGenerator($pdo, $firm_id, $fy, $quarter, $job_id);
  $txtFile = $generator->generateTXT();

  // Phase 2: Download CSI from bank
  $api = new SandboxTDSAPI($firm_id, $pdo);
  $csiContent = $api->downloadCSI($fy, $quarter);
  $csiFile = saveFile($job_id, 'csi', $csiContent);

  // Phase 3: Submit FVU generation job
  $fvuJob = $api->submitFVUGenerationJob(
    file_get_contents($txtFile),
    file_get_contents($csiFile)
  );

  // Store job details
  $stmt = $pdo->prepare('
    UPDATE tds_filing_jobs SET fvu_job_id=?, fvu_status=?
    WHERE id=?
  ');
  $stmt->execute([$fvuJob['job_id'], 'submitted', $job_id]);

  json_ok(['job_id' => $job_id, 'fvu_job_id' => $fvuJob['job_id']]);

} catch (Exception $e) {
  $stmt = $pdo->prepare('UPDATE tds_filing_jobs SET fvu_status=?, fvu_error_message=? WHERE id=?');
  $stmt->execute(['failed', $e->getMessage(), $job_id ?? null]);
  json_err($e->getMessage());
}
?>
```

---

## **Phase 5: Admin UI Restructure**

### **New Dashboard Sections**

1. **Multi-Firm Selector** — dropdown to switch between firms
2. **Filing Status Board** — shows all filing jobs by status
3. **Quick Actions** →
   - Add Firm
   - Upload Invoices/Challans
   - Initiate Filing
   - Check Filing Status
4. **Compliance Calendar** — FY/Q deadlines

### **New Pages**

- `admin/firms.php` — Manage multiple firms
- `admin/filing-job.php` — View specific filing job details
- `admin/filing-status.php` — Dashboard of all filings
- `admin/sandbox-config.php` — API credentials setup

---

## **Phase 6: Validation Rules (Income Tax Act 1961)**

### **Mandatory Validations**

1. **Invoices**
   - ✅ Vendor PAN must be 10 chars
   - ✅ Invoice date must be within FY
   - ✅ Base amount > 0
   - ✅ TDS rate matches section code
   - ✅ TDS amount = Base × Rate / 100

2. **Challans**
   - ✅ BSR code must be 7 digits
   - ✅ Challan date ≤ quarter end date
   - ✅ Amount TDS > 0
   - ✅ CSI validation from bank

3. **Allocation**
   - ✅ All invoice TDS must be allocated
   - ✅ No over-allocation
   - ✅ Challan date ≤ Quarter end date

4. **Firm Setup**
   - ✅ TAN (10 chars, unique)
   - ✅ PAN (10 chars)
   - ✅ Address (complete)
   - ✅ Responsible Person details
   - ✅ API credentials (for filing)

---

## **Implementation Timeline**

| Phase | Component | Effort | Status |
|-------|-----------|--------|--------|
| 1 | Database Schema | 2-3 hrs | 🟡 In Progress |
| 2 | Sandbox API Integration | 4-5 hrs | ⏳ Pending |
| 3 | TXT Generation Engine | 3-4 hrs | ⏳ Pending |
| 4 | Workflow Orchestration | 3-4 hrs | ⏳ Pending |
| 5 | Admin UI Refactor | 4-5 hrs | ⏳ Pending |
| 6 | Testing & Validation | 2-3 hrs | ⏳ Pending |

**Total: ~18-24 hours**

---

## **Key Files to Create/Modify**

### **New Files**
- `lib/SandboxTDSAPI.php` — API integration
- `lib/TDS26QGenerator.php` — TXT generation
- `lib/TDSValidator.php` — Business logic validation
- `api/filing/initiate.php` — Filing workflow
- `api/filing/check-status.php` — Job polling
- `api/filing/submit.php` — E-filing
- `admin/firms.php` — Multi-firm management
- `admin/filing-status.php` — Filing dashboard

### **Modified Files**
- `config.php` — API config structure
- `lib/db.php` — New table creation
- `lib/helpers.php` — Add validation functions
- `admin/_layout_top.php` — Add firm selector
- `admin/dashboard.php` — Show filing status

### **Deprecated/Refactor**
- `lib/TDS26QBuilder.php` → Replace with TDS26QGenerator.php
- `api/generate_26q.php` → Simplify or deprecate
- `api/reconcile.php` → Move logic to validator

---

## **Success Criteria**

✅ Single interface for multi-firm management
✅ Automated TXT generation per IT Act 1961
✅ Seamless Sandbox API integration
✅ Real-time filing status tracking
✅ Complete audit trail of all filing steps
✅ No manual file uploads (automated CSI download)
✅ Digital signature support (via Sandbox)
✅ Form 16/16A generation (future)

