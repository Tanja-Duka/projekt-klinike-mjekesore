// ============================================================
// reserve.js - Logjika e rezervimit të takimit
// ============================================================

$(document).ready(function () {

    // TODO: $('#doctor-select, #date-input').on('change', function() {
    //     loadAvailableSlots();
    // });

    // TODO: function loadAvailableSlots() {
    //     const doctorId = $('#doctor-select').val();
    //     const date = $('#date-input').val();
    //     if (!doctorId || !date) return;
    //     $.ajax({
    //         url: BASE_URL + '/api/check_slot.php',
    //         data: { doctor_id: doctorId, date: date },
    //         success: function(data) { populateSlots(data.available_slots); }
    //     });
    // }

    // TODO: function populateSlots(slots) {
    //     // Populate #time-slot dropdown
    // }

    // TODO: $('#reserve-form').on('submit', function(e) {
    //     e.preventDefault();
    //     // Shfaq spinner
    //     $.ajax({
    //         url: BASE_URL + '/api/reserve_ajax.php',
    //         method: 'POST',
    //         data: $(this).serialize(),
    //         success: function(data) {
    //             if (data.success) { window.location.href = 'appointments.php'; }
    //             else { showError(data.message); }
    //         }
    //     });
    // });

});
