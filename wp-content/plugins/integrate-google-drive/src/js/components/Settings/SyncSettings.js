import {showProModal} from "../../includes/ProModal";
import {Tooltip} from "react-tooltip";

const {SelectControl, FormToggle, ButtonGroup, Button} = wp.components;
const {ModuleBuilderModal} = window;

export default function SyncSettings({data, setData, saveSettings}) {

    const isPro = !!igd.isPro;

    const {
        autoSync,
        syncInterval = '3600',
        customSyncInterval,
        syncType = 'all',
        syncFolders = []
    } = data;

    return (
        <div className="igd-settings-body">

            <h3 className="igd-settings-body-title">
                {wp.i18n.__('Auto Synchronization', 'integrate-google-drive')}

                <a href="https://softlabbd.com/docs/how-to-enable-auto-synchronization/" target="_blank"
                   className="igd-btn btn-outline-info">
                    <i className="dashicons dashicons-editor-help"></i>

                    {wp.i18n.__('Documentation', 'integrate-google-drive')}
                </a>

            </h3>

            {/* Enable Synchronization */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Enable Auto Synchronization", "integrate-google-drive")} </h4>

                <div className="settings-field-content">
                    <FormToggle
                        data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                        data-tooltip-id={`enable-auto-sync-tooltip`}
                        checked={autoSync && isPro}
                        className={!isPro ? 'disabled' : ''}
                        onChange={() => {
                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to enable auto synchronization.', 'integrate-google-drive'));

                                return;
                            }
                            setData({...data, autoSync: !autoSync})
                        }}
                    />

                    {!isPro &&
                        <Tooltip
                            id={`enable-auto-sync-tooltip`}
                            effect="solid"
                            place="right"
                            variant={'warning'}
                            className={"igd-tooltip"}
                        />
                    }

                    <p className="description">
                        {wp.i18n.__("Enable/ disable the local cache file auto synchronization with the cloud files.", "integrate-google-drive")}
                    </p>

                    <div className="igd-notice igd-notice-info">
                        <div className="igd-notice-content">
                            <h5>
                                {wp.i18n.__("To refresh the module files automatically, append", "integrate-google-drive")}{" "}
                                <code>?module_refresh=1</code>{" "}
                                {wp.i18n.__("to your page URL.", "integrate-google-drive")}
                            </h5>
                        </div>
                    </div>

                </div>
            </div>

            {(autoSync || !isPro) &&
                <>
                    {/* Sync Interval */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Synchronization Interval", "integrate-google-drive")} </h4>
                        <div className="settings-field-content">
                            <SelectControl
                                data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                data-tooltip-id={`sync-interval-tooltip`}
                                value={syncInterval}
                                disabled={!isPro}
                                options={[
                                    {label: '1 Hour', value: 3600},
                                    {label: '6 Hours', value: 21600},
                                    {label: '24 Hours', value: 86400},
                                    {label: '2 Days', value: 172800},
                                    {label: '3 Days', value: 259200},
                                    {label: '7 Days', value: 604800},
                                    {label: 'Custom', value: 'custom'},
                                ]}
                                onChange={syncInterval => {
                                    if (!isPro) {
                                        showProModal(wp.i18n.__('Upgrade to PRO to enable auto synchronization.', 'integrate-google-drive'));

                                        return;
                                    }
                                    setData({...data, syncInterval})
                                }}
                            />
                            {!isPro &&
                                <Tooltip
                                    id={`sync-interval-tooltip`}
                                    effect="solid"
                                    place="right"
                                    variant={'warning'}
                                    className={"igd-tooltip"}
                                />
                            }

                            <p className="description">{wp.i18n.__("Select the automatic cloud files synchronization interval.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                    {/* Custom Sync Interval */}
                    {'custom' === syncInterval &&
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Custom Sync Interval", "integrate-google-drive")} </h4>
                            <div className="settings-field-content">
                                <input
                                    type="number"
                                    value={customSyncInterval}
                                    onChange={e => setData({...data, customSyncInterval: e.target.value})}
                                    min={60}
                                />

                                <p className="description">{wp.i18n.__("Enter the custom synchronization interval in seconds (min: 60 seconds).", "integrate-google-drive")}</p>
                                <p className="description">{wp.i18n.__("e.g: 3600 = 1 hour, 1800 = 30 minutes.", "integrate-google-drive")}</p>
                            </div>
                        </div>
                    }

                    {/* Sync Type */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Synchronization Type", "integrate-google-drive")} </h4>
                        <div className="settings-field-content">
                            <ButtonGroup
                                data-tooltip-content={wp.i18n.__('PRO Feature', 'integrate-google-drive')}
                                data-tooltip-id={`sync-type-tooltip`}
                            >
                                <Button
                                    variant={'all' === syncType ? 'primary' : 'secondary'}
                                    className={!isPro ? 'pro-feature disabled' : ''}
                                    onClick={() => {

                                        if (!igd.isPro) {
                                            showProModal(wp.i18n.__('Upgrade to PRO to enable auto synchronization.', 'integrate-google-drive'));

                                            return;
                                        }

                                        setData({
                                            ...data,
                                            syncType: 'all'
                                        })
                                    }}
                                >
                                    <span>{wp.i18n.__("All Folders", "integrate-google-drive")}</span>
                                </Button>

                                <Button
                                    variant={'selected' === syncType ? 'primary' : 'secondary'}
                                    className={!isPro ? 'pro-feature disabled' : ''}
                                    onClick={() => {

                                        if (!igd.isPro) {
                                            showProModal(wp.i18n.__('Upgrade to PRO to enable auto synchronization.', 'integrate-google-drive'));

                                            return;
                                        }

                                        setData({...data, syncType: 'selected'})
                                    }}
                                >
                                    <span>{wp.i18n.__("Specific Selected Folders", "integrate-google-drive")}</span>
                                </Button>

                            </ButtonGroup>

                            {!isPro &&
                                <Tooltip
                                    anchorSelect={`.pro-feature`}
                                    effect="solid"
                                    place="right"
                                    variant={'warning'}
                                    className={"igd-tooltip"}
                                />
                            }

                            <p className="description">{wp.i18n.__("Select the synchronization type.", "integrate-google-drive")}</p>

                            <div className="igd-notice igd-notice-info">
                                <div className="igd-notice-content">
                                    <p>
                                        <strong>{wp.i18n.__('All Folders', 'integrate-google-drive')}</strong> → {wp.i18n.__("All the cached folders will be synchronized with the cloud.", "integrate-google-drive")}
                                    </p>
                                    <p>
                                        <strong>{wp.i18n.__('Specific Folders', 'integrate-google-drive')}</strong> → {wp.i18n.__("Syncs only chosen folders with the cloud, helpful for large sets of folders when only a few need synchronization.", "integrate-google-drive")}
                                    </p>
                                </div>
                            </div>

                            {'selected' === syncType &&
                                <div className="settings-field-sub">

                                    {/* Sync Folders */}
                                    <div className="settings-field sync-folders">
                                        <h4 className="settings-field-label">{wp.i18n.__("Select Folders", "integrate-google-drive")} </h4>

                                        <div className="settings-field-content">

                                            <div className="template-folder-wrap">

                                                {!!syncFolders.length &&
                                                    syncFolders.map((folder, index) => {
                                                        const {id, name, iconLink} = folder;
                                                        return (

                                                            <div className="template-folder">
                                                                <div className="template-folder-item">

                                                                    {!!iconLink ? <img src={iconLink}/> :
                                                                        <i className="dashicons dashicons-category"></i>}

                                                                    <span
                                                                        className="template-folder-name">{name}</span>

                                                                    <div className="dashicons dashicons-no-alt"
                                                                         onClick={() => {
                                                                             const newFolders = syncFolders.filter(folder => folder.id !== id);
                                                                             setData({
                                                                                 ...data,
                                                                                 syncFolders: newFolders
                                                                             })
                                                                         }}
                                                                    ></div>
                                                                </div>

                                                            </div>)
                                                    })
                                                }

                                                <button
                                                    data-tooltip-content="PRO Feature"
                                                    data-tooltip-id={`select-folders-tooltip`}
                                                    className="igd-btn btn-info"
                                                    onClick={() => {

                                                        if (!igd.isPro) {
                                                            showProModal(wp.i18n.__('Upgrade to PRO to enable auto synchronization.', 'integrate-google-drive'));

                                                            return;
                                                        }

                                                        Swal.fire({
                                                            html: `<div id="igd-select-files" class="igd-module-builder-modal-wrap"></div>`,
                                                            showConfirmButton: false,
                                                            customClass: {
                                                                container: 'igd-module-builder-modal-container'
                                                            },
                                                            didOpen(popup) {
                                                                const element = document.getElementById('igd-select-files');

                                                                ReactDOM.render(
                                                                    <ModuleBuilderModal
                                                                        initData={{folders: syncFolders}}
                                                                        onUpdate={(res) => {

                                                                            const {folders = []} = res;

                                                                            const settingsData = {
                                                                                ...data,
                                                                                syncFolders: folders.map(folder => ({
                                                                                    id: folder.id,
                                                                                    name: folder.name,
                                                                                    iconLink: folder.iconLink,
                                                                                    accountId: folder.accountId,
                                                                                })),
                                                                            };

                                                                            setData(settingsData);
                                                                            saveSettings(settingsData);
                                                                            Swal.close();
                                                                        }}
                                                                        onClose={() => Swal.close()}
                                                                        isSelectFiles
                                                                        selectionType="folders"
                                                                    />,
                                                                    element
                                                                );

                                                            },

                                                            willClose(popup) {
                                                                const element = document.getElementById('igd-select-files');
                                                                ReactDOM.unmountComponentAtNode(element);
                                                            }

                                                        });

                                                    }}
                                                >
                                                    <i className="dashicons dashicons-open-folder"></i>
                                                    <span>{!!syncFolders.length ? wp.i18n.__('Update Folders', 'integrate-google-drive') : wp.i18n.__('Select Folders', 'integrate-google-drive')}</span>
                                                </button>

                                                {!isPro &&
                                                    <Tooltip
                                                        id={`select-folders-tooltip`}
                                                        effect="solid"
                                                        place="right"
                                                        variant={'warning'}
                                                        className={"igd-tooltip"}
                                                    />
                                                }

                                            </div>

                                            <p className="description">{wp.i18n.__("Select the folders to be synchronized with the cloud files.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>
                                </div>
                            }
                        </div>
                    </div>
                </>

            }

        </div>
    )
}