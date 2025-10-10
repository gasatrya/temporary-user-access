/**
 * Admin JavaScript for Temporary User Access plugin
 * 
 * @package TemporaryUserAccess
 */

(function($) {
    'use strict';

    /**
     * Initialize expiry date clear functionality
     */
    function initExpiryClear() {
        var btn = $('#user_expiry_clear_btn');
        if (btn.length) {
            btn.on('click', function(e) {
                e.preventDefault();
                $('#user_expiry_date').val('');
                $('#user_expiry_clear').prop('checked', true);
                $('#user_expiry_date').focus();
                
                var msg = $('#user_expiry_clear_msg');
                if (msg.length) { 
                    msg.text(tua_admin.expiry_cleared_text); 
                    setTimeout(function(){ 
                        msg.text(''); 
                    }, 2000); 
                }
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initExpiryClear();
    });

})(jQuery);
