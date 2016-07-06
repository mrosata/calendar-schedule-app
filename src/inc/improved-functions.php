<?php
/**
 * Created by michael on 3/2/16.
 */

namespace copro;

require_once 'utils.php';
require_once 'Config.php';
require_once 'Times_Utils.php';
require_once 'Time_Interval.php';
require_once 'connection.php';
require_once 'Improved_Project_Database_Interface-class.php';
require_once 'Globals.php';
require_once 'Improved_Project_Investor-classes.php';
require_once 'Availability_Time_Manager.php';
require_once 'Scheduler_Advanced.php';

$running_push_date = !!Config::$push_date;
define( 'RUNNING_PUSH_DATE', $running_push_date );

Globals::check_for_api_post_request();
Globals::get_projects_and_investors_loaded();

// Get the running dates from the form
// Get the breaks from the form
$shed = new Scheduler_Advanced(Globals::$all_investors, Globals::$all_projects, Config::$running_dates, Config::$breaks);


