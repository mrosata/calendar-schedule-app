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

// RUNNING_PUSH_DATE set at the bottom of crazy-settings.php
$push_date_investors = array();
if (defined('\RUNNING_PUSH_DATE') && \RUNNING_PUSH_DATE) {
    // The user wants to start the schedule into the events (already started and some have happened)
    // This also requires that we have a calendar-id (because we need to know which events to work with)
    // also.. we need to work on the same calendar which the push-date relates to
    $push_date = strtotime(\Util\post( 'push-date' ));
    $calendar_name = \Util\post( 'calendar-id' );
    $calendar_id = $api->get_or_create_calendar_id( $calendar_name );
    $previous_scheduled_events = $api->get_events( $calendar_id );
    //\Util\print_pre($previous_scheduled_events);
    //exit();
    if (isset($previous_scheduled_events['value']) && is_array($previous_scheduled_events['value'])) {
        foreach ($previous_scheduled_events['value'] as $event) {
            if (isset($event['Body']) && is_array($event['Body']) && isset($event['Body']['Content'])) {
                $event_project = preg_match("|@@\[([^]]+)\]@@|", $event['Body']['Content'], $matches);
                if (count($matches) > 1) {
                    $investor_project = explode("&", $matches[1]);
                    $data = array();
                    // get the ids from the investor and project stored in body of the event
                    $data['I'] = explode('=', $investor_project[0])[1];
                    $data['P'] = explode('=', $investor_project[1])[1];
                    $data['start'] = new \DateTime(
                        preg_replace("|([^T]+)T([^.]+).*|", '$1 $2', $event['Start']['DateTime'])
                        , new \DateTimeZone( 'UTC' ));
                        /*, new \DateTimeZone($event['Start']['TimeZone']));*/
                    $data['end'] = new \DateTime(
                        preg_replace("|([^T]+)T([^.]+).*|", '$1 $2', $event['End']['DateTime'])
                        , new \DateTimeZone( 'UTC' ));
                       /* , new \DateTimeZone($event['End']['TimeZone']))*/;

                    $data['start'] = $data['start']->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
                    $data['end'] = $data['end']->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');


                    //$push_date = \RUNNING_PUSH_DATE;
                    //echo "push? {$data['start']} <= {$push_date}<br>";
                    //echo "push? ".strtotime( $data['start'] )." <= ".strtotime(\RUNNING_PUSH_DATE)."<br>";

                    // If this meeting has happened already then we will keep it for checks later to avoid conflicts.
                    if ( strtotime( $data['start'] ) < strtotime(\RUNNING_PUSH_DATE . ':00') ) {
                        // Now store easy access to this object by investor ID.
                        if (!isset($push_date_investors[ (int)$data['I'] ])) {
                            $push_date_investors[ (int)$data['I'] ] = array();
                        }
                        // These are the projects they saw
                        $push_date_investors[ (int)$data['I'] ][] = (int)$data['P'];
                    }
                }
            }
        }
    }
}
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
    return $shed->export_meetings_to_calendar(\Util\post('calendar-id'), 0);
}
