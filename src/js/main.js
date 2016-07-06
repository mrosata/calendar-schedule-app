
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
        timepickerOpts = {sideBySide: true, showMeridian: false, defaultTime: '00:00'};

    $datetimepickers._0 = $('#day-1').datetimepicker({format: datetimeFormat});
    $datetimepickers._0.datetimepicker('options', timepickerOpts);

    $datetimepickers.pushDate = $('#push-date').datetimepicker({
        format: datetimeFormat,
        defaultDate: $('input[name=push-date]').val()
    });
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




    let $inputEventId = $('#dates-event-id');
    let $getDatesBtn = $('#get-dates-by-event');
    let $clearPushDateBtn = $('#clear-push-date');

    console.log('%cCopro %cShedule%c Algorithm!%c...\n\n', 'color:red;font-size:34px',
        'color:blue;font-size:30px', 'color:green;font-size:25px',
        'color:pink;font-style:italic;font-size:16px');
    if ($clearPushDateBtn.length) {
        $clearPushDateBtn.on('click', function(evt){
            evt.preventDefault();
            console.log('clearing push date');
            $('[name=push-date]').val('');
        });
    }


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

    function createModalOnForm() {
        let $modal = $('#confirm-submission');
        let $form = $('form[name="config"]');

        if (!$form.length || !$modal.length) {
            console.error('Missing either form or modal on page!');
            return;
        }
        // If activate-push-date is checked we turn submit button into regular button so it will show
        // the modal on click.


        $form
            .find('button[type="submit"]')
            .on('click', function (evt) {
                const activatePushDateChecked = $('[name="activate-push-date"]', $form).is(':checked');
                const honorPinnedChecked = $('[name="honor-pinned-meetings"]', $form).is(':checked');
                const overwriteFinalizeChecked = $('input[name="finalize"]').length && $('input[name="finalize"]').is(':checked');
                const calendarId = $('input[name="calendar-id"]').val();
                if (!overwriteFinalizeChecked) {
                    console.log('%cform %cwas %csubmitted!',
                        'font-style:italic;font-weight:bold;font-size:40px;color:green', 'font-size:25px;color:blue', 'font-size:30px;color:red' );

                    // submit form it is dry run.
                    $form.submit();
                    return true;
                }
                evt.preventDefault();


                // This will show the data on the modal custom to the form!
                $modal.on('shown.bs.modal', function () {
                    $('.push-date-active').toggleClass('hidden', !activatePushDateChecked );
                    $('.push-date-inactive').toggleClass('hidden', activatePushDateChecked );
                    $('.honor-pinned-meetings').toggleClass('hidden', !honorPinnedChecked );
                    $('.not-honor-pinned-meetings').toggleClass('hidden', honorPinnedChecked );
                    $('span[data-form-value="push-date"]').text($('input[name="push-date"]').val());
                    $('span[data-form-value="calendar"]').html(`<a href="https://copro.ezadmin3.com/copro.co.il/originals/miker/calendar/index.html?eventid=${calendarId}" target="_blank">Calendar ${calendarId}</a>`);
                    $modal.off('shown.bs.modal');
                });

                console.log('%cSHOWING %cCONFIRMATION %cMODAL!!!',
                    'font-style:italic;font-weight:bold;font-size:40px;color:green', 'font-size:25px;color:blue', 'font-size:30px;color:red' );

                $modal.modal('show');

                $('button[data-run-form="true"]', $modal)
                    .one('click', function() {
                        $modal.modal('hide');

                        const $loadingAnime = $('.loading-animation');
                        if ($loadingAnime.length) {
                            $loadingAnime.css({
                                display: 'block',
                                width: '100%',
                                height: '100000000%',
                                position: 'absolute',
                                top: '0',
                                bottom: '0',
                                right: '0',
                                left: '0'
                            });
                            $loadingAnime.addClass('active');
                        }
                        $form.submit();
                    });

            });
    }
    createModalOnForm();



    window.devbug = () => {
        let hiddenInput = $('input[type=hidden][name=devbug]');
        if (hiddenInput.length) {
            // toggle the inputs value
            return hiddenInput.val(!hiddenInput.val());
        }
        $('form[name=config]').append('<input type="hidden" name="devbug" value="true">');
    };
});

