import AppContext from "../../../contexts/AppContext";
import Uploader from "../Uploader";

const {useContext} = React;

export default function EmptyPlaceholder() {

    const context = useContext(AppContext);
    const {
        isUpload,
        permissions,
        setIsUpload,
        isShortcodeBuilder,
        shortcodeBuilderType,
        files,
    } = context;

    const shouldUpload = !isShortcodeBuilder && (!shortcodeBuilderType || 'browser' === shortcodeBuilderType) && (!permissions || permissions.upload);

    let title = wp.i18n.__('There are no items here.', 'integrate-google-drive');
    let subtitle = wp.i18n.__('The folder is empty.', 'integrate-google-drive');

    if (isShortcodeBuilder && !files.length) {
        title = wp.i18n.__('No items available for selection.', 'integrate-google-drive');

        if ('gallery' === shortcodeBuilderType) {
            subtitle = wp.i18n.__('This folder doesn\'t contain any images or videos.', 'integrate-google-drive');
        } else if ('media' === shortcodeBuilderType) {
            subtitle = wp.i18n.__('This folder doesn\'t contain any media files.', 'integrate-google-drive');
        } else {
            subtitle = wp.i18n.__('This folder doesn\'t contain any selectable items.', 'integrate-google-drive');
        }
    }

    return (
        <div className={`igd-root-placeholder empty-folder-placeholder ${isUpload ? 'igd-hidden' : ''}`}>
            <img src={`${igd.pluginUrl}/assets/images/file-browser/empty-folder-placeholder.svg`}
                 alt="Empty Folder"/>

            <span className="igd-root-placeholder-title">{title}</span>
            <span className="igd-root-placeholder-text">{subtitle}</span>

            {shouldUpload &&
                <button type="button" className="igd-btn btn-primary uploader-btn" onClick={() => {
                    setIsUpload(true);

                    setTimeout(() => {
                        const browseBtn = document.querySelector('.igd-file-uploader-buttons .browse-files');

                        if (browseBtn) {
                            browseBtn.click();
                        }

                    }, 10);

                }}>
                    <i className="dashicons dashicons-cloud-upload"></i>
                    {wp.i18n.__('Upload Files', 'integrate-google-drive')}
                </button>
            }

        </div>

    )
}