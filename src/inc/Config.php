<?php
/**
 * Created by Michael Rosata -- TQ-Soft -- Shai Dayan.
 *
 * Project: sched.copro.co.il
 * Date: 5/26/16
 *
 * @description Get any of the configuration type variables that might have been
 *              set inside of the copro form
 */


namespace copro;

require_once 'Times_Utils.php';

use Util\Times_Utils as Time_Utils;

class Config
{

    // OVERFLOW TIME IS TIME TO ADVANCE WHEN IN OVERFLOW
    // (So there is a gap between real meetings)
    static $OVERFLOW_TIME = 108000;
    static $push_date;
    static $event_id;
    static $section_ids;
    static $running_dates;
    static $breaks;
    static $conflicts;
    static $finalize;
    static $meeting_length;
    static $output = '';
    static $import;
    static $export;
    static $update;

    static function add_output($str) {
        self::$output .= $str;
    }
    static function prepend_output($str) {
        self::$output = $str . self::$output;
    }
    /**
     * Get a global ID which can be used for any object. So Investors
     * and projects can both use this function to get id numbers and
     * not have to worry about collisions in id;
     *
     * @return int
     */
    static function get_global_id() {
        static $gid = 1;
        return $gid++;
    }
    /**
     * Get all the configuration values from the form and store the in
     * static properties that are easier to get from all around the
     * app. This function is called as soon as this class declaration
     * closes.
     */
    static function parse_form() {
        self::$push_date  = self::get_push_date();
        self::$event_id = (int)self::get_event_id();
        self::$section_ids = self::get_section_ids();
        self::$running_dates = self::get_events_running_dates();
        self::$breaks = self::get_breaks();
        self::$conflicts = self::get_conflicts();
        self::$meeting_length = self::get_meeting_length();
        self::$finalize = self::get_finalize_value();
        self::$import = self::get_api_config('import');
        self::$export = self::get_api_config('export');
        self::$update = self::get_api_config('update');
    }


    /**
     * Get the meeting length from form that is integer in seconds
     *
     * @return int
     */
    static function get_meeting_length() {
        return !!\Util\post( 'meeting-length' ) ? (int)\Util\post( 'meeting-length' ) * 60 : 1200;
    }
    /**
     * Return the raw form push-date value if set.
     * @return null
     */
    static function get_push_date() {
        return \Util\post('push-date');
    }


    /**
     * Get the Event ID from form
     * @return null
     */
    static function get_event_id() {
        return \Util\post('dates-event-id');
    }

    /**
     * Get the Event ID from form
     * @return null
     */
    static function get_api_config($api_property) {
        return \Util\post($api_property);
    }

    static function get_finalize_value() {
        return ! !\Util\post( 'finalize' );
    }
    /**
     * Get Array of Section ID integers from form
     * @return array
     */
    static function get_section_ids() {
        $section_id_string = \Util\post( 'section-id' );
        $arr = explode(",", $section_id_string);
        if (!is_array($arr)) {
            $arr = array();
        }
        foreach ($arr as $key => $item) {
            if ($item === '') {
                unset( $arr[$key] );
            } else {
                $arr[$key] = (int)$item;
            }
        }
        return $arr;
    }

    /**
     * Get all the dates for the event being run
     * 
     * @return array
     */
    static function get_events_running_dates() {
        $running_dates = array();
        // The first day num on form is 1.
        $day_num = 1;

        while (!!\Util\post("day-{$day_num}")) {
            $running_dates[] = Time_Utils::x_to_format( \Util\post("day-{$day_num}"), 'Y-m-d' );
            $day_num++;
        }

        return $running_dates;
    }


    /**
     * Get All Breaks in array that has 'Y-m-d H:i' for  every single break
     * in the form. This combines the dates to breaks automatically.
     * @return array
     */
    static function get_breaks() {
        $rv = array();
        
        // The first day num on the form is 1
        $day_num = 1;
        while (!!\Util\post("breaks-day-{$day_num}") && !!\Util\post("day-{$day_num}")) {
            // the_day will be date string such as '2016-05-23'
            $the_day = Time_Utils::x_to_format(\Util\post("day-{$day_num}"), 'Y-m-d');
            
            $breaks_str = \Util\post("breaks-day-{$day_num}");
            if ($breaks_str !== '') {
                // There are breaks
                $breaks_input = explode(',', $breaks_str);
                foreach ($breaks_input as $break) {
                    $next_break = explode("-", $break);
                    if (count($next_break) > 1) {
                        $start = trim($next_break[0]);
                        $end = trim($next_break[1]);
                        
                        $rv[] = array(
                            'start' => "{$the_day} {$start}",
                            'end'   => "{$the_day} {$end}",
                        );
                    }
                }
            }
            $day_num++;
        }
        
        return $rv;
    }


    static function get_conflicts() {
        $conflicts = array();
        if ( !!\Util\post( 'conflicts' ) ) {
            try {
                // If conflicts-from-javascript then use json, else use PHP unserialize
                $conflicts = !!\Util\post('conflicts-from-javascript') ? json_decode(\Util\post( 'conflicts' ), 1) : json_decode(base64_decode(\Util\post('conflicts')), 1);
            }
            catch (\ErrorException $e) {
                $conflicts = array();
            }
        }

        return $conflicts;
    }

    /**
     * array(
     *   project_id: int
     *   investor_id: int
     *   starts: DateTime
     *   ends: DateTime
     * )
     * @param $meeting
     */
    static function insert_meeting_tqtag( $meeting ) {
        global $debug_url_calls;
        $event_id = trim($meeting['event_id'] );
        $project_id = trim($meeting['project'] );
        $project_name = trim(rawurlencode($meeting['project_name']) );
        $investor_id = trim($meeting['investor'] );
        $title = trim(rawurlencode($meeting['title']) );
        $starts = trim(rawurlencode(is_a( $meeting['start'], '\DateTime' ) ? $meeting['start']->format('Y-m-d H:i:s') : $meeting['start']) );
        $ends = trim(rawurlencode(is_a( $meeting['end'], '\DateTime') ? $meeting['end']->format('Y-m-d H:i:s') : $meeting['end']) );

        $insert_url = "http://copro.tqsoft.co.il/?mode=insertContent&insertSectionID=144&projectID={$project_id}&investorID={$investor_id}&meetingStart={$starts}&meetingEnd={$ends}&eventID={$event_id}&01MovieName={$title}&title={$project_name}&jsonResponse=1";
        $file_get_contents = @file_get_contents($insert_url);
        $debug_url_calls[] = array(
            'URL' => $insert_url,
            'result' => $file_get_contents
        );

        return $file_get_contents;
    }

}


/**
 * RUN THIS TO PREPARE THE CONFIG STATIC
 * PROPERTIES FOR EASY ACCESS TRHOUGHOUT
 * THE APPLICATION.
 */
Config::parse_form();