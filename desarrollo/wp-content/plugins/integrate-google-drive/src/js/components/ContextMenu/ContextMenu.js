import {Tooltip} from "react-tooltip";
import {animation, Item, Menu, Submenu} from "react-contexify";
import AppContext from "../../contexts/AppContext";

import MoveModal from "./MoveModal";
import CopyModal from "./CopyModal";
import preview from "../../includes/preview";
import {handleImport} from "./ImportModal";
import {handleShare} from "./ShareModal";
import {showProModal} from "../../includes/ProModal";

import {
    base64Encode,
    deleteFiles, getThumb, getTypeIcon, handleDownload, isAudioVideoType,
    isFolder, isImageType,
    setListingView,
} from "../../includes/functions";

const {useContext} = React;

export default function ContextMenu() {
    const context = useContext(AppContext);

    const {
        shortcodeId,
        activeAccount,
        getFiles,
        activeFiles,
        setActiveFiles,
        activeFile,
        activeFolder,
        permissions,
        notifications,
        shortcodeBuilderType,
        isOptions,
        setIsOptions,
        isList,
        setIsList,
        setShowDetails,
        setIsUpload,
        isShortcodeBuilder,
        contextMenuId,
        selectAll,
        setSelectAll,
        files,
        setFiles,
        setAllFiles,
        allFiles,
        initFolders,
        setActiveAccount,
        nonce,
    } = context;

    const isSearch = 'search' === shortcodeBuilderType;
    const isGallery = 'gallery' === shortcodeBuilderType;
    const isUploader = 'uploader' === shortcodeBuilderType;

    const isActiveFolder = activeFile && activeFolder && activeFile.id === activeFolder.id;

    const extractFiles = () => {
        if (!isActiveFolder && activeFile && !isFolder(activeFile)) {
            return [activeFile];
        }

        if (activeFiles.length === 1 && !isFolder(activeFiles[0])) {
            return [activeFiles[0]];
        }

        if (isOptions) {
            return activeFiles;
        }

        return [activeFile];
    };

    const handleRename = () => {
        Swal.fire({
            title: wp.i18n.__('Rename', 'integrate-google-drive'),
            text: wp.i18n.__('Enter new name', 'integrate-google-drive'),
            input: 'text',
            inputValue: activeFile.name,
            inputAttributes: {
                autocapitalize: 'off',
            },
            showCancelButton: false,
            confirmButtonText: wp.i18n.__('Rename', 'integrate-google-drive'),
            showLoaderOnConfirm: true,
            showCloseButton: true,
            customClass: {container: 'igd-swal'},
            preConfirm: (name) => {
                if (!name) {
                    return Swal.showValidationMessage(wp.i18n.__('Please enter a name', 'integrate-google-drive'))
                }

                return wp.ajax.post('igd_rename_file', {
                    shortcodeId,
                    name,
                    id: activeFile.id,
                    accountId: activeFile.accountId,
                    nonce: nonce || igd.nonce,
                }).done(() => {

                    //Set new name;
                    const items = files.map(file => file.id === activeFile.id ? {...file, name} : file);

                    setFiles(items);
                    setAllFiles(prevFiles => ({...prevFiles, [activeFolder.id]: items}));

                    Swal.close();

                    Swal.fire({
                        title: wp.i18n.__('Renamed!', 'integrate-google-drive'),
                        text: wp.i18n.__('File has been renamed.', 'integrate-google-drive'),
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        toast: true,
                        customClass: {container: 'igd-swal'},
                    });
                }).fail(error => {
                    Swal.fire({
                        title: wp.i18n.__('Error!', 'integrate-google-drive'),
                        text: error.message,
                        icon: 'error',
                        showConfirmButton: true,
                        confirmButtonText: wp.i18n.__('OK', 'integrate-google-drive'),
                        customClass: {container: 'igd-swal'},
                    });
                });
            },
        });
    }

    const handleMove = () => {
        Swal.fire({
            title: wp.i18n.__('Move', 'integrate-google-drive'),
            text: wp.i18n.__('Select destination', 'integrate-google-drive'),
            html: '<div id="igd-move"></div>',
            showCancelButton: false,
            showConfirmButton: false,
            showCloseButton: true,
            customClass: {container: 'igd-swal igd-move-swal'},
            didOpen(popup) {
                const element = document.getElementById('igd-move');

                ReactDOM.render(<MoveModal context={context}/>, element);
            },

            willClose() {
                const element = document.getElementById('igd-move');
                ReactDOM.unmountComponentAtNode(element);
            }
        });
    }

    const handleCopy = () => {
        Swal.fire({
            title: wp.i18n.__('Copy', 'integrate-google-drive'),
            text: wp.i18n.__('Select destination', 'integrate-google-drive'),
            html: '<div id="igd-copy"></div>',
            showCancelButton: false,
            showConfirmButton: false,
            showCloseButton: true,
            customClass: {container: 'igd-swal igd-copy-swal'},
            didOpen(popup) {
                ReactDOM.render(<CopyModal context={context}/>, document.getElementById('igd-copy'));
            },

            willClose() {
                ReactDOM.unmountComponentAtNode(document.getElementById('igd-copy'));
            }
        });
    }

    const handleExportAs = (key) => {
        const {mimetype} = activeFile.exportAs[key];

        window.location.href = `${igd.ajaxUrl}?action=igd_download&id=${activeFile.id}&accountId=${activeFile['accountId']}&mimetype=${mimetype}&shortcodeId=${shortcodeId}&nonce=${nonce || igd.nonce}`;
    }

    const handleCreateDoc = (type = 'doc') => {

        let title = wp.i18n.__('New Document', 'integrate-google-drive');
        let text = wp.i18n.__('Enter document name', 'integrate-google-drive');
        if ('sheet' === type) {
            title = wp.i18n.__('New Spreadsheet', 'integrate-google-drive');
            text = wp.i18n.__('Enter spreadsheet name', 'integrate-google-drive');
        } else if ('slide' === type) {
            title = wp.i18n.__('New Presentation', 'integrate-google-drive');
            text = wp.i18n.__('Enter presentation name', 'integrate-google-drive');
        }

        Swal.fire({
            title,
            text,
            input: 'text',
            inputValue: '',
            inputAttributes: {
                autocapitalize: 'off',
            },
            showCancelButton: false,
            confirmButtonText: wp.i18n.__('Create', 'integrate-google-drive'),
            showLoaderOnConfirm: true,
            showCloseButton: true,
            customClass: {container: 'igd-swal'},
            preConfirm: (name) => {

                if (!name) {
                    return Swal.showValidationMessage(wp.i18n.__('Please enter a name', 'integrate-google-drive'))
                }

                return wp.ajax.post('igd_create_doc', {
                    shortcodeId,
                    name,
                    type,
                    folder_id: activeFolder.id,
                    account_id: activeFolder['accountId'],
                    nonce: nonce || igd.nonce,
                }).done((item) => {
                    const items = [...files, item];

                    setFiles(items);
                    setAllFiles(prevFiles => ({...prevFiles, [activeFolder.id]: items}));

                    Swal.close();

                    let title = wp.i18n.__('Document created', 'integrate-google-drive');
                    let text = wp.i18n.__('Document created successfully', 'integrate-google-drive');

                    if ('sheet' === type) {
                        title = wp.i18n.__('Spreadsheet created', 'integrate-google-drive');
                        text = wp.i18n.__('Spreadsheet created successfully', 'integrate-google-drive');
                    } else if ('slide' === type) {
                        title = wp.i18n.__('Presentation created', 'integrate-google-drive');
                        text = wp.i18n.__('Presentation created successfully', 'integrate-google-drive');
                    }

                    Swal.fire({
                        title,
                        text,
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        toast: true,
                    });
                }).fail(error => {
                    console.log(error);

                    Swal.fire({
                        title: wp.i18n.__('Error!', 'integrate-google-drive'),
                        text: error.message,
                        icon: 'error',
                        showConfirmButton: true,
                        confirmButtonText: wp.i18n.__('OK', 'integrate-google-drive'),
                        customClass: {container: 'igd-swal'},
                    });
                });
            },
        });
    }

    const newFolder = () => {

        setActiveFiles([]);

        Swal.fire({
            title: wp.i18n.__('New Folder', 'integrate-google-drive'),
            text: wp.i18n.__('Enter new folder name', 'integrate-google-drive'),
            input: 'text',
            inputValue: '',
            inputAttributes: {
                autocapitalize: 'off',
            },
            showCancelButton: false,
            confirmButtonText: wp.i18n.__('Create', 'integrate-google-drive'),
            showLoaderOnConfirm: true,
            showCloseButton: true,
            customClass: {container: 'igd-swal'},
            preConfirm: (name) => {

                if (!name) {
                    return Swal.showValidationMessage(
                        wp.i18n.__('Please enter a name', 'integrate-google-drive')
                    )
                }

                return wp.ajax.post('igd_new_folder', {
                    shortcodeId,
                    name,
                    parent_id: activeFolder['id'],
                    account_id: activeFolder['accountId'],
                    nonce: nonce || igd.nonce,
                }).done((folder) => {
                    const items = [...files, folder];

                    setFiles(items);
                    setAllFiles(prevFiles => ({...prevFiles, [activeFolder.id]: items}));

                    Swal.close();

                    Swal.fire({
                        title: 'Created!',
                        text: 'Folder has been created.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        toast: true,
                    });
                }).fail(error => {
                    Swal.fire({
                        title: wp.i18n.__('Error!', 'integrate-google-drive'),
                        text: error.message,
                        icon: 'error',
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        customClass: {container: 'igd-swal'},
                    });
                });
            },
        });
    }

    const refresh = () => {
        getFiles(activeFolder, 'refresh');
    }

    const handleCopyLink = (key) => {

        const {id, accountId, type} = activeFile;

        const fileString = base64Encode(JSON.stringify({id, accountId}));

        let link = `${igd.homeUrl}/?direct_file=${fileString}`;

        if ('downloadLink' === key) {
            const fileIdsParam = isFolder(activeFile) ? `file_ids=${base64Encode(JSON.stringify([id]))}` : `id=${id}&accountId=${accountId}`;
            link = `${igd.homeUrl}?igd_download=1&${fileIdsParam}`;

        } else if ('googleLink' === key) {
            link = activeFile['webViewLink'];
        } else if ('mediaLink' === key) {
            if (isAudioVideoType(activeFile)) {
                const ext = type.indexOf('audio/') === 0 ? '.mp3' : '.mp4';
                link = `${igd.homeUrl}/?igd_stream=1&id=${id}&account_id=${accountId}&ext=${ext}`;
            } else if (isImageType(activeFile)) {
                link = getThumb(activeFile, 'full', {}, true);
            }
        }

        // copy link to clipboard and fire swal
        const input = document.createElement('textarea');
        input.value = link;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);

        Swal.fire({
            title: wp.i18n.__('Copied!', 'integrate-google-drive'),
            text: wp.i18n.__('Link copied to clipboard.', 'integrate-google-drive'),
            icon: 'success',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            toast: true,
            customClass: {container: 'igd-swal'},
        });

    }

    const filePermissions = activeFile?.permissions || {};

    const canEdit = !!filePermissions['canEdit'];
    const canPreview = !!filePermissions['canPreview'];
    const canRename = !!filePermissions['canRename'];

    if (permissions) {
        permissions.refresh = true;
        permissions.selectAll = permissions.download && permissions.zipDownload;
    }

    const createDocItems = [
        {
            key: 'doc',
            title: wp.i18n.__('Google Doc', 'integrate-google-drive'),
            icon: 'dashicons-media-document',
        },
        {
            key: 'sheet',
            title: wp.i18n.__('Google Sheet', 'integrate-google-drive'),
            icon: 'dashicons-media-spreadsheet',
        },
        {
            key: 'slide',
            title: wp.i18n.__('Google Slide', 'integrate-google-drive'),
            icon: 'dashicons-media-interactive',
        },
    ];

    const copyLinkItems = [
        {
            key: 'directLink',
            title: wp.i18n.__('Direct Preview Link', 'integrate-google-drive'),
            icon: 'dashicons-visibility',
            tooltip: wp.i18n.__('Link to preview the file directly on your site.', 'integrate-google-drive'),
        },
        {
            key: 'googleLink',
            title: wp.i18n.__('Google Drive Preview Link', 'integrate-google-drive'),
            icon: 'dashicons-visibility',
            tooltip: wp.i18n.__('Link to preview the file on Google Drive.', 'integrate-google-drive'),
        },
        {
            key: 'mediaLink',
            title: wp.i18n.__('Direct Media Link', 'integrate-google-drive'),
            icon: 'dashicons-admin-media',
            tooltip: wp.i18n.__('Link to access the media file directly.', 'integrate-google-drive'),
        },
        {
            key: 'downloadLink',
            title: wp.i18n.__('Download Link', 'integrate-google-drive'),
            icon: 'dashicons-download',
            tooltip: wp.i18n.__('Link to download the file.', 'integrate-google-drive'),
        },
    ];

    let downloadFileCount = 0;

    if (isOptions) {
        downloadFileCount = activeFiles.filter(file => !permissions || !isFolder(file) || permissions.folderDownload).length;
    }

    const menuItems = [
        {
            key: 'refresh', title: wp.i18n.__('Refresh', 'integrate-google-drive'), icon: 'dashicons-update-alt',
        },
        {
            key: 'newFolder', title: wp.i18n.__('New Folder', 'integrate-google-drive'), icon: 'dashicons-open-folder',
        },
        {
            key: 'createDoc',
            title: wp.i18n.__('Create Document', 'integrate-google-drive'),
            icon: 'dashicons-welcome-add-page',
        },
        {
            key: 'upload', title: wp.i18n.__('Upload Files', 'integrate-google-drive'), icon: 'dashicons-cloud-upload',
        },
        {
            key: 'preview',
            title: (!permissions || permissions.inlinePreview !== false) ? wp.i18n.__('Preview', 'integrate-google-drive') : wp.i18n.__('Preview in a new window', 'integrate-google-drive'),
            icon: 'dashicons-visibility',
        },
        {
            key: 'drive',
            title: wp.i18n.__('Open in Google Drive', 'integrate-google-drive'),
            icon: 'dashicons-migrate',
        },
        {
            key: 'details', title: wp.i18n.__('View Details', 'integrate-google-drive'), icon: 'dashicons-info-outline',
        },
        {
            key: 'copyLink',
            title: wp.i18n.__('Copy Links', 'integrate-google-drive'),
            icon: 'dashicons-admin-links',
        },
        {
            key: 'share', title: wp.i18n.__('Share', 'integrate-google-drive'), icon: 'dashicons-share',
        },
        {
            key: 'selectAll',
            title: selectAll ? wp.i18n.__('Deselect All', 'integrate-google-drive') : wp.i18n.__('Select All', 'integrate-google-drive'),
            icon: 'dashicons-screenoptions',
        },
        {
            key: 'edit', title: wp.i18n.__('Edit (New Window)', 'integrate-google-drive'), icon: 'dashicons-edit-page',
        },
        {
            key: 'download',
            title: wp.i18n.__('Download', 'integrate-google-drive') + `${downloadFileCount > 1 ? ` (${downloadFileCount})` : ''}`,
            icon: 'dashicons-download',
        },
        {
            key: 'import',
            title: wp.i18n.__('Import to Media Library', 'integrate-google-drive') + `${isOptions && activeFiles.length > 1 ? ` (${activeFiles.length})` : ''}`,
            icon: 'dashicons-migrate',
        },
        {
            key: 'move',
            title: wp.i18n.__('Move', 'integrate-google-drive') + `${isOptions && activeFiles.length > 1 ? ` (${activeFiles.length})` : ''}`,
            icon: 'dashicons-editor-break',
        },
        {
            key: 'rename', title: wp.i18n.__('Rename', 'integrate-google-drive'), icon: 'dashicons-edit',
        },
        {
            key: 'copy',
            title: wp.i18n.__('Copy', 'integrate-google-drive') + `${isOptions && activeFiles.length > 1 ? ` (${activeFiles.length})` : ''}`,
            icon: 'dashicons-admin-page',
        },
        {
            key: 'delete',
            title: wp.i18n.__('Delete', 'integrate-google-drive') + `${isOptions && activeFiles.length > 1 ? ` (${activeFiles.length})` : ''}`,
            icon: 'dashicons-trash',
        },
        {
            key: 'view',
            title: isList ? wp.i18n.__('Grid View', 'integrate-google-drive') : wp.i18n.__('List View', 'integrate-google-drive'),
            icon: isList ? 'dashicons-grid-view' : 'dashicons-list-view',
        },
    ];

    const items = menuItems.filter(({key}) => {

        if (isShortcodeBuilder && isOptions && !['view', 'selectAll', 'refresh'].includes(key)) return;

        if (isSearch && !['preview', 'download', 'view'].includes(key)) return;

        //if isOptions remove drive item
        if ('drive' === key && (isOptions || initFolders)) return;

        // Check settings permissions if action is allowed except view
        if (permissions && !permissions[key]) return;

        // Remove view, if gallery
        if ('view' === key && ((isGallery && !isShortcodeBuilder) || !isOptions)) return;

        // Check edit permission
        if ('edit' === key && !canEdit) return;

        // Share File not show in options
        if (['share', 'copyLink', 'details'].includes(key) && !activeFile) return;

        // Remove select all, if not options
        if ('selectAll' === key && (!files.length || !isOptions || isUploader)) return;

        // Remove preview, if no file is selected
        if ('preview' === key && ((!activeFile && (activeFiles.length !== 1 || isFolder(activeFiles[0]))) || (activeFile && (!canPreview || isFolder(activeFile))) || (isOptions && (activeFiles.length !== 1 || isFolder(activeFiles[0]))))) return;

        // Remove copy, if not items selected || active file is folder || any folders selected
        if ('copy' === key && ((isOptions && !activeFiles.length) || (!isOptions && activeFile && isFolder(activeFile)) || (isOptions && activeFiles.length && activeFiles.some(file => isFolder(file))))) return;

        // Remove move if no items selected
        if ('move' === key && ((isOptions && !activeFiles.length))) return;

        // Remove import, if any folders selected
        if ('import' === key && ((isOptions && (!activeFiles.length || activeFiles.some(file => isFolder(file)))) || (activeFile && isFolder(activeFile)))) return;

        // Remove rename if multiple selected or no permission to rename
        if ('rename' === key && (isOptions && (activeFiles.length > 1 || !canRename))) return;

        // Remove delete from options if no file is selected
        if ('delete' === key && ((isOptions && !activeFiles.length) || (activeFile && !filePermissions.canDelete))) return;

        // Remove download from options if no file is selected or has no download permission
        if ('download' === key
            && (
                (!activeFile && !activeFiles.length)
                || (activeFile && (
                    !activeFile.permissions
                    || (!isFolder(activeFile) && !activeFile.permissions.canDownload)
                    || (isFolder(activeFile) && permissions && !permissions?.folderDownload)
                ))
                || (isOptions && !downloadFileCount)
            )) return;

        // Remove options if not active folder, or any files selected
        if ((!isActiveFolder && (!activeFolder || !isOptions || activeFile || activeFiles.length)) && ['refresh', 'newFolder', 'upload', 'createDoc'].includes(key)) return;

        // For root folders only show view option
        if (isOptions && activeFolder && ['computers', 'shared-drives',].includes(activeFolder.id) && !['view'].includes(key)) return;

        // Remove options if shared
        if (isOptions && activeFolder && activeFolder.id === 'shared' && ['newFolder', 'upload', 'createDoc', 'move', 'delete'].includes(key)) return;

        // On click on the file list context menu
        if (isActiveFolder && !activeFiles.length && !isOptions && !['refresh', 'newFolder', 'upload', 'createDoc',].includes(key)) return;

        return true;
    });

    if (!items.length && permissions && !permissions.view) {
        setIsOptions(null);
    }

    return (
        <Menu
            id={contextMenuId}
            className={"igd-context-menu"}
            animation={animation.fade}
            onHidden={() => setIsOptions(false)}
        >
            {items.map(({key, title, icon}) => {

                return (
                    <Item key={key}
                          onClick={({data, event, triggerEvent}) => {

                              setIsOptions(false);

                              if ('newFolder' === key) {
                                  newFolder();
                                  return;
                              }

                              if ('refresh' === key) {
                                  refresh();
                                  return;
                              }

                              if ('upload' === key) {
                                  setIsUpload(true);
                                  return;
                              }

                              if ('edit' === key) {
                                  window.open(activeFile['webViewLink'], '_blank').focus();
                                  return;
                              }

                              if ('share' === key) {
                                  handleShare(activeFile);
                                  return;
                              }

                              if ('preview' === key) {
                                  const previewItem = !!activeFile ? activeFile : activeFiles[0];
                                  preview(event, previewItem.id, [previewItem], permissions, notifications, false, shortcodeId, nonce);

                                  return;
                              }

                              if ('drive' === key) {
                                  const previewItem = !!activeFile ? activeFile : activeFiles[0];
                                  window.open(previewItem['webViewLink'], '_blank').focus();
                                  return;
                              }

                              if ('download' === key) {

                                  if (!!activeFile && !!activeFile.exportAs && Object.keys(activeFile.exportAs).length) {
                                      return;
                                  }

                                  if (!isActiveFolder && activeFile && !isFolder(activeFile)) {
                                      window.location.href = `${igd.ajaxUrl}?action=igd_download&id=${activeFile.id}&accountId=${activeFile['accountId']}&shortcodeId=${shortcodeId}&nonce=${nonce || igd.nonce}`;
                                  } else if (activeFiles.length === 1 && !isFolder(activeFiles[0])) {
                                      window.location.href = `${igd.ajaxUrl}?action=igd_download&id=${activeFiles[0].id}&accountId=${activeFiles[0]['accountId']}&shortcodeId=${shortcodeId}&nonce=${nonce || igd.nonce}`;
                                  } else {
                                      handleDownload(isOptions, activeFiles, activeFile, permissions, shortcodeId, nonce);
                                  }

                                  // Send download notification
                                  if (notifications && notifications.downloadNotification) {
                                      wp.ajax.post('igd_notification', {
                                          files: extractFiles(),
                                          notifications,
                                          type: 'download',
                                          nonce: nonce || igd.nonce,
                                      });
                                  }

                                  return;
                              }

                              // Rename
                              if ('rename' === key) {
                                  handleRename();
                                  return;
                              }

                              // Copy
                              if ('move' === key) {
                                  handleMove();
                                  return;
                              }

                              // Copy
                              if ('copy' === key) {
                                  handleCopy();
                                  return;
                              }

                              // Import
                              if ('import' === key) {
                                  if (!igd.isPro) {
                                      showProModal(wp.i18n.__('Upgrade to PRO to import cloud files to the Media Library.', 'integrate-google-drive'));

                                      return;
                                  }

                                  const files = isOptions ? activeFiles.filter(item => !isFolder(item)) : [activeFile].filter(item => !isFolder(item));

                                  handleImport(files);

                                  return;
                              }

                              // Delete
                              if ('delete' === key) {
                                  deleteFiles({
                                      files,
                                      activeFiles,
                                      setFiles,
                                      setAllFiles,
                                      activeFolder,
                                      setActiveFiles,
                                      setActiveAccount,
                                      isOptions,
                                      activeFile,
                                      notifications,
                                      shortcodeId,
                                      nonce,
                                  });

                                  return;
                              }

                              // View
                              if ('view' === key) {
                                  setIsList(!isList);
                                  setListingView(shortcodeId, isList ? 'grid' : 'list');
                                  return;
                              }

                              // Details
                              if ('details' === key) {
                                  setShowDetails(true);
                                  localStorage.setItem('igd_show_details', 1);
                                  return;
                              }

                              // Select All
                              if ('selectAll' === key) {
                                  setSelectAll(prev => !prev);
                                  return;
                              }

                          }}
                          data={{action: key}}
                          className={`context-menu-item ${key} ${['import'].includes(key) && !igd.isPro ? 'disabled' : ''}`}
                    >

                        {'copyLink' === key ?
                            <Submenu
                                className="context-submenu"
                                label={<div className={`context-submenu-label`}>
                                    <i className={`dashicons ${icon}`}></i>
                                    <span>{title}</span>
                                </div>}
                                arrow={<i className={`dashicons dashicons-arrow-right`}></i>}
                            >
                                {copyLinkItems.map(({key, title, icon, tooltip}) => {

                                    if (!isAudioVideoType(activeFile) && !isImageType(activeFile) && 'mediaLink' === key) {
                                        return null;
                                    }

                                    return (
                                        <Item
                                            key={key}
                                            onClick={() => handleCopyLink(key)}
                                            className={`context-menu-item ${key}`}
                                        >
                                            <i className={`dashicons ${icon}`}></i>
                                            <span>{title}</span>

                                            <Tooltip
                                                anchorSelect={`.context-menu-item.${key}`}
                                                content={tooltip}
                                                className="context-tooltip"
                                            />

                                        </Item>
                                    )
                                })}
                            </Submenu>
                            :
                            'createDoc' === key ?
                                <Submenu
                                    className="context-submenu"
                                    label={<div className={`context-submenu-label`}>
                                        <i className={`dashicons ${icon}`}></i>
                                        <span>{title}</span>
                                    </div>}
                                    arrow={<i className={`dashicons dashicons-arrow-right`}></i>}
                                >
                                    {createDocItems.map(({key, title, icon}) => {
                                        return (<Item key={key}
                                                      onClick={() => handleCreateDoc(key)}
                                                      className={`context-menu-item ${key}`}
                                        >
                                            <i className={`dashicons ${icon}`}></i>
                                            <span>{title}</span>
                                        </Item>)
                                    })}
                                </Submenu>
                                :
                                ('download' === key && !!activeFile && !!activeFile.exportAs && Object.keys(activeFile.exportAs).length) ?
                                    <Submenu
                                        className="context-submenu"
                                        label={<div className={`context-submenu-label`}>
                                            <i className={`dashicons ${icon}`}></i>
                                            <span>{wp.i18n.__('Download as', 'integrate-google-drive')}</span>
                                        </div>}
                                        arrow={<i className={`dashicons dashicons-arrow-right`}></i>}
                                    >
                                        {Object.keys(activeFile.exportAs).map(key => {
                                            return (
                                                <Item key={key} className={`context-menu-item ${key}`}
                                                      onClick={() => handleExportAs(key)}>
                                                    <img src={getTypeIcon(activeFile.exportAs[key].mimetype)} alt={key}
                                                         width="16" height="16"/>
                                                    <span>{key}</span>
                                                </Item>
                                            )
                                        })}
                                    </Submenu>
                                    :
                                    <>
                                        <i className={`dashicons ${icon}`}></i>
                                        <span>{title}</span>

                                        {['import'].includes(key) && !igd.isPro && <div className="pro-badge">
                                            <i className="dashicons dashicons-lock"></i>
                                            <span>{wp.i18n.__('PRO', 'integrate-google-drive')}</span>
                                        </div>}
                                    </>
                        }

                    </Item>)
            })}

        </Menu>
    )

}