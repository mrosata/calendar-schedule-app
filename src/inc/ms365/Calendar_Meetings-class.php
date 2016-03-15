<?php
namespace ms365;
require_once "outlook.php";

/**
 * Created by michael on 2/26/16.
 */

class Calendar_Meetings_API {

    private $access_token;
    private $user_email;

    function __construct($access_token=null, $user_email=null) {
        $this->access_token = is_null($access_token) && isset($_SESSION['access_token']) ?
            $_SESSION['access_token'] : $access_token;
        $this->user_email = is_null($user_email) && isset($_SESSION['user_email']) ?
            $_SESSION['user_email'] : $user_email;

    }


    public function get_events($calendar_id = null) {
        return OutlookService::getEvents( $this->access_token, $this->user_email, $calendar_id );
    }

    public function get_calendars($calendar_group_id = null) {
        return OutlookService::getCalendars( $this->access_token, $this->user_email, $calendar_group_id );
    }

    public function get_calendar_groups() {
        return OutlookService::getCalendarGroups( $this->access_token, $this->user_email );
    }

    public function get_calendar_group($id) {
        return OutlookService::getCalendarGroup( $this->access_token, $this->user_email, $id );

    }

    public function create_calendar_group($group_name) {
        $group = OutlookService::createCalendarGroup( $this->access_token, $group_name );
        return (is_array($group) && isset($group['Id'])) ? $group['Id'] : null;
    }

    public function create_calendar($calendar_name) {
        return OutlookService::createCalendar( $this->access_token, $calendar_name, 1 );
    }


    /**
     * Lookup a calendar by name. This will get all the users calendars and then check for a
     * calendar matching the name. If found it will return the calendar id, else null;
     *
     * @param $calendar_name
     *
     * @return null|string  - calendar id or null
     */
    public function get_calendar_by_name( $calendar_name ) {
        $all_calendars = OutlookService::getCalendars($this->access_token, $this->user_email);

        if (is_array($all_calendars) && isset($all_calendars['value'])) {
            foreach($all_calendars['value'] as $calendar) {
                if (trim($calendar['Name']) == trim($calendar_name)) {
                    return $calendar['Id'];
                }
            }
        }
        return null;
    }


    /**
     * Lookup a calendar by its name and create it if not already there. Returns calendar id
     *
     * @param $calendar_name
     * @param null $group_id
     *
     * @return null|string  - should return id of calendar in string or null if something went wrong.
     */
    public function get_or_create_calendar_id( $calendar_name, $group_id =null ) {
        $calendar_id = $this->get_calendar_by_name( $calendar_name );

        if ( !is_null( $calendar_id ) ) {
            return $calendar_id;
        }
        $calendar = array('name'=> $calendar_name);
        if (!is_null($group_id)) {
            $calendar['group'] = $group_id;
        }
        $cal = OutlookService::createCalendar( $this->access_token, $calendar );

        return (is_array($cal) && isset($cal['Id'])) ? $cal['Id'] : null;
    }


    /**
     * Delete a single event using event_id.
     *
     * @param $event_id
     *
     * @return array|mixed
     */
    public function delete_event($event_id) {
        return OutlookService::deleteEvent($this->access_token, $this->user_email, $event_id);
    }

    public function delete_calendar($calendar_id) {
        return OutlookService::deleteCalendar($this->access_token, $this->user_email, $calendar_id);
    }

    public function delete_all_calendars() {
        $deleted = array();
        $calendars = self::get_calendars();
        foreach($calendars['value'] as $calendar) {
            if (isset($calendar['Id'])) {
                array_push($deleted, $calendar);
                OutlookService::deleteCalendar($this->access_token, $this->user_email, $calendar['Id']);
            }
        }
        return $deleted;
    }

    public function delete_all_events($calendar_id) {
        $events = $this->get_events( $calendar_id );
        while (is_array($events['value']) && count($events['value'])) {
            foreach ($events['value'] as $event) {
                error_log( "About to delete: " . $event['Id'] );
                error_log( "Subject: " . $event['Subject'] );
                $this->delete_event($event['Id']);
                ob_flush();
            }
            try {
                $events = $this->get_events( $calendar_id );
            }
            catch(\ErrorException $e) {
                return false;
            }
        }
        return true;
    }


    public function create_event($event = array(), $return_resp_on_error = 0) {
        /*
        $event = array(
            'start' => new DateTime('now + 97 hour'),
            'end' => new DateTime('now + 100 hour'),

            'subject' => "This is from the app!",
            'content' => "Yea! this is from the app",
            'location' => "Boston, MA",
            'attendees' => array(
                array(
                    'address' => 'mrosata@outlook.com',
                    'name' => 'Michael Rosata'
                ),
                array(
                    'address' => 'mrosata1984@gmail.com',
                    'name' => 'Pizza Pepsi-cola'
                )
            )
        );
        */
        return OutlookService::addEventToCalendar($this->access_token, $event, $return_resp_on_error);
    }
}
