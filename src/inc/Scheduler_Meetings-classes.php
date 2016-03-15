<?php
/**
 * Created by michael on 2/23/16.
 */
namespace copro;

class Meeting {
    public $meeting_name = 'Meeting ';
    public $free_time = 0;
    public $start_time;
    public $investor;
    public $project;
    public $end_time;

    function __construct($config) {
        foreach ($config as $setting => $value) {
            if (property_exists($this, $setting)) {
                $this->$setting = $value;
            }
        }
    }

}


class Scheduler {

    private $time_lines = array();
    private $run_dates = array();
    private $projects = array();
    private $next_free_time = 0;
    public $start_date;
    private $started_day_at; // This is the current populating calendars starting datetime
    private $current_time;
    private $current_date;
    private $api;
    private $latest_time_possible;
    private $start_datetime;
    private $settings = array(
        'meeting_length' => 600,
        'hours' => 8,
        'start_datetime' => '',
        'breaks' => array(),
        'cal_id' => ''
    );
    private $start_new_day;
    private $day = 1;
    private $start_push_date = 0;
    // These 2 settings are to makes sure we compress all the way with new added conflicts (or there could be gaps at end.)
    private $WATCH_CONFLICTS = true;
    private $compressed_twice = false;

    function __construct($investors, $projects, $run_dates, \ms365\Calendar_Meetings_API $api, $settings = array()) {
        $this->time_lines = $investors;
        $this->run_dates  = $this->sanitize_dates( $run_dates );
        $this->pad_projects_gte_investors( $projects );

        // Store all projects by id on $this->projects.
        foreach ( $projects as $project ) {
            $this->projects[ $project->id ] = $project;
        }
        $this->_projects = $projects;

        // Extend the settings with any passed in.
        $this->settings['meeting_length'] = isset( $settings['meeting_length'] ) && (int) $settings['meeting_length'] > 1 ? ( (int) $settings['meeting_length'] * 60 ) : \Setting\SECONDS_PER_MEETING;
        $this->settings['hours']          = isset( $settings['hours'] ) && (int) $settings['hours'] >= 1 ? (int) $settings['hours'] : 8;
        //$this->settings['start_datetime'] = isset($settings['start_datetime']) ? $settings['start_datetime'] : \Setting\START_DATE . ' ' . \Setting\START_TIME;
        $this->settings['start_datetime'] = date( 'Y-m-d H:i', $this->run_dates[0] );
        $this->settings['breaks']         = isset( $settings['breaks'] ) && is_array( $settings['breaks'] ) ? $settings['breaks'] : unserialize( \Setting\BREAKS );
        $this->settings['cal_id']         = isset( $settings['cal_id'] ) ? $settings['cal_id'] : '';

        $this->api = $api;
        // Some dummy times.
        $this->start_datetime = date( 'Y-m-d H:i:00', $this->run_dates[0] );

        // If this is a reschedule (push date) We must set the push start date
        if ( defined( '\RUNNING_PUSH_DATE' ) && \RUNNING_PUSH_DATE ) {
            $this->start_push_date = strtotime(\RUNNING_PUSH_DATE);
        }

        $push_date = strtotime( \RUNNING_PUSH_DATE );
        $this->reset_values();
    }


    private function sanitize_dates($date_array) {
        if (!is_array($date_array) || !isset($date_array[0])) {
            $date_array = array( strtotime( $this->settings['start_datetime'] ) );
        }
        return $date_array;
    }

    private function reset_values() {

        // RESET THE TIME SLOTS
        $this->current_time = $this->started_day_at = (int)($this->run_dates[0]);

        $this->current_date = date( 'Y-m-d', $this->current_time );

        // The latest_time_possible is starting time - meeting length + day_length (because meeting can't start after end of day - meeting len).
        $this->latest_time_possible = strtotime("+{$this->settings['hours']} hour", $this->started_day_at);

        $this->start_new_day = true;
        $this->day = 1;
    }


    /**
     * Utility to add another day to $this->run_dates;
     * It's used if the events exceed the days in array it will just stack another
     * on top. (This could happen before projects are filtered completely)
     */
    private function add_extra_day() {
        $index = max(count($this->run_dates) - 1, 0);
        $this->run_dates[] = strtotime('+1 day', $this->run_dates[$index]);
    }

