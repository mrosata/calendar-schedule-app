'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj; };

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
      timepickerOpts = { sideBySide: true, showMeridian: false, defaultTime: '8:00' };

  $datetimepickers._0 = $('#start-date-dt').datetimepicker({ format: 'YYYY-MM-DD HH:mm' });
  $datetimepickers._1 = $('#day-2').datetimepicker({ format: 'YYYY-MM-DD HH:mm' });
  $datetimepickers._2 = $('#day-3').datetimepicker({ format: 'YYYY-MM-DD HH:mm' });
  $datetimepickers.pushDate = $('#push-date').datetimepicker({ format: 'YYYY-MM-DD HH:mm' });

  if ($datetimepickers._0.length) {
    $datetimepickers._0.datetimepicker('options', timepickerOpts);
    $datetimepickers._1.datetimepicker('options', timepickerOpts);
    $datetimepickers._2.datetimepicker('options', timepickerOpts);
    $datetimepickers.pushDate.datetimepicker('options', timepickerOpts);
  }

  // All the tooltips
  $('[data-toggle="tooltip"]').tooltip();
  $('[data-toggle="popover"]').popover();

  var $getDatesBtn = $('#get-dates-by-event');
  $getDatesBtn.on('click', function (evt) {
    var eventID = +$('#dates-event-id').val();
    $.ajax({
      url: 'ajax.php',
      type: 'post',
      data: {
        'event-id': eventID
      },
      dataType: 'json',
      complete: function complete(resp) {
        if (resp.hasOwnProperty('responseJSON') && _typeof(resp.responseJSON) === "object" && !!resp.responseJSON.hasOwnProperty('data')) {
          if (resp.responseJSON.success) {
            var data = resp.responseJSON.data;
            console.debug(data);
            if (_typeof(data.dates) === "object" && data.dates.constructor === Array) {
              data.dates.forEach(function (dateTime, ind) {
                if ($datetimepickers.hasOwnProperty('_' + ind)) {
                  // Set the date onto the datetimepicker (using .data("DateTimePicker").FUNCTION() )
                  $datetimepickers['_' + ind].data("DateTimePicker").date(dateTime);
                }
              });
              if (_typeof(data.conflicts) === "object") {
                // Get the emails and their conflicts
                $('input[name="conflicts"]').val(JSON.stringify(data.conflicts));

                var conflictsForm = "<div class='row panel'><div class='panel panel-heading'>Watching for conflicts:</div><div class='panel panel-body'>";
                for (var email in data.conflicts) {
                  if (data.conflicts.hasOwnProperty(email)) {
                    conflictsForm += '<strong>' + email + '</strong> @ <code>' + data.conflicts[email].from + '</code> - <code>' + data.conflicts[email].to + '</code><br>';
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
    $('.break-timepicker').timepicker(timepickerSettings).on('changeTime.timepicker', function () {
      var $tc = $(this);
      if ($tc.attr('name').match(/break-start/)) {
        // Make sure end is after start
        var $end = $tc.parent().next('label[for="break-end[]"]').find('input');
        if (+$end.val().replace(/[^0-9]/, '') < +$tc.val().replace(/[^0-9]/, '')) {
          // the end time must be equal or greater than start
          $end.timepicker('setTime', $tc.val());
        }
      } else if ($tc.attr('name').match(/break-end/)) {
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

//# sourceMappingURL=main-compiled.js.map