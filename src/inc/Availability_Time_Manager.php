<?php
/**
 * Created by Michael Rosata -- TQ-Soft -- Shai Dayan.
 * User: michael
 * Date: 5/26/16
 * Time: 11:59 AM
 */

namespace copro;

require_once 'Times_Utils.php';
require_once 'Time_Interval.php';

use Util\Times_Utils as Times_Utils;

class Availability_Time_Manager
{
    private $has_begun = 0;
    public $current_date;
    public $running_dates = array(/* Date Strings '2016-02-21' */);
    private $global_exceptions = array(/*
        ['2016-02-21'] => Time_Interval
    */);

    // same as above but using system id so that projects and
    // investors can be stored and looked up using the same
    // function. Only difference is this array is multi dimen
    // and the inner arrays use global id of investor or of the
    // project for whom the exceptions are for
    private $individual_exceptions = array();
    private $finished = false;
    // Current time can be used system wide to see what time we are currently
    // scheduling for.
    public $current_time = null;
    // Has the system gone through all the time?
    // Add extra time on_overflow_time
    public $on_overflow_time = 0;
    public $push_date = 0;

    function __construct() {
        if (!!Config::$push_date) {
            // This can safely be removed I believe.
            $this->push_date = Times_Utils::sanitize_time( Config::$push_date );
        }
    }


    /**
     * Return the next time, update the value $this->current_date
     */
    public function get_next_time() {
        // If this is the first time running then we need to get the time which
        // is safe to start at. Also we should set some flags so that the rest
        // of the system knows it is safe to start.
        $next_safe_time = (!$this->has_begun && is_null($this->current_time)) ? $this->_get_first_time() : $this->_get_next_safe_time();
        $this->current_time = $next_safe_time;
        $this->current_date = Times_Utils::x_to_format($next_safe_time, 'Y-m-d');
        $this->is_finished();
        return $this->current_time;
    }


    /**
     * Reset so the clock could be run again
     */
    function reset() {
        $this->finished = false;
        $this->has_begun = false;
        $this->current_date = $this->running_dates[ 0 ];
        $this->current_time = null;
        reset($this->running_dates);
    }
    
    
    function is_finished() {
        return !!$this->finished;
    }


    function is_available($global_id) {
        $this->current_date = Times_Utils::x_to_format($this->current_time, 'Y-m-d');

        if (!isset($this->individual_exceptions[$this->current_date])) {
            return true;
        }
        $exceptions = $this->individual_exceptions[ $this->current_date ];
        if (!isset($exceptions[$global_id]) || !is_array($exceptions[$global_id])) {
            return true;
        }

        $time = $this->current_time;
        foreach ( $exceptions[$global_id] as $interval ) {
            if ($interval->time_is_after($time)) {
                // This is after the end of the interval so it's safe from this one
                continue;
            }
            if ($interval->overlaps_time($time)) {
                // Time fell into this interval so we can't use it. increment and try again.
                return false;
            }
        }
        // This investor/project is cool.
        return true;
    }

    /**
     * Get the first time for the schedule
     * @return bool|int|null
     */
    private function _get_first_time() {
        $this->has_begun = true;
        $this->current_time = Times_Utils::sanitize_time("{$this->current_date} 00:00");

        if ($this->time_is_safe_to_schedule($this->current_time)) {
            return $this->current_time;
        };
        // Most likely the first time isn't safe, in which case we need to get the next safe time;
        return $this->_get_next_safe_time();
    }


    /**
     * Get the next safe time. This doesn't change the current time though.
     *
     * @return bool|null
     */
    private function _get_next_safe_time() {
        $next_time = $this->current_time + Config::$meeting_length;
        $i = 0;
        while (!$this->finished && !$this->time_is_safe_to_schedule($next_time)) {
            $next_time = $next_time + Config::$meeting_length;
            $i++;
            if ($i > 100000) {
                return false;
            }
        }
        return $next_time;
    }


    /**
     * Return this time or the next safe time to schedule
     *
     * @param $time
     * @return bool
     */
    function time_is_safe_to_schedule($time) {
        $global_intervals = $this->global_exceptions;

        // Need to track if the time falls after all intervals. Because that would mean that
        // we need to move to the next day.
        $time_is_after_all_intervals = true;

        foreach ( $global_intervals as $interval ) {
            if (!!$this->push_date && $interval->time_is_before($time)) {
                //return false;
            }
            if ($interval->time_is_after($time)) {
                // This is after the end of the interval so it's safe from this one
                continue;
            }
            if ($this->between_11_pm_and_1_am($time)) {
                return false;
            }
            if ($interval->overlaps_time($time)) {
                // Time fell into this interval so we can't use it. increment and try again.
                return false;
            }
            $time_is_after_all_intervals = false;
        }
        if ( $time_is_after_all_intervals ) {
            if (!$this->on_overflow_time) {
                // We switch to overflow time which should be an extra day ahead
                // so that they know to reschedule it. Change the modifier then
                // when it comes back it will return true on subsequent overflows
                // on_overflow_time shall be 0 when time is not in overflow.
                $this->on_overflow_time = Config::$OVERFLOW_TIME;
                return false;
            } else {
                $this->finished = true;
            }
            // THIS TIME IS OVERFLOW BUT WE SHALL SCHEDULE IT ANYWAYS
            return true;
        }

        // If the current time passed all those tests then it is safe to use
        return true;
    }


