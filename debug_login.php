<?php
// Enable debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include core files
include_once("config.inc.php");

echo "<h1>Login Debug Page</h1>";

// Check if TOKENID is defined
echo "<h2>Configuration Check</h2>";
echo "<pre>";
echo "SITEURL: " . (defined('SITEURL') ? SITEURL : "Not defined") . "
";
echo "MXSET['TOKENID']: " . (isset($MXSET["TOKENID"]) ? $MXSET["TOKENID"] : "Not defined") . "
";
echo "</pre>";

// Check session
echo "<h2>Session Information</h2>";
echo "<pre>";
echo "session_id: " . session_id() . "
";
echo "SESSION contents: 
";
print_r($_SESSION);
echo "</pre>";

// Direct login code
echo "<h2>Manual Login Form</h2>";
echo "<form method='post' action='debug_login.php'>";
echo "<div>Username: <input type='text' name='userName' value='xadmin'></div>";
echo "<div>Password: <input type='password' name='userPass'></div>";
echo "<div><input type='submit' name='submit' value='Login'></div>";
echo "<input type='hidden' name='xAction' value='debug_login'>";
echo "</form>";

// Process the form submission
if (isset($_POST['xAction']) && $_POST['xAction'] == 'debug_login') {
    echo "<h2>Login Attempt</h2>";
    
    // Set token directly
    $_SESSION[SITEURL]['CSRF_TOKEN'] = session_id();
    
    // Include required files
    include_once("core/core.inc.php");
    include_once("xadmin/core-admin/settings.inc.php");
    
    // Manual login function
    function debug_login($username, $password) {
        global $MXADMIN;
        if ($username == $MXADMIN["user"] && md5($password) == $MXADMIN["pass"]) {
            // Set token
            global $MXSET;
            $sesid = session_id();
            $_SESSION[SITEURL][$MXSET["TOKENID"]] = $sesid;
            
            // Set session variables
            $_SESSION[SITEURL]['LOGINTYPE'] = "backend";
            $_SESSION[SITEURL]['MXID'] = "SUPER";
            $_SESSION[SITEURL]['MXNAME'] = "Maxdigi Solutions";
            $_SESSION[SITEURL]['MXROLE'] = "SUPER";
            
            return true;
        }
        return false;
    }
    
    $result = debug_login($_POST['userName'], $_POST['userPass']);
    if ($result) {
        echo "<p style='color:green'>Login successful!</p>";
        echo "<p>Session data:</p>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        echo "<p><a href='/xadmin/'>Go to Admin Panel</a></p>";
    } else {
        echo "<p style='color:red'>Login failed!</p>";
    }
}
?>