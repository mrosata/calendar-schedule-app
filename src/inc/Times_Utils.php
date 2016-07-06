<?php
/**
 * Created by Michael Rosata -- TQ-Soft -- Shai Dayan.
 *
 * Date: 5/26/16
 *
 * @description: This class is for time based utilities. Mainly it has comparision functions
 *               which make it easier to compare two times and determine whether they come
 *               before or after one another or if one date falls in between two other dates
 *               or times.
 */

namespace Util;


class Times_Utils
{
    
    /**
     * Convert a date or time into a different format.
     *
     * @param mixed $x        Date as string or time int
     * @param $format_string  Format specified by PHP date specifiers
     *              ex: 'Y-m-d'
     * @return string
     */
    static public function x_to_format($x, $format_string) {
        $x_as_time = Times_Utils::sanitize_time($x);
        return date($format_string, $x_as_time);
    }


    /**
     * Sort array from earliest to latest date.
     * @param $array
     * @param null $time_prop
     * @return mixed
     */
    static public function sort_chronologically(&$array, $time_prop = null) {
        if ( !is_null( $time_prop ) ) {
            usort($array, function($x, $y) use ($time_prop) {
                // The associative index $time_prop is the date/time. Sort by that.
                return Times_Utils::sanitize_time($x[$time_prop]) - Times_Utils::sanitize_time($y[$time_prop]);
            });
            return $array;
        }

        usort($array, function($x, $y) {
            // The value of the array is the date/time. Sort by that.
            return Times_Utils::sanitize_time($x) - Times_Utils::sanitize_time($y);
        });
        return $array;
    }


    /**
     * Does a time or date come between these 2 dates?
     *
     * Times_Utils::x_between_y_and_z('2015-10-10 10:10','2015-09-01 10:00','2015-10-19 10:10')
     * @param $time
     * @param $start
     * @param $end
     * @param int $inclusive_end - Should x == z be true? Default is false
     * @return bool
     */
    static public function x_between_y_and_z($time, $start, $end, $inclusive_end = 0) {
        $time = Times_Utils::sanitize_time($time);
        $start = Times_Utils::sanitize_time($start);
        $end = Times_Utils::sanitize_time($end);

        return Times_Utils::x_on_or_after($time, $start) && ($inclusive_end ? $time <= $end : $time < $end);
    }


    /**
     * Does time $x take place on or after $time?
     *
     * @param $x
     * @param $time
     * @return bool
     */
    static public function x_on_or_after($x, $time) {
        $x = Times_Utils::sanitize_time($x);
        $time = Times_Utils::sanitize_time($time);
        return $x >= $time;
    }


    /**
     * Does time $x come after passed in $time?
     * @param $x
     * @param $time
     * @return bool
     */
    static public function x_is_after($x, $time) {
        $x = Times_Utils::sanitize_time($x);
        $time = Times_Utils::sanitize_time($time);
        return $x > $time;
    }
    
    
    /**
     * Does time $x come before passed in $time?
     * @param $x
     * @param $time
     * @return bool
     */
    static public function x_is_before($x, $time) {
        $x = Times_Utils::sanitize_time($x);
        $time = Times_Utils::sanitize_time($time);
        return $x < $time;
    }
    
    
    /**
     * Does time/date $x take place on or before $time?
     *
     * @param $x
     * @param $time
     * @return bool
     */
    static public function x_on_or_before($x, $time) {
        $x = Times_Utils::sanitize_time($x);
        $time = Times_Utils::sanitize_time($time);
        return $x <= $time;
    }


    /**
     * Add 2 times together
     */
    static public function add_times_together($x, $y) {
        return self::sanitize_time( $x ) + self::sanitize_time( $y );
    }


    /**
     * Sanitize any time to be a time integer.
     *
     * @param mixed $time - string, Datetime or int.
     *      ex: '2016-02-25 14:12'
     *      ex: DateTime('2016-02-25 14:12')
     *      ex: 1231241412
     * @return int
     */
    static public function sanitize_time($time) {
        if (is_a($time, 'Time_Unit')) {
            $time = $time->as_int;
        }
        return is_string($time) ? strtotime($time) : (int)$time;
    }
}