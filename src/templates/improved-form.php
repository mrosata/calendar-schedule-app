<?php
/**
 * Created by michael on 3/4/16.
 */

define('MIN_MEETING_MINS', 5);
define('DEFAULT_MEETING_MINS', 20);
define('MAX_MEETING_MINS', 200);


function create_forms_confirmation_modal() {
    $modal = '';
    $modal .= "
    <!-- Modal -->
    <div class='modal fade' id='confirm-submission' tabindex='-1' role='dialog' aria-labelledby='modal-label'>
        <div class='modal-dialog' role='document'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                    <h4 class='modal-title' id='modal-label'>Beginning Overwrite!</h4>
                </div>
                <div class='modal-body'>
                    <div class='push-date-active hidden'>
                        <span class='danger label'>Push Date Active</span>: Only meetings already put on
                        <span data-form-value='calendar'>calendar</span> after 
                        <span data-form-value='push-date'>the push date</span> will be replaced. Every meetings before 
                        <span data-form-value='push-date'>the push date</span> will stay on the calendar.
                        
                        <div class='honor-pinned-meetings hidden'>
                            <span class='danger label'>Pinned Meetings</span>: Will not be overwritten
                        </div>
                        
                        <div class='not-honor-pinned-meetings hidden'>
                            <span class='danger label'>Pinned Meetings</span>: Will be overwritten if after push date
                        </div>
                    </div>
                    <div class='push-date-inactive hidden'>
                        <div class='honor-pinned-meetings hidden'>
                            
                            <span class='danger label'>Starting Over</span>: Every single meeting scheduled on
                            <span data-form-value='calendar'>calendar</span> will be erased except for the pinned meetings 
                            which are already on the form.
                            <span class='danger label'>Pinned Meetings</span>: Will not be overwritten
                        </div>
                        
                        <div class='not-honor-pinned-meetings hidden'>
                        
                            <span class='danger label'>Starting Over</span>: Every single meeting scheduled on
                            <span data-form-value='calendar'>calendar</span> will be erased!<br><br>
                            <span class='danger label'>Pinned Meetings</span>: Will be overwritten as well
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-default' data-dismiss='modal'>Cancel</button>
                    <button type='button' class='btn btn-primary' data-run-form='true'>Continue with Write</button>
                </div>
            </div>
        </div>
    </div>
    ";

    return $modal;
}


