<?php
// Security headers
header("X-Content-Type-Options: nosniff", false);
header("X-Frame-Options: DENY", false);
header("Content-Language: en-IN", false);

// Check login status for non-login pages
$currentPage = $TPL->pageUri ?? '';
if (strpos($currentPage, 'hrms/login') === false && !isHRMSLoggedIn()) {
    header('Location: ' . SITEURL . '/hrms/login/');
    exit;
}
?>
<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS Portal - Bombay Engineering Syndicate</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="BES HRMS">
    <meta name="application-name" content="BES HRMS">
    <meta name="msapplication-TileColor" content="#2563eb">
    <meta name="msapplication-config" content="none">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/xsite/mod/hrms/manifest.json">

    <!-- PWA Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/xsite/mod/hrms/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/xsite/mod/hrms/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/xsite/mod/hrms/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/xsite/mod/hrms/icons/icon-128x128.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/xsite/mod/hrms/icons/icon-96x96.png">
    <link rel="apple-touch-icon" href="/xsite/mod/hrms/icons/icon-192x192.png">

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo UPLOADURL; ?>/setting/<?php echo $MXSET['FAVICON'] ?? 'favicon.png'; ?>" type="image/x-icon">
    <link rel="icon" href="<?php echo UPLOADURL; ?>/setting/<?php echo $MXSET['FAVICON'] ?? 'favicon.png'; ?>" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Core JS -->
    <script src="<?php echo mxGetUrl(SITEURL . '/' . LIBDIR . '/js/jquery-3.3.1.min.js'); ?>"></script>
    <script src="<?php echo mxGetUrl(COREURL . '/config.js.php', getJsVars()); ?>"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            /* Colors */
            --blue-50: #eff6ff;
            --blue-100: #dbeafe;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --green-500: #22c55e;
            --green-100: #dcfce7;
            --red-500: #ef4444;
            --red-100: #fee2e2;
            --yellow-500: #eab308;
            --yellow-100: #fef9c3;

            /* Semantic Colors (for dashboard/attendance pages) */
            --accent: #2563eb;
            --accent-muted: #3b82f6;
            --accent-light: #dbeafe;
            --success: #22c55e;
            --success-bg: #dcfce7;
            --error: #ef4444;
            --error-bg: #fee2e2;
            --warning: #eab308;
            --warning-bg: #fef9c3;
            --info: #3b82f6;
            --info-bg: #dbeafe;

            /* Backgrounds */
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-card: #ffffff;
            --bg-elevated: #f3f4f6;
            --bg-card-hover: #f9fafb;
            --card-bg: #ffffff;
            --surface-bg: #f3f4f6;
            --hover-bg: #f9fafb;
            --accent-hover: #1d4ed8;

            /* Text */
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --text-muted: #9ca3af;
            --text-disabled: #d1d5db;

            /* Borders */
            --border: #e5e7eb;
            --border-light: #f3f4f6;
            --border-color: #e5e7eb;

            /* Spacing */
            --space-xs: 4px;
            --space-sm: 8px;
            --space-md: 12px;
            --space-lg: 16px;
            --space-xl: 24px;
            --space-2xl: 32px;

            /* Radius */
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;

            /* Fonts */
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.5;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
            padding: 0 20px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .header-logo {
            width: 40px;
            height: 40px;
            background: var(--blue-600);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
        }

        .header-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .header-subtitle {
            font-size: 12px;
            color: var(--gray-500);
        }

        .header-nav {
            display: none;
            gap: 4px;
        }

        @media (min-width: 768px) {
            .header-nav { display: flex; }
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.15s;
        }

        .nav-link:hover {
            background: var(--gray-100);
            color: var(--gray-900);
        }

        .nav-link.active {
            background: var(--blue-50);
            color: var(--blue-700);
        }

        .nav-link svg {
            width: 18px;
            height: 18px;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info {
            text-align: right;
            display: none;
        }

        @media (min-width: 640px) {
            .user-info { display: block; }
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .user-role {
            font-size: 11px;
            color: var(--blue-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--blue-600);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
        }

        .logout-btn {
            width: 40px;
            height: 40px;
            background: var(--gray-100);
            border: none;
            border-radius: 8px;
            color: var(--gray-500);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }

        @media (min-width: 768px) {
            .logout-btn { display: flex; }
        }

        .logout-btn:hover {
            background: var(--red-100);
            color: var(--red-500);
        }

        .logout-btn svg {
            width: 20px;
            height: 20px;
        }

        /* Main Content */
        .main {
            padding: 24px 20px;
            padding-bottom: 100px;
        }

        @media (min-width: 768px) {
            .main {
                padding: 32px 24px;
                padding-bottom: 32px;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .page-header p {
            font-size: 14px;
            color: var(--gray-500);
        }

        /* Cards */
        .card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .card-body {
            padding: 20px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (min-width: 640px) {
            .stats-grid { grid-template-columns: repeat(4, 1fr); }
        }

        .stat-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 20px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .stat-icon svg {
            width: 20px;
            height: 20px;
        }

        .stat-icon.green { background: var(--green-100); color: var(--green-500); }
        .stat-icon.red { background: var(--red-100); color: var(--red-500); }
        .stat-icon.yellow { background: var(--yellow-100); color: var(--yellow-500); }
        .stat-icon.blue { background: var(--blue-100); color: var(--blue-600); }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
        }

        .btn svg {
            width: 18px;
            height: 18px;
        }

        .btn-primary {
            background: var(--blue-600);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--blue-700);
        }

        .btn-secondary {
            background: #fff;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-50);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-green, .badge.present { background: var(--green-100); color: var(--green-500); }
        .badge-red, .badge.absent { background: var(--red-100); color: var(--red-500); }
        .badge-yellow, .badge.late, .badge.pending { background: var(--yellow-100); color: var(--yellow-500); }
        .badge-blue, .badge.leave { background: var(--blue-100); color: var(--blue-600); }

        /* Form */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 15px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            outline: none;
            transition: all 0.15s;
        }

        .form-input:focus {
            border-color: var(--blue-500);
            box-shadow: 0 0 0 3px var(--blue-100);
        }

        /* List */
        .list-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-item:hover {
            background: var(--gray-50);
        }

        .list-avatar {
            width: 40px;
            height: 40px;
            background: var(--blue-100);
            color: var(--blue-600);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }

        .list-content {
            flex: 1;
            min-width: 0;
        }

        .list-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .list-subtitle {
            font-size: 13px;
            color: var(--gray-500);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            color: var(--gray-300);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 4px;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--gray-500);
        }

        /* Loading */
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px;
        }

        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--blue-600);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-100);
        }

        .table th {
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--gray-50);
        }

        .table td {
            font-size: 14px;
            color: var(--gray-700);
        }

        .table tbody tr:hover td {
            background: var(--gray-50);
        }

        /* Bottom Nav (Mobile) */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid var(--gray-200);
            padding: 8px 16px;
            padding-bottom: calc(8px + env(safe-area-inset-bottom));
            display: flex;
            justify-content: space-around;
            z-index: 100;
        }

        @media (min-width: 768px) {
            .bottom-nav { display: none; }
        }

        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
            color: var(--gray-400);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.15s;
        }

        .bottom-nav-item:hover,
        .bottom-nav-item.active {
            color: var(--blue-600);
        }

        .bottom-nav-item.active {
            background: var(--blue-50);
        }

        .bottom-nav-item svg {
            width: 24px;
            height: 24px;
        }

        .bottom-nav-item span {
            font-size: 10px;
            font-weight: 600;
        }

        /* Utility */
        .text-center { text-align: center; }
        .text-muted { color: var(--gray-500); }
        .mt-4 { margin-top: 16px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-6 { margin-bottom: 24px; }
    </style>
</head>
<body>
    <?php if (isHRMSLoggedIn()): ?>
    <!-- Header -->
    <header class="header">
        <a href="<?php echo SITEURL; ?>/hrms/home/" class="header-brand">
            <div class="header-logo">BES</div>
            <div>
                <div class="header-title">HRMS Portal</div>
                <div class="header-subtitle">Bombay Engineering</div>
            </div>
        </a>

        <nav class="header-nav">
            <a href="<?php echo SITEURL; ?>/hrms/home/" class="nav-link <?php echo (strpos($currentPage, 'hrms/home') !== false) ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Home
            </a>
            <a href="<?php echo SITEURL; ?>/hrms/attendance/" class="nav-link <?php echo (strpos($currentPage, 'hrms/attendance') !== false && strpos($currentPage, 'hrms/reports') === false) ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Attendance
            </a>
            <a href="<?php echo SITEURL; ?>/hrms/reports/" class="nav-link <?php echo (strpos($currentPage, 'hrms/reports') !== false) ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Reports
            </a>
            <a href="<?php echo SITEURL; ?>/hrms/salary/" class="nav-link <?php echo (preg_match('#hrms/salary/?$#', $currentPage)) ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Salary
            </a>
            <a href="<?php echo SITEURL; ?>/hrms/leave/" class="nav-link <?php echo (strpos($currentPage, 'hrms/leave') !== false) ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Leave
            </a>
            <?php if ($_SESSION['HRMS_IS_MANAGER'] ?? false): ?>
            <a href="<?php echo SITEURL; ?>/hrms/team/" class="nav-link <?php echo (strpos($currentPage, 'hrms/team') !== false) ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Team
            </a>
            <?php endif; ?>
            <a href="<?php echo SITEURL; ?>/hrms/documents/" class="nav-link <?php echo (strpos($currentPage, 'hrms/documents') !== false) ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Documents
            </a>
            <?php if (($_SESSION['HRMS_IS_HR_ADMIN'] ?? false) || ($_SESSION['HRMS_IS_ACCOUNTS'] ?? false)): ?>
            <a href="<?php echo SITEURL; ?>/hrms/salary-processing/" class="nav-link <?php echo (strpos($currentPage, 'hrms/salary-processing') !== false) ? 'active' : ''; ?>">
                <svg viewBox="0 0 16 16" fill="currentColor"><path d="M4 3.06h2.726c1.22 0 2.12.575 2.325 1.724H4v1.051h5.051C8.855 7.001 8 7.558 6.788 7.558H4v1.317L8.437 14h2.11L6.095 8.884h.855c2.316-.018 3.465-1.476 3.688-3.049H12V4.784h-1.345c-.08-.778-.357-1.335-.793-1.732H12V2H4z"/></svg>
                Payroll
            </a>
            <?php endif; ?>
        </nav>

        <div class="header-user">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['HRMS_USER_NAME'] ?? 'Employee'); ?></div>
                <div class="user-role"><?php
                    if (!empty($_SESSION['HRMS_IS_HR_ADMIN'])) echo 'HR Admin';
                    elseif (!empty($_SESSION['HRMS_IS_ACCOUNTS'])) echo 'Accounts';
                    elseif (!empty($_SESSION['HRMS_IS_MANAGER'])) echo 'Manager';
                    else echo 'Employee';
                ?></div>
            </div>
            <a href="<?php echo SITEURL; ?>/hrms/profile/" class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['HRMS_USER_NAME'] ?? 'E', 0, 1)); ?>
            </a>
            <button type="button" class="logout-btn" onclick="hrmsLogout()" title="Logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </button>
        </div>
    </header>
    <?php endif; ?>

    <main class="main">
        <div class="container">
