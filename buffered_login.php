<?php
// Start output buffering
ob_start();

// Include configuration
include_once("config.inc.php");

// Clear all session data
session_start();
session_destroy();
session_write_close();
setcookie(session_name(), '', time()-42000, '/');

// Start a new session
session_start();

// Set token manually
$_SESSION[SITEURL]['CSRF_TOKEN'] = session_id();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Buffered Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .login-form { max-width: 400px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; border: none; padding: 10px 15px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Admin Login</h2>
        
        <form method="post" action="buffered_login.php">
            <div class="form-group">
                <label for="userName">Username:</label>
                <input type="text" id="userName" name="userName" value="xadmin">
            </div>
            
            <div class="form-group">
                <label for="userPass">Password:</label>
                <input type="password" id="userPass" name="userPass">
            </div>
            
            <button type="submit" name="submit">Login</button>
            <input type="hidden" name="xAction" value="buffered_login">
        </form>
        
        <?php
        // Process login
        if (isset($_POST['xAction']) && $_POST['xAction'] == 'buffered_login') {
            include_once("xadmin/core-admin/settings.inc.php");
            
            // Get the admin credentials
            global $MXADMIN;
            
            if ($_POST['userName'] == $MXADMIN["user"] && md5($_POST['userPass']) == $MXADMIN["pass"]) {
                // Set token and session variables
                $_SESSION[SITEURL]['LOGINTYPE'] = "backend";
                $_SESSION[SITEURL]['MXID'] = "SUPER";
                $_SESSION[SITEURL]['MXNAME'] = "Maxdigi Solutions";
                $_SESSION[SITEURL]['MXROLE'] = "SUPER";
                
                echo "<p style='color:green'>Login successful! <a href='/xadmin/'>Go to Admin Panel</a></p>";
                echo "<pre>";
                print_r($_SESSION);
                echo "</pre>";
            } else {
                echo "<p style='color:red'>Invalid username or password.</p>";
            }
        }
        ?>
    </div>
</body>
</html>
<?php
// Flush the output buffer
ob_end_flush();
?>