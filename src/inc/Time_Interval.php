<?php
/**
 * Created by Michael Rosata -- TQ-Soft -- Shai Dayan.
 *
 * Date: 5/26/16
 *
 * @description Time_Interval is a class which creates objects that can be used to
 *              represent a duration of time. A Time_Interval instance has methods
 *              for the user to ask whether or not another time might fall inside
 *              of the interval.
 */

namespace copro;

use Util\Times_Utils as Times_Utils;

class Time_Unit
{
    public $as_raw;      // Value passed in upon construction
    public $as_int;   // Time_Unit as a integer

    function __construct($time)
    {
        // It's important to use sanitize_time as it keep consistancy across code
        $this->as_int = Times_Utils::sanitize_time($time);
        $this->as_raw = $time;
    }

    function date($format = 'Y-m-d') {
        return date($format, $this->as_int);
    }

}


class Time_Interval
{
    private $start;  // time integer
    private $end;    // time integer
    private $date;   // '2016-06-16'

    /**
     * Time_Interval constructor.
     *
     * @param $start - date and time as string or int
     * @param $end   - date and time as string or int
     */
    function __construct($start, $end)
    {
        $this->start = new Time_Unit($start);
        $this->end   = new Time_Unit($end);
        $this->date  = $this->start->date();
    }


    /**
     * Does time fall inside of this Interval?
     *
     * @param     $time
     * @param int $inclusive_end - should end be considered inclusive? This is set as
     *                           false by default so if $time falls at the end of
     *                           interval then it does not interfere.
     * @return bool
     */
    function overlaps_time($time, $inclusive_end = 0) {
        $time = Times_Utils::sanitize_time($time);
        return Times_Utils::x_between_y_and_z($time, $this->start->as_int, $this->end->as_int, $inclusive_end);
    }

    function time_is_after($time) {
        return Times_Utils::x_is_after($time, $this->end->as_int);
    }

    function time_is_before($time) {
        return Times_Utils::x_is_before($time, $this->end->as_int);
    }
}