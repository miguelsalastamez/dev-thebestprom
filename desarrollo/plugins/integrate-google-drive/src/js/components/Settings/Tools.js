const {SelectControl, FormToggle} = wp.components;
const {useState} = React;

export default function Tools({data, setData, saveSettings}) {

    const {
        exportData = 'all',
        autoSave = false,
        shouldMigrate,
    } = data;

    const [isLoading, setIsLoading] = useState(false);

    const clearCache = () => {

        Swal.fire({
            title: wp.i18n.__('Are you sure?', 'integrate-google-drive'),
            text: wp.i18n.__('Clear all the cached files!', 'integrate-google-drive'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: wp.i18n.__('Clear cache', 'integrate-google-drive'),
            cancelButtonText: wp.i18n.__('Cancel', 'integrate-google-drive'),
            reverseButtons: true,
            showLoaderOnConfirm: true,
            customClass: {container: 'igd-swal'},
            preConfirm: () => {

                return new Promise((resolve, reject) => {
                    wp.ajax.post('igd_clear_cache', {nonce: igd.nonce})
                        .done(() => resolve());
                });

            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: wp.i18n.__('Cache Cleared!', 'integrate-google-drive'),
                    text: wp.i18n.__('Cached files are deleted and synchronized with the cloud files.', 'integrate-google-drive'),
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                    toast: true,
                });
            }
        });

    }

    const getExportData = () => {
        setIsLoading('export');

        wp.ajax.post('igd_get_export_data', {
            type: exportData,
            nonce: igd.nonce,
        })
            .done(response => {
                const json = JSON.stringify(response);
                const blob = new Blob([json], {type: 'application/json'});
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');

                link.href = url;
                link.download = `integrate-google-drive-export-${exportData.replace('_', '-')}-${new Date().toISOString().slice(0, 10)}.json`;
                link.click();

                URL.revokeObjectURL(url);
            })
            .fail((error) => console.log(error))
            .always(() => setIsLoading(false));
    }

    const importData = async () => {
        try {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'application/json';

            input.onchange = async () => {
                const file = input.files[0];
                const reader = new FileReader();

                reader.readAsText(file);
                reader.onload = async () => {
                    const json = JSON.parse(reader.result);
                    const chunkSize = 1000; // Adjust the chunk size as needed
                    const chunks = [];

                    // Convert the JSON object into an array of chunks
                    let currentChunk = {};
                    let count = 0;
                    for (const key in json) {
                        if (count < chunkSize) {
                            currentChunk[key] = json[key];
                            count++;
                        } else {
                            chunks.push(currentChunk);
                            currentChunk = {};
                            currentChunk[key] = json[key];
                            count = 1;
                        }
                    }
                    if (Object.keys(currentChunk).length > 0) {
                        chunks.push(currentChunk);
                    }

                    setIsLoading('import');

                    // Send each chunk to the server separately
                    for (const chunk of chunks) {
                        await wp.ajax.post('igd_import_data', {
                            data: JSON.stringify(chunk),
                            nonce: igd.nonce,
                        })
                            .done(response => {
                                if (response.settings) {
                                    setData(response.settings);
                                }
                            })
                            .fail(error => {
                                throw new Error(error);
                            });
                    }

                    setIsLoading(false);

                    // Show success message or perform any other actions
                    Swal.fire({
                        title: wp.i18n.__('Imported!', 'integrate-google-drive'),
                        text: wp.i18n.__('Data imported successfully.', 'integrate-google-drive'),
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                        toast: true,
                    });
                }
            }

            input.click();
        } catch (error) {
            console.error('Error importing data:', error);
            setIsLoading(false);

            Swal.fire({
                title: wp.i18n.__('Error!', 'integrate-google-drive'),
                text: wp.i18n.__('An error occurred while importing data.', 'integrate-google-drive'),
                icon: 'error',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                toast: true,
            });

        }
    }

    const startMigration = function () {
        const $ = jQuery;

        const $progress = $('.migration-progress .progress-message');

        $progress.text(wp.i18n.__('Starting migration...', 'integrate-google-drive'));

        function doBatch() {
            wp.ajax.post('igd_run_151_migration_batch', {
                nonce: igd.nonce,
            }).done(function (resp) {
                $progress.text(resp.message);

                if (!resp.completed) {

                    // Update state
                    if (resp.step) {
                        $('.migration-status .migration-step span').text(resp.step);
                    }

                    $('.migration-status .migration-offset span').text(resp.offset);

                    setTimeout(doBatch, 300);
                } else {
                    $('.migration-status').hide();
                    $('.migration-progress .igd-spinner').hide();

                    $progress.addClass('completed').text(wp.i18n.__('Migration complete!', 'integrate-google-drive'));

                    setTimeout(function () {
                        Swal.fire({
                            title: wp.i18n.__('Migration Complete!', 'integrate-google-drive'),
                            text: wp.i18n.__('All shortcode modules have been successfully migrated to the new format.', 'integrate-google-drive'),
                            icon: 'success',
                            showConfirmButton: true,
                            customClass: {container: 'igd-swal'},
                        }).then((result) => {
                            if (result.isConfirmed) {
                                wp.ajax.post('igd_clear_setting_migration', {nonce: igd.nonce});
                            }
                        });
                    }, 5000);

                }

            }).fail(function () {
                $progress.text(wp.i18n.__('Migration failed due to a network or server error.', 'integrate-google-drive'));
            });
        }

        doBatch();
    }

    const restoreModules = () => {
        Swal.fire({
            title: wp.i18n.__('Are you sure?', 'integrate-google-drive'),
            text: wp.i18n.__('This will restore old shortcode modules to the new format.', 'integrate-google-drive'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: wp.i18n.__('Restore', 'integrate-google-drive'),
            cancelButtonText: wp.i18n.__('Cancel', 'integrate-google-drive'),
            reverseButtons: true,
            showLoaderOnConfirm: true,
            customClass: {container: 'igd-swal'},
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    html: `
                    <div class="notice-text">
                        <h3>${wp.i18n.__('Migration in Progress', 'integrate-google-drive')}</h3>
                        <p>${wp.i18n.__('This process may take a while depending on the number of posts and pages. Please do not close this window until the process is complete.', 'integrate-google-drive')}</p>
                    
                        <div class="migration-status">
                            <p class="migration-step">${wp.i18n.__('Current Step: ', 'integrate-google-drive')} <span>N/A</span> </p>
                            <p class="migration-offset">${wp.i18n.__('Processed Offset: ', 'integrate-google-drive')} <span>0</span></p>
                        </div>
                        
                        <div class="migration-progress">
                            <span class="igd-spinner"></span>
                            <p class="progress-message">${wp.i18n.__('Checking migration status...', 'integrate-google-drive')}</p>
                        </div>
                        
                    </div>
                    `,
                    showCloseButton: true,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    allowEscapeKey: false,
                    customClass: {container: 'igd-swal migration-swal'},
                    didOpen: () => {
                        startMigration();
                    }
                });
            }
        });
    }

    return (
        <div className="igd-settings-body">

            <h3 className="igd-settings-body-title">{wp.i18n.__('Tools', 'integrate-google-drive')}</h3>

            {/* Auto-save Settings */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Auto-save Settings", "integrate-google-drive")}</h4>

                <div className={`settings-field-content`}>

                    <FormToggle
                        checked={autoSave}
                        onChange={() => setData({...data, autoSave: !autoSave})}
                    />

                    <p className="description">{wp.i18n.__("Enable to automatically save the settings after each change.", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Reset Cache */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Clear Cached Files", "integrate-google-drive")}</h4>
                <div className="settings-field-content">

                    <button className="igd-btn btn-warning" type={'button'} onClick={clearCache}>
                        <i className="dashicons dashicons-update"></i>
                        <span>{wp.i18n.__("Clear cache", "integrate-google-drive")}</span>
                    </button>

                    <p className="description">{wp.i18n.__("Clear cached files and synchronize the cloud files.", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Export Data */}
            <div className="settings-field">
                <h4 className={`settings-field-label`}>{wp.i18n.__("Export Data", "integrate-google-drive")}</h4>
                <div className="settings-field-content">

                    <SelectControl
                        value={exportData}
                        options={[
                            {label: wp.i18n.__("Settings", "integrate-google-drive"), value: 'settings'},
                            {
                                label: wp.i18n.__("Modules", "integrate-google-drive"),
                                value: 'shortcodes'
                            },
                            {
                                label: wp.i18n.__("User Private Files", "integrate-google-drive"),
                                value: 'user_files'
                            },
                            {label: wp.i18n.__("Statistics Logs", "integrate-google-drive"), value: 'events'},
                            {
                                label: wp.i18n.__("Export All (Settings, Modules, User Private Files, Statistics Logs)", "integrate-google-drive"),
                                value: 'all'
                            },
                        ]}
                        onChange={exportData => {
                            setData({...data, exportData})
                        }}
                    />
                    <p className="description">{wp.i18n.__("Select the data you want to export.", "integrate-google-drive")}</p>
                    <br/>

                    <button type={`button`} className={`igd-btn btn-info`} onClick={getExportData}>

                        {'export' === isLoading ? <div className="igd-spinner"></div> :
                            <i className={`dashicons dashicons-download`}></i>}

                        <span>{'export' === isLoading ? wp.i18n.__("Exporting...", "integrate-google-drive") : wp.i18n.__("Export", "integrate-google-drive")}</span>
                    </button>

                    <p className="description">{wp.i18n.__("Export the selected data to a JSON file.", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Import Settings */}
            <div className="settings-field">
                <h4 className={`settings-field-label`}>{wp.i18n.__("Import Data", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <button type={`button`} className={`igd-btn btn-info`} onClick={importData}>
                        {'import' === isLoading ? <div className="igd-spinner"></div> :
                            <i className={`dashicons dashicons-upload`}></i>}
                        <span>{'import' === isLoading ? wp.i18n.__("Importing...", "integrate-google-drive") : wp.i18n.__("Import", "integrate-google-drive")}</span>
                    </button>

                    <p className="description">{wp.i18n.__("Select the exported JSON file you would like to import. Please note that the import will replace the current data", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Restore Shortcode Modules */}
            {shouldMigrate &&
                <div className="settings-field">
                    <h4 className="settings-field-label">{wp.i18n.__("Migrate Shortcode Modules", "integrate-google-drive")}</h4>
                    <div className="settings-field-content">

                        <button className="igd-btn btn-info" type={'button'} onClick={restoreModules}>
                            <i className="dashicons dashicons-image-rotate"></i>
                            <span>{wp.i18n.__("Restore Modules", "integrate-google-drive")}</span>
                        </button>

                        <p className="description">{wp.i18n.__("The module builder was updated in version 1.5.1. If you upgraded from an earlier version, your shortcode modules in pages or posts should migrate automatically to the new format. If they don’t, you can manually restore them by clicking the button below.", "integrate-google-drive")}</p>
                    </div>
                </div>
            }

            {/*  Reset Settings */}
            <div className="settings-field">
                <h4 className={`settings-field-label`}>{wp.i18n.__("Reset Settings", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <button type={`button`} className={`igd-btn btn-danger`} onClick={() => {
                        Swal.fire({
                            title: wp.i18n.__('Are you sure?', 'integrate-google-drive'),
                            text: wp.i18n.__('We recommend you to export your current settings before resetting them.', 'integrate-google-drive'),
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: wp.i18n.__('Reset', 'integrate-google-drive'),
                            reverseButtons: true,
                            showLoaderOnConfirm: true,
                            customClass: {container: 'igd-swal'},
                        }).then((result) => {
                            if (result.value) {
                                setData({});
                                saveSettings({});
                            }
                        });
                    }}>
                        <i className={`dashicons dashicons-update`}></i>
                        <span>{wp.i18n.__("Reset", "integrate-google-drive")}</span>
                    </button>

                    <p className="description">{wp.i18n.__("Reset all settings to default.", "integrate-google-drive")}</p>
                </div>
            </div>


        </div>
    )
}