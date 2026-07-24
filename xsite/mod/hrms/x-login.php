<?php
// Redirect if already logged in
if (isHRMSLoggedIn()) {
    header('Location: ' . SITEURL . '/hrms/home/');
    exit;
}
?>

<style>
/* Override main container padding for login page */
.main { padding: 0; }
body { overflow: hidden; }

.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: linear-gradient(135deg, #eff6ff 0%, #f9fafb 100%);
}

.login-box {
    width: 100%;
    max-width: 400px;
}

.login-header {
    text-align: center;
    margin-bottom: 32px;
}

.login-logo {
    width: 64px;
    height: 64px;
    background: var(--blue-600);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.login-logo svg {
    width: 32px;
    height: 32px;
    color: #fff;
}

.login-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.login-header p {
    font-size: 14px;
    color: var(--gray-500);
}

.login-card {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    overflow: hidden;
}

@media (max-width: 400px) {
    .login-card {
        padding: 24px 16px;
    }
}

.login-steps {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-bottom: 32px;
}

.login-step {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: var(--gray-100);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-400);
}

.login-step.active {
    background: var(--blue-100);
    color: var(--blue-700);
}

.login-step.done {
    background: var(--green-100);
    color: var(--green-500);
}

.login-step-num {
    width: 20px;
    height: 20px;
    background: currentColor;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #fff;
}

.login-section {
    display: none;
}

.login-section.active {
    display: block;
}

.login-error {
    background: var(--red-100);
    border: 1px solid var(--red-500);
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: none;
    font-size: 14px;
    color: var(--red-500);
}

.login-error.show {
    display: block;
}

.login-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 8px;
}

.login-input {
    width: 100%;
    padding: 12px 16px;
    font-size: 15px;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    outline: none;
    margin-bottom: 20px;
}

.login-input:focus {
    border-color: var(--blue-500);
    box-shadow: 0 0 0 3px var(--blue-100);
}

.otp-inputs {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin: 24px 0;
    max-width: 100%;
}

.otp-input {
    width: 42px;
    height: 50px;
    text-align: center;
    font-size: 20px;
    font-weight: 600;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    outline: none;
    flex-shrink: 1;
    min-width: 0;
    padding: 0;
}

.otp-input:focus {
    border-color: var(--blue-500);
    box-shadow: 0 0 0 3px var(--blue-100);
}

@media (max-width: 360px) {
    .otp-inputs {
        gap: 4px;
    }
    .otp-input {
        width: 38px;
        height: 46px;
        font-size: 18px;
        border-radius: 6px;
    }
}

.login-btn {
    width: 100%;
    padding: 14px 24px;
    background: var(--blue-600);
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.login-btn:hover {
    background: var(--blue-700);
}

.login-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.login-btn svg {
    width: 18px;
    height: 18px;
}

.email-show {
    text-align: center;
    padding: 12px;
    background: var(--gray-100);
    border-radius: 8px;
    margin-bottom: 16px;
}

.email-show-label {
    font-size: 11px;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.email-show-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--blue-600);
}

.timer-text {
    text-align: center;
    margin-top: 20px;
    font-size: 13px;
    color: var(--gray-500);
}

.timer-value {
    font-weight: 600;
    color: var(--blue-600);
}

.resend-link {
    background: none;
    border: none;
    color: var(--blue-600);
    font-weight: 600;
    cursor: pointer;
    font-size: 13px;
}

.resend-link:hover {
    text-decoration: underline;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 20px;
    padding: 8px 12px;
    background: none;
    border: none;
    color: var(--gray-500);
    font-size: 13px;
    cursor: pointer;
    border-radius: 6px;
}

.back-link:hover {
    background: var(--gray-100);
    color: var(--gray-700);
}

.back-link svg {
    width: 16px;
    height: 16px;
}

.login-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--gray-200);
    font-size: 13px;
    color: var(--gray-500);
}

.login-footer a {
    color: var(--blue-600);
    text-decoration: none;
}

.login-footer a:hover {
    text-decoration: underline;
}

.success-box {
    text-align: center;
    padding: 32px;
}

