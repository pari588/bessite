<?php
//To fix clickjacking
header("Content-Security-Policy: frame-ancestors 'none'", false);
header("X-Frame-Options: DENY");
// End.
// To fetch header user info data.
$siteSettingInfo = getSiteInfo();
// End.
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bombay Engineering Syndicate</title>
    <!-- comman js files  -->
    <?php echo mxGetMeta(); ?>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(SITEURL . '/' . LIBDIR . '/js/jquery-3.3.1.min.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/config.js.php', getJsVars()); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/common.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/dialog.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/validate.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/form.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(ADMINURL . '/core-admin/js/common.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(ADMINURL . '/core-admin/js/inside.inc.js'); ?>"></script>
    <script src="<?php echo mxGetUrl(SITEURL . '/inc/js/x-site.inc.js'); ?>"></script>
    <!-- End. -->

    <meta property=’og:title’ content='Bombay Engineering Syndicate' />
    <meta property=’og:description’ content='Bombay Engineering Syndicate' />
    <meta property=’og:url’ content='<?php echo SITEURL; ?>' />
    <meta property="og:image" content="<?php echo SITEURL; ?>/images/moters.jpeg">
    <meta property="og:image:type" content="image/png">

    <!-- favicons Icons -->
    <link href="<?php echo UPLOADURL; ?>/setting/<?php echo $MXSET['FAVICON']; ?>" rel="SHORTCUT ICON" type="images/icon" />
    <link rel="shortcut icon" sizes="152x152" href="<?php echo UPLOADURL; ?>/setting/BEappicon.png"/>

    <!-- fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">


    <!-- template styles -->
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/reey-font/stylesheet.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/fontawesome/css/all.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/mellis.css') ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/mellis-responsive.css'); ?>" />
    <!-- MX STYLE SHEETS -->
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/style.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/device.css'); ?>" />

</head>

<body class="webapp">
    <div class="body-contener">
        <header>
            <img class="logo" src="<?php echo SITEURL . '/images/logo.png' ?>" alt="Bombay Engineering Syndicate">
            <?php if (isset($_SESSION['LEADUSERID']) || isset($_SESSION['LEAVEUSERID'])) { 
                $userNameKey = isset($_SESSION['LEADUSERID']) ? 'LEADUSERNAME' : 'LEAVEUSERNAME';
                ?>
            <div class="user-box">
                <span>Welcome,<strong>
                    <?php
                        $userName =  isset($_SESSION[$userNameKey]) ? $_SESSION[$userNameKey] : '';
                    ?>
                    <?php echo $userName; ?>
                </strong></span>
                <button type="button" class="thm-btn user-Logout">Logout</button>
            </div>
            <?php } ?>
        </header>