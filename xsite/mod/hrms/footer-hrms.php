        </div>
    </main>

    <?php if (isHRMSLoggedIn()): ?>
    <!-- Mobile Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="<?php echo SITEURL; ?>/hrms/home/" class="bottom-nav-item <?php echo (strpos($TPL->pageUri ?? '', 'hrms/home') !== false) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Home</span>
        </a>
        <a href="<?php echo SITEURL; ?>/hrms/attendance/" class="bottom-nav-item <?php echo (strpos($TPL->pageUri ?? '', 'hrms/attendance') !== false && strpos($TPL->pageUri ?? '', 'hrms/reports') === false) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>Attendance</span>
        </a>
        <a href="<?php echo SITEURL; ?>/hrms/leave/" class="bottom-nav-item <?php echo (strpos($TPL->pageUri ?? '', 'hrms/leave') !== false) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
            <span>Leave</span>
        </a>
        <a href="<?php echo SITEURL; ?>/hrms/reports/" class="bottom-nav-item <?php echo (strpos($TPL->pageUri ?? '', 'hrms/reports') !== false) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Reports</span>
        </a>
        <a href="<?php echo SITEURL; ?>/hrms/salary/" class="bottom-nav-item <?php echo (strpos($TPL->pageUri ?? '', 'hrms/salary') !== false) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            <span>Salary</span>
        </a>
    </nav>
    <?php endif; ?>

    <!-- PWA Install Prompt Banner -->
    <div id="pwaInstallBanner" class="pwa-install-banner" style="display: none;">
        <div class="pwa-install-content">
            <div class="pwa-install-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
            </div>
            <div class="pwa-install-text">
                <strong>Install BES HRMS</strong>
                <span>Add to home screen for quick access</span>
            </div>
        </div>
        <div class="pwa-install-actions">
            <button type="button" class="pwa-install-btn" onclick="installPWA()">Install</button>
            <button type="button" class="pwa-dismiss-btn" onclick="dismissPWABanner()">Not now</button>
        </div>
    </div>

    <style>
    .pwa-install-banner {
        position: fixed;
        bottom: 80px;
        left: 16px;
        right: 16px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        padding: 16px;
        z-index: 1000;
        animation: slideUp 0.3s ease;
    }
    @media (min-width: 768px) {
        .pwa-install-banner {
            bottom: 24px;
            left: auto;
            right: 24px;
            max-width: 360px;
        }
    }
    @keyframes slideUp {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .pwa-install-content {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    .pwa-install-icon {
        width: 48px;
        height: 48px;
        background: var(--blue-100);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .pwa-install-icon svg {
        width: 24px;
        height: 24px;
        color: var(--blue-600);
    }
    .pwa-install-text {
        display: flex;
        flex-direction: column;
    }
    .pwa-install-text strong {
        font-size: 15px;
        color: var(--gray-900);
    }
    .pwa-install-text span {
        font-size: 13px;
        color: var(--gray-500);
    }
    .pwa-install-actions {
        display: flex;
        gap: 8px;
    }
    .pwa-install-btn {
        flex: 1;
        padding: 10px 16px;
        background: var(--blue-600);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .pwa-install-btn:hover {
        background: var(--blue-700);
    }
    .pwa-dismiss-btn {
        padding: 10px 16px;
        background: var(--gray-100);
        color: var(--gray-600);
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }
    .pwa-dismiss-btn:hover {
        background: var(--gray-200);
    }
    /* Offline indicator */
    .offline-indicator {
        position: fixed;
        top: 64px;
        left: 0;
        right: 0;
        background: #fef3c7;
        color: #92400e;
        text-align: center;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 500;
        z-index: 99;
        display: none;
    }
    .offline-indicator.show {
        display: block;
    }
    /* Update available toast */
    .pwa-update-toast {
        position: fixed;
        bottom: 100px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--gray-900);
        color: #fff;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        display: none;
        gap: 12px;
        align-items: center;
        z-index: 1001;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .pwa-update-toast.show {
        display: flex;
    }
    .pwa-update-toast button {
        background: var(--blue-500);
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    </style>

    <!-- Offline Indicator -->
    <div id="offlineIndicator" class="offline-indicator">
        <svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="1" y1="1" x2="23" y2="23"/>
            <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
            <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
        </svg>
        You're offline. Some features may be limited.
    </div>

    <!-- Update Toast -->
    <div id="pwaUpdateToast" class="pwa-update-toast">
        <span>A new version is available</span>
        <button onclick="updatePWA()">Update</button>
    </div>

    <script>
    function hrmsLogout() {
        if (confirm('Are you sure you want to logout?')) {
            $.ajax({
                url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
                type: 'POST',
                data: { xAction: 'logout' },
                dataType: 'json',
                success: function(res) {
                    window.location.href = SITEURL + '/hrms/login/';
                }
            });
        }
    }

    // PWA Service Worker Registration
    let deferredPrompt = null;
    let swRegistration = null;

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                swRegistration = await navigator.serviceWorker.register('/hrms-sw.js', { scope: '/hrms/' });
                console.log('HRMS SW registered:', swRegistration.scope);

                // Check for updates
                swRegistration.addEventListener('updatefound', () => {
                    const newWorker = swRegistration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            document.getElementById('pwaUpdateToast').classList.add('show');
                        }
                    });
                });
            } catch (err) {
                console.log('HRMS SW registration failed:', err);
            }
        });
    }

    // PWA Install Prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        // Show install banner if not dismissed recently
        const dismissed = localStorage.getItem('pwa_banner_dismissed');
        const dismissedTime = dismissed ? parseInt(dismissed) : 0;
        const dayAgo = Date.now() - (24 * 60 * 60 * 1000);

        if (!dismissed || dismissedTime < dayAgo) {
            setTimeout(() => {
                document.getElementById('pwaInstallBanner').style.display = 'block';
            }, 3000);
        }
    });

    function installPWA() {
        if (!deferredPrompt) return;

        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((result) => {
            if (result.outcome === 'accepted') {
                console.log('PWA installed');
            }
            deferredPrompt = null;
            document.getElementById('pwaInstallBanner').style.display = 'none';
        });
    }

    function dismissPWABanner() {
        document.getElementById('pwaInstallBanner').style.display = 'none';
        localStorage.setItem('pwa_banner_dismissed', Date.now().toString());
    }

    function updatePWA() {
        if (swRegistration && swRegistration.waiting) {
            swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
        }
        window.location.reload();
    }

    // Listen for SW controller change
    if (navigator.serviceWorker) {
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            window.location.reload();
        });
    }

    // Offline/Online Detection
    function updateOnlineStatus() {
        const indicator = document.getElementById('offlineIndicator');
        if (navigator.onLine) {
            indicator.classList.remove('show');
        } else {
            indicator.classList.add('show');
        }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();

    // Track app installed
    window.addEventListener('appinstalled', () => {
        console.log('BES HRMS installed');
        deferredPrompt = null;
        document.getElementById('pwaInstallBanner').style.display = 'none';
    });
    </script>
</body>
</html>
