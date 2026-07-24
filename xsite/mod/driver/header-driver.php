<?php
// Security headers
header("X-Content-Type-Options: nosniff", false);
header("X-Frame-Options: DENY", false);
header("Content-Language: en-IN", false);
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-IN">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

    <title>BES Driver Portal</title>
    <meta name="description" content="Bombay Engineering Syndicate - Driver Attendance Portal" />

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#157bba" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="BES Driver" />

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo SITEURL; ?>/xsite/mod/driver/pwa/manifest.json" />

    <!-- App Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo SITEURL; ?>/xsite/mod/driver/pwa/icon-192.png" />
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo SITEURL; ?>/xsite/mod/driver/pwa/icon-512.png" />
    <link rel="apple-touch-icon" href="<?php echo SITEURL; ?>/xsite/mod/driver/pwa/icon-192.png" />

    <!-- Splash Screen for iOS -->
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

    <!-- Core JS files -->
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(SITEURL . '/' . LIBDIR . '/js/jquery-3.3.1.min.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/config.js.php', getJsVars()); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/common.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/dialog.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/validate.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/form.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(ADMINURL . '/core-admin/js/common.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(ADMINURL . '/core-admin/js/inside.inc.js'); ?>"></script>

    <!-- Dialog/Alert Styles -->
    <style>
        /* Alert/Dialog Popup Styles */
        div.mxdialog {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        div.mxdialog div.body {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            max-width: 320px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: popIn 0.3s ease;
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        div.mxdialog h2 {
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 12px 0;
            padding-right: 30px;
        }

        div.mxdialog .content {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #4b5563;
            line-height: 1.5;
        }

        div.mxdialog a.del {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 28px;
            height: 28px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background 0.2s;
        }

        div.mxdialog a.del:hover {
            background: #e5e7eb;
        }

        div.mxdialog a.del:before {
            content: '×';
            font-size: 20px;
            color: #6b7280;
            line-height: 1;
        }

        /* Loader styles */
        #mxloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        #mxloader #mxmsg {
            display: none;
        }

        #mxloader .progress {
            display: none;
        }

        #mxloader .spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        #mxloader .spinner div {
            width: 8px;
            height: 8px;
            background: #157bba;
            border-radius: 50%;
            animation: loaderBounce 1.4s ease-in-out infinite both;
            font-size: 0;
            color: transparent;
            overflow: hidden;
        }

        #mxloader .spinner .f1 { animation-delay: -0.32s; }
        #mxloader .spinner .f2 { animation-delay: -0.16s; }
        #mxloader .spinner .f3 { animation-delay: 0s; }
        #mxloader .spinner .f4 { animation-delay: 0.16s; }
        #mxloader .spinner .f5 { animation-delay: 0.32s; }
        #mxloader .spinner .f6 { animation-delay: 0.48s; }

        @keyframes loaderBounce {
            0%, 80%, 100% {
                transform: scale(0.6);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Pre-loader for buttons */
        #pre-loader {
            position: absolute;
            background: #157bba;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            z-index: 9999;
        }
    </style>

    <style>
        /* Reset body margin/padding for PWA */
        html, body {
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }

        /* Safe area for notched phones */
        body {
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        }
    </style>
</head>

<body>
