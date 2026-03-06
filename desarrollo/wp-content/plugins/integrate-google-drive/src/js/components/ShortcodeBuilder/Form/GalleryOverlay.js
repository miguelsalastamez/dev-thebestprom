const {FormToggle, Button, ButtonGroup} = wp.components;

export default function GalleryOverlay({editData, setEditData, isSlider = false}) {

    const {
        galleryOverlay = true,
        overlayDisplayType = 'hover',
        galleryOverlayTitle = true,
        galleryOverlayDescription = true,
        galleryOverlaySize,
    } = editData;

    return (
        <div className="settings-field">

            <h4 className="settings-field-label">{wp.i18n.__("Show Overlay", "integrate-google-drive")}</h4>

            <div className="settings-field-content">
                <FormToggle
                    checked={galleryOverlay}
                    onChange={() => setEditData({
                        ...editData,
                        galleryOverlay: !galleryOverlay
                    })}
                />

                <p className="description">{wp.i18n.__("Show the image overlay.", "integrate-google-drive")}</p>

                {!!galleryOverlay &&
                    <div className="settings-field-sub">

                        {/* Display Type */}
                        <div className="settings-field">

                            <h4 className="settings-field-label">{wp.i18n.__("Display Type", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">

                                <ButtonGroup>
                                    <Button isPrimary={'always' === overlayDisplayType}
                                            isSecondary={'hover' === overlayDisplayType}
                                            onClick={() => setEditData({
                                                ...editData,
                                                overlayDisplayType: 'always'
                                            })}
                                    >
                                        <span>{wp.i18n.__("Always", "integrate-google-drive")}</span>
                                    </Button>

                                    <Button isPrimary={'hover' === overlayDisplayType}
                                            isSecondary={'always' === overlayDisplayType}
                                            onClick={() => setEditData({
                                                ...editData,
                                                overlayDisplayType: 'hover'
                                            })}
                                    >
                                        <span>{wp.i18n.__("On Hover", "integrate-google-drive")}</span>
                                    </Button>
                                </ButtonGroup>

                                <p className="description">{wp.i18n.__("Select the image overlay display type.", "integrate-google-drive")}</p>
                            </div>
                        </div>

                        {/* Show Title */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Show Title", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">
                                <FormToggle
                                    checked={galleryOverlayTitle}
                                    onChange={() => setEditData({
                                        ...editData,
                                        galleryOverlayTitle: !galleryOverlayTitle
                                    })}
                                />

                                <p className="description">{wp.i18n.__("Show the image title in the overlay.", "integrate-google-drive")}</p>
                            </div>
                        </div>

                        {/* Description */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Show Description", "integrate-google-drive")}</h4>

                            <FormToggle
                                checked={galleryOverlayDescription}
                                onChange={() => setEditData({
                                    ...editData,
                                    galleryOverlayDescription: !galleryOverlayDescription
                                })}
                            />

                            <p className="description">{wp.i18n.__("Show the image description in the overlay.", "integrate-google-drive")}</p>
                        </div>

                        {/* Size */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Show Size", "integrate-google-drive")}</h4>

                            <FormToggle
                                checked={galleryOverlaySize}
                                onChange={() => setEditData({
                                    ...editData,
                                    galleryOverlaySize: !galleryOverlaySize
                                })}
                            />

                            <p className="description">{wp.i18n.__("Show the image size in the overlay.", "integrate-google-drive")}</p>
                        </div>
                    </div>
                }

            </div>
        </div>
    );
}