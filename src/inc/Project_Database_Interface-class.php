<?php
/**
 * Created by michael on 3/2/16.
 */


namespace copro;
require_once 'utils.php';


class Database_Queries {
/*
    tblcontentitemsextension.projectID as projectID
    tblcontentitemsextension.investorID as investorID
    tblcontentitemsextension.meetingStart as start
    tblcontentitemsextension.meetingEnd as end
    tblcontentitemsextension.eventID = id of the event
*/

    public $queries = array(
        'all_meetings' => "SELECT itemID as id, eventID as event_id, 01MovieName as title, investorID as investor, projectID as project, meetingStart as start, meetingEnd as `end` FROM `tblcontentitemsextension` JOIN tblcontentitems ON tblcontentitemsextension.itemID=tblcontentitems.id WHERE tblcontentitems.sectionID = 144",
        'event_meetings' => "SELECT  itemID as id, eventID as event_id, 01MovieName as title, investorID as investor, projectID as project, meetingStart as start, meetingEnd as `end` FROM `tblcontentitemsextension` JOIN tblcontentitems ON tblcontentitemsextension.itemID=tblcontentitems.id WHERE tblcontentitems.sectionID = 144 AND tblcontentitemsextension.eventID = :event_id",
        'event_investor_meetings' => "SELECT  itemID as id, eventID as event_id, 01MovieName as title, investorID as investor, projectID as project, meetingStart as start, meetingEnd as `end` FROM `tblcontentitemsextension` JOIN tblcontentitems ON tblcontentitemsextension.itemID=tblcontentitems.id WHERE tblcontentitems.sectionID = 144 AND tblcontentitemsextension.eventID = :event_id AND investorID = :investor_id",
        'event_investors_meetings' => "SELECT  itemID as id, eventID as event_id, 01MovieName as title, investorID as investor, projectID as project, meetingStart as start, meetingEnd as `end` FROM `tblcontentitemsextension` JOIN tblcontentitems ON tblcontentitemsextension.itemID=tblcontentitems.id WHERE tblcontentitems.sectionID = 144 AND tblcontentitemsextension.eventID = :event_id AND investorID IN ( :investor_ids )",
        'event_project_meetings' => "SELECT  itemID as id, eventID as event_id, 01MovieName as title, investorID as investor, projectID as project, meetingStart as start, meetingEnd as `end` FROM `tblcontentitemsextension` JOIN tblcontentitems ON tblcontentitemsextension.itemID=tblcontentitems.id WHERE tblcontentitems.sectionID = 144 AND tblcontentitemsextension.eventID = :event_id AND projectID = :project_id",
        'event_projects_meetings' => "SELECT  itemID as id, eventID as event_id, 01MovieName as title, investorID as investor, projectID as project, meetingStart as start, meetingEnd as `end` FROM `tblcontentitemsextension` JOIN tblcontentitems ON tblcontentitemsextension.itemID=tblcontentitems.id WHERE tblcontentitems.sectionID = 144 AND tblcontentitemsextension.eventID = :event_id AND projectID IN ( :project_ids )",
        'projects_since' => "SELECT * FROM tblcontentitems WHERE active = 'on' AND addDate > :date",
        //'projects_with_meetings' => "select tblcontentitemsapprvoed.itemID as id, 01MovieName as project_title, 00InvIntrest as interested from tblcontentitemsapprvoed inner join tblcontentitemsextensionapproved on tblcontentitemsapprvoed.itemID=tblcontentitemsextensionapproved.itemID where sectionID IN( :section_id ) and active='on' and deleted='off'",
        'projects_latest' => "SELECT * FROM (SELECT * FROM tblcontentitemsextension order by versionID desc) versions group by versions.ItemID",
        'projects' => "SELECT * FROM tblcontentitems tci JOIN tblcontentitemsextension tcie ON tci.id = tcie.itemID WHERE tci.active = 'on' AND tci.addDate > ':date' group by tcie.itemID order by tcie.versionID desc;",
        'projects_all' => "SELECT * FROM tblcontentitems tci JOIN tblcontentitemsextension tcie ON tci.id = tcie.itemID WHERE tci.active = 'on' group by tcie.itemID order by tcie.versionID desc;",
        'investors' => "SELECT * FROM tblcustomers tc JOIN tblcustomersextension tce ON tc.id = tce.customerID WHERE tc.active = 'on' group by tc.email order by tc.addDate desc",
        'investors_api' => "SELECT id, lName, fName, email FROM tblcustomers tc JOIN tblcustomersextension tce ON tc.id = tce.customerID WHERE tc.active = 'on' group by tc.email order by tc.addDate desc",
        'project_data' => "select tblcontentitemsapprvoed.itemID as id, 01MovieName as project_title, 00ProducerFirstName as producer_first_name, 01ProducerLastName as producer_last_name, 06ProducerEmail as producer_email, 00DirectorFirstName as director_first_name, 01DirectorLastName as director_last_name, 07DirectorEmail as director_email, 00InvIntrest as interested from tblcontentitemsapprvoed inner join tblcontentitemsextensionapproved on tblcontentitemsapprvoed.itemID=tblcontentitemsextensionapproved.itemID where sectionID IN( :section_id ) and active='on' and deleted='off'",
        'projects_api' => "select tblcontentitemsapprvoed.itemID as id, 01MovieName as project_title, 00ProducerFirstName as producer_first_name, 01ProducerLastName as producer_last_name, 06ProducerEmail as producer_email, 00DirectorFirstName as director_first_name, 01DirectorLastName as director_last_name, 07DirectorEmail as director_email, 00InvIntrest as interested from tblcontentitemsapprvoed inner join tblcontentitemsextensionapproved on tblcontentitemsapprvoed.itemID=tblcontentitemsextensionapproved.itemID where sectionID IN( :section_id ) and active='on' and deleted='off'",
        'cross_ref_investors' => "select id, email as investor_email, fName as investor_first_name, lName as investor_last_name from tblcustomers where id in (x?x?x?x)",
        'insert_project' => "INSERT INTO tblcontentitemsextension (sectionID, eventID, projectID, investorID, meetingStart, meetingEnd) VALUES (144, :event_id, :project_id, :investor_id, :starts, :ends)"
    );

