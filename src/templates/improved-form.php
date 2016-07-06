<?php
/**
 * Created by michael on 3/4/16.
 */

define('MIN_MEETING_MINS', 5);
define('DEFAULT_MEETING_MINS', 20);
define('MAX_MEETING_MINS', 200);

// The form
function build_schedule_controls_form() {
    $section_id_num = (\Util\post('section-id'));

    $meeting_mins = \Util\post('meeting-length') && (int)\Util\post('meeting-length') >= MIN_MEETING_MINS && (int)\Util\post('meeting-length') <= MAX_MEETING_MINS ? (int)\Util\post('meeting-length') : DEFAULT_MEETING_MINS;

    $dynamic_dates = generate_datepickers_and_breaks();
    $push_date = \Util\post('push-date') ? \Util\post('push-date') : '';

    $dates_event_id = \Util\post('dates-event-id') ? (int)\Util\post('dates-event-id') : '';
    $conflicts = !!\Util\post('conflicts') && !!\Util\post('conflicts-from-javascript') ? base64_encode(\Util\post('conflicts')) : \Util\post('conflicts');

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
                              </div>";
    }

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
                                        <label for="dates-event-id">Generate schedule for Copro EventID (This will remove and rebuild any meetings equal to eventID):
                                            <input type="text" class="form-control" name="dates-event-id" id="dates-event-id" value="{$dates_event_id}">
                                        </label>

                                       <!-- SECTION IDS Input -->
                                        <label for="section-id">Comma separated SectionIDs to use in project search: (example: 87,88,89)
                                            <input type="text" name="section-id" value="{$section_id_num}" class="form-control input-md" 
                                                   default="2,142,88,89,83,85,86,87" placeholder="2,142,88,89,83,85,86,87" required>
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
                                        <label for="push-date" style="height:4rem;">Push DateTime (start from a time after events :<br></label>
                                        <div class='input-group date' id='push-date'>
                                            <input type='text' class="form-control" name="push-date" value="{$push_date}" />
                                            <span class="input-group-addon">
                                                <span class="glyphicon glyphicon-calendar"></span>
                                            </span>
                                        </div>
                                    </div>



                                    {$finalize_checkbox}

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
