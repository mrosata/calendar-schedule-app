<?php
/**
 * Created by michael on 3/4/16.
 */

// The form
function build_schedule_controls_form() {
    $loggedIn = defined('LOGGED_IN') && LOGGED_IN;

    $section_id_num = (\Util\get('section-id') && (int)$_GET['section-id'] > 0) ? (int)$_GET['section-id'] : 0;

    $mock_run = (\Util\get('mock-run') && !!$_GET['mock-run']) ? 'checked="checked"' : '';
    $num_mock_investors = (\Util\get('mock-investors') && (int)$_GET['mock-investors'] > 0 && (int)$_GET['mock-investors'] <= 50) ? (int)$_GET['mock-investors'] : 7;
    $num_mock_projects = (\Util\get('mock-projects') && (int)$_GET['mock-projects'] >= 10 && (int)$_GET['mock-projects'] <= 1000) ? (int)$_GET['mock-projects'] : 3;
    $email_pool_val = \Util\get('email-pool') && (int)\Util\get('email-pool') >= 10 && (int)\Util\get('email-pool') <= 1000 ? (int)\Util\get('email-pool') : 100;
    $daily_hours = \Util\get('daily-hours') && (int)\Util\get('daily-hours') >= MIN_DAILY_HOURS && (int)\Util\get('daily-hours') <= MAX_DAILY_HOURS ? (int)\Util\get('daily-hours') : DEFAULT_DAILY_HOURS;
    $meeting_mins = \Util\get('meeting-length') && (int)\Util\get('meeting-length') >= MIN_MEETING_MINS && (int)\Util\get('meeting-length') <= MAX_MEETING_MINS ? (int)\Util\get('meeting-length') : DEFAULT_MEETING_MINS;
    $start_date_val = \Util\get('start-date-dt') ? \Util\get('start-date-dt') : DEFAULT_START_DT;
    $date_day_2 = \Util\get('day-2') ? \Util\get('day-2') : '';
    $date_day_3 = \Util\get('day-3') ? \Util\get('day-3') : '';

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

    $calendar_name = \Util\get('calendar-name') && $loggedIn ? \Util\get('calendar-name') : '';
    $lower_form_mso365 = $loggedIn ?
        "<label for='calendar-name'>Calendar Name: (Outlook Calendar to create)<input type='text' name='calendar-id' value='{$calendar_name}' class='form-control'></label>"
        : '';


    $mock_content_form = <<<DOCSTRING
                    <form action="index.php" name="config">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <div class="well well-md">
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
                                                <input type="number" name="mock-projects" class="form-control" default="30" min="3" max="1000" value="{$num_mock_projects}">
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
                                        <div class="form-group col-md-6 col-lg-4">
                                            <div class="form-group calendar-form-group">
                                                <label for="start-date-dt" style="height:4rem;">Pick a start date (This will be the first day and time for the 3 day event).</label>
                                                <div class='input-group date' id='start-date-dt'>
                                                    <input type='text' class="form-control" name="start-date-dt" value="{$start_date_val}" />
                                                    <span class="input-group-addon">
                                                        <span class="glyphicon glyphicon-calendar"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group col-md-6 col-lg-4">
                                            <div class="form-group calendar-form-group">
                                                <label for="start-date-dt" style="height:4rem;">Day 2:</label>
                                                <div class='input-group date' id='day-2'>
                                                    <input type='text' class="form-control" name="day-2" value="{$date_day_2}" />
                                                    <span class="input-group-addon">
                                                        <span class="glyphicon glyphicon-calendar"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group col-md-6 col-lg-4">
                                            <div class="form-group calendar-form-group">
                                                <label for="start-date-dt" style="height:4rem;">Day 3:<br></label>
                                                <div class='input-group date' id='day-3'>
                                                    <input type='text' class="form-control" name="day-3" value="{$date_day_3}" />
                                                    <span class="input-group-addon">
                                                        <span class="glyphicon glyphicon-calendar"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Breaks</label>

                                        {$break_time_inputs}

                                        <span class="btn btn-info add-new-break"><i class='glyphicon glyphicon-plus'> </i> Add Break</span>
                                    </div>

                                    <div class="form-group">
                                        <label for="section-id">SectionID to write events for: (example: 87)
                                            <input type="number" name="section-id" value="{$section_id_num}" class="form-control input-md">
                                        </label>
                                    </div>


                                    <div class="form-group">
                                        <label for="attendees">Send emails to attendees? (uncheck while testing)
                                            <input type="checkbox" name="attendees" class="form-control checkbox checkbox-md">
                                        </label>
                                    </div>


                                    <div class="form-group">
                                        <label for="export-calendar">Export Events to Calendar? (EXPORT TO OUTLOOK)
                                            <input type="checkbox" name="export-calendar" class="form-control checkbox checkbox-md">
                                        </label>
                                    </div>


                                    <div class="form-group">
                                        {$lower_form_mso365}
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-success" type="submit">Run it!</button>
                                        <a class="btn btn-danger float-right" href="/index.php?">Restart Entire Form</a>
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

echo build_schedule_controls_form();

/* This will echo out the form */
