<?php

if (!!\Util\get('export-calendar')) {
    // This resets each page load
    $_SESSION['export_calendar'] = 1;
}
if (!!\Util\get('section-id')) {
    // This resets each page load
    $_SESSION['section-id'] = 1;
}

$section_id = !!\Util\get('section-id') && (int)\Util\get('section-id') > 0 ? (int)\Util\get('section-id') : 0;
define( 'QUERY_VALUE_SECTION_ID', $section_id);

$mock_run = ( \Util\get( 'mock-run' ) && \Util\get( 'mock-run' ) ) || $_SERVER['SERVER_NAME'] == '0.0.0.0' ? 1 : 0;
define( 'MOCK_RUN', $mock_run );
$send_emails = ( \Util\get( 'attendees' ) && \Util\get( 'attendees' ) ) ? 1 : 0;
define( 'SEND_EMAILS', $send_emails );

$num_mock_investors = (\Util\get('mock-investors') && (int)$_GET['mock-investors'] > 0 && (int)$_GET['mock-investors'] <= 50) ? (int)$_GET['mock-investors'] : 7;
define('NUM_MOCK_INVESTORS', $num_mock_investors);
$num_mock_projects = (\Util\get('mock-projects') && (int)$_GET['mock-projects'] >= 10 && (int)$_GET['mock-projects'] <= 1000) ? (int)$_GET['mock-projects'] : 25;
define('NUM_MOCK_PROJECTS', $num_mock_projects);
$email_pool_val = \Util\get('email-pool') && (int)\Util\get('email-pool') >= 10 && (int)\Util\get('email-pool') <= 1000 ? (int)\Util\get('email-pool') : 100;
define('EMAIL_POOL_VAL', $email_pool_val);
$daily_hours = \Util\get('daily-hours') && (int)\Util\get('daily-hours') >= MIN_DAILY_HOURS && (int)\Util\get('daily-hours') <= MAX_DAILY_HOURS ? (int)\Util\get('daily-hours') : DEFAULT_DAILY_HOURS;


$meeting_mins = \Util\get('meeting-length') && (int)\Util\get('meeting-length') >= MIN_MEETING_MINS && (int)\Util\get('meeting-length') <= MAX_MEETING_MINS ? (int)\Util\get('meeting-length') : DEFAULT_MEETING_MINS;
define('MEETING_MINS', $meeting_mins);

$start_date_val = \Util\get('start-date-dt') ? \Util\get('start-date-dt') : DEFAULT_START_DT;

$start_breaks = \Util\get('break-start') && is_array(\Util\get('break-start')) ? \Util\get('break-start') : unserialize(DEFAULT_BREAK_START);
$end_breaks = \Util\get('break-end') && is_array(\Util\get('break-end')) ? \Util\get('break-end') : unserialize(DEFAULT_BREAK_END);
$break_time_inputs = '';
for ( $i = 0; $i < count( $start_breaks ) && $i < count( $end_breaks ); $i ++ ) {
    $break_time_inputs .= "<div class='form-group new-break-input-container'>"
                          ."<label for='break-start[]'>Start Time:"
                          . "<input type='text' name='break-start[]' class='form-control break-timepicker' value='{$start_breaks[$i]}'></label>"
                          . "<label for='break-end[]'>End Time:"
                          . "<input type='text' name='break-end[]' class='form-control break-timepicker' value='{$end_breaks[$i]}'></label></div>";
}

$calendar_id = \Util\get('calendar-id') && LOGGED_IN ? \Util\get('calendar-id') : '';

// This is settings for the scheduler


define('SECONDS_PER_MEETING', 600);
define('START_MONTH', date('m', strtotime('+1 day')) );
define('START_DAY', date('d', strtotime('+1 day')) );
define('START_YEAR', date('Y'));
define('START_TIME', '08:00');
define('START_DATE', START_YEAR."-".START_MONTH."-".START_DAY);
define('CONVENTION_LOCATION', "Copro Festival, IL");
define('BREAKS', serialize(
    array(/*
        array(
            'start' => '09:15',
            'end' => '09:45',
        ),
        array(
            'start' => '12:00',
            'end' => '13:00',
        ),*/
    )
));

define( 'SCHEDULE_SETTINGS', serialize(
    array(
        'num_projects' => $num_mock_projects,
        'num_investors' => $num_mock_investors,
        'email_pool' => $email_pool_val,
        'hours' => $daily_hours,
        'meeting_length' => $meeting_mins,
        'start_datetime' => $start_date_val,
        'breaks' => array_map(function($start, $end) {
            return ($start > $end) ?
                array('start'=>$end, 'end'=>$start)
                : array('start'=>$start, 'end' => $end);
        }, $start_breaks, $end_breaks),
        'cal_id' => $calendar_id
    ))
);




if (defined('MOCK_RUN') && MOCK_RUN) {

    /**
     * Mock_Projects_Investors extends Mock_Content_Creator extends Mock_Content_Store
     * - Instanciating Mock_Projects_Investors creates 'email', 'movie_title' and
     *   'full_name' data types in our mock content storage automatically.
     */
    $mock = new Mock_Projects_Investors();
    // Create mock projects
    $mock->set_model($project_model);
    $projects = $mock->generate_content($num_mock_projects);

    // Create mock investors
    $mock->set_model($investor_model);
    $investors = $mock->generate_content($num_mock_investors);

    $day = 1;

} else {

}


/**  GET RUN DATES -- THIS MIGHT BE DATABASE SOON (in which case move this into MOCK_RUN block above **/

$run_dates = array();
$first_date = 0;
while (($day++) < 3 || strtotime( \Util\get("day-{$day}") )) {
    /**
     * While we haven't filled all 3 days into array of dates in schedule
     * or we haven't ran out of dates added in the URL params then push to array.
     * This will allow for more than 4 days if needed but never less than 3
     *
     * $day == 1 on first loop-through.
     */
    if (!$first_date) {
        $first_date = !!strtotime(\Util\get("start-date-dt")) ?
            strtotime(\Util\get("start-date-dt")) :
            strtotime(START_YEAR."-".START_MONTH."-".START_DAY." ".START_TIME);
        $run_dates[] = $first_date;
    }

    $run_dates[] = !!strtotime(\Util\get("day-{$day}")) ?
        strtotime(\Util\get("day-{$day}")) :
        strtotime("+{$day} day", $first_date);
}
unset( $first_date );