    private function goto_next_day () {
        $this->start_new_day = true;
        //$this->started_day_at = strtotime( "+{$this->day} day", strtotime($this->start_datetime) );
        if (!isset($this->run_dates[$this->day])) {
            // Add another day to run_dates because we used the ones we have already.
            $this->add_extra_day();
        }
        $this->started_day_at = $this->run_dates[$this->day];

        $this->started_day_at = $this->run_dates[$this->day];
        $this->current_time = $this->started_day_at;
        $this->current_date = date( 'Y-m-d', $this->started_day_at );
        $this->latest_time_possible = strtotime("+{$this->settings['hours']} hour", $this->started_day_at);
        $this->day++;
    }


    /**
     * @param null $calendar_name
     * @param int $print
     *
     * @return bool
     * @throws \ErrorException
     */
    function export_meetings_to_calendar($calendar_name = null, $print = 0) {
        $this->reset_values();
        $num_time_lines       = count( $this->time_lines );

        if (is_null($calendar_name) || !$calendar_name) {
            $calendar_name = \Setting\DEFAULT_CALENDAR_NAME;
        }
        // If calendar was passed null then we should find or make one.
        if ( !is_null( $calendar_name ) ) {
            $calendar = $this->api->get_or_create_calendar_id($calendar_name);
        }

        // Check again if the calendar is null
        if (is_null($calendar)) {
            throw new \ErrorException("Unable to create or get calendar.");
        }

        // Let's clear out the calendar so we can start over.
        ob_start();
        if (!defined('\RUNNING_PUSH_DATE') || !\RUNNING_PUSH_DATE) {
            // Set from bottom crazy-settings.php (basically if user says "start at this datetime" then we
            // can't just wipe their whole calendar. If not set or false then we can wipe it all!
            $this->api->delete_calendar($calendar);
            $calendar = $this->api->get_or_create_calendar_id($calendar_name);
        } else {
            $this->api->delete_all_events( $calendar );
        }
        $debug = "<h4>DEBUG</h4>";
        for ($row_num = 0; $row_num < $this->_largest_item_stack(); $row_num++) {
            $debug .= "<br><code>ROW {$row_num}</code>";
            // TODO: This should be at the end of the loop or you should decriment this first event $this->meeting_length;
            $start_time = (int)$this->next_time_slot();
            $end_time = $start_time + (int)$this->settings['meeting_length'];

            for ( $i = 0; $i < $num_time_lines; $i ++ ) {
                $debug .= "<br>---<code>TIME LINE: {$i}</code>";
                if (!isset($this->time_lines[$i]))
                    continue;

                $investor = $this->time_lines[$i];
                if (!isset($investor->items[$row_num]) || $this->_slot_is_empty($investor, $row_num)) {
                    $debug .= "<br>------ EMPTY!";
                    continue;
                }

                $project = $investor->items[$row_num];/*
                if (!is_a($project, '\Project') || !($project->id && $project->id !== 0))
                    continue;*/

                $event = array(
                    'start' => new \DateTime(date('Y-m-d H:i', $start_time)),
                    'end' => new \DateTime(date('Y-m-d H:i', $end_time)),
                    'subject' => "{$investor->name} to meet {$project->project_title}",
                    'body' => array(
                        "ContentType" => "HTML",
                        "Content" => "@@[I={$investor->id}&P={$project->id}]@@"),
                    'location' => \CONVENTION_LOCATION,
                    'attendees' => array(),
                    'id' => $calendar
                );
                // Add Emails from project members

                if (defined('\SEND_EMAILS') && \SEND_EMAILS) {
                    /**
                     * Only add these events if the setting 'attendees' is set (checkbox).
                     */
                    array_push( $event['attendees'],
                        array(
                            'address' => $investor->email,
                            'name' => "{$investor->name}"
                        )
                    );

                    if (is_object($project) && is_array($project->contacts)) {
                        foreach ($project->contacts as $attendee_info) {
                            array_push($event['attendees'], $attendee_info);
                        }
                    }
                }

                // Create the event on the $calendar
                $resp = $this->api->create_event($event, 1);

                if ($print) {
                    echo "<br><h3>Creating Next Event</h3>";
                    //\Util\print_pre( $event );
                    \Util\print_pre($resp);
                    echo "<hr>";
                }
            }
            ob_flush();
            flush();
        }
        if (defined('DEBUG_EVENTS') && DEBUG_EVENTS) {
            echo $debug;
        }

    }