    function __construct() {
    }

    /**
     * Get a formatted query by passing a key string for the $queries array and
     * an array that will populate it's format specifiers
     *
     * @param $index
     * @param $args
     *
     * @return int|string
     */
    function sprint($index, $args) {
        if (isset($this->queries[$index])) {
            $f_str = $this->queries[$index];
            return is_array($args) ? vprintf( $f_str, $args ) : sprintf($f_str, $args);
        }
        return '';
    }
}

class Project_Database_Interface extends Database_Queries {

    private $conn;
    public $today;

    function __construct(\PDO $conn) {
        $this->conn = $conn;
        $this->today = date('Y-m-d');
    }

    function query($SQL) {
        return $this->conn->query($SQL);
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

        if ($statement_name === 'insert_project') {
            echo $statement;
            exit;
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

    /**
     * Take an array or integer of sectionID and run the project_data query.
     * NOTE: I think that the method Investor_Project_PHP_Handler::load_projects
     *      replaces the need for this method. But I updated this method to support
     *      array functionality as well as the original int argument.
     * 
     * @param $section_id
     * @return array|int
     */
    function get_project_data($section_id) {
        if (!$section_id) {
            return array();
        }
        if (is_integer($section_id)) {
            $section_id = array($section_id);
        }
        $section_id = implode( ', ', $section_id );
        return $this->prep_statement( 'project_data', array('section_id' => $section_id) );
    }


    function get_investor_data_from_list ($id_list) {
        // Use this array to replace x?x?x?x with the appropriate # of ?, ?, ? in prepared statement.
        if (count( $id_list )) {
            $change_query_parts = array(
                'x?x?x?x' => implode( ', ', array_fill( 0, count( $id_list ), '?' ) )
            );
            return $this->prep_statement( 'cross_ref_investors', $id_list, $change_query_parts );
        }
        return null;
    }


    function get_investors_data() {
        return $this->prep_statement( 'investors');
    }


    function get_projects_since($date=null) {
        if (is_null($date))
            $this->prep_statement( 'projects_all' );
        return $this->prep_statement( 'projects', array('date' => $date) );
    }

    function get_projects_latest() {
        return $this->prep_statement( 'projects_latest' );
    }

}


class Investor_Project_PHP_Handler extends Project_Database_Interface {

    private $projects = array();
    private $investors = array();

    function __construct( \PDO $conn ) {
        parent::__construct( $conn );
    }


    /**
     * Update a single meeting
     *    $meeting = array(
     *      'investor_id' => \Util\post( 'investor'  ),
     *      'project_id' => \Util\post( 'project'  ),
     *      'item_id' => \Util\post( 'item_id'  ),
     *      'event_id' => \Util\post( 'event_id'  ),
     *      'meeting_end' => \Util\post( 'end'  ),
     *      'meeting_start' => \Util\post( 'start'  )
     *    )
     *
     * @param array $meeting - An array specifying the meeting details. item_id is
     *                         the important one.
     * @return array|int
     */
    function update_meeting( $meeting ) {
        if (isset($meeting['item_id'])  && isset($meeting['meeting_end']) &&
            isset($meeting['meeting_start']) && isset($meeting['event_id']) &&
            isset($meeting['project_id']) && isset($meeting['investor_id'])) {
            $item_id = $meeting['item_id'];
            $project_id = $meeting['project_id'];
            $meeting_end = $meeting['meeting_end'];
            $meeting_start = $meeting['meeting_start'];
            $event_id = $meeting['event_id'];
            $investor_id = $meeting['investor_id'];
            $update_query1 = "UPDATE tblcontentitemsextension SET meetingEnd = '{$meeting_end}', meetingStart = '{$meeting_start}' WHERE projectID = {$project_id} AND investorID = {$investor_id} AND itemID = {$item_id} AND eventID = {$event_id}";
            $update_query2 = "UPDATE tblcontentitemsextensionapproved SET meetingEnd = '{$meeting_end}', meetingStart = '{$meeting_start}' WHERE projectID = {$project_id} AND investorID = {$investor_id} AND itemID = {$item_id} AND eventID = {$event_id}";
            $this->query($update_query2);
            return $this->query($update_query1);
        }
        return 0;

    }


    /**
     * SELECT MEETINGS FROM THE DB.
     * optionally filter by event_id, or event_id and project_id, or event_id and investor_id
     *
     * @param string $event_id
     * @param array $investor_project - include key for 1 either 'investor' => ID or 'project' => ID
     * @return array|int
     */
    function select_meetings( $event_id = '', $investor_project = array()) {
        if ($event_id === '') {
            return $this->prep_statement('all_meetings');
        } else {
            // Get all meetings by event and single investor
            if (isset($investor_project['investor']) && !!($investor_project['investor'])) {
                return $this->prep_statement('event_investor_meetings', array('event_id'=> $event_id, 'investor_id' => $investor_project['investor']));
            }
            // Get all meetings by event and single project
            if (isset($investor_project['project']) && !!($investor_project['project'])) {
                return $this->prep_statement('event_project_meetings', array('event_id'=> $event_id, 'project_id' => $investor_project['project']));
            }

            // Get all meetings by event and in (...) investors
            if (isset($investor_project['investors']) && !!($investor_project['investors'])) {
                if (is_array($investor_project['investors'])) {
                    // Turn array into string for the single query param x, x, x, x....
                    $investor_project['investors'] = implode(', ', $investor_project['investors']);
                    return $this->prep_statement('event_investors_meetings', array(), array(':event_id'=> $event_id, ':investor_ids' => $investor_project['investors']));
                }
            }

            // Get all meetings by event and in (...) projects
            if (isset($investor_project['projects']) && !!($investor_project['projects'])) {
                if (is_array($investor_project['projects'])) {
                    // Turn array into string for the single query param x, x, x, x....
                    $investor_project['projects'] = implode(', ', $investor_project['projects']);
                    return $this->prep_statement('event_projects_meetings', array(),  array(':event_id'=> $event_id, ':project_ids' => $investor_project['projects']));
                }
            }

            // Get all meetings by event
            return $this->prep_statement('event_meetings', array('event_id'=> $event_id));
        }
    }



    /**
     * DELETE ALL MEETINGS FROM THE DB.
     */
    function delete_all_meetings( $event_id = '') {
        if (!$event_id) {
            print "NO EVENT ID:";
        } else {
            //$this->query("DELETE FROM tblcontentitemsextension WHERE eventID > 0");
            $this->query("DELETE FROM tblcontentitemsextension WHERE eventID > 0 && eventID = {$event_id}");
        }
    }

    
    /**
     * array(
     *   project_id: int
     *   investor_id: int
     *   starts: DateTime
     *   ends: DateTime
     * )
     * @param $meeting_info
     * @return array|int
     */
   /* function add_new_meeting($meeting_info) {
        if (!isset($meeting_info['event_id'])) {
            $meeting_info['event_id'] =\EVENT_ID;
        }
        return $this->prep_statement('insert_project', $meeting_info);
    }*/
/*
    function add_new_meetings($meetings_info_array) {
        foreach ($meetings_info_array as $meeting) {
            $converted_meeting = array();
            $converted_meeting['project_id'] = $meeting['project'];
            $converted_meeting['starts'] = is_a( $meeting['starts'], '\DateTime' ) ? $meeting['starts']->format('Y-m-d H:i:s') : $meeting['starts'];
            $converted_meeting['ends'] = is_a( $meeting['end'], '\DateTime') ? $meeting['end']->format('Y-m-d H:i:s') : $meeting['end'];

            $this->add_new_meeting($converted_meeting);
        }
    }*/


    /**
     * "<h3>Step 1: <small>Query Projects; Instantiate Project class objects</small></h3>";
     * Load projects into the projects array (not the investors though)
     *
     * @param array $sectionID_array
     *
     * @return array
     */
    function load_projects( $sectionID_array ) {
        $this->projects = array();
        if (!$sectionID_array) {
            //return array();
            return $this->projects;
        }
        $sectionID_string = implode( ', ', $sectionID_array );
        
        $temp_projects = $this->prep_statement( 'project_data', array(), array(':section_id' => $sectionID_string) );
        foreach ( $temp_projects as $temp_project ) {
            array_push($this->projects, new \Project($temp_project));
        }
        // Figure out how to get this to query multiple sectionIDs

        return $this->projects;
    }


    /**
     * "<h3>Step 2: <small>Build Unique Investor ID List using Projects</small></h3>";
     * Build the investors arrays 'id' and 'objects'
     *
     * @return int
     */
    function build_investor_list_from_projects () {
        if ( !is_array( $this->projects ) || !count( $this->projects ) ) {
            $this->investors['ids'] = array();
            $this->investors['index'] = array();
            $this->investors['objects'] = array();
            return 0;
        }
        $this->investors = array('ids' => array(), 'objects' => array());

        foreach($this->projects as $project) {
            // Turn the string of IDs into an array and leave out any empty indexes
            $this->investors['ids'] = array_unique(array_merge($this->investors['ids'], $project->interested));
        }

        $this->investors['objects'] = $this->get_investor_data_from_list($this->investors['ids']);
        return $this->investors['objects'];
    }


    /**
     * "<h3>Step 3: <small>Instantiate Investor Class from Filtered Investor Query</small></h3>";
     * Convert the investor stdClass from sql to investor objects from Investor class
     * required for the scheduler app we made.
     * @return mixed
     * @throws \ErrorException
     */
    function instantiate_investor_objects() {
        if (!isset($this->investors['objects']) || !is_array($this->investors['objects'])) {
            $this->investors['objects'] = array();
        }
        $this->investors['index'] = array();

        foreach ($this->investors['objects'] as $key => $investor) {
            $temp_investor = new \Investor( $investor );
            if (isset($this->investors[$temp_investor->id])) {
                throw new \ErrorException("Duplicate Investor ID in investor index: {$temp_investor->id}");
            }
            $this->investors['index'][(int)$temp_investor->id] = $temp_investor;
        }
        unset( $temp_investor );
        // Reset the investors objects array (we don't need it anymore)
        unset( $this->investors['objects'] );
        $this->investors['objects'] = array();

        return $this->investors['index'];
    }


    /**
     * "<h3>Step 4: <small>Add references from investors to interested projects</small></h3>";
     * Run the add_project() method on each investor for each project that they are interested in.
     * This helps the scheduler to know which projects to schedule meetings with which investors.
     *
     * @return mixed
     * @throws \ErrorException
     */
    function add_project_references_to_investors() {
        if ( !is_array( $this->projects ) || !count( $this->projects ) ) {
            return 0;
        }
        foreach($this->projects as $project) {
            if ( ! property_exists( $project, 'interested' ) )
                continue;

            foreach($project->interested as $investor_id) {
                if (!isset($this->investors['index'][(int)$investor_id])) {
                    continue;
                    //throw new \ErrorException("Investor listed in project not present in application!");
                }
                if (!is_a(($investor = $this->investors['index'][ (int) $investor_id ]), '\Investor')) {
                    throw new \ErrorException("Investor isn't instantiated! Can't reference project until Investor class is created.");
                }
                if (! \RUNNING_PUSH_DATE || ! $this->investor_has_met_with_project($investor->id, $project->id)) {
                    $investor->add_project( (int) $project->id );
                }
            }
        }

        return $this->investors['objects'];
    }

    function get_projects() {
        return $this->projects;
    }

    /**
     * Since our investors are in an array by ID we need to put them into just a regular array by 0..1..2 index
     * before they become usable in the schedule app.
     * @return array
     */
    function get_investors() {
        $zero_indexed = array();
        foreach($this->investors['index'] as $investor)
            array_push( $zero_indexed, $investor );

        return $zero_indexed;
    }

    /**
     * Get a property back from the object provided that it exists
     *
     * @param $key
     *
     * @param null $index
     *
     * @return null
     */
    public function get($key, $index=null) {
        if ( property_exists($this, $key) ) {
            try {
                if (!is_null($index)) {
                    return is_array($this->$key) ? $this->$key[ $index ] : $this->$key->$index;
                }
                return $this->$key;
            }
            catch (\ErrorException $e) {
                return null;
            }
        }
        return null;
    }


    /**
     * Only call this on RUNNING_PUSH_DATE. It checks if the investor has already met
     * with a project. So that it isn't rescheduled when the app creates new schedule.
     *
     * @param $investor_id
     * @param $project_id
     *
     * @return bool
     */
    private function investor_has_met_with_project($investor_id, $project_id) {
        global $push_date_investors;

        if (!isset($push_date_investors) || !is_array($push_date_investors) || !is_array($push_date_investors[$investor_id])) {
            return false;
        }

        foreach($push_date_investors[$investor_id] as $old_schedule) {
            // Looping through the meetings which already happened or already started.
            if ($old_schedule['P'] == $project_id) {
                return true;
            }
        }
        return false;
    }
}
