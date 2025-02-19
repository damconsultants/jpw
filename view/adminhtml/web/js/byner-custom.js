require([
    'jquery',
    'select2'
], function ($) {
    jQuery(document).ready(function () {
        jQuery('#bynder_property').select2();
        jQuery('#bynder_property_image_role').select2();
        jQuery('#bynder_property_alt_tax').select2();
    });
});