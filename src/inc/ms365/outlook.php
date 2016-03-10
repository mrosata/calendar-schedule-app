<?php
/**
 * Created by michael on 2/24/16.
 */
namespace ms365;
function print_pre($r) {
    echo "<pre>";
    print_r( $r );
    echo "</pre>";
}
class OutlookService {
    public static function makeApiCall($access_token, $user_email, $method, $url, $payload = NULL) {
        // Generate the list of headers to always send.
        $headers = array(
            "User-Agent: schedule-app/2.0",         // Sending a User-Agent header is a best practice.
            "Authorization: Bearer ".$access_token, // Always need our auth token!
            "Accept: application/json",             // Always accept JSON response.
            "client-request-id: ".self::makeGuid(), // Stamp each new request with a new GUID.
            "return-client-request-id: true",       // Tell the server to include our request-id GUID in the response.
            "X-AnchorMailbox: ".$user_email         // Provider user's email to optimize routing of API call
        );

        $curl = curl_init($url);

        switch(strtoupper($method)) {
            case "GET":
                // Nothing to do, GET is the default and needs no
                // extra headers.
                error_log("Doing GET");
                break;
            case "POST":
                error_log("Doing POST");
                // Add a Content-Type header (IMPORTANT!)
                $headers[] = "Content-Type: application/json; odata=verbose";

                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                break;
            case "PATCH":
                error_log("Doing PATCH");
                // Add a Content-Type header (IMPORTANT!)
                $headers[] = "Content-Type: application/json";
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                break;
            case "DELETE":
                error_log("Doing DELETE");
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                error_log("INVALID METHOD: ".$method);
                exit;
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);
        // print_pre($response);
        error_log("curl_exec done.");

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        error_log("Request returned status ".$httpCode);
        if ($httpCode >= 400) {
            return array('errorNumber' => $httpCode,
                         'error' => 'Request returned HTTP error '.$httpCode);
        }

        $curl_errno = curl_errno($curl);
        $curl_err = curl_error($curl);
        if ($curl_errno) {
            $msg = $curl_errno.": ".$curl_err;
            error_log("CURL returned an error: ".$msg);
            curl_close($curl);
            return array('errorNumber' => $curl_errno,
                         'error' => $msg);
        }
        else {
            error_log("Response: ".$response);
            curl_close($curl);
            return json_decode($response, true);
        }
    }



    // This function convert a dateTime from local TZ to UTC, then
    // encodes it in the format expected by the Outlook APIs.
    public static function encodeDateTime($dateTime) {
        $utcDateTime = $dateTime->setTimeZone(new \DateTimeZone("UTC"));

        $dateFormat = "Y-m-d\\TH:i:00";
        return preg_replace('|\-(\d+)+$|', '.$1', date_format($utcDateTime, $dateFormat));
    }


    // This function generates a random GUID.
    public static function makeGuid(){
        if (function_exists('com_create_guid')) {
            error_log("Using 'com_create_guid'.");
            return strtolower(trim(com_create_guid(), '{}'));
        }
        else {
            error_log("Using custom GUID code.");
            $charid = strtolower(md5(uniqid(rand(), true)));
            $hyphen = chr(45);
            $uuid = substr($charid, 0, 8).$hyphen
                    .substr($charid, 8, 4).$hyphen
                    .substr($charid, 12, 4).$hyphen
                    .substr($charid, 16, 4).$hyphen
                    .substr($charid, 20, 12);

            return $uuid;
        }
    }


    private static $outlookApiUrl = "https://outlook.office.com/api/beta";

    /**
     * Get Email
     *
     * @param $access_token
     * @param $user_email
     *
     * @return array|mixed
     */
    public static function getMessages($access_token, $user_email) {
        $getMessagesParameters = array (
            // Only return Subject, ReceivedDateTime, and From fields
            "\$select" => "Subject,ReceivedDateTime,From",
            // Sort by ReceivedDateTime, newest first
            "\$orderby" => "ReceivedDateTime DESC",
            // Return at most 10 results
            "\$top" => "10"
        );

        $getMessagesUrl = self::$outlookApiUrl."/Me/Messages?".http_build_query($getMessagesParameters);

        return self::makeApiCall($access_token, $user_email, "GET", $getMessagesUrl);
    }