.success-icon {
    width: 64px;
    height: 64px;
    background: var(--green-100);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.success-icon svg {
    width: 32px;
    height: 32px;
    color: var(--green-500);
}

.success-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.success-text {
    font-size: 14px;
    color: var(--gray-500);
}
</style>

<div class="login-page">
    <div class="login-box">
        <div class="login-header">
            <div class="login-logo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <h1>HRMS Portal</h1>
            <p>Sign in to access your employee dashboard</p>
        </div>

        <div class="login-card">
            <div class="login-steps">
                <div class="login-step active" id="step1Ind">
                    <span class="login-step-num">1</span>
                    Email
                </div>
                <div class="login-step" id="step2Ind">
                    <span class="login-step-num">2</span>
                    Verify
                </div>
            </div>

            <div class="login-error" id="loginError"></div>

            <!-- Step 1: Email -->
            <div class="login-section active" id="step1">
                <form id="emailForm">
                    <label class="login-label">Work Email Address</label>
                    <input type="email" id="userEmail" class="login-input" placeholder="yourname@company.com" required autofocus>
                    <button type="submit" class="login-btn">
                        Send OTP
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Step 2: OTP -->
            <div class="login-section" id="step2">
                <div class="email-show">
                    <div class="email-show-label">OTP sent to</div>
                    <div class="email-show-value" id="maskedEmail"></div>
                </div>

                <form id="otpForm">
                    <input type="hidden" id="otpEmail">
                    <div class="otp-inputs">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                    </div>
                    <button type="submit" class="login-btn" id="verifyBtn">
                        Verify & Login
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </button>
                </form>

                <div class="timer-text">
                    <span id="timerBox">Resend in <span class="timer-value" id="timerVal">10:00</span></span>
                    <button type="button" class="resend-link" id="resendBtn" style="display:none" onclick="resendOTP()">Resend OTP</button>
                </div>

                <div style="text-align:center">
                    <button type="button" class="back-link" onclick="backToEmail()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"/>
                            <polyline points="12 19 5 12 12 5"/>
                        </svg>
                        Change Email
                    </button>
                </div>
            </div>

            <!-- Step 3: Success -->
            <div class="login-section" id="step3">
                <div class="success-box">
                    <div class="success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <div class="success-title">Welcome Back!</div>
                    <div class="success-text">Redirecting to your dashboard...</div>
                </div>
            </div>

            <div class="login-footer">
                Having trouble? <a href="mailto:hr@bombayengg.net">Contact HR</a>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var timer, seconds = 600;

    // Email form
    $('#emailForm').on('submit', function(e) {
        e.preventDefault();
        hideError();
        var email = $('#userEmail').val().trim();
        if (!email) { showError('Enter your email'); return; }

        var btn = $(this).find('button');
        btn.prop('disabled', true).text('Sending...');

        $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
            xAction: 'sendOTP', userEmail: email
        }, function(res) {
            if (res.err == 0) {
                $('#otpEmail').val(email);
                $('#maskedEmail').text(res.email);
                showStep(2);
                startTimer();
            } else {
                showError(res.msg);
            }
        }, 'json').fail(function() {
            showError('Connection error');
        }).always(function() {
            btn.prop('disabled', false).html('Send OTP <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>');
        });
    });

    // OTP inputs
    $('.otp-input').on('input', function() {
        var v = $(this).val();
        if (!/^\d*$/.test(v)) { $(this).val(''); return; }
        if (v.length === 1) $(this).next('.otp-input').focus();
        if ($('.otp-input').filter(function() { return this.value !== ''; }).length === 6) {
            $('#otpForm').submit();
        }
    }).on('keydown', function(e) {
        if (e.key === 'Backspace' && $(this).val() === '') {
            $(this).prev('.otp-input').focus();
        }
    });

    $('.otp-input').first().on('paste', function(e) {
        e.preventDefault();
        var d = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        if (/^\d{6}$/.test(d)) {
            var arr = d.split('');
            $('.otp-input').each(function(i) { $(this).val(arr[i]); });
            $('#otpForm').submit();
        }
    });

    // OTP form
    $('#otpForm').on('submit', function(e) {
        e.preventDefault();
        hideError();
        var otp = '';
        $('.otp-input').each(function() { otp += $(this).val(); });
        if (otp.length !== 6) { showError('Enter 6-digit OTP'); return; }

        var btn = $('#verifyBtn');
        btn.prop('disabled', true).text('Verifying...');

        $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
            xAction: 'verifyOTP', userEmail: $('#otpEmail').val(), otp: otp
        }, function(res) {
            if (res.err == 0) {
                showStep(3);
                clearInterval(timer);
                setTimeout(function() {
                    window.location.href = res.redirect || SITEURL + '/hrms/home/';
                }, 1500);
            } else {
                showError(res.msg);
                $('.otp-input').val('').first().focus();
            }
        }, 'json').fail(function() {
            showError('Connection error');
        }).always(function() {
            btn.prop('disabled', false).html('Verify & Login <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><polyline points="20 6 9 17 4 12"/></svg>');
        });
    });

    window.backToEmail = function() {
        showStep(1);
        $('.otp-input').val('');
        clearInterval(timer);
        hideError();
    };

    window.resendOTP = function() {
        $('#userEmail').val($('#otpEmail').val());
        $('#emailForm').submit();
    };

    function showStep(n) {
        $('.login-section').removeClass('active');
        $('#step' + n).addClass('active');
        $('#step1Ind, #step2Ind').removeClass('active done');
        if (n === 1) { $('#step1Ind').addClass('active'); }
        if (n === 2) { $('#step1Ind').addClass('done'); $('#step2Ind').addClass('active'); $('.otp-input').first().focus(); }
        if (n === 3) { $('#step1Ind, #step2Ind').addClass('done'); }
    }

    function startTimer() {
        seconds = 600;
        updateTimer();
        $('#timerBox').show();
        $('#resendBtn').hide();
        timer = setInterval(function() {
            seconds--;
            if (seconds <= 0) {
                clearInterval(timer);
                $('#timerBox').hide();
                $('#resendBtn').show();
            } else {
                updateTimer();
            }
        }, 1000);
    }

    function updateTimer() {
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        $('#timerVal').text(m + ':' + (s < 10 ? '0' : '') + s);
    }

    function showError(msg) {
        $('#loginError').text(msg).addClass('show');
    }

    function hideError() {
        $('#loginError').removeClass('show');
    }
});
</script>
