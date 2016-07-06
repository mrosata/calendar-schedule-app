<?php
/**
 * Created by michael on 2/23/16.
 */
namespace copro;

use Util\Times_Utils;

$debug_url_calls = array();
$debug_collisions = '';


class Meeting {
    public $meeting_name = 'Meeting ';
    public $display_title = 0;
    public $start_time;
    public $end_time;
    public $investor;
    public $project;

    function __construct($config) {
        foreach ($config as $setting => $value) {
            if (property_exists($this, $setting)) {
                $this->$setting = $value;
            }
        }
    }

}


class Meetings {
    static $meetings = array();

    static function add_meeting($meeting) {
        if (!is_array(self::$meetings[$meeting->start])) {
            self::$meetings[$meeting->start] = array();
        }
        self::$meetings[$meeting->start][] = $meeting;
    }

    static function clear_meetings() {
        self::$meetings = array();
    }

}



class Scheduler_Advanced {

    function __construct($all_investors, $all_projects, $running_dates, $breaks) {

        $this->investors = array();
        $this->projects = array();
        foreach($all_projects as $p) {
            $this->projects[$p->id] = $p;
        }
        foreach($all_investors as $i) {
            $this->investors[$i->id] = $i;
        }

        unset( $all_projects );
        unset( $all_investors);
        if (is_null($running_dates) || !count($running_dates)) {
            // Unable to run the scheduler because form isn't filled out
            return false;
        }

        $this->ATM = new Availability_Time_Manager();


        if (Config::$activate_push_date && count(Globals::$ignored_meetings)) {

            // These meetings have already happened. But they are not pinned (fixed) so they will
            // need to be rescheduled to the calendar.
            Config::add_output("<hr><h3><strong>Meetings already happened</strong></h3>");
            $this->list_meetings_that_already_have_been_scheduled(Globals::$ignored_meetings);
        }
        if (Config::$honor_pinned_meetings && count(Globals::$fixed_meetings)) {
            // These meetings don't need to be sent to the calendar. They need to be taken into account
            // when scheduling meetings, but they don't need to be stored to the calendar. We don't want
            // to overwrite them because then they wouldn't be fixed (pinned).
            Config::add_output("<hr><h3><strong>Fixed Meetings</strong></h3>");
            $this->list_meetings_that_already_have_been_scheduled(Globals::$fixed_meetings);
        }


        // 1. Need to add all the collisions into the Timer Object.
        $this->pass_global_exceptions_to_timer($breaks);        
        $this->pass_individual_exceptions_to_timer($this->projects);
        $this->pass_individual_exceptions_to_timer($this->investors);

        $this->pass_running_dates_to_timer($running_dates);



        $i = 0;
        Config::add_output("<div style='margin-left:2rem;'>");
        while ($i < 300 && !$this->ATM->is_finished()) {
            $i++;
            $the_next_time = $this->ATM->get_next_time();
            if (!$this->ATM->is_finished()) {
                Config::add_output("<br><h5><strong>");
                Config::add_output(date('Y-m-d H:i', $the_next_time));
                Config::add_output("</strong></h5>");
            } else {
                Config::add_output("<br><br><h4><strong>EXTRA EXTRA OVERFLOWED!</strong></h4>");
            }

            $avail_inv = $this->sort_who_is_available($this->investors);
            $avail_proj = $this->sort_who_is_available($this->projects);

            $this->schedule_from_availible($avail_inv['yes'], $avail_proj['yes']);
        }
        Config::add_output("</div>");


        if (Config::$finalize) {
            $event_id = Config::$event_id;
            $section_ids = rawurlencode(implode(',', Config::$section_ids));
            Config::prepend_output( "<h2><a target='_blank' href='https://copro.ezadmin3.com/copro.co.il/originals/miker/calendar/index.html?eventid={$event_id}&project-sections={$section_ids}&project-search=&investor-search='>CLICK FOR CALENDAR</a></h2>");
        }
        $this->ATM->reset();
    }



    function schedule_from_availible($investors, $projects) {
        $everyone_at_meetings_now = array();

        foreach($investors as $inv) {
            $projects_req = $inv->projects;
            $investor_free = true;
            foreach ( $projects as $project ) {

                if (!$investor_free) {
                    continue;
                }
                $inv_emails = array($inv->email);
                if (in_array($project->id, $projects_req) &&
                    !count(array_intersect($everyone_at_meetings_now, $project->emails)) &&
                        !count(array_intersect($everyone_at_meetings_now, $inv_emails))) {
                    // We can schedule this meeting!
                    $investor_free = false;
                    $this->schedule_meeting($inv, $project);
                    foreach ($project->emails as $email) {
                        // Add the emails from this project to the array that we use to look for collisions between
                        // projects before scheduling them at this time.
                        $everyone_at_meetings_now[] = $email;
                    }
                    // Also add the investor email to the array
                    $everyone_at_meetings_now[] = $inv->email;
                    $index = array_search($project->id, $projects_req);
                    unset($inv->projects[ $index ]);
                }
            }
        }
    }


