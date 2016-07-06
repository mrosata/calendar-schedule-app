<?php
/**
 * Created by Michael Rosata -- TQ-Soft -- Shai Dayan.
 *
 * Project: sched.copro.co.il
 * Date: 5/26/16
 */

namespace copro;

require_once 'connection.php';
require_once 'Improved_Project_Database_Interface-class.php';

class Globals
{

    static $projects;
    static $investors;
    static $all_investors;
    static $all_projects;
    static $fixed_meetings = array();
    static $ignored_meetings = array();
    static $db = 0;


    static function connect_to_db() {
        $pdo = \Connection\get_connection();
        self::$db = new Investor_Project_PHP_Handler( $pdo );
    }

    
    
    static function check_for_api_post_request() {
        if ( !Config::$import ) {
            return;
        }
        if (self::$db === 0) {
            $pdo = \Connection\get_connection();
            self::$db = new Investor_Project_PHP_Handler( $pdo );
        }
        $db = self::$db;
        // Import previous results from database to export to calendar
        // If this is API then we don't need to create any html. Just build all the data here as json
        header('Content-Type: application/json; charset=UTF-8');
        $event_id = !!\Util\post('calendar-id') ? \Util\post('calendar-id') : \Util\post('dates-event-id');

        $project = !!\Util\post('project') ? \Util\post('project') : null;
        $investor = !!\Util\post('investor') ? \Util\post('investor') : null;
        $projects = !!\Util\post('projects') ? \Util\post('projects') : null;
        $investors = !!\Util\post('investors') ? \Util\post('investors') : null;

        if (!!$investors) {
            // Filter the meetings by an investor id
            $all_event_data = get_all_database_meetings($event_id, array('investors'=>$investors));
        } elseif (!!$projects) {
            // Filter the meetings by a project id
            $all_event_data = get_all_database_meetings($event_id, array('projects'=>$projects));
        }
        elseif (!!$investor) {
            // Filter the meetings by amany investor id
            $all_event_data = get_all_database_meetings($event_id, array('investor'=>$investor));
        } elseif (!!$project) {
            // Filter the meetings by many project id
            $all_event_data = get_all_database_meetings($event_id, array('project'=>$project));
        }
        else {
            // Get all projets by and event_id
            $all_event_data = get_all_database_meetings($event_id);
        }

        ob_flush();
        echo json_encode($all_event_data);
        exit;

    }
    
    
    /**
     *
     */
    static function get_projects_and_investors_loaded() {
        if (self::$db === 0) {
            $pdo = \Connection\get_connection();
            self::$db = new Investor_Project_PHP_Handler( $pdo );
        }
        $db = self::$db;

        // STEP 4 // Go through projects which are to be fixed or ignored and take them from the investors.
        if ( Config::$honor_pinned_meetings) {
            self::$fixed_meetings = $db->get_fixed_meetings( Config::$event_id );
        }
        if ( Config::$activate_push_date) {
            self::$ignored_meetings = $db->get_meetings_before(Config::$event_id, Config::$push_date);
        }

        // STEP 1 // Get products and build projects class.
        $section_id_array = Config::$section_ids;
        
        if ( array( $section_id_array ) > 0 ) {
            \Util\debug( "<br><strong>About to do 'STEP 1 // Load projects using section ids.'</strong>" );
            // STEP 1 // Load projects using section ids.
            $db->load_projects( $section_id_array );

            \Util\debug( "<br><strong>About to do 'STEP 2 // Get interested investor ids list'</strong>" );
            // STEP 2 // Get interested investor ids list.
            $db->build_investor_list_from_projects();

            \Util\debug( "<br><strong>About to do 'STEP 3 // Get investors and build investors class.'</strong>" );
            // STEP 3 // Get investors and build investors class.
            $db->instantiate_investor_objects( self::$fixed_meetings );

            \Util\debug( "<br><strong>About to do 'STEP 4 // Go through projects foreach and push ids.'</strong>" );
            // STEP 4 // Go through projects foreach and push ids.
            $db->add_project_references_to_investors( self::$ignored_meetings );


            // STEP 5 // Use these in the application like we did with mock data objects.
            self::$all_investors = $db->get_investors();
            self::$all_projects = $db->get_projects();

            if ( ! !Config::$finalize ) {
                $db->delete_all_meetings( Config::$event_id );
            }
        }
        else {
            // don't have data to get single section!
            self::$all_investors = array();
            self::$all_projects = array();
        }

    }

/*

    static function add_meeting_to_collision($investor_id, $project_id, &$meeting_array) {
        if (!isset($meeting_array[(int)$investor_id]) || !is_array($meeting_array[(int)$investor_id])) {
            $meeting_array[(int)$investor_id] = array();
        }
        $meeting_array[(int)$investor_id][] = (int)$project_id;
    }


    static function remove_ignored_projects_from_investors($past_meetings, $fixed_meetings)
    {

        if ( is_array( $past_meetings ) ) {
            foreach ( $past_meetings as $meeting ) {
                if ( !$meeting->investor || !$meeting->project )
                    continue;
                // Add this combo to the list of $ignored_meetings
                add_meeting_to_collision( $meeting->investor, $meeting->project, $ignored_meetings );
            }

            //\Util\print_pre($past_meetings);
            //\Util\print_pre($ignored_meetings);
            unset( $past_meetings );
        }

        if ( is_array( $fixed_meetings ) ) {
            // We do have to do the same thing with fixed meetings (but also fixed meetings we use later too so they don't
            // get booked over).
            foreach ( $fixed_meetings as $meeting ) {
                if ( !$meeting->investor || !$meeting->project )
                    continue;
                // Add this combo to the list of $ignored_meetings
                add_meeting_to_collision( $meeting->investor, $meeting->project, $ignored_meetings );
            }

            if ( ! !\Util\post( 'finalize' ) ) {
                // Show the fixed meetings on export
                echo "<h3>Fixed Meetings</h3>";
                \Util\print_pre( $fixed_meetings );
            }
        }
    }

*/

}