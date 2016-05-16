<?php
/**
 * Created by michael on 3/2/16.
 */

namespace copro;

require_once 'utils.php';
require_once 'connection.php';
require_once 'Project_Database_Interface-class.php';


global $run_dates; // from crazy-settings
global $projects;
global $investors;
global $all_investors;
global $all_projects;

// RUNNING_PUSH_DATE set at the bottom of crazy-settings.php
$push_date_investors = array();

$collisions = '';
if (! MOCK_RUN) {
    $pdo = \Connection\get_connection();
    $db = new Investor_Project_PHP_Handler($pdo);
    
    
    // STEP 1 // Get products and build projects class.
    $section_id_array = \QUERY_VALUE_SECTION_ID ? unserialize(\QUERY_VALUE_SECTION_ID) : array();
    if ($section_id_array > 0) {
        $projects = $db->load_projects($section_id_array);

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
        $investor_id = $investor->id;
        $num_interests = (int)rand(9, $projects_count - 1);
        while($num_interests--) {
            $project_id = (int)rand(0, $projects_count-1);

            if (defined('\RUNNING_PUSH_DATE') && \RUNNING_PUSH_DATE && isset($push_date_investors[(int)$investor_id])) {
                // We need to make sure that investor didn't see project already (if rescheduling "push_date")
                // With Mock Data this really doesn't make super sense b/c it's random. But it shows it functions
                // for mock tests. Old data just won't match to the new b/c projects and investors are newly
                // generated on page load
                $old_investor_data = $push_date_investors[(int)$investor_id];

                $investor_saw_project_already = in_array($project_id, $old_investor_data);

                if (!$investor_saw_project_already) {
                    $_investor->add_project($all_projects[$project_id]);
                }
                unset($investor_saw_project_already);
            } else {
                $_investor->add_project($all_projects[$project_id]);
            }

        }
        $all_investors[] = $_investor;

    }
}

unset( $projects );
unset( $investors );
$shed = new Scheduler($all_investors, $all_projects, $run_dates, unserialize(SCHEDULE_SETTINGS) );



function do_step($step_method, $return_table=0) {
    global $shed;
    if ( method_exists($shed ,$step_method ) ) {
        $shed->$step_method();
        if ($return_table) {
            return @$shed->show_time_lines();
        }
        return true;
    }
    throw new \ErrorException("That is not a valid step.. Steps must be methods on the Scheduler class.");
}


function export_meetings_to_outlook() {
    global $shed;
    return $shed->export_meetings_to_calendar(\Util\post('calendar-id'), 0);
}

function export_meetings_to_json() {
    global $shed;
    $all_event_data = $shed->all_event_data();
    return json_encode($all_event_data);
}

function get_all_database_meetings($event_id = '', $investor_or_project_filter = array()) {
    global $db;
    return $db->select_meetings($event_id, $investor_or_project_filter);
}


function export_meetings_to_tqtag() {
    global $shed;
    global $db;
    // Delete all TQ TAG MEETINGS!
    $db->delete_all_meetings( \EVENT_ID );
    // Add meetings using TQ-Tag url function as callback to Sheduler::all_event_data()
    $all_event_data = $shed->all_event_data( 1 );
    return $all_event_data;
}