// The form
function build_schedule_controls_form() {
    $section_id_num = (\Util\post('section-id'));

    $meeting_mins = \Util\post('meeting-length') && (int)\Util\post('meeting-length') >= MIN_MEETING_MINS && (int)\Util\post('meeting-length') <= MAX_MEETING_MINS ? (int)\Util\post('meeting-length') : DEFAULT_MEETING_MINS;

    $dynamic_dates = generate_datepickers_and_breaks();
    $push_date = !!\Util\post('push-date') ? \Util\post('push-date') : '';
    if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' && !$push_date ) {
        // Set default push date as today.
        $push_date = date('Y-m-d 00:00');
    }

    // Dates event id is the id # to use to get dates from database
    $dates_event_id = \Util\post('dates-event-id') ? (int)\Util\post('dates-event-id') : '';
    // Calendar id is the id # to store meetings under and to use to get right JavaScript calendar (drag and drop page).
    $calendar_id = \Util\post('calendar-id') ? (int)\Util\post('calendar-id') : $dates_event_id;

    $conflicts = !!\Util\post('conflicts') && !!\Util\post('conflicts-from-javascript') ? base64_encode(\Util\post('conflicts')) : \Util\post('conflicts');
    
    /// Should active-push-date checkbox be checked?
    $honor_pinned_meetings = !!\Util\post( 'honor-pinned-meetings' ) ? 'checked="checked"' : '';
    $activate_push_date_checked = !!\Util\post( 'activate-push-date' ) ? 'checked="checked"' : '';
    
    $finalize_checkbox = '';
    if (!$section_id_num || !$dynamic_dates || !$dates_event_id) {
        // Form is ready
        $finalize_checkbox .= "<div class=\"form-group\" style=\"border: 1px #555 solid;border-radius:4px\"><legend>";
        $finalize_checkbox .= "<label>Run the form once time with test values, then the option to save the finalized ";
        $finalize_checkbox .= "schedule with \"Overwrite\" will appear.</label></legend></div>";
    }
    else {
        // Form isn't ready
        $finalize_checkbox .= "<div class=\"form-group\" style=\"border: 1px #555 solid;border-radius:4px\">
                                  <legend>
                                      <label for=\"finalize\">Check Here To Overwrite Entire Calendar!
                                          <input type=\"checkbox\" name=\"finalize\" class=\"form-control checkbox checkbox-md\">
                                      </label>
                                  </legend>
                                  <p>If this is the restart of an event or calendar, please check this box and leave the push date blank to ensure you get a completely rescheduled calendar.</p>
                              </div>";
    }

    $bootstrap_confirmation_modal = create_forms_confirmation_modal();

    $hide_instructions = !!\Util\post('hide-instructions') ? 'checked="checked"' : '';
    $mock_content_form = <<<DOCSTRING
                    <form action="index.php" method="post" name="config">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-sm-12">
                                        <div class="well well-md">
                                        <div class="form-instructions">
                                            <h4 class="title"> Scheduling configuration </h4>
                                            <label for="hide-instructions"></label>
                                            <input type="checkbox" class="checkbox checkbox-sm small" name="hide-instructions" {$hide_instructions}>Hide instructions
                                            <p class="instructions">
                                                Use this form to build the initial schedule. The first run through is like a test. 
                                                If you think the results look alright, a checkbox will appear below to allow you 
                                                to "Overwrite" the results. This means that any previous meetings for the event 
                                                ID you supply below will be overwritten in the database. After that you should goto
                                                the calendar view to fine tune your results by dragging and dropping meetings. At the 
                                                top of the page there is a button "Get Meeting Schedule" that can take you to the 
                                                calendar if you don't think you're on the correct page.
                                                
                                            <br><br>
                                            
                                                <strong>NOTE:</strong>There is an exception to the "overwrite" rule. If you supply a 
                                                "Push Date" in the bottom date input, the system won't use any meetings for your event 
                                                that happened before that date. Likewise, if you "drag and drop" meetings in the calendar
                                                view they will not be effected or show up when using the "Push Date" input. If you wanted 
                                                to overwrite the entire event again you would have to use this for <em>without</em> the 
                                                "Push Date" option.
                                            </p>
                                       </div>
                                      
                                       <!-- MINUTES PER MEETING Input -->
                                        <label for="meeting-length">Mins per Meeting
                                            <input type="number" name="meeting-length" class="form-control" default="10" min="1" max="60" value="{$meeting_mins}" required>
                                        </label>


                                       <!-- EVENT ID Input -->
                                        <label for="dates-event-id">Generate schedule for Copro EventID (This is ID to get dates from):
                                            <input type="text" class="form-control" name="dates-event-id" id="dates-event-id" value="{$dates_event_id}">
                                        </label>

                                       <!-- SECTION IDS Input -->
                                        <label for="section-id">Comma separated SectionIDs to use in project search: (example: 87,88,89)
                                            <input type="text" name="section-id" value="{$section_id_num}" class="form-control input-md" 
                                                   default="2,142,88,89,83,85,86,87" placeholder="2,142,88,89,83,85,86,87" required>
                                        </label>
                                    
                                       <!-- CALENDAR ID Input -->
                                        <label for="calendar-id">Pick a number to use as ID of calendar to work with (leave blank to use Event ID above '{$dates_event_id}'):
                                            <input type="text" name="calendar-id" value="{$calendar_id}" class="form-control input-md" 
                                                   default="{$calendar_id}" placeholder="ID of Calendar (to store meetings in) eg: {$calendar_id}00}" required>
                                        </label>
                                    
                                   
                                    </div>



                                    <div class="row">

                                        <div class="col-sm-12">
                                            <div class="form-group">
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


                                    <div class="form-group calendar-form-group">
                                        <label for="push-date" style="height:4rem;">Push DateTime (setting this will ignore meetings on calendar before this time and also "pinned" meetings):<br></label>
                                        <p><small>NOTE: If Push Date isn't empty then it will leave any meetings already on calendar before that date and time..
                                                also setting push date will make sure NOT to overwrite "pinned" events. You *MUST* leave this field empty 
                                                to generate the first draft of a calendar!</small></p>
                                        <div class='input-group date' id='push-date'>
                                            <input type='text' class="form-control" name="push-date" value="{$push_date}" />
                                            <span class="input-group-addon">
                                                <span class="glyphicon glyphicon-calendar"></span>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-sm-6 col-md-4">
                                                <label for="activate-push-date" data-toggle="tooltip" data-placement="top" 
                                                title="This checkbox will activate the push date which means any meetings already 
                                                       on the calendar BEFORE that date will remain exactly where they are.">
                                                       Turn Push Date On
                                                    <input type="checkbox" class="checkbox form-control" name="activate-push-date" {$activate_push_date_checked}>
                                                </label>
                                            </div>
                                            
                                            <div class="col-sm-6 col-md-4">
                                                <label for="honor-pinned-meetings" 
                                                data-toggle="tooltip" 
                                                data-placement="top" 
                                                title="If 'Honor Pinned Meetings' is checked any meeting that displays as 
                                                       pinned on the calendar (moved over clicked by human) will remain
                                                       exactly where they are.">
                                                        Honor Pinned Meetings
                                                    <input type="checkbox" class="checkbox form-control" name="honor-pinned-meetings" {$honor_pinned_meetings}>
                                                </label>
                                            </div>
                                            
                                            
                                            <div class="col-sm-6 col-md-4">
                                                <span class="btn btn-warning" id="clear-push-date">CLEAR PUSH DATE</span>                                    
                                            </div>
                                        </div>
                                    </div>


                                    {$finalize_checkbox}

                                    <div class="form-group col-md-6 col-lg-4">

                                        <div class="form-group">
                                            <input type="hidden" name="conflicts" id="conflicts" value="{$conflicts}" />
                                            
                                            <!-- Button trigger modal -->
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
                    
                        {$bootstrap_confirmation_modal}
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