    /**
     * Get Calendar Contacts
     *
     * @param $access_token
     * @param $user_email
     *
     * @return array|mixed
     */
    public static function getContacts($access_token, $user_email) {
        $getContactsParameters = array (
            // Only return GivenName, Surname, and EmailAddresses fields
            "\$select" => "GivenName,Surname,EmailAddresses",
            // Sort by GivenName, A-Z
            "\$orderby" => "GivenName",
            // Return at most 10 results
            "\$top" => "10"
        );

        $getContactsUrl = self::$outlookApiUrl."/Me/Contacts?".http_build_query($getContactsParameters);

        return self::makeApiCall($access_token, $user_email, "GET", $getContactsUrl);
    }


    /**
     * Get Calendar Events
     *
     * @param $access_token
     * @param $user_email
     *
     * @param null $calendar_id
     *
     * @return array|mixed
     */
    public static function getEvents($access_token, $user_email, $calendar_id=null) {
        $getEventsParameters = array (
            // Only return Subject, Start, and End fields
            "\$select" => "Subject,Organizer,Start,End",
            // Sort by Start, oldest first
            "\$orderby" => "Start/DateTime",
            // Return at most 10 results
            "\$top" => "50"
        );

        $getEventsUrl = ! is_null( $calendar_id ) ?
            self::$outlookApiUrl . "/me/calendars/{$calendar_id}/events?" . http_build_query( $getEventsParameters ) :
            self::$outlookApiUrl . "/me/events?" . http_build_query( $getEventsParameters );

        return self::makeApiCall($access_token, $user_email, "GET", $getEventsUrl);
    }



    public static function createCalendar($access_token, $info, $return_failed_resp = 0) {
        // If $info is array then we are creating calendar in a group
        if ( is_array( $info ) && isset($info['group'])) {
            $calendar = array(
                "Name" => $info['name'],
            );
            $calendar_group_id = $info['group'];

            $createCalendarUrl = self::$outlookApiUrl."/me/calendargroups/{$calendar_group_id}/calendars";
        }
        else {
            $calendar = array(
                "Name" => is_array($info) ? $info['name'] : $info,
            );
            $createCalendarUrl = self::$outlookApiUrl."/me/calendars";
        }

        $calendarPayload = json_encode($calendar);


        $response = self::makeApiCall($access_token, $_SESSION['user_email'], "POST", $createCalendarUrl, $calendarPayload);

        // If the call succeeded, the response should be a JSON representation of the
        // new event. Try getting the Id property and return it.
        if (isset($response['Id'])) {
            return $response;
        }

        else {
            error_log("ERROR CREATING CALENDAR: {$response}");
            return $return_failed_resp ? $response : null;
        }
    }


    public static function createCalendarGroup($access_token, $name, $return_failed_resp = 0) {
        $calendar = array(
            "Name" => $name,
        );

        $calendarPayload = json_encode($calendar);

        $createCalendarUrl = self::$outlookApiUrl."/me/calendargroups";

        $response = self::makeApiCall($access_token, $_SESSION['user_email'], "POST", $createCalendarUrl, $calendarPayload);

        // If the call succeeded, the response should be a JSON representation of the
        // new event. Try getting the Id property and return it.
        if (isset($response['Id'])) {
            return $response;
        }

        else {
            print_pre( $response );
            error_log("ERROR CREATING CALENDAR: {$response}");
            return $return_failed_resp ? $response : null;
        }
    }

    /**
     * Get All Calendar Groups
     * @param $access_token
     * @param $user_email
     *
     * @return array|mixed
     */
    public static function getCalendars($access_token, $user_email, $calendar_group_id = null) {

        $getCalendarGroupsURI = is_null($calendar_group_id) ?
            self::$outlookApiUrl."/me/calendars?" :
            self::$outlookApiUrl."/me/calendargroups/{$calendar_group_id}/calendars?";

        return self::makeApiCall($access_token, $user_email, "GET", $getCalendarGroupsURI);
    }


