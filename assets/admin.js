/**
 * Admin JavaScript for Temporary User Access plugin
 *
 * @package TempUsAc
 */

(function($) {
	'use strict';

	/**
	 * Toggle auto-delete checkbox based on expiry date presence
	 */
	function toggleAutoDeleteState() {
		var expiryDate = $('#user_expiry_date').val();
		var autoDeleteCheckbox = $('#user_auto_delete');

		if (!expiryDate) {
			autoDeleteCheckbox.prop('checked', false);
			autoDeleteCheckbox.prop('disabled', true);
		} else {
			autoDeleteCheckbox.prop('disabled', false);
		}
	}

	/**
	 * Initialize expiry date clear functionality
	 */
	function initExpiryClear() {
		var btn = $('#user_expiry_clear_btn');
		var expiryField = $('#user_expiry_date');

		if (btn.length) {
			btn.on('click', function(e) {
				e.preventDefault();
				expiryField.val('');
				$('#user_expiry_clear').prop('checked', true);
				expiryField.focus();

				// Update auto-delete state after clearing
				toggleAutoDeleteState();

				var msg = $('#user_expiry_clear_msg');
				if (msg.length) {
					msg.text(tempusac_admin.expiry_cleared_text);
					setTimeout(function(){
						msg.text('');
					}, 2000);
				}
			});
		}

		// Listen for manual changes to the date field
		expiryField.on('change input', function() {
			toggleAutoDeleteState();
		});
	}

	// Initialize when document is ready
	$(document).ready(function() {
		initExpiryClear();
		// Set initial state
		toggleAutoDeleteState();
	});

})(jQuery);
