/**
 * Smart Discount Rules - Admin JS
 *
 * @package Woo_Smart_Discounts
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        var $typeSelect = $('#wsd_rule_type');

        function toggleFields() {
            var type = $typeSelect.val();

            // Hide all conditional fields.
            $('.wsd-field').hide();

            if (!type) {
                return;
            }

            // Show fields matching the selected type.
            $('.wsd-field-' + type).show();
        }

        $typeSelect.on('change', toggleFields);
        toggleFields();
    });

})(jQuery);
