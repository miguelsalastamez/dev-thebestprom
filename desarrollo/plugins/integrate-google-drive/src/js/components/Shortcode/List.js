import preview from "../../includes/preview";
import {base64Encode, isFolder} from "../../includes/functions";

const List = ({
                  folders: files = [],
                  notifications = {},
                  linkListStyle = 'default',
                  defaultClickAction = 'view',
                  shortcodeId,
                  nonce,
                  permissions,
                  linkButtonText = wp.i18n.__('View', 'integrate-google-drive'),
                  listDownloadButtonText = wp.i18n.__('Download', 'integrate-google-drive'),
                  listEditButtonText = wp.i18n.__('Edit', 'integrate-google-drive'),
              }) => {

    if (!files.length) return null;

    const sendNotification = function (file, type) {

        wp.ajax.post('igd_notification', {
            files: [file],
            notifications,
            type,
            nonce,
        });

    }

    const renderItem = (file) => {
        const {name, iconLink, webViewLink, id, accountId} = file;

        const filePermissions = file?.permissions || {};

        const canEdit = !!filePermissions['canEdit'];
        const canPreview = !!filePermissions['canPreview'];

        const viewLink = `https://drive.google.com/file/d/${id}/preview?rm=minimal`;
        const downloadLink = `${igd.siteUrl}?igd_download=1&${isFolder(file) ? `file_ids="${base64Encode(JSON.stringify([id]))}` : `id=${id}`}&accountId=${accountId}&shortcodeId=${shortcodeId}&nonce=${nonce}`;
        const editLink = webViewLink;

        const defaultActionLink = defaultClickAction === 'view' ? viewLink : defaultClickAction === 'download' ? downloadLink : editLink;

        return 'default' === linkListStyle ?
            (
                <a
                    key={id}
                    href={defaultActionLink}
                    className="igd-link"
                    onClick={(e) => {
                        sendNotification(file, defaultClickAction);

                        if ('view' === defaultClickAction && permissions.inlinePreview) {
                            e.preventDefault();
                            e.stopPropagation();

                            preview(e, id, [file], permissions, notifications, false, shortcodeId, nonce);
                        }
                    }}
                    target={'_blank'}
                    title={wp.i18n.sprintf('%s %s', linkButtonText, name)}
                    aria-label={wp.i18n.sprintf('%S %s', linkButtonText, name)}
                >
                    {name}
                </a>
            )
            :
            (
                <div key={id} className="igd-link igd-list-item">
                    <img className="item-icon" src={iconLink} alt={name}/>

                    <a
                        className="item-name"
                        href={defaultActionLink}
                        onClick={(e) => {

                            sendNotification(file, defaultClickAction);

                            if ('view' === defaultClickAction && permissions.inlinePreview) {
                                e.preventDefault();
                                e.stopPropagation();

                                preview(e, id, [file], permissions, notifications, false, shortcodeId, nonce);
                            }
                        }}
                        target={'_blank'}
                        title={wp.i18n.sprintf('%s %s', linkButtonText, name)}
                        aria-label={wp.i18n.sprintf('%s %s', linkButtonText, name)}
                    >
                        {name}
                    </a>

                    {permissions.preview && (
                        <a
                            className="item-action"
                            href={viewLink}
                            onClick={(e) => {
                                sendNotification(file, defaultClickAction);

                                if (permissions.inlinePreview) {
                                    e.preventDefault();
                                    e.stopPropagation();

                                    preview(e, id, [file], permissions, notifications, false, shortcodeId, nonce);
                                }
                            }}
                            target={'_blank'}
                            title={wp.i18n.sprintf('%s %s', linkButtonText, name)}
                            aria-label={wp.i18n.sprintf('%s %s', linkButtonText, name)}
                        >
                            <i className="dashicons dashicons-visibility"/>
                            <span className="item-action-text">{linkButtonText}</span>
                        </a>
                    )}

                    {permissions.download && (
                        <a
                            className="item-action"
                            href={downloadLink}
                            title={wp.i18n.sprintf('%s %s', listDownloadButtonText, name)}
                            aria-label={wp.i18n.sprintf('%s %s', listDownloadButtonText, name)}
                            onClick={() => {
                                sendNotification(file, 'download');
                            }}
                        >
                            <i className="dashicons dashicons-download"/>
                            <span className="item-action-text">{listDownloadButtonText}</span>
                        </a>
                    )}

                    {canEdit && permissions.edit && (
                        <a
                            className="item-action"
                            href={editLink}
                            title={wp.i18n.sprintf('%s %s', listEditButtonText, name)}
                            aria-label={wp.i18n.sprintf('%s %s', listEditButtonText, name)}
                            target={'_blank'}
                            onClick={() => {
                                sendNotification(file, 'edit');
                            }}
                        >
                            <i className="dashicons dashicons-edit"/>
                            <span className="item-action-text">{listEditButtonText}</span>
                        </a>
                    )}


                </div>
            );
    };

    const content = files.map(renderItem);

    return linkListStyle !== 'default' ? (
        <div className={`igd-list-wrap list-style-${linkListStyle}`}>{content}</div>
    ) : (
        <>{content}</>
    );
};

export default List;
