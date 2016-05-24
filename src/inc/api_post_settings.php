<?php
/**
 * This file is for when user is making calls through the api and not via
 * the form. They may not need the enitire form to run.
 */
$possible_querys = array(
    'all_meetings', 'event_meetings', 'event_investor_meetings', 'event_project_meetings',
    'projects_since', 'projects_latest', 'projects', 'projects_all', 'investors',
    'project_data', 'cross_ref_investors', 'insert_project', 'investors_api', 'projects_api'
);

// IS THIS CALL SIMPLY AN API UPDATE CALL?
if (\Util\post('queryrun')) {
    $query_name = \Util\post( 'queryrun');
    $ret = array();
    $ret['success'] = 0;

    if ( in_array($query_name, $possible_querys) ) {

        // User can pass in ars to fill in the prep statement through post variable.
        // prepare_statement will handle SQL Injections
        $query_args_array = !!\Util\post('args') ? \Util\post('args') : array();

        require_once 'utils.php';
        require_once 'connection.php';
        require_once 'Project_Database_Interface-class.php';
        $pdo = \Connection\get_connection();
        $db = new \copro\Investor_Project_PHP_Handler( $pdo );

        $results = $db->prep_statement($query_name, array(), $query_args_array);

        $ret['success'] = !!$results;
        $ret['results'] = json_encode($results);
        $ret['message'] = "made SQL request.";
    } else {
        $ret['message'] = 'missing url params';
    }
    header("application/json; charset=UTF-8");
    echo json_encode($ret);
    exit;
}

// IS THIS CALL SIMPLY AN API UPDATE CALL?
if (\Util\post('update')) {
    $ret = array();
    $ret['success'] = 0;

    if (\Util\post( 'investor' ) && \Util\post( 'project' ) && \Util\post( 'start' ) &&
        \Util\post( 'end' ) && \Util\post( 'event_id' ) && \Util\post( 'item_id' )) {

        require_once 'utils.php';
        require_once 'connection.php';
        require_once 'Project_Database_Interface-class.php';
        $pdo = \Connection\get_connection();
        $db = new \copro\Investor_Project_PHP_Handler( $pdo );

        $params = array(
            'investor_id' => \Util\post('investor'),
            'project_id' => \Util\post('project'),
            'item_id' => \Util\post('item_id'),
            'event_id' => \Util\post('event_id'),
            'meeting_end' => \Util\post('end'),
            'meeting_start' => \Util\post('start')
        );

        $ret['success'] = !!$db->update_meeting( $params ) ? 1 : 0;

        $ret['wkka'] = json_encode($params);
        $ret['message'] = "made update request.";
    } else {
        $ret['message'] = 'missing url params';
    }
    header("application/json; charset=UTF-8");
    echo json_encode($ret);
    exit;
}
