/* Mail settings - "Test Connection" button.
 * Sends a sample test email through the app's normal mail pipeline (sendEmail())
 * using the currently saved SMTP settings, and shows the result inline.
 * Every attempt is logged server-side to core_emaillog regardless of outcome.
 */
$(function () {
	var $btn = $('#testEmailBtn');
	if (!$btn.length) return;

	function showResult(type, icon, text) {
		var $div = $('<div>').addClass('alert alert-' + type);
		$('<i>').addClass('fa ' + icon).appendTo($div);
		$div.append(document.createTextNode(' ' + text));
		$('#testEmailResult').empty().append($div);
	}

	$btn.on('click', function () {
		var to = $.trim($('#email_test_to').val());
		var $spinner = $('#testEmailSpinner');

		$('#testEmailResult').empty();

		if (!to) {
			showResult('warning', 'fa-exclamation-triangle', 'Please enter an email address to send the test to.');
			return;
		}

		$btn.prop('disabled', true);
		$spinner.show();

		$.post('', {
			action: 'testEmailSettings',
			email_test_to: to
		}, function (data) {
			$spinner.hide();
			$btn.prop('disabled', false);

			if (data && data.success) {
				showResult('success', 'fa-check-circle', data.message || 'Test email sent successfully.');
			} else {
				var err = (data && data.error) ? data.error : 'Unknown error.';
				showResult('danger', 'fa-exclamation-circle', 'Test email failed: ' + err);
			}
		}, 'json').fail(function (xhr) {
			$spinner.hide();
			$btn.prop('disabled', false);
			showResult('danger', 'fa-exclamation-circle', 'Test email failed: request error (HTTP ' + xhr.status + ').');
		});
	});
});
