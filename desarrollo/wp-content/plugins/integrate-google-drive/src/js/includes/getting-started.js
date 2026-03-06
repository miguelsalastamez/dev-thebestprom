const $ = jQuery;

const app = {

    init: () => {
        $(document).on('click', '.igd-getting-started .menu-item', app.toggleGettingStartedTab);

        // Handle Get Started Check List
        $(document).on('click', '.timeline-step-finish .btn-finish', app.getStartedFinish);
        $(document).on('click', '.timeline-content, .timeline-content-toggle', app.handleGetStartedToggle);
        $(document).on('click', '.timeline-content .igd-btn', (e) => e.stopPropagation());
        $(document).on('click', '.timeline-content .igd-btn.activate-license', app.openLicenseModal);

        $(document).on('click', '#all-feature-show', function () {
            $('.features-wrap > .igd-hidden').removeClass('igd-hidden')
            $('#all-feature-show').parent().remove();
        });
    },

    handleGetStartedToggle: function (e) {
        $(this).closest('.timeline-item').toggleClass('active').find('.timeline-body').slideToggle();
    },

    getStartedFinish: function (e) {

        $('#introduction').addClass('setup-complete');

        $('.section-get-started, .heading-get-started').slideUp();

        wp.ajax.post('igd_hide_setup', {nonce: igd.nonce,})

    },

    openLicenseModal: () => {
        $('.fs-modal-license-activation-integrate-google-drive').addClass('active');
    },

    toggleGettingStartedTab: function () {
        const target = $(this).data('target');

        $('.menu-item').removeClass('active');
        $('.getting-started-content').removeClass('active');

        $(this).addClass('active');
        $('#' + target).addClass('active');
    },

}

export default app;