    /**
     * Display an HTML Table with all the scheduled events.
     *
     * @return string
     */
    function show_time_lines() {
        $this->reset_values();

        $num_time_lines       = count( $this->time_lines );
        $col_span = $num_time_lines + 1;
        $max_len = array();


        $ret = '<table class="table-condensed table-responsive table table-striped text-center"><thead><th><strong>TIME</strong></th>';

        $num_time_lines = count( $this->time_lines );
        for ( $i = 0; $i < $num_time_lines; $i ++ ) {
            $ret .= "<th><small>{$this->time_lines[$i]->tooltip()}</small></th>";
            // We should determine the largest items array for next loop
            $max_len[] = count($this->time_lines[$i]->items);
        }
        $max_len = array_reduce($max_len, function($last, $current) {
            return $current > $last ? $current : $last;
        });
        $ret .= "</thead><tbody>";

        for ($row_num = 0; $row_num < $max_len; $row_num++) {
            $schedule_time = $this->next_time_slot(true);
            // Check if this is a break time.
            if (is_array($schedule_time)) {
                $break_conflict_time = date('H:i a', $schedule_time['time']);
                $ret .= "<tr class='warning'><td class='time-slot'><strong>{$break_conflict_time}</strong></td><td colspan='{$col_span}'><strong> -- {$schedule_time['conflict']} -- </strong></td></tr>";
                // Now we get the next time regardless of current break (or else we could have many break rows!)
                $schedule_time = $this->next_time_slot();
            }

            if ($this->start_new_day) {
                $current_day = date( 'm/d/Y', $schedule_time );
                $ret .= "<tr class='info'><td colspan='{$col_span}'><h3>NEW DAY <small class='secondary'>{$current_day}</small></h3></td></tr>";
                $this->start_new_day = false;
            }

            $schedule_time = date('H:i a', $schedule_time);
            $ret .= "<tr>";
            $ret .= "<td class='time-slot'><strong>{$schedule_time}</strong></td>";
            for ( $i = 0; $i < $num_time_lines; $i ++ ) {
                if (isset($this->time_lines[$i]->items[$row_num]) && is_a($this->time_lines[$i]->items[$row_num], 'Project'))
                    $ret .= "<td>{$this->time_lines[$i]->items[$row_num]->tooltip()}</td>";
                else
                    $ret .= "<td></td>";
            }
            $ret .= "</tr>";
        }
        $ret .= '</tbody></table>';
        return $ret;
    }


    /**
     * Return the next time to start scheduling events. In the form of
     * HH:MM
     *
     * @param bool $return_null_if_conflict - Return null on conflict? Normally method will continue
     *                    to run until it finds a suitable time..
     *
     * @return bool|string
     */
    public function next_time_slot($return_null_if_conflict = false) {
        $meeting_length = (int)$this->settings['meeting_length'];
        // Get time slot first (or else start time wouldn't be used).
        $starting_at = $this->current_time;
        $this->current_time = $this->current_time + $meeting_length;

        // If it's too late to start another meeting then go into the next day

        if ($starting_at + (int)$this->settings['meeting_length'] > ($this->latest_time_possible)) {
            $this->goto_next_day();
            return $this->next_time_slot($return_null_if_conflict);
        }

        // If we are rescheduling "Push Date"ing. Then we can't start schedule til push date is past.
        if ( $this->start_push_date && $starting_at < $this->start_push_date) {
            return $this->next_time_slot();
        }
        // Make sure not interfering with any breaks.
        foreach($this->settings['breaks'] as $break) {
            $ending_at = $starting_at + (int)$this->settings['meeting_length'];

            $break_start = strtotime( $this->current_date . " " . $break['start']);
            $break_end = strtotime( $this->current_date . " " . $break['end']);
            //echo "{$starting_at} --- {$ending_at}  --->>> BREAKSTART = {$break_start} <<-->> BREAKEND {$break_end}<br><br>";
            // if meeting starts during break ($starting_at >= $break_start && $starting_at < $break_end)
            // or meeting ends during break  ($ending_at > $break_start && $ending_at < $break_end)
            if (($starting_at >= $break_start && $starting_at < $break_end) || ($ending_at > $break_start && $ending_at < $break_end)) {
                if ($return_null_if_conflict)
                    return array('time' => $starting_at, 'conflict' => ' BREAK TIME ');
                return $this->next_time_slot();
            }
        }
        // Return $stating at not $this->current_time because then we'd be 1 meeting in the future.
        // Return time slot ie: "10:50" but as a strtotime() int.
        return $starting_at;
    }

