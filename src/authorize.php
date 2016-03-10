<?php
/**
 * Created by michael on 3/5/16.
 */

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);
define('ROOT_DIR', __DIR__);
echo "Loading local MSO-365 Client Code...<br>";
require_once 'inc/ms365/oauth.php';

$auth_code = $_GET['code'];
echo "Authorization code:  {$auth_code}";
$_SESSION['session_state'] = isset($_GET['session_state']) ? $_GET['session_state'] : '';
//$redirectUri = 'https://copro.tqsoft.co.il/copro.co.il/originals/miker/dist/authorize.php';
$redirectUri = 'https://copro.ezadmin3.com/copro.co.il/originals/miker/dist/authorize.php';

$tokens = \ms365\oAuthService::getTokenFromAuthCode($auth_code, $redirectUri);

if (isset($tokens['access_token'])) {
    $_SESSION['logged_in_at'] = time();
    $_SESSION['access_token'] = $tokens['access_token'];

    // Get user email
    $user_email = \ms365\oAuthService::getLoginUrl( $tokens['id_token'] );
    $_SESSION['user_email'] = $user_email;

    // ET go home
    header( "Location: https://copro.ezadmin3.com/copro.co.il/originals/miker/dist/index.php" );

}
else {
    session_destroy();
    echo "<p>ERROR ERROR: {$tokens['error']}</p>";
}


?>

<p>Auth code: <?= $auth_code ?></p>
