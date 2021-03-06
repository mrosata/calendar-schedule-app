<?php
/**
 * Created by:
 *      Stayshine Web Development
 * @author  Michael Rosata
 * @url     www.stayshine.com
 * @date    3/15/16
 */
ini_set('display_errors', 1);
ini_set('output_buffering', 1);
error_reporting(E_ALL);
mb_internal_encoding("UTF-8");
define('ROOT_DIR', __DIR__);
session_start();
ob_start();
header('Access-Control-Allow-Origin: *');
require_once 'inc/utils.php';

$event_id = \Util\post('event-id');
if ( is_null( $event_id ) || (mb_strtoupper($_SERVER['REQUEST_METHOD']) != "POST")) {
    ob_clean();
    echo json_encode(array('success'=>0));
    exit;
}

/**
 * SETTINGS FOR THE SCHEDULER MEETINGS
 */

require_once 'inc/connection.php';


class Ajax_Database_Connection {

    private $conn;
    public $today;
    private $queries = array(
        'exceptions' => 'select id as exceptionID,exceptionEmail,exceptionStartTime,exceptionEndTime, (select title as eventDate from tbladminssections where parentID = :event_id limit 1) as eventDate from tblcontentitemsapprvoed inner join tblcontentitemsextensionapproved on tblcontentitemsapprvoed.itemID=tblcontentitemsextensionapproved.itemID where sectionID= :section_id',

        'dates-by-event' => 'select id AS dateID, title as eventDate, content as eventBreaks from tbladminssections where parentID = :event_id',

        'conflict-times' => 'select id as exceptionID, exceptionEmail, exceptionStartTime, exceptionEndTime from tblcontentitemsapprvoed inner join tblcontentitemsextensionapproved on tblcontentitemsapprvoed.itemID = tblcontentitemsextensionapproved.itemID where sectionID = :section_id'
    );

    function __construct(\PDO $conn) {
        $this->conn = $conn;
        $this->today = date('Y-m-d');
    }

    function get_dates_by_event_id($event_id) {
        return $this->prep_statement('dates-by-event', array('event_id' => (int)$event_id));
    }

    function get_conflicts_by_date_id( $section_id ) {
        return $this->prep_statement('conflict-times', array('section_id' => (int)$section_id));
    }


    function prep_statement($statement_name, $args=array(), $replace_with = array()) {
        if ( ! isset( $this->queries[ $statement_name ] ) ) {
            return 0;
        }

        $statement = $this->queries[ $statement_name ];
        if ( count($replace_with) ) {
            foreach( $replace_with as $replace => $change) {
                $statement = str_replace( $replace, $change, $statement );
            }
        }

        try {
            $stmt = $this->conn->prepare($statement);
            $stmt->execute($args);

            $rv = array();
            while ( $row = $stmt->fetchObject() ) {
                array_push($rv, $row);
            }
            return $rv;
        }
        catch(\PDOException $e) {
            echo 'ERROR: ' . $e->getMessage();
        }

        return 0;
    }

    function get_project_data($section_id) {
        return $this->prep_statement( 'project_data', array('section_id' => (int)$section_id) );
    }

}




$res = array('success'=>0, 'data'=>'Not logged in.');

try {
    $db = new Ajax_Database_Connection( \Connection\get_connection() );

    $dates = $db->get_dates_by_event_id( $event_id );
    if (!$dates) {
        throw new ErrorException("Wasn't able to get the dates from Database.");
    }
    $date_array = array();
    $conflicts = array();
    $breaks = array();
    // date map for breaks is used so after we sort dates we can still match them to correct breaks
    $date_map_for_breaks = array();
    foreach($dates as $date) {
        // Check if we got a date (because query returns random words as dates some cases)
        if (! preg_match('|^\d{4,}-\d{2,}-\d{2,}|', trim($date->eventDate)) ) {
            // We should continue if the date doesn't match.. or else we can't have date for conflicts
            continue;
        }
        $temp_date = date('Y-m-d', strtotime($date->eventDate));
        // TODO: This forces the hour b/c client doesn't want to store hours. Remove this if they change mind
        array_push($date_array, $temp_date . ' 00:00:00');

        $date_map_for_breaks[$temp_date . ' 00:00:00'] = str_replace("\n", ',', $date->eventBreaks);
        // Now get any conflicts for this date
        $conflict_res = $db->get_conflicts_by_date_id( $date->dateID );
        if ( !$conflict_res || !is_array( $conflict_res ) ) {
            continue;

        }
        foreach ( $conflict_res as $conflict ) {
            // If not a valid time then continue to the next conflict
            if (! preg_match('|\d{1,2}:\d{2,}|', trim($conflict->exceptionEndTime)) || ! preg_match('|\d{1,2}:\d{2}|', trim($conflict->exceptionStartTime)) )
                continue;
            // If it is a valid time then push it to the object.
            if (!isset($conflicts[$conflict->exceptionEmail]) || !is_array($conflicts[$conflict->exceptionEmail])) {
                $conflicts[$conflict->exceptionEmail] = array();
            }
            array_push($conflicts[$conflict->exceptionEmail], array(
                'from' => ("{$temp_date} {$conflict->exceptionStartTime}"),
                'to' => ("{$temp_date} {$conflict->exceptionEndTime}")
            ));
        }
    }
    sort($date_array);
    foreach ($date_array as $date) {
        // create breaks array in same order as sorted dates.
        array_push($breaks, $date_map_for_breaks[$date]);
    }
    $res = array('success'=>1, 'args' => $event_id, 'data'=>array('dates'=>$date_array, 'conflicts'=>$conflicts, 'breaks' => $breaks));
} catch (ErrorException $e) {
    $res = array('success'=>0, 'data'=>$e->getMessage());
}

ob_clean();
echo json_encode( $res );
exit;