    function between_11_pm_and_1_am($time) {
        $hour = (int)Times_Utils::x_to_format( $time, 'H' );
        return in_array($hour, array(0, 23, 1));
    }


    function get_next_date( ) {
        //$this->current_date = next( $this->running_dates );
        if ( !$this->current_date ) {
            $this->finished = true;
        }
        return $this->current_date;
    }


    public function place_meeting($investor_id, $project_id, $meeting_name, $project_title, $start, $end, $send_to_calendar = true) {
        if ( is_null( $start ) ) {
            $start = Times_Utils::x_to_format($this->current_time, 'Y-m-d H:i');
            $end = Times_Utils::x_to_format($this->current_time + Config::$meeting_length, 'Y-m-d H:i');
        }

        if (Config::$finalize) {
            $meeting = array(
                'end' => $end,
                'start' => $start,
                'event_id' => Config::$event_id,
                'project' => $project_id,
                'investor' => $investor_id,
                'title'   => $meeting_name,
                'project_name' => $project_title,
            );
            // If meeting isn't supposed to be stored to calendar then send it to TQ-Tag
            if ($send_to_calendar) {
                // This is to make the incriment in the overflowed dates.
                if ($this->on_overflow_time > 0) {
                    $meeting['start'] = Times_Utils::x_to_format( Times_Utils::add_times_together( $start, $this->on_overflow_time ), 'Y-m-d H:i');
                    $meeting['end'] = Times_Utils::x_to_format( Times_Utils::add_times_together( $end, $this->on_overflow_time ), 'Y-m-d H:i');
                }
                Config::add_output( Config::insert_meeting_tqtag($meeting) );
            } else {
                // Some output for debug or information, this meeting should already be on schedule.
                Config::add_output( "(Already on calendar): {$meeting_name}" );
            }
        } else {
            if ($send_to_calendar) {
                //<div class='sched-temp' style='width: 500px;'>
                Config::add_output("<dl class='dl dl-horizontal horizontal'><dt>{$start}</dt><dd>{$meeting_name}</dd></dl>");
            } else {
                Config::add_output("<dl class='dl dl-horizontal horizontal'><dt>(ALREADY ON CALENDAR)</dt><dd>{$start} - {$meeting_name}</dd></dl>");
            }
        }
    }


    /**
     * Add a date to the array of dates that event runs
     *
     * 1. Make sure that the date passed in is formated properly as Y-m-d
     * 2. Add this properly formatted date to $this->running_dates
     * 3. Sort the dates so they are in order
     *
     * @param $date_or_time
     * @return int index of $date_or_time in the running_dates array
     * @throws \ErrorException
     */
    public function add_available_date($date_or_time) {
        if ($this->has_begun) {
            throw new \ErrorException("Can't change the dates after started scheduling");
        }
        // 1. 
        $yyyy_mm_dd = Times_Utils::x_to_format('Y-m-d', $date_or_time);
        // 2. 
        array_push($this->running_dates, $yyyy_mm_dd);
        // 3.
        $this->running_dates = Times_Utils::sort_chronologically($this->running_dates);

        // Always set the date to be the first day in the array
        $this->current_date = $this->running_dates[ 0 ];
        reset( $this->running_dates );

        $this->on_overflow_time = 0;
        return array_search($yyyy_mm_dd, $this->running_dates);
    }


    /**
     * Add a global exception time.
     * 
     * Global exception times mean that no one may schedule an appointment at this
     * time. 
     * 
     * @param $exception_start - date and time as str or int
     * @param $exception_end   - date and time as str or int
     */
    public function add_global_exception($exception_start, $exception_end) {
        $interval = new Time_Interval( $exception_start, $exception_end );
        array_push($this->global_exceptions, $interval);
    }


    /**
     * @param $system_id
     * @param $exception_start
     * @param $exception_end
     * @return null | bool
     */
    public function add_individual_exception($system_id, $exception_start, $exception_end) {
        $interval = new Time_Interval( $exception_start, $exception_end );
        $system_id = (int)$system_id;

        $index = Times_Utils::x_to_format($exception_start, 'Y-m-d');

        if ( !$index || !$exception_start || !$exception_end ) {
            echo "<br> Unable to make exception for args: {$system_id}, {$exception_start}, {$exception_end}";
            // We don't want to add array if we don't even have the correct params
            return null;
        }
        // There must be an array for this date. That array holds an array for each inv/project and their exceptions on the day
        if ( !isset( $this->individual_exceptions[ $index ] ) || !is_array( $this->individual_exceptions[ $index ] ) ) {
            $this->individual_exceptions[$index] = array();
        }
        // This would be the inner array, make sure it exists.
        if ( !isset( $this->individual_exceptions[ $index ][$system_id] ) || !is_array( $this->individual_exceptions[ $index ][$system_id] ) ) {
            $this->individual_exceptions[$index][$system_id] = array();
        }

        return array_push( $this->individual_exceptions[$index][$system_id], $interval );
    }


}