// Component Imports
import App from "./components/App";
import ShortcodeBuilder from "./components/ShortcodeBuilder/ShortcodeBuilder";

import ModuleBuilderModal from "./components/ModuleBuilderModal";
import IgdShortcode from "./components/Shortcode";

import {initShortcode} from "./includes/functions";

window.ModuleBuilderModal = ModuleBuilderModal;
window.ModuleBuilder = ShortcodeBuilder;
window.IgdShortcode = IgdShortcode;
window.initShortcode = initShortcode;

import gettingStarted from './includes/getting-started';
import migration from './includes/migration';

// Integration Imports
import classicEditor from "./integrations/classic-editor";
import cf7 from "./integrations/cf7";

;(function ($) {

    const {integrations = ['classic-editor', 'gutenberg-editor', 'elementor']} = igd.settings;

    const app = {

        init: () => {
            app.initFileBrowser();
            app.initShortcodeBuilder();

            // Elementor Classic Editor
            $(window).on('elementor/frontend/init', function () {

                if (typeof elementor === 'undefined') return;

                elementor.hooks.addAction('panel/open_editor/widget', function (panel, model, view) {
                    // Once any widget panel is opened, check for TinyMCE
                    if (window.parent.tinyMCE) {
                        window.parent.tinyMCE.on('AddEditor', function (event) {
                            classicEditor.init(window.parent.tinymce);
                        });
                    }
                });

            });

        },

        ready: () => {

            // Classic Editor Init
            if (integrations.includes('classic-editor') && typeof tinymce !== 'undefined') {
                classicEditor.init();
            }

            // Contact Form 7 Uploader
            if (integrations.includes('cf7')) {
                cf7.init();
            }

            // Update Block Category Icon
            if (integrations.includes('gutenberg-editor')) {
                app.updateBlockCategoryIcon();
            }

            // Handle offer notice dismiss
            $(document).on('click', '.igd-offer-notice .offer-btn-main, .igd-offer-notice .notice-dismiss', app.handleNoticeDismiss);

            // Handle migration
            migration.init();

            // Getting Started
            gettingStarted.init();

        },


        handleNoticeDismiss: function (e) {
            $(this).closest('.igd-admin-notice').slideUp();

            wp.ajax.post('igd_dismiss_offer_notice', {
                nonce: igd.nonce,
            });
        },

        initFileBrowser: () => {
            const AppElement = document.getElementById('igd-app');
            if (AppElement) {
                const root = ReactDOM.createRoot(AppElement);
                root.render(<App/>);
            }
        },

        initShortcodeBuilder: () => {
            const element = document.getElementById('igd-shortcode-builder');
            if (element) {
                const root = ReactDOM.createRoot(element);
                root.render(<ShortcodeBuilder/>);
            }
        },

        updateBlockCategoryIcon: function () {
            const icon = <svg width="18" height="18" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg">
                <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z"
                      fill="#0066da"/>
                <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z"
                      fill="#00ac47"/>
                <path
                    d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z"
                    fill="#ea4335"/>
                <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z"
                      fill="#00832d"/>
                <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z"
                      fill="#2684fc"/>
                <path
                    d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z"
                    fill="#ffba00"/>
            </svg>;

            if (wp.blocks) {
                wp.blocks.updateCategory('igd-category', {icon});
            }
        },

    }

    app.init();
    $(document).on('ready', app.ready);

})(jQuery);

