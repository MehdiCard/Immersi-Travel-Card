(function($) {
    'use strict';

    $(document).ready(function() {
        $('.itc-toggle-status').on('change', function() {
            var $checkbox = $(this);
            var order_id = $checkbox.data('order_id');
            var field = $checkbox.data('field');
            var value = $checkbox.is(':checked') ? 1 : 0;

            $.post(itcImprimerieAjax.ajax_url, {
                action: 'itc_toggle_imprimerie_status',
                order_id: order_id,
                field: field,
                value: value,
                _ajax_nonce: itcImprimerieAjax.nonce
            });
        });
    });
})(jQuery);
