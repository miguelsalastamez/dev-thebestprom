const $ = jQuery;

const migration = {
    running: false,
    interval: null,

    init: function () {

        this.checkStatus();

        $(document).on('click', '#igd-start-migration', this.startMigration);

    },

    checkStatus: function () {
        const $progress = $('#igd-migration-progress .progress-message');

        if (!$('#igd-migration-progress').length) {
            return;
        }

        const $spinner = $('#igd-migration-progress img');
        const $btn = $('#igd-start-migration');
        const $notice = $('.igd-migration-notice');

        wp.ajax.post('igd_get_migration_status', {
            nonce: igd.nonce,
        }).done(function (resp) {
            $progress.text(resp.message);

            // Update step + offset in the notice
            $('.migration-status').html(`
                <p>
                    ${wp.i18n.__('Current Step:', 'integrate-google-drive')} <code>${resp.step || 'N/A'}</code><br>
                    ${wp.i18n.__('Processed Offset:', 'integrate-google-drive')} ${resp.offset || 0}
                </p>
            `);

            if (resp.status === 'running') {
                $spinner.show();
                $btn.parent().hide();
                $notice.removeClass('notice-warning notice-success').addClass('notice-info');
                migration.running = true;

                if (!migration.interval) {
                    migration.interval = setInterval(migration.checkStatus, 2000);
                }

            } else if (resp.status === 'completed') {

                $spinner.hide();
                $btn.parent().hide();
                $progress.addClass('completed').text(wp.i18n.__('Migration complete!', 'integrate-google-drive'));
                $notice.removeClass('notice-warning notice-info').addClass('notice-success');

                if (migration.interval) {
                    clearInterval(migration.interval);
                    migration.interval = null;
                }

                setTimeout(function () {
                    $('.igd-migration-notice').slideUp();
                }, 5000);
            }

        }).fail(function (e) {
            console.log(e);

            $spinner.hide();
            $notice.removeClass('notice-success notice-info').addClass('notice-warning');

            if (migration.interval) {
                clearInterval(migration.interval);
                migration.interval = null;
            }

            console.error('Failed to fetch migration status.');
        });
    },

    startMigration: function () {
        if (migration.running) {
            alert(wp.i18n.__('Migration is already running. Please wait for it to complete.', 'integrate-google-drive'));
            return;
        }

        migration.running = true;

        const $btn = $('#igd-start-migration');
        const $progress = $('#igd-migration-progress .progress-message');
        const $spinner = $('#igd-migration-progress img');
        const $notice = $('.igd-migration-notice');

        $btn.prop('disabled', true).text(wp.i18n.__('Updating...', 'integrate-google-drive'));
        $progress.text(wp.i18n.__('Starting migration...', 'integrate-google-drive'));
        $spinner.show();
        $notice.removeClass('notice-success notice-warning').addClass('notice-info');

        function doBatch() {
            wp.ajax.post('igd_run_151_migration_batch', {
                nonce: igd.nonce,
            }).done(function (resp) {
                $progress.text(resp.message);

                // Update state
                $('.migration-status').html(`
                    <p>
                        ${wp.i18n.__('Current Step:', 'integrate-google-drive')} <code>${resp.step || 'N/A'}</code><br>
                        ${wp.i18n.__('Processed Offset:', 'integrate-google-drive')} ${resp.offset || 0}
                    </p>
                `);

                if (!resp.completed) {
                    setTimeout(doBatch, 300);
                } else {
                    $spinner.hide();
                    $btn.parent().hide();
                    $progress.addClass('completed').text(wp.i18n.__('Migration complete!', 'integrate-google-drive'));
                    $notice.removeClass('notice-warning notice-info').addClass('notice-success');

                    if (migration.interval) {
                        clearInterval(migration.interval);
                        migration.interval = null;
                    }

                    setTimeout(function () {
                        $('.igd-migration-notice').slideUp();
                    }, 5000);
                }

            }).fail(function () {
                $spinner.hide();
                $btn.prop('disabled', false).text(wp.i18n.__('Update Database', 'integrate-google-drive'));
                $progress.text(wp.i18n.__('Migration failed due to a network or server error.', 'integrate-google-drive'));
                migration.running = false;
            });
        }

        doBatch();
    }
};

export default migration;
