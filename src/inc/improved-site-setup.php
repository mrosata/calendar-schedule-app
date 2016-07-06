<?php
/**
 * Improved Site Setup
 */

namespace copro;

require_once 'utils.php';
require_once 'improved_api_post_settings.php';

// WAS THIS AN API CALL?
define('API_EXPORT', \Util\post('export') ? true : false);
define('API_IMPORT', \Util\post('import') ? true : false);
define('FINALIZE', \Util\post('finalize') ? true : false);
define('EVENT_ID', \Util\post('dates-event-id'));

/**
 * SETTINGS FOR THE SCHEDULER MEETINGS
 */

require_once 'improved-functions.php';