    /**
     * Shedule each investor to see each project 1 time.
     *
     * @throws ErrorException
     */
    public function schedule_all() {
        $projects = $this->projects;
        if (!is_array($projects))
            throw new \ErrorException("Scheduler->schedule_all requires array");

        /**
         * Step 1: Iterate each investor,
         * Step 2: shift index 0 off projects and push on top
         *         as long as there are more projects than investors
         *         we have scheduled each investor to see *all* project
         */
        for ( $i = 0; $i < count($this->time_lines); $i++ ) {
            $this->time_lines[$i]->merge($projects);
// swap bottom project to top.
            $_swapper = array_shift($projects);
            array_push($projects, $_swapper);
            unset($_swapper);
        }

    }


    /**
     * Remove any project from timeline which investor is not interested in.
     * Map any items in a time line which don't relate to relevant projects for an
     * investor to "Empty Slots".
     *
     * @return array
     */
    public function filter_out_meetings() {
        for ( $i = 0; $i < count($this->time_lines); $i++ ) {
// Grab array of id #'s for projects this timeline/investor should meet with.
            $projects = $this->time_lines[$i]->projects;
// Map the unwanted projects in timeline to empty slots.
            $this->time_lines[$i]->items = array_map(function($proj_meeting) use ($projects) {
                if (in_array($proj_meeting->id, $projects)) {
                    return $proj_meeting;
                }
                $empty_slot = new \Project();
                return $empty_slot;
            }, $this->time_lines[$i]->items);
        }
        return $this->time_lines;
    }


    /**
     * Count the items in the time line with most slots so that if we
     * are looping for instance we could use this to keep a dynamic
     * condition on the max loop iterations.
     *
     * @return int|mixed
     */
    private function _largest_item_stack() {
        $stacks = count($this->time_lines);
        $max = 0;
        while ($stacks--) {
            $max = max(count($this->time_lines[$stacks]->items), $max);
        }
        return $max;
    }
    /**
     * Walk through every single time slot looking for empty slots and fill them in with
     * the latest meeting in that time_line that will not cause a collision.
     *
     * @throws ErrorException
     */
    public function compress_meetings() {
        if ($this->timelines_are_empty()) {
            throw new \ErrorException("Can't compress meetings until sheduling timelines!");
        }
        $this->reset_values();

        $num_time_lines = count($this->time_lines);

        // Iterate Every time slot as array: (8:00, 8:10, 8:20 ect...)
        // use the function count() in iteration constraint b/c it may grow when pushing meetings to end.
        $highest_stack = $this->_largest_item_stack();
        for ( $slot = 0; $slot < $highest_stack; $slot ++ ) {
            // need to recalculate the height of tallest stack each time.
            $highest_stack = $this->_largest_item_stack();

            /* This is important, we need to adjust clock as if we are building schedule (so we can do checks against time conflicts) */
            // Store every ID from this time_slot into array to use in comparisons
            $projects_in_slot = $this->_project_ids_at($slot, $num_time_lines);

            if (!count($projects_in_slot)) {
                    // If count is the same then there are no empty slots this time_slot.
                    $this->next_time_slot();
                    continue;
            }

// Check each time_line in this time_slot for empty slot
            for ( $j = 0; $j < $num_time_lines; $j ++ ) {
                // This timeline might be done already (some are longer then others in the end).
                if (isset($this->time_lines[$j]->items[ $slot ])) {
                    $project = $this->time_lines[$j]->items[ $slot];

                $empty_for_sure = false;
                // Check if the project here has a collision with time
                } else { continue; }
                if ($this->_has_collision_at($this->time_lines[$j]) || $this->_has_collision_at($project)) {
                    /* This project can't show at this time due to human collision scheduling (they said they can't meet now).
                          so we push them to the top of the array and leave this slot empty */
                    $this->_push_slot_to_top($this->time_lines[$j], $slot);
                    $empty_for_sure = true;
                }
                // If the above condition met then this will be true.
                if ($empty_for_sure || $this->_slot_is_empty($this->time_lines[$j], $slot)) {
// Fill in slot with later meeting and update the $projects in slot
                    $this->_fill_slot_from_top($this->time_lines[$j], $slot, $projects_in_slot);
                }
            }
            // move to the next time slot.
            $this->next_time_slot();

        } /* end for */

        /* End - public function compress_meetings() */
        $this->_remove_padding();
        if ($this->WATCH_CONFLICTS && !$this->compressed_twice) {
            $this->compressed_twice = true;
            $this->compress_meetings();
        }
    }


