<?php
/**
 * Created by michael on 2/25/16.
 *
 * Array
(
[@odata.context] => https://outlook.office.com/api/beta/$metadata#Me/CalendarGroups('AQMkADM4M2ViNmI3LWJlOTctNGQ4NC04YzRjLTY4MWJjNTlkMTJkNgBGAAAD6fB3k-xwUUCIlqrn_j8D4AcAPawvUY6L2UOV-Khgk4FR1gAAAgEGAAAAPawvUY6L2UOV-Khgk4FR1gAAAiaGAAAA')/Calendars/$entity
[@odata.id] => https://outlook.office.com/api/beta/Users('b75c7552-39be-4317-871a-9cd1cfe28ff7@60042594-34f5-43e6-a65c-0c6ea8b536bf')/Calendars('AAMkADM4M2ViNmI3LWJlOTctNGQ4NC04YzRjLTY4MWJjNTlkMTJkNgBGAAAAAADp8HeT-HBRQIiWquf6PwPgBwA9rC9RjovZQ5X8qGCTgVHWAAAAAAEGAAA9rC9RjovZQ5X8qGCTgVHWAAACZJkoAAA=')
[Id] => AAMkADM4M2ViNmI3LWJlOTctNGQ4NC04YzRjLTY4MWJjNTlkMTJkNgBGAAAAAADp8HeT-HBRQIiWquf6PwPgBwA9rC9RjovZQ5X8qGCTgVHWAAAAAAEGAAA9rC9RjovZQ5X8qGCTgVHWAAACZJkoAAA=
[Name] => Meetings App Schedule
[Color] => Auto
[ChangeKey] => PawvUY6L2UOV/Khgk4FR1gAAAmTJ0Q==
)
 *
 */

function show_calendar($api) {

    $events = $api->get_events();
    //$table = new \U\Table(array('Subject','Organizer', 'Start', 'End'));
    //$table->head(null, 'table table-condensed');

    if (isset($events['value'])) {
        foreach($events['value'] as $event) {
            $row = array(
                $event['Subject'],
                $event['Organizer']['EmailAddress']['Name'],
                date('M d, Y H:i a', strtotime($event['Start']['DateTime'])),
                date('M d, Y H:i a', strtotime($event['End']['DateTime']))
            );
            //$table->row( $row );

            $api->delete_event($event['Id']);
        }
    }
    else {
        echo "<h5>No Events! <em class='secondary'>Unable to retrieve any Outlook Events with supplied information!</em></h5>";
    }

    //$table->close();

}
