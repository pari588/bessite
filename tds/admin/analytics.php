<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/SandboxTDSAPI.php';
require_once __DIR__.'/../lib/helpers.php';

$page_title='Risk Analytics & Potential Notices';
include __DIR__.'/_layout_top.php';

// Get firm data
$firm = $pdo->query('SELECT id, tan FROM firms LIMIT 1')->fetch();
$firm_id = $firm['id'] ?? null;
$firm_tan = $firm['tan'] ?? '';

// Get current FY and quarter
$today = date('Y-m-d');
[$curFy, $curQ] = fy_quarter_from_date($today);

// Get parameters
$fy = $_GET['fy'] ?? $curFy;
$quarter = $_GET['quarter'] ?? $curQ;
$tab = $_GET['tab'] ?? 'tds'; // tds or tcs

// Initialize API
$apiKey = getenv('SANDBOX_API_KEY') ?? '';
$api = new SandboxTDSAPI($apiKey, '', function($msg) { /* logging */ });

// Process actions
$actionResult = null;
$action = $_POST['action'] ?? '';

// Handle Analytics API actions
if (!empty($action)) {
    try {
        switch ($action) {
            case 'submit_tds_analytics':
                // Submit TDS form for risk analysis
                $form_type = $_POST['form'] ?? '26Q';
                $form_content = $_POST['form_content'] ?? '';

                if (empty($form_content)) {
                    throw new Exception("Form content is required");
                }

                if (!in_array($form_type, ['24Q', '26Q', '27Q'])) {
                    throw new Exception("Invalid form type: $form_type");
                }

                $result = $api->submitTDSAnalyticsJob(
                    $firm_tan,
                    $quarter,
                    $form_type,
                    $fy,
                    $form_content
                );

                if ($result['error']) {
                    throw new Exception($result['error']);
                }

                $actionResult = [
                    'status' => 'success',
                    'message' => "Analytics job submitted for $form_type. Job ID: {$result['job_id']}",
                    'data' => [
                        'job_id' => $result['job_id'],
                        'form' => $result['form'],
                        'status' => $result['job_status'],
                        'created_at' => $result['created_at']
                    ]
                ];
                break;

            case 'submit_tcs_analytics':
                // Submit TCS form for risk analysis
                $form_content = $_POST['form_content'] ?? '';

                if (empty($form_content)) {
                    throw new Exception("Form content is required");
                }

                $result = $api->submitTCSAnalyticsJob(
                    $firm_tan,
                    $quarter,
                    $fy,
                    $form_content
                );

                if ($result['error']) {
                    throw new Exception($result['error']);
                }

                $actionResult = [
                    'status' => 'success',
                    'message' => "Analytics job submitted for Form 27EQ. Job ID: {$result['job_id']}",
                    'data' => [
                        'job_id' => $result['job_id'],
                        'form' => $result['form'],
                        'status' => $result['job_status'],
                        'created_at' => $result['created_at']
                    ]
                ];
                break;

            case 'check_analytics_status':
                // Check status of analytics job
                $job_id = $_POST['job_id'] ?? '';
                $job_type = $_POST['job_type'] ?? 'tds';

                if (empty($job_id)) {
                    throw new Exception("Job ID is required");
                }

                $result = $job_type === 'tds'
                    ? $api->pollTDSAnalyticsJob($job_id)
                    : $api->pollTCSAnalyticsJob($job_id);

                if ($result['error']) {
                    throw new Exception($result['error']);
                }

                $actionResult = [
                    'status' => 'success',
                    'message' => 'Job Status: ' . ucfirst($result['status']),
                    'data' => [
                        'job_id' => $job_id,
                        'status' => $result['status'],
                        'risk_level' => $result['risk_level'] ?? 'N/A',
                        'risk_score' => $result['risk_score'] ?? 'N/A',
                        'potential_notices' => $result['potential_notices_count'] ?? 0,
                        'report_url' => $result['report_url'] ?? null,
                        'issues' => $result['issues'] ?? []
                    ]
                ];
                break;

            case 'fetch_tds_jobs':
                // Fetch historical TDS analytics jobs
                $result = $api->fetchTDSAnalyticsJobs(
                    $firm_tan,
                    $quarter,
                    $_POST['form'] ?? null,
                    $fy,
                    $_POST['page_size'] ?? 50
                );

                if ($result['error']) {
                    throw new Exception($result['error']);
                }

                $actionResult = [
                    'status' => 'success',
                    'message' => "Found {$result['count']} analytics jobs",
                    'data' => [
                        'count' => $result['count'],
                        'jobs' => $result['jobs'] ?? [],
                        'has_more' => $result['has_more'] ?? false
                    ]
                ];
                break;

            case 'fetch_tcs_jobs':
                // Fetch historical TCS analytics jobs
                $result = $api->fetchTCSAnalyticsJobs(
                    $firm_tan,
                    $quarter,
                    $fy,
                    $_POST['page_size'] ?? 50
                );

                if ($result['error']) {
                    throw new Exception($result['error']);
                }

                $actionResult = [
                    'status' => 'success',
                    'message' => "Found {$result['count']} TCS jobs",
                    'data' => [
                        'count' => $result['count'],
                        'jobs' => $result['jobs'] ?? [],
                        'has_more' => $result['has_more'] ?? false
                    ]
                ];
                break;

            default:
                throw new Exception("Unknown action: $action");
        }
    } catch (Exception $e) {
        $actionResult = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

?>

<div class="container mt-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-2">Risk Analytics & Potential Notices</h1>
            <p class="text-muted">Analyze TDS/TCS returns for potential tax notices and compliance risks</p>
        </div>
        <div class="col-md-4 text-right">
            <div class="alert alert-info mb-0">
                <strong>TAN:</strong> <?php echo htmlspecialchars($firm_tan); ?><br>
                <strong>FY:</strong> <?php echo htmlspecialchars($fy); ?> | <strong>Q:</strong> <?php echo htmlspecialchars($quarter); ?>
            </div>
        </div>
    </div>

    <!-- Action Result Messages -->
    <?php if ($actionResult): ?>
        <div class="alert alert-<?php echo $actionResult['status'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <strong><?php echo ucfirst($actionResult['status']); ?>:</strong> <?php echo htmlspecialchars($actionResult['message']); ?>
            <?php if (isset($actionResult['data']) && !empty($actionResult['data'])): ?>
                <details class="mt-2">
                    <summary style="cursor: pointer; text-decoration: underline;">Details</summary>
                    <pre class="mt-2"><code><?php echo json_encode($actionResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></code></pre>
                </details>
            <?php endif; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'tds' ? 'active' : ''; ?>" href="?tab=tds" role="tab">
                <i class="fas fa-chart-bar"></i> TDS Analytics
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'tcs' ? 'active' : ''; ?>" href="?tab=tcs" role="tab">
                <i class="fas fa-chart-bar"></i> TCS Analytics
            </a>
        </li>
    </ul>

    <!-- TDS Analytics Tab -->
    <?php if ($tab === 'tds'): ?>
    <div class="tab-content">
        <div class="row">
            <!-- Submit TDS Form -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-upload"></i> Submit Form for Risk Analysis</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="submit_tds_analytics">

                            <div class="form-group">
                                <label>Form Type</label>
                                <select name="form" class="form-control" required>
                                    <option value="24Q">24Q - Salary TDS</option>
                                    <option value="26Q" selected>26Q - Non-Salary TDS</option>
                                    <option value="27Q">27Q - NRI TDS</option>
                                </select>
                                <small class="form-text text-muted">
                                    24Q: Salary/Wages | 26Q: Non-Salary Payments | 27Q: NRI Payments
                                </small>
                            </div>

                            <div class="form-group">
                                <label>Form Content (XML or JSON)</label>
                                <textarea name="form_content" class="form-control" rows="6" placeholder="Paste form data here" required></textarea>
                                <small class="form-text text-muted">
                                    Paste the complete form content in XML or JSON format. It will be base64 encoded before submission.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i> Submit for Analysis
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Check Job Status -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-spinner"></i> Check Job Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="check_analytics_status">
                            <input type="hidden" name="job_type" value="tds">

                            <div class="form-group">
                                <label>Job ID</label>
                                <input type="text" name="job_id" class="form-control" placeholder="e.g., job-uuid-here" required>
                                <small class="form-text text-muted">
                                    The job ID returned when you submitted a form
                                </small>
                            </div>

                            <button type="submit" class="btn btn-info btn-block">
                                <i class="fas fa-search"></i> Check Status
                            </button>
                        </form>

                        <hr>

                        <div class="alert alert-info">
                            <strong>Job Status Lifecycle:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>created</strong> - Job created, waiting to process</li>
                                <li><strong>queued</strong> - Job in processing queue</li>
                                <li><strong>processing</strong> - Currently analyzing form</li>
                                <li><strong>succeeded</strong> - Analysis complete, results available</li>
                                <li><strong>failed</strong> - Analysis failed, check error message</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job History -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Job History</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form-inline mb-3">
                            <input type="hidden" name="action" value="fetch_tds_jobs">

                            <div class="form-group mr-2">
                                <label class="mr-2">Form:</label>
                                <select name="form" class="form-control">
                                    <option value="">All Forms</option>
                                    <option value="24Q">24Q</option>
                                    <option value="26Q">26Q</option>
                                    <option value="27Q">27Q</option>
                                </select>
                            </div>

                            <div class="form-group mr-2">
                                <label class="mr-2">Page Size:</label>
                                <input type="number" name="page_size" class="form-control" value="50" min="1" max="100" style="width: 80px;">
                            </div>

                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Fetch History
                            </button>
                        </form>

                        <p class="text-muted">Submit a form or check status to see job history</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TCS Analytics Tab -->
    <?php if ($tab === 'tcs'): ?>
    <div class="tab-content">
        <div class="row">
            <!-- Submit TCS Form -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-upload"></i> Submit Form 27EQ for Risk Analysis</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="submit_tcs_analytics">

                            <div class="form-group">
                                <label>Form Type</label>
                                <div class="form-control-plaintext">
                                    <strong>Form 27EQ</strong> - TCS (Tax Collected at Source)
                                </div>
                                <small class="form-text text-muted">
                                    Used for Tax Collected at Source collections and compliance
                                </small>
                            </div>

                            <div class="form-group">
                                <label>Form Content (XML or JSON)</label>
                                <textarea name="form_content" class="form-control" rows="6" placeholder="Paste Form 27EQ data here" required></textarea>
                                <small class="form-text text-muted">
                                    Paste the complete Form 27EQ content in XML or JSON format
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i> Submit for Analysis
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Check TCS Job Status -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-spinner"></i> Check Job Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="check_analytics_status">
                            <input type="hidden" name="job_type" value="tcs">

                            <div class="form-group">
                                <label>Job ID</label>
                                <input type="text" name="job_id" class="form-control" placeholder="e.g., job-uuid-here" required>
                                <small class="form-text text-muted">
                                    The job ID returned when you submitted Form 27EQ
                                </small>
                            </div>

                            <button type="submit" class="btn btn-info btn-block">
                                <i class="fas fa-search"></i> Check Status
                            </button>
                        </form>

                        <hr>

                        <div class="alert alert-info">
                            <strong>Processing Time:</strong> 30 minutes - 2 hours
                            <br><strong>Risk Score:</strong> 0-100 (higher = more risk)
                            <br><strong>Risk Levels:</strong> Low, Medium, High
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TCS Job History -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Job History</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form-inline mb-3">
                            <input type="hidden" name="action" value="fetch_tcs_jobs">

                            <div class="form-group mr-2">
                                <label class="mr-2">Page Size:</label>
                                <input type="number" name="page_size" class="form-control" value="50" min="1" max="100" style="width: 80px;">
                            </div>

                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Fetch History
                            </button>
                        </form>

                        <p class="text-muted">Submit Form 27EQ or check status to see job history</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Information Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> About Risk Analytics</h5>
                </div>
                <div class="card-body">
                    <p>
                        The Analytics API analyzes your TDS/TCS forms to identify potential risks and tax notices before filing.
                        This helps you address compliance issues proactively.
                    </p>

                    <div class="row">
                        <div class="col-md-6">
                            <h6><strong>What it does:</strong></h6>
                            <ul>
                                <li>Analyzes form structure and data validity</li>
                                <li>Identifies compliance gaps</li>
                                <li>Predicts potential tax notices</li>
                                <li>Provides risk scoring (0-100)</li>
                                <li>Suggests remediation plans</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><strong>Processing:</strong></h6>
                            <ul>
                                <li><strong>TDS Forms:</strong> 24Q, 26Q, 27Q</li>
                                <li><strong>TCS Forms:</strong> 27EQ</li>
                                <li><strong>Time:</strong> 30 min - 2 hours</li>
                                <li><strong>Status:</strong> Poll using Job ID</li>
                                <li><strong>Output:</strong> Risk report with issues</li>
                            </ul>
                        </div>
                    </div>

                    <p class="mt-3 mb-0">
                        <strong>Documentation:</strong> See <code>SANDBOX_ANALYTICS_API_REFERENCE.md</code> for complete API reference and examples.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include __DIR__.'/_layout_bottom.php'; ?>
