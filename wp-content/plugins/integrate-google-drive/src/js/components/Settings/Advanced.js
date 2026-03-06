const {
    FormToggle,
    ButtonGroup,
    Button
} = wp.components;

export default function Advanced({data, setData}) {

    const {
        mediaPreview = 'embed',
        rememberLastFolder = true,
        deleteData,
    } = data;

    return (
        <div className="igd-settings-body">

            <h3 className="igd-settings-body-title">{wp.i18n.__('Advanced Settings', 'integrate-google-drive')}</h3>

            {/* Media Preview Mode */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Media Preview Mode", "integrate-google-drive")}</h4>

                <div className="settings-field-content">
                    <ButtonGroup className="igd-button-group">
                        <Button
                            size={"default"}
                            variant={mediaPreview === 'direct' ? 'primary' : 'secondary'}
                            isPrimary={mediaPreview === 'direct'}
                            isSecondary={mediaPreview !== 'direct'}
                            onClick={() => setData({
                                ...data,
                                mediaPreview: 'direct'
                            })}
                        >
                            {wp.i18n.__('Direct Media', 'integrate-google-drive')}
                        </Button>

                        <Button
                            size={"default"}
                            variant={mediaPreview === 'embed' ? 'primary' : 'secondary'}
                            isPrimary={mediaPreview === 'embed'}
                            isSecondary={mediaPreview !== 'embed'}
                            onClick={() => setData({
                                ...data,
                                mediaPreview: 'embed'
                            })}
                        >
                            {wp.i18n.__('Google Drive Embed', 'integrate-google-drive')}
                        </Button>
                    </ButtonGroup>

                    <p className="description">{wp.i18n.__("Choose how images, audio and video files are displayed: direct media (browser-native) or Google Drive's native embed viewer.", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Remember Last Open Folder */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Remember Last Folder", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <FormToggle
                        checked={rememberLastFolder}
                        onChange={() => setData({...data, rememberLastFolder: !rememberLastFolder})}
                    />

                    <p className="description">{wp.i18n.__('Automatically load the last viewed folder when revisiting the module.', 'integrate-google-drive')}</p>
                </div>
            </div>


            {/* Delete Data on Uninstall */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Delete Data on Uninstall", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <FormToggle
                        checked={deleteData}
                        onChange={() => setData({...data, deleteData: !deleteData})}
                    />
                    <p className="description">{wp.i18n.__("Delete the plugin data (settings, cache, accounts) on the uninstallation.", "integrate-google-drive")}</p>
                </div>
            </div>

        </div>
    )
}