<?php
/**
 * Created by:
 *      Stayshine Web Development
 * @author  Michael Rosata
 * @url     www.stayshine.com
 * @date    3/15/16
 */
?><!doctype html>
<html class="no-js" lang="">
<head>
    <?php
    ini_set('display_errors', 1);
    ini_set('output_buffering', 1);
    error_reporting(E_ALL);
    define('ROOT_DIR', __DIR__);

    require_once( 'templates/html-head.php' );


    mb_internal_encoding("UTF-8");
    session_start();
    require_once 'inc/utils.php';
    $loggedIn = !is_null( \Util\session( 'access_token' ) ) && !is_null(\Util\session('user_email'));
    define( 'LOGGED_IN', $loggedIn );
    if ($_SERVER['SERVER_NAME'] == '0.0.0.0') {
        $redirectUri = 'http://localhost:'.$_SERVER['SERVER_PORT'].'/authorize.php';
        echo "<h4> Set to run on local server.. if you see this message then there is problem</h4>";
    } else {
        $redirectUri = "https://copro.ezadmin3.com/copro.co.il/originals/miker/dist/authorize.php";
    }
    define( 'REDIRECT_URI', $redirectUri );
    $calendar_name = \Util\post('calendar-name');

    /**
     * SETTINGS FOR THE SCHEDULER MEETINGS
     */

    require_once 'inc/ms365/oauth.php';
    require_once 'inc/ms365/outlook.php';
    require_once 'inc/ms365/Calendar_Meetings-class.php';


    $errors = null;
    $auth_url = \ms365\oAuthService::getLoginUrl($redirectUri);
    if (!LOGGED_IN) {
        \Util\show_login_url($auth_url);
        exit();
    }

    $api = new \ms365\Calendar_Meetings_API();
    if ( ! is_null( $calendar_name ) ) {
        $calendar_id = $api->get_calendar_by_name($calendar_name);
        $api->delete_calendar($calendar_id);
    }
    else {
        $api->delete_all_calendars();
    }


    ?>

    <script>
        window.setTimeout(function() {
            window.location.pathname = window.location.pathname.replace(/(delete_all\.php.*)$/gi, 'index.php');
        }, 3000);
    </script>
</head>
<body>
    <div class="inner-body container">
        <div class="container">
            <div class="row expanded">

                <?php
                \Util\show_template( 'nav-header.php' );
                ?>

            </div>

            <div class="row">
                <div class="col-sm-6">
                    <div class="panel">
                        <div class="panel-head">
                            <h3>Deleting all events from <?php echo (is_null($calendar_name) ? "all calendars" : $calendar_name )?></h3>
                        </div>
                        <div class="panel-body">
                            <p>
                                If you are not redirected, please <a href="index.php">click here</a>
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
