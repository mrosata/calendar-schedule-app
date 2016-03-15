jQuery(function ($) {
  var timepickerSettings = {
      template: false,
      showInputs: false,
      maxHours: 24,
      minuteStep: 5,
      default: '12:00',
      showSeconds: false,
      showMeridian: false,
      explicitMode: true
    },
    $datetimepickers = [],
    timepickerOpts = {sideBySide: true, showMeridian: false, defaultTime: '8:00'};

  $datetimepickers._1 = $('#start-date-dt').datetimepicker({format: 'YYYY-MM-DD HH:mm'});
  $datetimepickers._2 = $('#day-2').datetimepicker({format: 'YYYY-MM-DD HH:mm'});
  $datetimepickers._3 = $('#day-3').datetimepicker({format: 'YYYY-MM-DD HH:mm'});
  $datetimepickers.pushDate = $('#push-date').datetimepicker({format: 'YYYY-MM-DD HH:mm'});

  if ($datetimepickers._1.length) {
    $datetimepickers._1.datetimepicker('options', timepickerOpts);
    $datetimepickers._2.datetimepicker('options', timepickerOpts);
    $datetimepickers._3.datetimepicker('options', timepickerOpts);
    $datetimepickers.pushDate.datetimepicker('options', timepickerOpts);
  }

  // All the tooltips
  $('[data-toggle="tooltip"]').tooltip();
  $('[data-toggle="popover"]').popover();




  var $getDatesBtn = $('#get-dates-by-event');
  $getDatesBtn.on('click', (evt) => {
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
            console.debug(data);
            if (typeof data.dates === "object" && data.dates.constructor === Array) {
              data.dates.forEach((dateTime, ind)=> {
                if ($datetimepickers.hasOwnProperty(`_${ind}`)) {
                  // Set the date onto the datetimepicker (using .data("DateTimePicker").FUNCTION() )
                  $datetimepickers[`_${ind}`].data("DateTimePicker").date(dateTime);
                }
              });
              if (typeof data.conflicts === "object") {
                // Get the emails and their conflicts
                $('input[name="conflicts"]').val(JSON.stringify(data.conflicts));

                let conflictsForm = "<div class='row panel'><div class='panel panel-heading'>Watching for conflicts:</div><div class='panel panel-body'>";
                for (let email in Object.keys(data.conflicts)) {
                  if (data.conflicts.email) {
                    let _from = new Date().setTime(+data.conflicts[email].from).format('Y-m-d H:i');
                    let _to = new Date().setTime(+data.conflicts[email].to).format('Y-m-d H:i');

                    conflictsForm += `<div class='col-sm-6'><strong>${email}</strong></div><div class='col-sm-3'><code>${_from}</code></div><div class='col-sm-3'><code>${_to}</code></div>`;
                  }
                }
                conflictsForm += "</div></div>";

                $('.conflicts-group').html(conflictsForm);
              }
            }
          } else {
            console.error('fail', resp.responseJSON);
          }
        }
      }
    });

    evt.preventDefault();
  });




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
        //console.log('The time is ' + e.time.value, 'The hour is ' + e.time.hours, 'The minute is ' + e.time.minutes, 'The meridian is ' + e.time.meridian);
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

});
