
// this is the datetime format for all the datetimepickers on the page
var datetimeFormat = 'YYYY-MM-DD HH:mm'; // HH:mm

jQuery(function ($) {
    var timepickerSettings = {
            template: false,
            showInputs: false,
            maxHours: 24,
            minuteStep: 5,
            default: '00:00',
            showSeconds: false,
            showMeridian: false,
            explicitMode: true
        },
        $datetimepickers = [],
        timepickerOpts = {sideBySide: true, showMeridian: false, defaultTime: '8:00'};

    $datetimepickers._0 = $('#day-1').datetimepicker({format: datetimeFormat});
    $datetimepickers._0.datetimepicker('options', timepickerOpts);

    $datetimepickers.pushDate = $('#push-date').datetimepicker({format: datetimeFormat});
    $datetimepickers.pushDate.datetimepicker('options', timepickerOpts);

    let i = 2;
    let $nextDynamicDatePicker = $(`input[name="day-${i++}"]`);
    while ($nextDynamicDatePicker.length) {
        $nextDynamicDatePicker
            .datetimepicker({format: datetimeFormat});
        $nextDynamicDatePicker
            .datetimepicker('options', timepickerOpts);

        $nextDynamicDatePicker = $(`#day-${i++}`);
    }

    // All the tooltips
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();




    var $inputEventId = $('#dates-event-id');
    var $getDatesBtn = $('#get-dates-by-event');


    function lookupEventDatesAndBreakMask (evt) {
        evt.preventDefault();
        let eventID = + ($('#dates-event-id').val());
        $.ajax({
            url: 'ajax.php',
            type: 'post',
            data: {
                'event-id': eventID
            },
            dataType: 'json',
            complete(resp) {
                if (resp.hasOwnProperty('responseJSON') && typeof resp.responseJSON === "object" && !!resp.responseJSON.hasOwnProperty('data')) {
                    if (resp.responseJSON.success) {
                        let data = resp.responseJSON.data;

                        if (typeof data.dates === "object" && data.dates.constructor === Array) {
                            let $dynamicDates = $('#dynamic-dates');
                            $dynamicDates.html('');

                            data.dates.forEach((dateTime, ind)=> {
                                if (/.*\s\d{2}:\d{2}:.*/.test(dateTime)) {
                                    dateTime = /(.*\s\d{2}:\d{2}):.*/.exec(dateTime)[1];

                                }
                                if ($dynamicDates.find(`input[name=day-${ind+1}]`).length) {
                                    // There is already an element for this datetimepicker to take over
                                    // Set the date onto the datetimepicker (using .data("DateTimePicker").FUNCTION() )
                                    $dynamicDates.find(`input[name=day-${ind+1}]`).datetimepicker({date: dateTime});
                                }
                                else {
                                    let breaks = typeof data.breaks === "object" && data.breaks.constructor === Array && data.breaks.length > ind ? data.breaks[ind] :'';
                                    // remove trailing commas
                                    breaks = breaks.replace(/(.*\d),+$/, '$1');

                                    // There is no datetimepicker element so we must add one.
                                    let nextDateTimePicker = `
                    <div class='form-group col-md-6 col-lg-4'>
                        <div class='form-group calendar-form-group'>
                            <label for='day-1' style='height:4rem;'>Day ${ind + 1}:</label>
                            <div class='input-group date' id='day-${ind + 1}'>
                                <input type='text' class='form-control' name='day-${ind + 1}' value="${dateTime}" />
                                <span class='input-group-addon'>
                                    <span class='glyphicon glyphicon-calendar'></span>
                                </span>
                            </div>
                        </div>
                        Breaks:<br><input type='text' value='${breaks}' name='breaks-day-${ind + 1}' class='input form-control'>
                    </div>`;
                                    // Add this date to the page and then update the date inside of it.
                                    $dynamicDates.append(nextDateTimePicker);
                                    $(`#day-${ind + 2}`)
                                        .datetimepicker({format: datetimeFormat, date: dateTime});
                                    $(`#day-${ind + 2}`)
                                        .datetimepicker('options', timepickerOpts);

                                }

                            });

                            let numDatepickers = data.dates.length + 1;
                            while ( $(`#day-${numDatepickers++}`).length ) {
                                $(`#day-${numDatepickers++}`).parents('.form-group').first().remove();
                            }

                            if (typeof data.conflicts === "object") {
                                // Get the emails and their conflicts
                                $('input[name="conflicts"]').val(JSON.stringify(data.conflicts));
                                $('input[name="conflicts"]').parents('form').first().append('<input name="conflicts-from-javascript" type="hidden" value="1" />');

                                let conflictsForm = "<div class='row panel'><div class='panel panel-heading'>Watching for conflicts:</div><div class='panel panel-body'>";
                                for (let email in data.conflicts) {
                                    if (data.conflicts.hasOwnProperty(email)){
                                        conflictsForm += `<strong>${email}</strong> @ <code>${data.conflicts[email].from}</code> - <code>${data.conflicts[email].to}</code><br>`;
                                    }
                                }
                                conflictsForm += "</div></div>";

                                $('.conflicts-group').html(conflictsForm);
                            }
                        }
                    } else {
                        //console.error('fail', resp.responseJSON);
                    }
                }
            }
        });
    }

    
    $getDatesBtn.on('click', lookupEventDatesAndBreakMask);

    if ($getDatesBtn.length && $inputEventId.length) {
        // NEW PLAN, HIDE THE BUTTON TO LOOKUP BREAKS AND DATES. We will do it automagically!
        $getDatesBtn.hide();
        $inputEventId.on('change', lookupEventDatesAndBreakMask);
    }



    function setupAllTimePickers() {
        $('.break-timepicker').timepicker(timepickerSettings)
            .on('changeTime.timepicker', function() {
                var $tc = $(this);
                if ($tc.attr('name').match(/break-start/)) {
                    // Make sure end is after start
                    var $end = $tc.parent().next('label[for="break-end[]"]').find('input');
                    if (+$end.val().replace(/[^0-9]/, '') < +$tc.val().replace(/[^0-9]/, '')) {
                        // the end time must be equal or greater than start
                        $end.timepicker('setTime', $tc.val());
                    }
                }
                else if ($tc.attr('name').match(/break-end/)) {
                    // Make sure start doesn't come after end
                    var $start = $tc.parent().prev('label[for="break-start[]"]').find('input');
                    if (+$start.val().replace(/[^0-9]/, '') > +$tc.val().replace(/[^0-9]/, '')) {
                        // the end time must be equal or greater than start
                        $start.timepicker('setTime', $tc.val());
                    }
                }
            });
    }


    setupAllTimePickers();
    $('.add-new-break').on('click', function () {
        var $btn = $(this);
        var $newBreakInput = $btn.prev('.new-break-input-container');
        if ($btn.length && $newBreakInput.length) {


            if ($newBreakInput.hasClass('hidden')) {
                $newBreakInput.removeClass('hidden');
            } else {
                $btn.before($newBreakInput.clone());
            }
            setupAllTimePickers();
        }
    });

    window.devbug = () => {
        let hiddenInput = $('input[type=hidden][name=devbug]');
        if (hiddenInput.length) {
            // toggle the inputs value
            return hiddenInput.val(!hiddenInput.val());
        }
        $('form[name=config]').append('<input type="hidden" name="devbug" value="true">');
    };
});

