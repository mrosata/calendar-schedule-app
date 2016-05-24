<?php
/**
 * Functionality for Push Dates
 *
 * Step 0. --> Get the push-date. If push-date != '' then continue to [Step 1.]
 * Step 1. --> ($fixed) Get list of all meetings which are "fixed" in place (quote=1, they have
 *             been moved by the user via calendar. Those meetings should not be adjusted).
 * Step 2. --> ($past_meetings) Get list of all meetings which happened before push-date. 
 * Step 3. --> ($ignore_meetings) Create a list of meetings to ignore (this is combine($fixed, $past))
 *             ($imaginary_meetings) Create array of imaginary meetings with start,end. They can be compared
 *             to when compressing
 * Step 4. --> When adding projects to investors. Check if that is one of the meetings that doesn't
 *             need to be scheduled. [ ( X ).. completed ]
 * Step 5. --> Add exceptions for investor and for project at times in $fixed.
 * 
 * 
 * 
 * Step 6. --> (if "overwrite") Remove all meetings before push-date and also meetings quote != 1
 */

global $ignore_meetings; // Array[investor_id] = Array[project_id's]
global $fixed_meetings;
global $db;

$push_date = \Util\post('push-date');

/**
 * Step 1. Get list of meetings which are after push-date which are fixed (edited).
 */
$fixed_meetings = $db->get_fixed_meetings(EVENT_ID, $push_date);

/**
 * Step 2. Get list of meetings which happened before push date
 */
$past_meetings = $db->get_meetings_before(EVENT_ID, $push_date);

function add_meeting_to_collision($investor_id, $project_id, &$meeting_array) {
    if (!isset($meeting_array[(int)$investor_id]) || !is_array($meeting_array[(int)$investor_id])) {
        $meeting_array[(int)$investor_id] = array();
    }
    $meeting_array[(int)$investor_id][] = (int)$project_id;
}

function add_collisions_to_investors_and_projects($investors, $projects) {
    global $fixed_meetings;
    
}

/**
 * Step 3.
 */
if (is_array($past_meetings)) {
    foreach($past_meetings as $meeting) {
        if (!$meeting->investor || !$meeting->project)
            continue;
        // Add this combo to the list of $ignored_meetings
        add_meeting_to_collision($meeting->investor, $meeting->project, $ignore_meetings);
    }
    unset($past_meetings);
}

if (is_array($fixed_meetings)) {
    foreach($fixed_meetings as $meeting) {
        if (!$meeting->investor || !$meeting->project)
            continue;
        // Add this combo to the list of $ignored_meetings
        add_meeting_to_collision($meeting->investor, $meeting->project, $ignore_meetings);
    }
    unset($fixed_meetings);
}

\Util\print_pre($past_meetings);
exit;
