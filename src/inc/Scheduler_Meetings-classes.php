<?php
/**
 * Created by michael on 2/23/16.
 */
namespace copro;

$debug_url_calls = array();
$debug_collisions = '';

/**
 * array(
 *   project_id: int
 *   investor_id: int
 *   starts: DateTime
 *   ends: DateTime
 * )
 * @param $meeting
 */
function insert_meeting_tqtag( $meeting ) {
    global $debug_url_calls;
    $event_id = trim($meeting['event_id'] );
    $project_id = trim($meeting['project'] );
    $project_name = trim(rawurlencode($meeting['project_name']) );
    $investor_id = trim($meeting['investor'] );
    $title = trim(rawurlencode($meeting['title']) );
    $ends = trim(rawurlencode(is_a( $meeting['end'], '\DateTime') ? $meeting['end']->format('Y-m-d H:i:s') : $meeting['end']) );
    $starts = trim(rawurlencode(is_a( $meeting['start'], '\DateTime' ) ? $meeting['start']->format('Y-m-d H:i:s') : $meeting['start']) );

    $insert_url = "http://copro.tqsoft.co.il/?mode=insertContent&insertSectionID=144&projectID={$project_id}&investorID={$investor_id}&meetingStart={$starts}&meetingEnd={$ends}&eventID={$event_id}&01MovieName={$title}&title={$project_name}&jsonResponse=1";
    $file_get_contents = @file_get_contents($insert_url);
    $debug_url_calls[] = array(
        'URL' => $insert_url,
        'result' => $file_get_contents
    );

    return $file_get_contents;
}


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

    private $last_slot_was_break = false; // Flag to signal when slot might be longer/shorter than $meeting_length
    private $time_lines = array();
    private $run_dates = array();
    private $projects = array();
    private $investors = array();
    public $start_date;
    private $started_day_at; // This is the current populating calendars starting datetime
    private $current_time;
    private $current_date;
    private $latest_time_possible;
    private $start_datetime;
    private $settings = array(
        'meeting_length' => 600,
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

    function __construct($investors, $projects, $run_dates, $settings = array(), $fixed_meetings = array()) {
        $this->time_lines = $investors;
        $this->run_dates  = $this->sanitize_dates( $run_dates );
        $this->pad_projects_gte_investors( $projects );
        // An array matching days to hours in them set in form
        // Store all projects by id on $this->projects.
        foreach ( $projects as $project ) {
            $this->projects[ $project->id ] = $project;
        }
        foreach ( $investors as $investor) {
            $this->investors[ $investor->id ] = $investor;
        }

        // Extend the settings with any passed in.
        $this->settings['meeting_length'] = isset( $settings['meeting_length'] ) && (int) $settings['meeting_length'] > 1 ? ( (int) $settings['meeting_length'] * 60 ) : 600;
        $this->settings['start_datetime'] = date( 'Y-m-d H:i', $this->run_dates[0] );
        $this->settings['breaks'] =       $this->create_breaks_arrays();
        $this->settings['cal_id']         = isset( $settings['cal_id'] ) ? $settings['cal_id'] : '';

        \Util\debug($this->settings['breaks']);

        $this->start_datetime = date( 'Y-m-d H:i:00', $this->run_dates[0] );

        // If this is a reschedule (push date) We must set the push start date
        if ( defined( '\RUNNING_PUSH_DATE' ) && \RUNNING_PUSH_DATE ) {
            $this->start_push_date = strtotime(\RUNNING_PUSH_DATE);
        }

        $this->reset_values();
    }


    /**
     * This isn't really sanitizing dates, it looks like it's just making sure
     * that there is a start date in the array.
     *
     * @param $date_array
     * @return array
     */
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
        $this->day = 1;
        $this->start_new_day = true;

        //$this->latest_time_possible = strtotime("+{$day_index} hour", $this->started_day_at);
        // Changing this to just be the end of day b/c now the breaks mask the entire day.
        $this->latest_time_possible = strtotime("+1 day", $this->started_day_at);
        $this->latest_time_possible = date('Y-m-d 00:00', $this->latest_time_possible);
        $this->latest_time_possible = strtotime($this->latest_time_possible);

    }


    /**
     * Utility to add another day to $this->run_dates;
     * It's used if the events exceed the days in array it will just stack another
     * on top. (This could happen before projects are filtered completely)
     */
    private function add_extra_day() {
        $index = max(count($this->run_dates) - 1, 0);
        $this->run_dates[] = strtotime('+1 day', $this->run_dates[$index]);
        $this->settings['breaks'][] = array();
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


        //$this->latest_time_possible = strtotime("+{$day_index} hour", $this->started_day_at);
        $this->latest_time_possible = strtotime("+1 day", $this->started_day_at);
        $this->latest_time_possible = date('Y-m-d 00:00', $this->latest_time_possible);
        $this->latest_time_possible = strtotime($this->latest_time_possible);


        $this->day++;
    }


    /**
     * A callback passed in will be called 1 time for each meeting which is generated
     *
     * @param string $tq_tag
     * @return array
     */
    function all_event_data( $tq_tag = 0 ) {
        $this->reset_values();
        $num_time_lines       = count( $this->time_lines );
        // This will hold return value
        $events_array = array();

        for ($row_num = 0; $row_num < $this->_largest_item_stack(); $row_num++) {
            // TODO: This should be at the end of the loop or you should decriment this first event $this->meeting_length;
            $start_time = (int)$this->next_time_slot();
            $end_time = $start_time + (int)$this->settings['meeting_length'];
            $event_id = \Util\post('dates-event-id');

            for ( $i = 0; $i < $num_time_lines; $i ++ ) {
                if (!isset($this->time_lines[$i]))
                    continue;

                $investor = $this->time_lines[$i];
                if (!isset($investor->items[$row_num]) || $this->_slot_is_empty($investor, $row_num)) {
                    continue;
                }

                $project = $investor->items[$row_num];/*
                if (!is_a($project, '\Project') || !($project->id && $project->id !== 0))
                    continue;*/

                $event = array(
                    'project_name' => $project->project_title,
                    'title' => "({$investor->id}): {$investor->first_name} {$investor->last_name} \n ({$project->id}): {$project->project_title}",
                    'start' => new \DateTime(date('Y-m-d H:i', $start_time)),
                    'end' => new \DateTime(date('Y-m-d H:i', $end_time)),
                    'project' => $project->id,
                    'event_id' => $event_id,
                    'investor' => $investor->id,
                );

                // Push the completed event to the return array.
                array_push($events_array, $event);

                if (!!$tq_tag) {
                    insert_meeting_tqtag( $event );
                }
            }
        }

        // This is for debug the real call to store schedule in tq-tag database.
        global $debug_url_calls;
        if (!!$tq_tag) {
            return $debug_url_calls;
        }

        // This is for practice runs
        return $events_array;
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
                $current_day = date( 'Y-m-d', $schedule_time );
                $ret .= "<tr class='info'><td colspan='{$col_span}'><h3>NEW DAY {$this->day} <small class='secondary'>{$current_day}</small></h3></td></tr>";
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
        // Get time and put it into $starting_at before updating current time to the next slot.
        $starting_at = $this->current_time;
        $this->current_time = $this->current_time + $meeting_length;

        // This is sort of monkey patch to stop 11pm -> 1am events (this is when new days start).
        $the_hour = (int)date('H', $starting_at);
        /// 1.
        if ($the_hour > 22 || $the_hour < 1 ) {
            // This is midnight, so we can't have a meeting now.
            return $this->next_time_slot($return_null_if_conflict);
        }

        /// 2.
        // If it's too late to start another meeting then go into the next day
        if ($starting_at + (int)$this->settings['meeting_length'] > ($this->latest_time_possible)) {
            $this->goto_next_day();
            return $this->next_time_slot($return_null_if_conflict);
        }

        /// 3.
        // If we are rescheduling "Push Date"ing. Then we can't start schedule til push date is past.
        if ( $this->start_push_date && $starting_at < $this->start_push_date) {
            return $this->next_time_slot();
        }


        /// 4. [Run through all the breaks
        if (isset($this->settings['breaks'][$this->day])) {
            // Make sure not interfering with any breaks.
            foreach($this->settings['breaks'][$this->day] as $break) {

                $ending_at = $starting_at + (int)$this->settings['meeting_length'];

                $break_start = strtotime( $this->current_date . " " . $break['start']);
                $break_end = strtotime( $this->current_date . " " . $break['end']);


                \Util\debug("{$starting_at} --- {$ending_at}  --->>> BREAKSTART = {$break_start} <<-->> BREAKEND {$break_end}<br><br>");
                // if meeting starts during break ($starting_at >= $break_start && $starting_at < $break_end)
                // or meeting ends during break  ($ending_at > $break_start && $ending_at < $break_end)
                if (($starting_at >= $break_start && $starting_at < $break_end) || ($ending_at > $break_start && $ending_at < $break_end)) {
                    if ($return_null_if_conflict)
                        return array('time' => $starting_at, 'conflict' => ' BREAK TIME ');

                    return $this->next_time_slot();
                }
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
            return null;
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

           /* if (!count($projects_in_slot)) {
                // If count is the same then there are no empty slots this time_slot.
                $this->next_time_slot();
                continue;
            }*/

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
            if ( ! $this->_has_collision_in($pid, $collides_with, 0) && !($this->_has_collision_at( $time_line->items[$i] ) || $this->_has_collision_at( $time_line )) ) {
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

                // Also, we need to check if this collision is a fixed meeting collision.
                if (isset($collision['fixed']) && !!$collision['fixed']) {
                    // This is not an exception. It's a fixed meeting. So we still return true.
                }
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
        global $debug_collisions;
        $is_colliding_with_comparators = in_array($pid, $compare_with);
        // If collision by id or we only care about collision by id.
        if ($is_colliding_with_comparators || $only_id) {
            // return if $pid in $compare_with
            return $is_colliding_with_comparators;
        }
        $test_project = $this->projects[$pid];

        foreach ($compare_with as $compare_id) {
            $compare_to = $this->projects[(int)$compare_id];

            $common_emails = array_intersect($compare_to->emails, $test_project->emails);
            if (count($common_emails)) {
                // TODO: REMOVE THIS IN PRODUCTION
                $email_list = implode(', ', $common_emails);
                $debug_collisions .= "<br><strong>COLLISION: </strong><code>[ID: {$compare_to->id}] & [ID: {$test_project->id}]</code><small>{$email_list}</small>";
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

    private function create_breaks_arrays()
    {
        $parsed_breaks = array();
        $len = count($this->run_dates) + 1;
        for ($i = 0; $i < $len; $i++) {
            $day_num = $i;
            $parsed_breaks[$i] = array();
            $breaks = !!\Util\post("breaks-day-{$day_num}") ? \Util\post("breaks-day-{$day_num}") : '';
            \Util\debug($breaks);
            if ($breaks !== '') {
                // There are breaks
                $breaks_input = explode(',', $breaks);
                foreach ($breaks_input as $break_string) {
                    $next_break = explode("-", $break_string);
                    if (count($next_break) === 2) {
                        // This will look like
                        /*array(array(
                            start => 09:00
                            end => 10:00
                        ), ...)*/
                        $next_break_settings = array();
                        $next_break_settings['start'] = $next_break[0];
                        $next_break_settings['end'] = $next_break[1];
                        array_push($parsed_breaks[$day_num], $next_break_settings);
                    }
                }
            }
        }

        return $parsed_breaks;
    }
}
