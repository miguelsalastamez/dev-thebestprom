import IgdShortcode from "./components/Shortcode";

import {initShortcode,} from "./includes/functions";

window.IgdShortcode = IgdShortcode;
window.initShortcode = initShortcode;


;(function ($) {

    const {integrations = ['classic-editor', 'gutenberg-editor', 'elementor']} = igd.settings;

    const app = {

        init: function () {

            initShortcode();

            // Elementor
            if (integrations.includes('elementor')) {
                $(window).on('elementor/frontend/init', function () {

                    // Render module shortcode
                    window.elementorFrontend.hooks.addAction('frontend/element_ready/igd_shortcodes.default', initShortcode);
                    window.elementorFrontend.hooks.addAction('frontend/element_ready/shortcode.default', initShortcode);
                    window.elementorFrontend.hooks.addAction('frontend/element_ready/form.default', initShortcode);
                    window.elementorFrontend.hooks.addAction('frontend/element_ready/mf-igd-uploader.default', initShortcode);
                    window.elementorFrontend.hooks.addAction('frontend/element_ready/text-editor.default', initShortcode);

                });
            }


        },

    }

    app.init();

})(jQuery);

