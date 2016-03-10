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
    $datetimepicker = $('#start-date-dt').datetimepicker({format: 'MM/DD/YYYY HH:mm'});

  if ($datetimepicker.length) {
    $datetimepicker.datetimepicker('options', {sideBySide: true});
  }

  // All the tooltips
  $('[data-toggle="tooltip"]').tooltip();
  $('[data-toggle="popover"]').popover();


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