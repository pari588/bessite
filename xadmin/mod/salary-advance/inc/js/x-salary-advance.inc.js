$(document).ready(function() {
    // Approve button
    $(document).on('click', '.approve-btn', function() {
        var advanceID = $(this).data('id');
        if (confirm('Are you sure you want to approve this advance request?')) {
            updateAdvanceStatus(advanceID, 'approve');
        }
    });

    // Reject button
    $(document).on('click', '.reject-btn', function() {
        var advanceID = $(this).data('id');
        if (confirm('Are you sure you want to reject this advance request?')) {
            updateAdvanceStatus(advanceID, 'reject');
        }
    });
});

function updateAdvanceStatus(advanceID, action) {
    $.ajax({
        url: 'x-salary-advance.inc.php',
        type: 'POST',
        data: {
            xAction: action,
            advanceID: advanceID,
            token: $('input[name="token"]').val()
        },
        success: function(response) {
            var res = JSON.parse(response);
            if (res.err == 0) {
                alert(res.alert || 'Status updated successfully');
                location.reload();
            } else {
                alert(res.msg || 'Error updating status');
            }
        },
        error: function() {
            alert('Request failed');
        }
    });
}
