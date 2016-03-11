<?php
/**
 * Created by michael on 3/2/16.
 */

namespace copro;

require_once 'utils.php';
require_once 'connection.php';
require_once 'Project_Database_Interface-class.php';

global $api;
global $run_dates; // from crazy-settings
global $projects;
global $investors;
global $all_investors;
global $all_projects;

$collisions = '';
if (! MOCK_RUN) {
    $pdo = \Connection\get_connection();
    $db = new Investor_Project_PHP_Handler($pdo);

    // STEP 1 // Get products and build projects class.
    $section_id = (int)\QUERY_VALUE_SECTION_ID ? (int)\QUERY_VALUE_SECTION_ID : 0;
    if ($section_id > 0) {
        $projects = $db->load_projects($section_id);

        // STEP 2 // Get interested investor ids list.
        $db->build_investor_list_from_projects();

        // STEP 3 // Get investors and build investors class.
        $db->instantiate_investor_objects();

        // STEP 4 // Go through projects foreach and push ids.
        $db->add_project_references_to_investors();


        // STEP 5 // Use these in the application like we did with mock data objects.
        $all_investors = $db->get_investors();
        $all_projects = $db->get_projects();
    } else {
        // don't have data to get single section!
        $all_investors = array();
        $all_projects = array();
    }

} else {
    // MOCK RUN

    $all_projects = array();
    $all_investors = array();
    for ($i = 0;$i < count($projects); $i++) {
        // This is the same as just renaming $projects. It's placeholder til
        // I write a Project Object
        $all_projects[] = new \Project($projects[$i]);
    }
    $projects_count = count($all_projects);

    foreach ( $investors as $investor ) {
        $_investor = new \Investor( $investor );
        $num_interests = (int)rand(9, $projects_count - 1);
        while($num_interests--) {
            $_investor->add_project($all_projects[(int)rand(0, $projects_count-1)]);
        }
        $all_investors[] = $_investor;

    }


}

unset( $projects );
unset( $investors );
$shed = new Scheduler($all_investors, $all_projects, $run_dates,  $api, unserialize(SCHEDULE_SETTINGS) );

// Delete all the calendars (this is more for testing).
//$calendars_resp = $api->delete_all_calendars();
$calendars_resp = $api->get_calendars();
if (!is_array($calendars_resp) || !isset($calendars_resp['value'])) {
    echo "<h1 class='bg-danger'>You need to login again!</h1>";
    echo "<form method='post' action='index.php'><input type='hidden' name='unset-session-creds' value='1'><button type='submit' class='btn btn-large btn-danger'>Reset the session</button></form>";
    exit();
}
\Util\show_template('projects-investors-accordion.php');


function do_step($step_method, $return_table=0) {
    global $shed;
    if ( method_exists($shed ,$step_method ) ) {
        $shed->$step_method();
        if ($return_table) {
            return $shed->show_time_lines();
        }
        return true;
    }
    throw new \ErrorException("That is not a valid step.. Steps must be methods on the Scheduler class.");
}


function export_meetings_to_outlook() {
    global $shed;
    return $shed->export_meetings_to_calendar(\Util\get('calendar-id'), 1);
}