    /**
     * After the schedules are compressed this will remove the empty slots from
     * the end of each array of meetings.
     *
     * @return array
     */
    private function _remove_padding() {
        for ( $i = 0; $i < count($this->time_lines); $i++ ) {
            $items_in_reverse = array_reverse($this->time_lines[$i]->items);

            $items_in_reverse = array_filter($items_in_reverse, function($project) {
                if (!is_a($project, 'Project') /*|| is_null($project->id)*/) {
                    return false;
                }
                return true;
            });

            $this->time_lines[$i]->items = array_reverse($items_in_reverse);
        }
        return $this->time_lines;
    }


    /**
     * Push the current project from its slot to the top of the Array (last meeting) and then
     * replace it with an empty project for now.
     *
     * @param $time_line
     * @param $slot
     *
     * @return mixed
     */
    private function _push_slot_to_top(&$time_line, $slot) {
        if (isset($time_line->items[$slot])) {
            // Create a new empty project, then replace real project and push real project to top of the list
            $empty_slot = new \Project();
            $temp = $time_line->items[$slot];
            $time_line->items[$slot] = $empty_slot;
            array_push($time_line->items, $temp);
        }
        return $time_line;
    }


    /**
     * Helper Method to swap the empty time slot in $slot with a non-conflicting meeting from the
     * top-most of the time_line. Checks for collisions in $collides_with to make sure to schedule
     * only 1 project per time_slot across all time_lines. (Pass By Reference $collides_with)
     *
     * @param $time_line
     * @param $slot
     * @param array $collides_with
     *
     * @return null
     */
    private function _fill_slot_from_top( &$time_line, $slot, &$collides_with = array() ) {
        $i =  count( $time_line->items );

        while ( $i -- > $slot ) {
            if ( $this->_slot_is_empty( $time_line, $i ) ) {
                continue;
            }

            $pid = $time_line->items[$i]->id;
            // Check for collisions before swapping time slots
            if ( ! $this->_has_collision_in($pid, $collides_with, 0) && ! $this->_has_collision_at( $time_line->items[$i] ) ) {
                // We can swap $i into current empty $slot
                $temp = $time_line->items[$slot];
                $time_line->items[$slot] = $time_line->items[$i];
                $time_line->items[$i] = $temp;
                /* push id onto the $collides_with array */
                $collides_with[] = $pid;
                return $time_line->items[$slot]->id;
            }
        }
        // Just return empty project
        return new \Project();
    }



