<?php

/**
 * Created by michael on 2/23/16.
 */

namespace ms365;

class Office_API {

    private $app_token;
    private $access_token;
    private $username;

    /**
     * Office_API constructor.
     * -- Takes credentials for Office App and sets up OAuth2 tokens.
     */
    function __construct() {

    }

}



class Outlook_Calendar extends Office_API {


    function __construct() {

    }

    function create_event($title, $desc, $start, $end) {
        echo "<br><strong>Created Event!</strong>";
        echo "<br>Title: {$title}";
        echo "<br>Desc: {$desc}";
        echo "<br>Start: {$start}";
        echo "<br>End: {$end}";
        return true;
    }
}