    function sort_who_is_available($objects, $ignore_finished = true) {
        $availability = array(
            'yes' => array(),
            'no'  => array(),
        );
        if ( !is_array( $objects ) ) {
            return $availability;
        }
        foreach($objects as $object) {
            if ($ignore_finished && ((property_exists($object, 'interested') && !count($object->interested)) && (property_exists($object, 'projects') && !count($object->projects))) ){
                // Theres no reason to check investor/project that is all set.
                continue;
            }
            if (!$this->ATM->is_available((int)$object->gid)) {
                $availability['no'][] = $object;
            } else {
                $availability['yes'][] = $object;
            }
        }
        return $availability;
    }

    public function list_meetings_that_already_have_been_scheduled( $meetings) {


        if (!is_array($meetings)) {
            return false;
        }

        foreach($meetings as $meeting) {
            // Schedule the meeting (this function is called regardless if meeting shall be stored to calendar or not).
            $this->schedule_meeting((int)$meeting->investor, (int)$meeting->project, $meeting->start, $meeting->end, false);

            if (isset($this->investors[(int)$meeting->investor])) {
                $object = $this->investors[(int)$meeting->investor];
                if ( is_array($object->projects) ) {
                    // Remove project from investors interested list.
                    $index = array_search($meeting->project, $object->projects);
                    unset($object->projects[ $index ]);
                }
                $exception = array(
                    'from' => Times_Utils::x_to_format($meeting->start, 'Y-m-d H:i'),
                    'to' => Times_Utils::x_to_format($meeting->end, 'Y-m-d H:i'),
                );

                $object->add_exception($exception);
            }
            if (isset($this->projects[(int)$meeting->project])) {
                $object = $this->projects[(int)$meeting->project];
                $exception = array(
                    'from' => Times_Utils::x_to_format($meeting->start, 'Y-m-d H:i'),
                    'to' => Times_Utils::x_to_format($meeting->end, 'Y-m-d H:i'),
                );

                $object->add_exception($exception);
            }
        }

    }


    function schedule_meeting ( $investor, $project, $start=null, $end=null, $send_to_calendar = true) {
        if (is_integer($investor)) {
            $investor = !!$this->investors[$investor] ? $this->investors[$investor] : null;
        }
        if (is_integer($project)) {
            $project = !!$this->projects[$project] ? $this->projects[$project] : null;
        }

        if ( !$investor || !$project ) {
            return false;
        }

        $index = array_search($project->id, $this->investors[$investor->id]->projects);
        if (!!$index) {
            unset($this->investors[$investor->id]->projects[$index]);
        }

        $meeting_name = self::generate_meeting_name($project, $investor);
        $title = property_exists($project, 'title') ? $project->title : $project->project_title;
        $this->ATM->place_meeting($investor->id, $project->id, $meeting_name, $title, $start, $end, $send_to_calendar);
    }


    /**
     * Generate a meeting display title for use in storing and showing the meetings in the calendar.
     * 
     * @param $investor
     * @param $project
     * @return string
     */
    static function generate_meeting_name($project, $investor) {
        $display_title_for_meeting = "( {$investor->id} ) {$investor->first_name}, {$investor->last_name} \n  ( {$project->id} ) {$project->project_title}";
        return $display_title_for_meeting;
    }


    /**
     * Give the available running dates to the time manager
     * @param $array_of_dates
     */
    function pass_running_dates_to_timer($array_of_dates) {
        foreach ($array_of_dates as $date) {
            $this->ATM->add_available_date($date);
        }
    }


    /**
     * Pass exceptions for the projects or for investors to the availability time manager
     *
     * @param $system_object
     */
    function pass_individual_exceptions_to_timer($system_object) {
        foreach ($system_object as $object) {
            if (property_exists($object, 'exceptions') && is_array($object->exceptions)) {
                foreach ($object->exceptions as $exception) {
                    if (!$object->gid || !isset($exception['to']) || !isset($exception['from']))
                        continue;

                    $this->ATM->add_individual_exception( $object->gid, $exception['from'], $exception['to']);
                }
            }
        }
    }


    /**
     * Pass global exceptions to the availability time manager. These are the breaks mask.
     * Basically it is a mask around available hours.
     *
     * @param $breaks_mask
     */
    function pass_global_exceptions_to_timer($breaks_mask) {
        foreach($breaks_mask as $exception) {
            $this->ATM->add_global_exception( $exception[ 'start' ], $exception[ 'end' ] );
        }
    }
    
    
    /**
     * A callback passed in will be called 1 time for each meeting which is generated
     *
     * @param string $tq_tag
     * @return array
     */
    function all_event_data( $tq_tag = 0 ) {}

    /**
     * Display an HTML Table with all the scheduled events.
     *
     * @return string
     */
    function show_time_lines() {}


    private function _push_slot_to_top(&$time_line, $slot) {}


    private function _fill_slot_from_top( ) {}


    private function _has_collision_at($obj) {}


    private function _has_collision_in($pid, $compare_with, $only_id = 1) {}


    private function _slot_is_empty( $time_line, $slot) {}


    private function _project_ids_at( $index, $num=null ) {}


    private function timelines_are_empty() {}


}
