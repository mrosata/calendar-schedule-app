<?php
/**
 * Created by michael on 3/4/16.
 */

// The form
function build_schedule_controls_form() {
    $loggedIn = defined('LOGGED_IN') && LOGGED_IN;
    
    $section_id_num = (\Util\post('section-id'));

    $mock_run = (\Util\post('mock-run') && !!$_POST['mock-run']) ? 'checked="checked"' : '';
    $num_mock_investors = (\Util\post('mock-investors') && (int)$_POST['mock-investors'] > 0 && (int)$_POST['mock-investors'] <= 50) ? (int)$_POST['mock-investors'] : 7;
    $num_mock_projects = (\Util\post('mock-projects') && (int)$_POST['mock-projects'] >= 2 && (int)$_POST['mock-projects'] <= 300) ? (int)$_POST['mock-projects'] : 15;
    $email_pool_val = \Util\post('email-pool') && (int)\Util\post('email-pool') >= 10 && (int)\Util\post('email-pool') <= 1000 ? (int)\Util\post('email-pool') : 100;
    $daily_hours = \Util\post('daily-hours') && (int)\Util\post('daily-hours') >= MIN_DAILY_HOURS && (int)\Util\post('daily-hours') <= MAX_DAILY_HOURS ? (int)\Util\post('daily-hours') : DEFAULT_DAILY_HOURS;
    $meeting_mins = \Util\post('meeting-length') && (int)\Util\post('meeting-length') >= MIN_MEETING_MINS && (int)\Util\post('meeting-length') <= MAX_MEETING_MINS ? (int)\Util\post('meeting-length') : DEFAULT_MEETING_MINS;

    $dynamic_dates = generate_datepickers_and_breaks();
    $push_date = \Util\post('push-date') ? \Util\post('push-date') : '';

    $start_breaks = \Util\post('break-start') && is_array(\Util\post('break-start')) ? \Util\post('break-start') : unserialize(DEFAULT_BREAK_START);
    $end_breaks = \Util\post('break-end') && is_array(\Util\post('break-end')) ? \Util\post('break-end') : unserialize(DEFAULT_BREAK_END);
    $break_time_inputs = '';
    for ( $i = 0; $i < count( $start_breaks ) && $i < count( $end_breaks ); $i ++ ) {
        $break_time_inputs .= "<div class='form-group new-break-input-container'>"
                              ."<label for='break-start[]'>Start Time:"
                              . "<input type='text' name='break-start[]' class='form-control break-timepicker' value='{$start_breaks[$i]}'></label>"
                              . "<label for='break-end[]'>End Time:"
                              . "<input type='text' name='break-end[]' class='form-control break-timepicker' value='{$end_breaks[$i]}'></label></div>";
    }

    $calendar_name = \Util\post('calendar-id') && $loggedIn ? \Util\post('calendar-id') : '';
    $lower_form_mso365 = $loggedIn ?
        "<label for='calendar-name'>Calendar Name: (Outlook Calendar to create)<input type='text' name='calendar-id' value='{$calendar_name}' class='form-control'></label>"
        : '';


    $dates_event_id = \Util\post('dates-event-id') ? (int)\Util\post('dates-event-id') : '';
    $conflicts = !!\Util\post('conflicts') && !!\Util\post('conflicts-from-javascript') ? base64_encode(\Util\post('conflicts')) : \Util\post('conflicts');

    $mock_content_form = <<<DOCSTRING
                    <form action="index.php" method="post" name="config">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <div class="well well-md hidden">
                                        <label for="mock-run">Mock Run? (check to do testing)</label>
                                        <input type="checkbox" name="mock-run" $mock_run class="form-control checkbox">

                                        <div class="mock-run-form-part">

                                            <h3>Let's check out the sort algorithm</h3>
                                            <p>
                                                This form will allow you to run tests against "Mock Data". It will allow you to test
                                                the sorting algorithm. Choose the number of investors, number of projects and the amount
                                                of random email address to create. Then, click the tabs "Step 1", "Step 2" and "Step 3" to see
                                                the sort algorithm in action. "Email Collisions" shows project conflicts which would have
                                                required a crew member to be in 2 places at 1 time but were caught and fixed!
                                            </p>

                                            <br>

                                            <p>
                                                Fill out the form below, Any fields which aren't filled out will have default values.
                                            </p>
                                            <h4 class="title"> Mock data settings </h4>
                                            <p>
                                              Use these settings to define the problem set. This builds a temporary datastore to supply life-like data to the algorithm.
                                              </p>
                                            <label for="mock-projects">
                                                Use Mock Projects (min 10)
                                                <input type="number" name="mock-projects" class="form-control" default="15" min="2" max="300" value="{$num_mock_projects}">
                                            </label>
                                            <label for="mock-investors">
                                                Mock Investors (min 1)
                                                <input type="number" name="mock-investors" class="form-control" default="8" min="1" max="50" value="{$num_mock_investors}">
                                            </label>

                                            <label for="email-pool">
                                                Email Pool (collision detection demo)
                                                <input type="number" name="email-pool" class="form-control" default="50" min="5" max="1000" value="{$email_pool_val}">
                                            </label>
                                        </div> <!-- .mock-run-form-part -->
                                    </div> <!-- .well -->
                                </div>  <!-- .col-sm-12 -->
                            </div>   <!-- .form-group -->

                            <hr>
                            <div class="form-group">
                                <div class="col-sm-12">
                                        <div class="well well-md">
                                        <h4 class="title"> Scheduling configuration </h4>
                                        <p>
                                          These next fields define extra rules for the algorithm to follow. Use these to change the date and time of the start of the convention. That value will determine what time each subsequent day begins. Choose how many consecutive hours the meetings should run for, how many minutes between each scheduled meeting and add as many breaks as you wish, enter a start and end time for each break. These settings allow us to maximize the use of time and build an intelligently laid out schedule.
                                        </p>
                                        <label for="daily-hours">Hours per day
                                            <input type="number" name="daily-hours" class="form-control" default="8" min="1" max="16" step="1" value="{$daily_hours}">
                                        </label>


                                        <label for="meeting-length">Mins per Meeting
                                            <input type="number" name="meeting-length" class="form-control" default="10" min="1" max="60" value="{$meeting_mins}">
                                        </label>
                                    </div>



                                    <div class="row">

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label for="dates-event-id">Generate schedule for Copro EventID (This will remove and rebuild any meetings equal to eventID):
                                                    <input type="text" class="form-control" name="dates-event-id" id="dates-event-id" value="{$dates_event_id}">
                                                </label>

                                                <div>
                                                     <button class="btn btn-info" id="get-dates-by-event">Press to get dates</button>
                                                <div>

                                            </div>

                                        </div>

                                        <div id="dynamic-dates">
                                        
                                        <!-- When the user gets dates by entering an eventID. The number of dates is unknown and so is 
                                            added in here. Generated above in the PHP and handled by JavaScript for the datepicke -->
                                         {$dynamic_dates}
                                        
                                        </div>
                                        
                                    </div>


                                    <div class="form-group">
                                        <label for="section-id">Comma separated SectionIDs to use in project search: (example: 87,88,89)
                                            <input type="text" name="section-id" value="{$section_id_num}" class="form-control input-md">
                                        </label>
                                    </div>


                                    <div class="form-group">
                                        {$lower_form_mso365}
                                    </div>



                                    <div class="form-group calendar-form-group">
                                        <label for="push-date" style="height:4rem;">Push DateTime (start from a time after events :<br></label>
                                        <div class='input-group date' id='push-date'>
                                            <input type='text' class="form-control" name="push-date" value="{$push_date}" />
                                            <span class="input-group-addon">
                                                <span class="glyphicon glyphicon-calendar"></span>
                                            </span>
                                        </div>
                                    </div>



                                    <div class="form-group" style="border: 1px #555 solid;border-radius:4px">
                                        <legend>
                                            <label for="finalize">Check Here To Overwrite Entire Calendar!
                                                <input type="checkbox" name="finalize" class="form-control checkbox checkbox-md">
                                            </label>
                                        </legend>
                                    </div>

                                    <div class="form-group col-md-6 col-lg-4">

                                        <div class="form-group">
                                            <input type="hidden" name="conflicts" id="conflicts" value="{$conflicts}" />

                                            <button class="btn btn-success" type="submit">Run it!</button>
                                            <a class="btn btn-danger float-right" href="/index.php?">Restart Entire Form</a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </form>
                    <hr>
                    <br>
                    <br>
DOCSTRING;


    return $mock_content_form;
}

/**
 * Create the string used to build the part of the form that has datepickers with all the dates for
 * an event to be held along with the times of breaks (The break mask). Called Break Mask because it
 * is a list of time intervals which tell us all hours of day when event isn't happening.
 *
 * @return string
 */
function generate_datepickers_and_breaks() {

    $day_number = 1;
    $dynamic_dates = '';

    $look_for_another_date = true;
    while ($look_for_another_date) {

        $next_date = \Util\post("day-{$day_number}");
        // If $next_date wasn't null or undefined then there could be another date as well
        // So that's why this loop is here so we keep looking for post values until we
        // come up empty handed.
        $look_for_another_date = !!$next_date;

        if ($look_for_another_date) {
            // This replaces the long time HH:mm:ssmm.... with HH:mm
            $next_date = str_replace('~(.*\s\d{2}:\d{2}):.*$~', '$1', $next_date);
            // This replace entire time with nothing (the new way!(
            //$next_date = str_replace('~(.*\s\d{2}:\d{2}):.*$~', '$1', $next_date);

            $dynamic_breaks = '';
            if (!!\Util\post("breaks-day-{$day_number}")) {
                // There is also breaks involved
                $breaks = \Util\post("breaks-day-{$day_number}");
                // Remove trailing "," or ",,"
                $breaks = trim($breaks, ',');

                $dynamic_breaks = "Breaks:<br><input type='text' value='{$breaks}' name='breaks-day-{$day_number}' class='input form-control'>";
            }
            $dynamic_dates .= "<div class='form-group col-md-6 col-lg-4'>
            <div class='form-group calendar-form-group'>
                <label for='day-1' style='height:4rem;'>Day {$day_number}:</label>
                <div class='input-group date' id='day-{$day_number}'>
                    <input type='text' class='form-control' name='day-{$day_number}' value='{$next_date}' />
                    <span class='input-group-addon'>
                        <span class='glyphicon glyphicon-calendar'></span>
                    </span>
                </div>
            </div>
            {$dynamic_breaks}
        </div>";
            $day_number++;
        }
    }

    return $dynamic_dates;
}
echo build_schedule_controls_form();

/* This will echo out the form */