    private function _projects_have_collision_at($investor) {
        foreach ($investor->projects as $pid) {
            if ($this->_has_collision_at($this->projects[(int)$pid])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper Method. Test if project with id $pid has a collision at the current time which
     * we are scheduling the meeting at.
     *
     * @param $obj - Either Investor or Project Object with collisions array.
     *
     * @return bool
     */
    private function _has_collision_at($obj) {
        if (!is_object($obj) || !is_array($obj->collisions) || !count($obj->collisions)) {
            return false;
        }
        $collisions = $obj->collisions;

        // Get the time slot that we are going to check if there is scheudule conflict.
        $meeting_length = (int)$this->settings['meeting_length'];
        $starting_at = (int)$this->current_time;
        $ending_at = $this->current_time + $meeting_length;

        // Check each collision on this project and return true if find one with current time.
        foreach($collisions as $collision) {
            $collision_start = $collision['from'];
            $collision_end = $collision['to'];
            if (
                ($starting_at >= $collision_start && $starting_at < $collision_end)
                || ($ending_at > $collision_start && $ending_at < $collision_end) ) {

                $start = date('Y-m-d H:i', $collision_start);
                $end = date('H:i', $collision_end);
                return true;
            }
        }

        return false;
    }


    /**
     * Helper Method. Test if project with id $pid has a collision in $compare_with array.
     * By default this will just check id numbers to compare. It can also check for collisions
     * by email. To do this pass 0 as $only_id;
     *
     * @param $pid
     * @param $compare_with
     * @param int $only_id
     *
     * @return bool
     */
    private function _has_collision_in($pid, $compare_with, $only_id = 1) {
        $is_colliding_with_comparators = in_array($pid, $compare_with);
// If collision by id or we only care about collision by id.
        if ($is_colliding_with_comparators || $only_id) {
// return if $pid in $compare_with
            return $is_colliding_with_comparators;
        }
        $test_project = $this->projects[$pid];

        foreach ($compare_with as $compare_id) {
            $compare_to = $this->projects[$compare_id];

// TODO: REMOVE THIS IN PRODUCTION
            if (count(array_intersect($compare_to->emails, $test_project->emails))) {
                if (defined('SHOW_EMAIL_COLLISIONS') && !!SHOW_EMAIL_COLLISIONS) {
                    global $collisions;
// If email-collisions is set show the collisions
                    $collisions .= "<div class='col-sm-6 col-md-4 col-lg-3'><p> &nbsp; &nbsp; <strong>project:</strong> {$test_project->tooltip()} collides with: {$compare_to->tooltip()}</p></div>";
                }

                return true;
            }
        }
        return false;
    }


    /**
     * Helper method to tell if $timeline has an empty slot at $slot
     *
     *@param $time_line
     * @param $slot
     *
     * @return bool
     */
    private function _slot_is_empty( $time_line, $slot) {
        if (!isset( $time_line->items[$slot]))
            return false;
        $project = $time_line->items[$slot];
        return (!isset($project->id) || is_null($project->id));
    }


    /**
     * Helper Method to return list of project ids in a given time slot.
     * Use this to avoid collisions when moving around meetings.
     *
     * @param $index
     * @param null $num - optionally pass in amount of time_lines to  avoid needless count();
     *
     * @return array
     */
    private function _project_ids_at( $index, $num=null ) {
        if ( is_null($num) ) {
            $num = count($this->time_lines);
        }
        $array_with_ids = array();

// Go through each timeline and push project ID that is at slot $index.
        for ( $j = 0; $j < $num; $j ++ ) {
            if ( isset( $this->time_lines[ $j ]->items[ $index ] ) &&
                 !$this->_slot_is_empty($this->time_lines[ $j ], $index) ) {
                $array_with_ids[] = $this->time_lines[$j]->items[$index]->id;
            }
        }

        return $array_with_ids;
    }


    /**
     * Check if there are no projects yet.
     * @return bool
     */
    private function timelines_are_empty() {
        return ( !isset($this->time_lines) ||
                 !isset($this->time_lines[0]) ||
                 !count($this->time_lines[0]->projects));
    }


    /**
     * If $projects has less items than there are investors then add
     * empty projects or else scheduler won't schedule correctly.
     * @param $projects
     *
     * @return array
     */
    private function pad_projects_gte_investors(&$projects) {
        $investor_count = count($this->time_lines);
        $empty = new \stdClass();
        $empty->id = 0;
        $projects = array_pad($projects, $investor_count, $empty);
        return $projects;
    }
}
