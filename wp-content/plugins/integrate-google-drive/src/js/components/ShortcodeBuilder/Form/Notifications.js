import {Tooltip} from "react-tooltip";
import ShortcodeBuilderContext from "../../../contexts/ShortcodeBuilderContext";
import {showProModal} from "../../../includes/ProModal";
import NamingTemplate from "./Fields/NamingTemplate";

const {useContext} = React;

const {FormToggle} = wp.components;

const {isPro} = igd;

export default function Notifications() {
    const context = useContext(ShortcodeBuilderContext);
    const {editData, setEditData} = context;

    const {
        type,
        enableNotification = 'review' === type,
        proofNotification = true,
        downloadNotification = true,
        uploadNotification = true,
        deleteNotification = true,
        playNotification = true,
        searchNotification = 'search' === type,
        viewNotification = true,
        notificationEmail = '%admin_email%',
        skipCurrentUserNotification = true,
    } = editData;

    const isBrowser = 'browser' === type;
    const isReview = 'review' === type;
    const isUploader = 'uploader' === type;
    const isMedia = 'media' === type;
    const isGallery = 'gallery' === type;
    const isSlider = 'slider' === type;
    const isSearch = 'search' === type;
    const isListModule = 'list' === type;

    return (
        <div className="shortcode-module-body">

            {/*--- Enable Notifications ---*/}
            <div className="settings-field">

                <h4 className="settings-field-label">{wp.i18n.__("Enable Notifications", "integrate-google-drive")}</h4>

                <div className="settings-field-content">
                    <FormToggle
                        checked={isPro && enableNotification}
                        className={!isPro ? 'pro-feature disabled' : ''}
                        onChange={() => {
                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to enable email notificationenableN', 'integrate-google-drive'));

                                return;
                            }

                            setEditData(editData => ({...editData, enableNotification: !enableNotification}))

                        }}
                    />

                    {!isPro &&
                        <Tooltip
                            anchorSelect={`.pro-feature`}
                            effect="solid"
                            place="right"
                            variant={"warning"}
                            className="igd-tooltip"
                            content={wp.i18n.__('PRO feature', 'integrate-google-drive')}
                        />
                    }

                    <p className="description">{wp.i18n.__("Enable email notifications to get notified on various user activities (upload, download, delete, etc).", "integrate-google-drive")}</p>

                    {(enableNotification || !isPro) &&
                        <div className="settings-field-sub">

                            {/* Proof Submission Notifications */}
                            {(isReview || isGallery) &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Proof Submission Notifications", "integrate-google-drive")} </h4>
                                    <div className="settings-field-content">
                                        <FormToggle
                                            checked={proofNotification && isPro}
                                            className={!isPro ? 'pro-feature disabled' : ''}
                                            onChange={() => {
                                                if (!isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable proof submission notifications.', 'integrate-google-drive'));

                                                    return;
                                                }

                                                setEditData({...editData, proofNotification: !proofNotification});
                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Receive email notification whenever a user selects and submits a proof for review.", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                            {/* Download Notification */}
                            {(isBrowser || isGallery || isSearch || isMedia || isSlider || isListModule) &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Download Notifications", "integrate-google-drive")} </h4>
                                    <div className="settings-field-content">
                                        <FormToggle
                                            data-tooltip-content="PRO Feature"
                                            data-tooltip-id={"download-notification"}
                                            checked={downloadNotification && isPro}
                                            className={!isPro ? 'pro-feature disabled' : ''}
                                            onChange={() => {
                                                if (!igd.isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable email notifications.', 'integrate-google-drive'));

                                                    return;
                                                }
                                                setEditData({...editData, downloadNotification: !downloadNotification})
                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Receive email notification whenever files is downloaded through this module.", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                            {/* Upload Notification */}
                            {(isBrowser || isUploader) &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Upload Notifications", "integrate-google-drive")} </h4>
                                    <div className="settings-field-content">

                                        <FormToggle
                                            checked={uploadNotification && isPro}
                                            className={!isPro ? 'pro-feature disabled' : ''}
                                            onChange={() => {
                                                if (!isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable email notification.', 'integrate-google-drive'));

                                                    return;
                                                }

                                                setEditData({...editData, uploadNotification: !uploadNotification})
                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Receive an email notifications whenever someone uploaded files through this module.", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                            {/* Delete Notification */}
                            {(isBrowser) &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Delete Notifications", "integrate-google-drive")} </h4>
                                    <div className="settings-field-content">
                                        <FormToggle
                                            checked={deleteNotification && isPro}
                                            className={!isPro ? 'pro-feature disabled' : ''}
                                            onChange={() => {
                                                if (!igd.isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable email notifications.', 'integrate-google-drive'));

                                                    return;
                                                }
                                                setEditData({...editData, deleteNotification: !deleteNotification})
                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Receive email notifications whenever someone deleted files through this module.", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                            {/* Play Notification */}
                            {(isMedia) &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Media Play Notifications", "integrate-google-drive")} </h4>
                                    <div className="settings-field-content">
                                        <FormToggle
                                            data-tooltip-content="PRO Feature"
                                            checked={playNotification && isPro}
                                            className={!isPro ? 'pro-feature disabled' : ''}
                                            onChange={() => {
                                                if (!isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable email notifications.', 'integrate-google-drive'));

                                                    return;
                                                }
                                                setEditData({...editData, playNotification: !playNotification})
                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Receive email notifications whenever someone play audio/video files through this module.", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                            {/* Search Notification */}
                            {(isBrowser || isGallery || isSearch || isMedia) &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Search Notifications", "integrate-google-drive")} </h4>
                                    <div className="settings-field-content">
                                        <FormToggle
                                            checked={searchNotification && isPro}
                                            className={!isPro ? 'pro-feature disabled' : ''}
                                            onChange={() => {
                                                if (!igd.isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable email notifications.', 'integrate-google-drive'));

                                                    return;
                                                }
                                                setEditData({...editData, searchNotification: !searchNotification})
                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Receive email notifications whenever someone search for files through this module.", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                            {/* View Notification */}
                            {isListModule &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("View Notifications", "integrate-google-drive")} </h4>
                                    <div className="settings-field-content">
                                        <FormToggle
                                            data-tooltip-content="PRO Feature"
                                            data-tooltip-id={"view-notification"}
                                            checked={viewNotification && isPro}
                                            className={!isPro ? 'pro-feature disabled' : ''}
                                            onChange={() => {
                                                if (!isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable email notifications.', 'integrate-google-drive'));

                                                    return;
                                                }
                                                setEditData({...editData, viewNotification: !viewNotification})
                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Receive email notifications whenever someone view files through this module.", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                        </div>
                    }

                </div>
            </div>

            {/*--- Notification Recipients ---*/}
            {(!!enableNotification || !isPro) &&
                <div className="settings-field">
                    <div className="settings-field-label">{wp.i18n.__("Notifications Recipients", "integrate-google-drive")}</div>

                    <div className="settings-field-content">
                        <div className="settings-field-sub">

                            {/*--- Notification Email Recipients ---*/}
                            <NamingTemplate
                                value={notificationEmail}
                                onUpdate={(notificationEmail) => setEditData(editData => ({
                                    ...editData,
                                    notificationEmail
                                }))}
                                type={'notifications'}
                            />

                            {/*-- Skip current user notification ---*/}
                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("Skip current user notification", "integrate-google-drive")} </h4>
                                <div className="settings-field-content">
                                    <FormToggle
                                        checked={isPro && skipCurrentUserNotification}
                                        className={!isPro ? 'pro-feature disabled' : ''}
                                        onChange={() => {

                                            if (!isPro) {
                                                showProModal(wp.i18n.__('Upgrade to PRO to enable email notifications.', 'integrate-google-drive'));
                                                return;
                                            }

                                            setEditData({
                                                ...editData,
                                                skipCurrentUserNotification: !skipCurrentUserNotification
                                            })
                                        }}
                                    />

                                    <p className="description">{wp.i18n.__("Enable to skip the notification for the user that executes the action.", "integrate-google-drive")}</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            }


        </div>
    )
}