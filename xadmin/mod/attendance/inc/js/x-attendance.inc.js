$(document).ready(function() {
    // View remarks popup
    $(document).on('click', '.view-remarks', function(e) {
        e.preventDefault();
        var attendanceID = $(this).data('id');
        // Load remarks via AJAX if needed
        $('.remarks-popup').show();
    });
});

function closeRemarksPopup() {
    $('.remarks-popup').hide();
}
