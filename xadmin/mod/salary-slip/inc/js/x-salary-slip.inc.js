$(document).ready(function() {
    // Mark as Paid button
    $(document).on('click', '.mark-paid', function() {
        var slipID = $(this).data('id');
        var netSalary = $(this).data('net');
        $('#paySlipID').val(slipID);
        $('#amountPaid').val(netSalary);
        $('.payment-popup').show();
    });

    // Payment form submit
    $('#paymentForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&xAction=markPaid&token=' + $('input[name="token"]').val();

        $.ajax({
            url: 'x-salary-slip.inc.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                var res = JSON.parse(response);
                if (res.err == 0) {
                    alert(res.alert || 'Payment marked successfully');
                    closePaymentPopup();
                    location.reload();
                } else {
                    alert(res.msg || 'Error marking payment');
                }
            },
            error: function() {
                alert('Request failed');
            }
        });
    });

    // Generate PDF button
    $(document).on('click', '.gen-pdf', function() {
        var slipID = $(this).data('id');
        if (confirm('Generate PDF for this salary slip?')) {
            $.ajax({
                url: 'x-salary-slip.inc.php',
                type: 'POST',
                data: {
                    xAction: 'generatePDF',
                    slipID: slipID,
                    token: $('input[name="token"]').val()
                },
                success: function(response) {
                    var res = JSON.parse(response);
                    if (res.err == 0) {
                        alert(res.alert || 'PDF generated successfully');
                        location.reload();
                    } else {
                        alert(res.msg || 'Error generating PDF');
                    }
                },
                error: function() {
                    alert('Request failed');
                }
            });
        }
    });
});

function closePaymentPopup() {
    $('.payment-popup').hide();
    $('#paymentForm')[0].reset();
}

function bulkGenerateSlips() {
    var month = $('#bulkMonth').val();
    var year = $('#bulkYear').val();
    if (confirm('Generate salary slips for all employees for ' + $('#bulkMonth option:selected').text() + ' ' + year + '?')) {
        $.ajax({
            url: 'x-salary-slip.inc.php',
            type: 'POST',
            data: {
                xAction: 'bulkGenerate',
                month: month,
                year: year,
                token: $('input[name="token"]').val()
            },
            success: function(response) {
                var res = JSON.parse(response);
                if (res.err == 0) {
                    alert(res.alert || 'Salary slips generated successfully');
                    location.reload();
                } else {
                    alert(res.msg || 'Error generating slips');
                }
            },
            error: function() {
                alert('Request failed');
            }
        });
    }
}
