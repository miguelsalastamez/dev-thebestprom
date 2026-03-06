import {Tooltip} from "react-tooltip";
import {showProModal} from "../../../../includes/ProModal";
import ShortcodeBuilderContext from "../../../../contexts/ShortcodeBuilderContext";
import NamingTemplate from "./NamingTemplate";

const {useContext} = React;

const {FormToggle} = wp.components;

export default function CommonUploadFields() {

    const {isPro} = igd;

    const context = useContext(ShortcodeBuilderContext);
    const {editData, setEditData, isFormBuilder, isWooCommerce} = context;

    const {
        type,
        enableFolderUpload,
        enableUploadDescription ,
        overwrite,
        uploadFileName = '%file_name%%file_extension%',
        createEntryFolders,
        entryFolderNameTemplate = 'cf7' === isFormBuilder ? 'Form entry - %form_title%' : 'Entry (%entry_id%) - %form_title%',
        folderNameTemplate = isWooCommerce ? 'Order - #%wc_order_id% - %wc_product_name% (%user_email%)' : '',
        mergeFolders,
    } = editData;

    const isBrowser = 'browser' === type;

    return (
        <>
            {/* Overwrite */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Overwrite Files", "integrate-google-drive")}</h4>

                <div className="settings-field-content">

                    <FormToggle
                        checked={overwrite}
                        onChange={() => setEditData({...editData, overwrite: !overwrite})}
                    />

                    <p className="description">{wp.i18n.__("Enable to overwrite files with the same name.", "integrate-google-drive")}</p>
                </div>

            </div>

            {/* Enable Folder Upload */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Enable Folder Upload", "integrate-google-drive")}</h4>

                <div className="settings-field-content">

                    <FormToggle
                        data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                        data-tooltip-id={'igd-pro-tooltip'}
                        className={!isPro ? 'disabled' : ''}
                        checked={isPro && enableFolderUpload}
                        onChange={() => setEditData({
                            ...editData,
                            enableFolderUpload: !enableFolderUpload
                        })}
                    />

                    {!isPro &&
                        <Tooltip
                            id={'igd-pro-tooltip'}
                            effect="solid"
                            place="right"
                            variant={'warning'}
                            className={"igd-tooltip"}
                        />
                    }

                    <p className="description">{wp.i18n.__("Allow users to upload folders. A folder upload button will be added.", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Enable File Description */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Enable File Description", "integrate-google-drive")}</h4>

                <div className="settings-field-content">

                    <FormToggle
                        data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                        data-tooltip-id={'igd-pro-tooltip'}
                        className={!isPro ? 'disabled' : ''}
                        checked={isPro && enableUploadDescription}
                        onChange={() => setEditData({
                            ...editData,
                            enableUploadDescription: !enableUploadDescription
                        })}
                    />

                    <p className="description">{wp.i18n.__("Allow users to add a description to the uploaded files.", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Create form entry folder */}
            {(isFormBuilder && !isBrowser) &&
                <div className="settings-field form-entry-field">
                    <h4 className="settings-field-label">{wp.i18n.__("Create Entry Folder", "integrate-google-drive")} </h4>

                    <div className="settings-field-content">
                        <FormToggle
                            data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                            data-tooltip-id={'igd-pro-tooltip'}
                            className={!isPro ? 'disabled' : ''}
                            checked={createEntryFolders}
                            onChange={() => {

                                if (!isPro) {
                                    showProModal(wp.i18n.__('Upgrade to Pro to create entry folders', 'integrate-google-drive'));
                                    return;
                                }

                                setEditData({...editData, createEntryFolders: !createEntryFolders})
                            }}
                        />

                        <p className="description">
                            {wp.i18n.__("Create a folder for the files uploaded through this upload field.", "integrate-google-drive")}
                        </p>

                        {(!!createEntryFolders && isPro) &&
                            <div className="settings-field-sub">

                                {/* Name Template */}
                                <NamingTemplate
                                    value={entryFolderNameTemplate}
                                    onUpdate={(entryFolderNameTemplate) => setEditData({
                                        ...editData,
                                        entryFolderNameTemplate
                                    })}
                                    type={'folder'}
                                />

                                {/* Merge Entry Folders */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Merge Folders", "integrate-google-drive")} </h4>

                                    <div className="settings-field-content">
                                        <FormToggle
                                            data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                            checked={mergeFolders}
                                            onChange={() => {

                                                if (!isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to Pro to create entry folders', 'integrate-google-drive'));
                                                    return;
                                                }

                                                setEditData({...editData, mergeFolders: !mergeFolders})
                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Allow merging folders if a folder with the same name already exists, and upload files into that existing folder instead of creating a new one.", "integrate-google-drive")}</p>

                                    </div>
                                </div>

                            </div>
                        }

                    </div>
                </div>
            }

            {/* Folder Name Template */}
            {isWooCommerce &&
                <NamingTemplate
                    value={folderNameTemplate}
                    onUpdate={(folderNameTemplate) => setEditData({...editData, folderNameTemplate})}
                    type={'wc_folder'}
                />
            }

            {/* Rename The File */}
            <NamingTemplate
                value={uploadFileName}
                onUpdate={(uploadFileName) => setEditData({...editData, uploadFileName})}
                type={'file'}
            />

        </>
    )
}