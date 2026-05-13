// ============================================================
// reserve.js - Logjika e rezervimit: slots + submit AJAX
// ============================================================

$(function () {
    var $doctorInput    = $('#doctorSelect');
    var $dateInput      = $('#dateInput');
    var $slotsContainer = $('#slotsContainer');
    var $timeSlots      = $('#timeSlots');
    var $timeSlotInput  = $('#timeSlotInput');
    var $noSlotsMsg     = $('#noSlotsMsg');
    var $submitBtn      = $('#submitBtn');
    var $reserveError   = $('#reserveError');
    var $form           = $('#reserveForm');

    // Trigger slot load when doctor changes (fired by selectDoctor() in reserve.php)
    $doctorInput.on('change', function () {
        var date = $dateInput.val();
        if ($(this).val() && date) loadSlots($(this).val(), date);
    });

    // Trigger slot load when date changes
    $dateInput.on('change', function () {
        var date = $(this).val();
        // Update summary date
        if (date) {
            var p = date.split('-');
            $('#sumDate').text(p[2] + '/' + p[1] + '/' + p[0]);
        }
        var doctorId = $doctorInput.val();
        if (doctorId && date) loadSlots(doctorId, date);
        checkSubmit();
    });

    // Called by selectDoctor() in page script when doctor + date already set
    window.loadSlots = function (doctorId, date) {
        $slotsContainer.show();
        $timeSlots.html('<span style="color:var(--ink-3);font-size:.82rem;">Duke ngarkuar oraret…</span>').show();
        $noSlotsMsg.hide();
        $timeSlotInput.val('');
        checkSubmit();

        $.ajax({
            url:     BASE_URL + '/api/check_slot.php',
            method:  'GET',
            data:    { doctor_id: doctorId, date: date },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (data) {
                $timeSlots.empty();
                if (!data.success || !data.slots || data.slots.length === 0) {
                    $timeSlots.hide();
                    $noSlotsMsg.show();
                } else {
                    $noSlotsMsg.hide();
                    $timeSlots.show();
                    $.each(data.slots, function (i, slot) {
                        $('<button>', { type: 'button', class: 'time-slot', text: slot })
                            .on('click', function () {
                                $timeSlots.find('.time-slot').removeClass('selected');
                                $(this).addClass('selected');
                                $timeSlotInput.val(slot);
                                $('#sumTime').text(slot);
                                checkSubmit();
                            })
                            .appendTo($timeSlots);
                    });
                }
            },
            error: function () {
                $timeSlots.html('<span style="color:var(--error,#c0392b);font-size:.82rem;">Gabim gjatë ngarkimit. Provoni sërish.</span>');
            }
        });
    };

    // Enable submit only when all required fields are filled
    function checkSubmit() {
        var ok = !!$doctorInput.val() &&
                 !!$('#serviceSelect').val() &&
                 !!$dateInput.val() &&
                 !!$timeSlotInput.val();
        $submitBtn.prop('disabled', !ok);
    }

    $('#serviceSelect').on('change', checkSubmit);
    $doctorInput.on('change', checkSubmit);

    // AJAX form submission
    $form.on('submit', function (e) {
        e.preventDefault();
        $reserveError.hide().text('');
        $submitBtn.prop('disabled', true).text('Duke rezervuar…');

        $.ajax({
            url:     BASE_URL + '/api/reserve_ajax.php',
            method:  'POST',
            data:    $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (data) {
                if (data.success) {
                    window.location.href = BASE_URL + '/patient/appointments.php';
                } else {
                    $reserveError.text(data.message || 'Ndodhi një gabim. Provoni sërish.').show();
                    $submitBtn.prop('disabled', false).text('Konfirmo rezervimin →');
                }
            },
            error: function () {
                $reserveError.text('Gabim rrjeti. Ju lutemi provoni sërish.').show();
                $submitBtn.prop('disabled', false).text('Konfirmo rezervimin →');
            }
        });
    });
});
