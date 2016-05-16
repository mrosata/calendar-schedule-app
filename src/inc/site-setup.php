<?php
/**
 *  Site Setup
 */
namespace Setting;

require_once 'utils.php';
require_once 'api_post_settings.php';

// WAS THIS AN API CALL?
define('API_EXPORT', \Util\post('export') ? true : false);
define('API_IMPORT', \Util\post('import') ? true : false);
define('FINALIZE', \Util\post('finalize') ? true : false);
define('EVENT_ID', \Util\post('dates-event-id'));

define('SITE_TITLE', 'Calendar Schedule App');



/**
 * SETTINGS FOR THE FORM CONTROLS
 */
define( 'DEFAULT_CALENDAR_NAME', '' );
define( 'SHOW_EMAIL_COLLISIONS', 1 );
define( 'DEFAULT_START_DT', date( 'Y-m-d 08:00 \A\M', strtotime( '+ 1 day' ) ) );
define( 'DEFAULT_DAILY_HOURS', 8 );
define( 'DEFAULT_MEETING_MINS', 20 );
define( 'MAX_DAILY_HOURS', 16 );
define( 'MIN_MEETING_MINS', 5 );
define( 'MAX_MEETING_MINS', 60 );
define( 'MIN_DAILY_HOURS', 1 );
define( 'DEFAULT_BREAK_START', serialize(array('11:00')) );
define( 'DEFAULT_BREAK_END', serialize(array('11:30')) );

/**
 * SETTINGS FOR THE SCHEDULER MEETINGS
 */
$errors = null;

require_once 'connection.php';
require_once 'markup-to-html-class.php';
require_once 'Mock_Content-class.php';
require_once 'Project_Investor-classes.php';
require_once 'Scheduler_Meetings-classes.php';
require_once 'Project_Database_Interface-class.php';
require_once 'crazy-settings.php';
require_once 'functions.php';

