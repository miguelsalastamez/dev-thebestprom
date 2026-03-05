import {ShortcodeBuilderProvider} from "../contexts/ShortcodeBuilderContext";
import Form from "./ShortcodeBuilder/Form";
import {moduleTypes} from "../includes/functions";
import {Tooltip} from "react-tooltip";

const {Button} = wp.components;

const {useState} = React;

export default function ModuleBuilderModal({
                                               initData,
                                               onUpdate,
                                               onClose,
                                               isSelectFiles,
                                               selectionType,
                                               isFormBuilder,
                                               isLMS,
                                               isWooCommerce,
                                           }) {

    const [editData, setEditData] = useState(initData || {
        title: wp.i18n.__('Module Title', 'integrate-google-drive'),
        type: igd.isPro ? 'browser' : 'embed',
    });

    let headerTitle = wp.i18n.__('Configure Module', 'integrate-google-drive');
    let headerIcon = 'dashicons dashicons-admin-generic';

    if (isSelectFiles) {

        if ('folders' === selectionType) {
            headerTitle = wp.i18n.__('Select Folders', 'integrate-google-drive');
            headerIcon = 'dashicons dashicons-open-folder';
        } else {
            headerTitle = wp.i18n.__('Select Files', 'integrate-google-drive');
        }

        headerIcon = 'dashicons dashicons-open-folder';

    } else if (isLMS) {
        headerTitle = wp.i18n.__('Select Video', 'integrate-google-drive');
        headerIcon = 'dashicons dashicons-video-alt3';
    }

    return (
        <div className="igd-module-builder-modal" onClick={e => e.stopPropagation()}>

            <div className="igd-module-builder-modal-header">

                <div className="header-title">
                    <i className={headerIcon}></i>
                    <h3>{headerTitle}</h3>
                </div>

                <div className="header-actions">

                    <Button
                        className="igd-btn btn-danger close"
                        isSecondary
                        onClick={onClose}
                        icon="no-alt"
                        text={wp.i18n.__('Close', 'integrate-google-drive')}
                        label={wp.i18n.__('Close Configuration', 'integrate-google-drive')}
                        showTooltip
                    />

                    <Button
                        className="igd-btn btn-primary done"
                        isPrimary
                        onClick={() => onUpdate(editData)}
                        icon="saved"
                        text={wp.i18n.__('Save Changes', 'integrate-google-drive')}
                        label={wp.i18n.__('Save Changes', 'integrate-google-drive')}
                        showTooltip
                    />

                </div>

            </div>

            <ShortcodeBuilderProvider
                value={{
                    editData,
                    setEditData,
                    updateShortcode: (data) => onUpdate(data),
                    isEditor: true,
                    builderType: editData.type,
                    isSelectFiles,
                    selectionType,
                    isFormBuilder,
                    isLMS,
                    isWooCommerce,
                }}>
                <Form/>
            </ShortcodeBuilderProvider>
        </div>
    )
}