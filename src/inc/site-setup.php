<?php
/**
 *  Site Setup
 */
namespace Setting;

mb_internal_encoding("UTF-8");
session_start();
require_once 'utils.php';
define('SITE_TITLE', 'Calendar Schedule App');
if (isset($_POST['unset-session-creds']) && !!$_POST['unset-session-creds']) {
    session_unset();
}
$loggedIn = !is_null( \Util\session( 'access_token' ) ) && !is_null(\Util\session('user_email'));
define( 'LOGGED_IN', $loggedIn );
//$redirectUri = 'https://' . $_SERVER['HTTP_HOST'] . '/authorize.php';
$redirectUri = "https://copro.ezadmin3.com/copro.co.il/originals/miker/dist/authorize.php";
define( 'REDIRECT_URI', $redirectUri );
define('CALENDAR_ID', "AAMkADM4M2ViNmI3LWJlOTctNGQ4NC04YzRjLTY4MWJjNTlkMTJkNgBGAAAAAADp8HeT-HBRQIiWquf6PwPgBwA9rC9RjovZQ5X8qGCTgVHWAAAAAAEGAAA9rC9RjovZQ5X8qGCTgVHWAAACZJkoAAA=");
$calendar_name = \Util\get('calendar-name') ? \Util\get('calendar-name') : "CoPro Film Project Investor Meetings";
/**
 * SETTINGS FOR THE FORM CONTROLS
 */
define( 'DEFAULT_CALENDAR_NAME', $calendar_name );
define( 'SHOW_EMAIL_COLLISIONS', 1 );
define( 'DEFAULT_START_DT', date( 'm/d/Y 08:00 \A\M', strtotime( '+ 1 day' ) ) );
define( 'DEFAULT_DAILY_HOURS', 8 );
define( 'DEFAULT_MEETING_MINS', 10 );
define( 'MAX_DAILY_HOURS', 16 );
define( 'MIN_MEETING_MINS', 5 );
define( 'MAX_MEETING_MINS', 60 );
define( 'MIN_DAILY_HOURS', 1 );
define( 'DEFAULT_BREAK_START', serialize(array('11:00')) );
define( 'DEFAULT_BREAK_END', serialize(array('11:30')) );

/**
 * SETTINGS FOR THE SCHEDULER MEETINGS
 */

require_once 'ms365/oauth.php';
require_once 'ms365/outlook.php';
require_once 'ms365/site-functions.php';
require_once 'ms365/Calendar_Meetings-class.php';

$errors = null;
$auth_url = \ms365\oAuthService::getLoginUrl($redirectUri);
if (!LOGGED_IN) {
    \Util\show_login_url($auth_url);
    exit();
}
$api = new \ms365\Calendar_Meetings_API();


require_once 'connection.php';
require_once 'markup-to-html-class.php';
require_once 'Mock_Content-class.php';
require_once 'Project_Investor-classes.php';
require_once 'Scheduler_Meetings-classes.php';
require_once 'Project_Database_Interface-class.php';
require_once 'Outlook_Calendar-class.php';
require_once 'crazy-settings.php';
require_once 'functions.php';

