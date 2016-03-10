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



    public function get_or_create_calendar_id( $calendar_name = 'New Calendar', $group_id =null ) {
        $all_calendars = OutlookService::getCalendars($this->access_token, $this->user_email);

        \Util\print_pre($all_calendars);
        if (is_array($all_calendars) && isset($all_calendars['value'])) {
            foreach($all_calendars['value'] as $calendar) {
                if (trim($calendar['Name']) == trim($calendar_name)) {
                    return $calendar['Id'];
                }
            }
        }

        $calendar = array('name'=> $calendar_name);
        if (!is_null($group_id)) {
            $calendar['group'] = $group_id;
        }
        $cal = OutlookService::createCalendar( $this->access_token, $calendar );
        \Util\print_pre( $cal );
        return (is_array($cal) && isset($cal['Id'])) ? $cal['Id'] : null;
    }


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

    public function delete_all_events($calendar_group_id = null) {

        /* TODO: OB_START AND FLUSH THIS EVERY ONCE AND WHILE */
        if ( ! is_null( $calendar_group_id ) ) {
            $calendars = $this->get_calendars( $calendar_group_id );
        } else {
            $calendars = $this->get_calendars();
        }

        if (is_array($calendars) && isset($calendars['value']) && is_array($calendars['value'])) {
            foreach ($calendars['value'] as $cal) {
                $events = $this->get_events($cal['Id']);
                foreach ($events['value'] as $event) {
                    $this->delete_event($event['Id']);
                }
            }
        } else {/*
            echo "<h2>Called from deleted all events?</h2>";
            \Util\print_pre($calendars);*/
        }

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