    /**
     * Get All Calendar Groups
     * @param $access_token
     * @param $user_email
     *
     * @return array|mixed
     */
    public static function getCalendarGroups($access_token, $user_email) {

        $getCalendarGroupsURI = self::$outlookApiUrl."/me/calendargroups?";

        return self::makeApiCall($access_token, $user_email, "GET", $getCalendarGroupsURI);
    }


    /**
     * Get Single Calendar Group
     *
     * @param $access_token
     * @param $user_email
     *
     * @param $id
     *
     * @return array|mixed
     */
    public static function getCalendarGroup($access_token, $user_email, $id) {
        $id = urlencode($id);

        $getCalendarGroupURI = self::$outlookApiUrl."/me/calendargroups/{$id}?";

        return self::makeApiCall($access_token, $user_email, "GET", $getCalendarGroupURI);
    }


    public static function deleteEvent($access_token, $user_email, $event_id) {
        $deleteEventURI = self::$outlookApiUrl."/me/events/{$event_id}?";

        return self::makeApiCall($access_token, $user_email, "DELETE", $deleteEventURI);
    }



    public static function deleteCalendar($access_token, $user_email, $calendar_id) {
        $deleteEventURI = self::$outlookApiUrl."/me/calendars/{$calendar_id}?";

        return self::makeApiCall($access_token, $user_email, "DELETE", $deleteEventURI);
    }


    public static function addEventToCalendar($access_token, $config, $return_resp_on_error = 0) {
        $odata_filters = '';
        $calendar_app_id = null;
        // Generate the JSON payload
        $event = array(
            "Body"    => array( "ContentType" => "HTML", "Content" => '' )
        );

        foreach ($config as $setting => $value) {
            switch ( strtoupper( $setting ) ) {
                case "SUBJECT":
                    $event['Subject'] = $value;
                    break;

                case "CONTENT":
                    if (is_array($value))
                        $event['Body'] = $value;
                    else
                        $event['Body'] = array( "ContentType" => "HTML", "Content" => $value );

                    break;

                case "LOCATION":
                    $event["Location"] = array("DisplayName" => $value);
                    break;

                case "START":
                    $event["Start"] = array(
                        "DateTime" => self::encodeDateTime($value),
                        "TimeZone" => "UTC"
                    );
                    break;

                case "END":
                    $event["End"]     = array(
                        "DateTime" => self::encodeDateTime($value),
                        "TimeZone" => "UTC"
                    );
                    break;

                case "ATTENDEES":
                    $attendees = array();
                    if ( is_array( $value )  ) {
                        foreach ( $value as $address_info ) {
                            error_log( "Adding {$address_info['name']} -- email: {$address_info['address']}" );

                            $attendees[] = array(
                                "EmailAddress" => array(
                                    "Name"    => $address_info['name'],
                                    "Address" => $address_info['address']
                                ),
                                "Type"         => "Required"
                            );
                        }
                    }
                    $event["Attendees"] = $attendees;
                    break;

                case "ID":
                    $calendar_app_id = $value;
                    break;

                case "ODATA":
                    $odata_filters = is_array($value) ? implode('&', array_map(function($key, $val) {
                        return "{$key}=" . urlencode($val);
                    }, array_keys($value), $value)) : $value;
                    break;

                default:
                    break;
            }
        }

        $eventPayload = json_encode( $event );

        if (is_null($calendar_app_id)) {
            $createEventUrl = self::$outlookApiUrl . "/me/events?{$odata_filters}";
        } else {
            $createEventUrl = self::$outlookApiUrl . "/me/calendars/$calendar_app_id/events?{$odata_filters}";
        }

        $response = self::makeApiCall( $access_token, $_SESSION['user_email'], "POST", $createEventUrl, $eventPayload );

        // If the call succeeded, the response should be a JSON representation of the
        // new event. Try getting the Id property and return it.
        if ( isset( $response['Id'] ) ) {
            return $response;
        } else {
            return $response;
        }
    }

}
