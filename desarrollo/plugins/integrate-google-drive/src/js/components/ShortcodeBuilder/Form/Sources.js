import {Tooltip} from "react-tooltip";
import ShortcodeBuilderContext, {ShortcodeBuilderProvider} from "../../../contexts/ShortcodeBuilderContext";
import App from '../../App.js';
import Form from "../Form";
import ModuleBuilderModal from "../../ModuleBuilderModal";
import Modal from "../../../includes/Modal";

import {showProModal} from "../../../includes/ProModal";
import {getRootFolders, isFolder, useMounted} from "../../../includes/functions";
import NamingTemplate from "./Fields/NamingTemplate";

const {
    FormToggle,
    ButtonGroup,
    Button,
} = wp.components;

const {useEffect, useRef, useContext, useState} = React;

export default function Sources() {

    const {isPro, settings, accounts = {}} = igd;

    const context = useContext(ShortcodeBuilderContext);
    const {
        isModuleBuilder,

        editData,
        setEditData,
        isSelectFiles,
        isInlineSelect,
        isFormBuilder,
        selectionType,
        isEditor,
        isLMS,
        initParentFolder,
        isWooCommerce,
    } = context;

    const {
        type,
        allFolders,
        folders = [],
        privateFolders,
        acfDynamicFiles,
        acfFieldKey,
        createPrivateFolder,
        nameTemplate = settings.nameTemplate ? settings.nameTemplate : '%user_login% (%user_email%)',
        parentFolder = settings.parentFolder ? settings.parentFolder : getRootFolders('root'),
        templateFolder = settings.templateFolder ? settings.templateFolder : null,
        uploadFolderSelection,
        uploadFolders = [],
        folderSelectionLabel = wp.i18n.__('Choose Upload Folder', 'integrate-google-drive'),
        allowEmbedPlayer = true,
        folderFiles,
    } = editData;

    const [selectParent, setSelectParent] = useState(false);
    const [selectTemplate, setSelectTemplate] = useState(false);
    const [showSelectFoldersModal, setShowSelectFoldersModal] = useState(false);
    const [selectedFolders, setSelectedFolders] = useState(folders);

    const [initFolder, setInitFolder] = useState(initParentFolder);

    const isBrowser = 'browser' === type;
    const isUploader = 'uploader' === type;
    const isSlider = 'slider' === type;
    const isGallery = 'gallery' === type;
    const isMedia = 'media' === type;
    const isSearch = 'search' === type;
    const isEmbed = 'embed' === type;
    const isListModule = 'list' === type;

    const browserWrapRef = useRef(null);

    // handle listing responsive view
    useEffect(() => {

        if (!browserWrapRef.current) return;
        const handleBrowserResize = (entries) => {
            const element = entries[0].target;
            const width = entries[0].contentRect.width;

            if (width < 700) {
                element.classList.add('view-list');
            } else {
                element.classList.remove('view-list');
            }

        }

        new ResizeObserver(handleBrowserResize).observe(browserWrapRef.current);

    }, []);

    let sourceTitle = wp.i18n.__(`Select Files & Folders`, 'integrate-google-drive');
    let sourceDescription = wp.i18n.__(`Select the files and folders to display in the module.`, 'integrate-google-drive');

    if (isUploader || isFormBuilder) {
        sourceTitle = wp.i18n.__(`Select Upload Folder`, 'integrate-google-drive');
        sourceDescription = wp.i18n.__(`Select the folder where the files will be uploaded.`, 'integrate-google-drive');
    } else if (isGallery) {
        sourceTitle = wp.i18n.__(`Select Files`, 'integrate-google-drive');
        sourceDescription = wp.i18n.__(`Select the files and folders to showcase in the gallery. You can include both image and video files in the gallery module.`, 'integrate-google-drive');
    } else if (isMedia) {
        sourceTitle = wp.i18n.__(`Select Files`, 'integrate-google-drive');
        sourceDescription = wp.i18n.__(`Select the files to display in the media player. You can include both audio and video files in the media player.`, 'integrate-google-drive');
    } else if (isEmbed) {
        sourceTitle = wp.i18n.__(`Select Files`, 'integrate-google-drive');
        sourceDescription = wp.i18n.__(`Select the files to embed.`, 'integrate-google-drive');
    } else if (isSlider) {
        sourceTitle = wp.i18n.__(`Select Files`, 'integrate-google-drive');
        sourceDescription = wp.i18n.__(`Select the files to display in the slider.`, 'integrate-google-drive');
    } else if (isListModule) {
        sourceTitle = wp.i18n.__(`Select Files`, 'integrate-google-drive');
        sourceDescription = wp.i18n.__(`Select the files to display in the list.`, 'integrate-google-drive');
    } else if (isSearch) {
        sourceTitle = wp.i18n.__(`Select Search Folders`, 'integrate-google-drive');
        sourceDescription = wp.i18n.__(`Select the folders to search for files.`, 'integrate-google-drive');
    }

    const shouldPrivateFolder = (!isEmbed || 'classic' !== isEditor) && !allFolders && !isSelectFiles && !isLMS && isWooCommerce !== 'download';

    let privateFolderDescription = wp.i18n.__('Upload files to the user’s private linked folder.', 'integrate-google-drive');

    if (isEmbed) {
        privateFolderDescription = wp.i18n.__('Turn ON to embed the private files and folders linked to the user.', 'integrate-google-drive');
    } else if (isUploader) {
        privateFolderDescription = wp.i18n.__('Turn ON to upload the files in the private folder linked to the user.', 'integrate-google-drive');
    } else if (isSearch) {
        privateFolderDescription = wp.i18n.__('Turn ON to search the private files and folders linked to the user.', 'integrate-google-drive');
    }

    const isAcfEnabled = settings.integrations?.includes('acf');

    const isMounted = useMounted();

    const getUniqueSorted = () => {
        const unique = selectedFolders.filter((item, index, self) => {
            return index === self.findIndex(it =>
                item?.id ? it?.id === item?.id : it === item
            );
        });

        // Sort: folders first, then files (keeps original order within groups)
        const sorted = [...unique].sort((a, b) => {
            const aIsFolder = isFolder(a);
            const bIsFolder = isFolder(b);

            if (aIsFolder && !bIsFolder) return -1;
            if (!aIsFolder && bIsFolder) return 1;
            return 0;
        });

        return sorted;
    }

    // Handle unique selected folders
    useEffect(() => {

        if (!isMounted) return;

        setEditData(editData => ({...editData, folders: getUniqueSorted()}));

    }, [selectedFolders]);

    // ——— DND (HTML5) with same-type only ———
    const dragSrcIndex = useRef(null);
    const dragType = useRef(null); // 'file' | 'folder'
    const overIndexRef = useRef(null);
    const [overIndex, setOverIndex] = useState(null);
    const [invalidOverIndex, setInvalidOverIndex] = useState(null);

    const getType = (item) => (isFolder(item) ? "folder" : "file");

    const reorder = (list, from, to) => {
        const next = list.slice();
        const [moved] = next.splice(from, 1);
        next.splice(to, 0, moved);
        return next;
    };

    const handleDragStart = (e, index) => {
        dragSrcIndex.current = index;
        dragType.current = getType(selectedFolders[index]);
        e.dataTransfer.setData("text/plain", String(index)); // required by FF
        e.dataTransfer.effectAllowed = "move";
        e.currentTarget.classList.add("dragging");
    };

    const handleDragOver = (e, index) => {
        e.preventDefault(); // allow drop checks
        const targetType = getType(selectedFolders[index]);

        // same-type only
        if (dragType.current !== targetType) {
            e.dataTransfer.dropEffect = "none";
            overIndexRef.current = null;
            setOverIndex(null);
            setInvalidOverIndex(index);
            return;
        }

        e.dataTransfer.dropEffect = "move";
        setInvalidOverIndex(null);

        if (overIndexRef.current !== index) {
            overIndexRef.current = index;
            setOverIndex(index);
        }
    };

    const handleDrop = (e, index) => {
        e.preventDefault();
        const src = dragSrcIndex.current;
        if (src == null || src === index) return;

        const targetType = getType(selectedFolders[index]);
        if (dragType.current !== targetType) {
            // block cross-type drop
            cleanupDragState();
            return;
        }

        const next = reorder(selectedFolders, src, index);
        setSelectedFolders(next);
        setEditData((d) => ({...d, folders: next}));
        cleanupDragState();
    };

    const handleDragEnd = (e) => {
        if (e?.currentTarget) e.currentTarget.classList.remove("dragging");
        cleanupDragState();
    };

    const cleanupDragState = () => {
        dragSrcIndex.current = null;
        dragType.current = null;
        overIndexRef.current = null;
        setOverIndex(null);
        setInvalidOverIndex(null);
    };

    return (
        <div className="shortcode-module-body content-sources">

            {/*--- Private Parent, Template Folder Selection ---*/}
            <Modal
                isOpen={selectParent || selectTemplate}
                onClose={() => {
                    setSelectParent(false);
                    setSelectTemplate(false);
                }}
                className="igd-select-files-modal"
                target={document.getElementById('igd-module-builder-modal') || document.body}
            >

                <ShortcodeBuilderProvider
                    value={{
                        editData: {folders: []},
                        setEditData: (data) => {

                            const {folders = []} = data();

                            if (selectParent) {
                                setEditData(editData => ({
                                    ...editData,
                                    parentFolder: folders[0]
                                }));
                            } else {
                                setEditData(editData => ({
                                    ...editData,
                                    templateFolder: folders[0]
                                }));
                            }

                            setSelectParent(false);
                            setSelectTemplate(false);
                        },
                        isShortcodeBuilder: true,
                        isEditor: true,
                        isInlineSelect: true,
                        isSelectFiles: true,
                        selectionType: "parent",
                        initParentFolder,
                    }}
                >
                    <Form/>
                </ShortcodeBuilderProvider>
            </Modal>

            {/* All Folders & Files  */}
            {!acfDynamicFiles && isWooCommerce !== 'download' && !isSlider && !isEmbed && !isMedia && !isListModule && !isUploader && !isSelectFiles && !isLMS &&
                <div className="settings-field">
                    <h4 className="settings-field-label">{wp.i18n.__("All Folders & Files", "integrate-google-drive")} </h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={allFolders}
                            onChange={() => setEditData({...editData, allFolders: !allFolders})}
                        />

                        <p className="description">
                            {wp.i18n.__("If turned ON, users can navigate through all the folders & files of all the linked Google Drive accounts.", "integrate-google-drive")}
                            <br/>
                            {wp.i18n.__("Otherwise, to select specific folders & files disable this option.", "integrate-google-drive")}
                        </p>
                    </div>
                </div>
            }

            {/* Use linked private folders  */}
            {!acfDynamicFiles && !!shouldPrivateFolder &&
                <div className="settings-field field-private-folder">

                    <h4 className="settings-field-label">
                        {isUploader ? wp.i18n.__("Use Private Folder", "integrate-google-drive") : wp.i18n.__("Use Private Files", "integrate-google-drive")}
                    </h4>

                    <div className="settings-field-content">
                        <FormToggle
                            data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                            data-tooltip-id="igd-pro-tooltip"
                            checked={isPro && privateFolders}
                            className={!isPro ? 'disabled' : ''}
                            onChange={() => {
                                if (!isPro) {
                                    showProModal(wp.i18n.__('Upgrade to Pro to use User Private Folders', 'integrate-google-drive'));
                                    return;
                                }

                                setEditData({...editData, privateFolders: !privateFolders});
                            }}
                        />

                        {!isPro &&
                            <Tooltip
                                id={"igd-pro-tooltip"}
                                effect="solid"
                                place="right"
                                variant={'warning'}
                                className={"igd-tooltip"}
                            />
                        }

                        <p className="description">
                            {privateFolderDescription}

                            <a href="https://softlabbd.com/docs/how-to-use-and-enable-private-folders-automatically-link-manually/"
                               target="_blank" className="">
                                {wp.i18n.__('Documentation', 'integrate-google-drive')}
                                <i className="dashicons dashicons-editor-help"></i>
                            </a>

                            <div className={`igd-notice igd-notice-info`}>
                                <div className="igd-notice-content">
                                    <strong>Note:</strong> {wp.i18n.__('When enabled, this module is visible only to logged-in users.', 'integrate-google-drive')}
                                </div>
                            </div>

                        </p>

                        {(isPro && privateFolders) &&
                            <div className="settings-field-sub">
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Create Private Folder", "integrate-google-drive")} </h4>

                                    <div className="settings-field-content">
                                        <FormToggle
                                            data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                            checked={isPro && createPrivateFolder}
                                            onChange={() => {
                                                if (!isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to Pro to use User Private Folders', 'integrate-google-drive'));
                                                    return;
                                                }
                                                setEditData({...editData, createPrivateFolder: !createPrivateFolder})
                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Turn ON to create and link a folder automatically to the user who has not linked any folder yet while the user will view the module.", "integrate-google-drive")}</p>

                                    </div>

                                    {!!createPrivateFolder &&
                                        <>
                                            {/*--- Name Template ---*/}
                                            <NamingTemplate
                                                value={nameTemplate}
                                                onUpdate={(nameTemplate) => setEditData(editData => ({
                                                    ...editData,
                                                    nameTemplate
                                                }))}
                                                type={'folder'}
                                            />

                                            {/*--- Parent Folder ---*/}
                                            <div className="settings-field">
                                                <h4 className="settings-field-label">{wp.i18n.__("Parent Folder", "integrate-google-drive")} </h4>
                                                <div className="settings-field-content">
                                                    <div className="template-folder-wrap">

                                                        {!!parentFolder &&
                                                            <div className="template-folder">
                                                                <div className="template-folder-account">
                                                                    {accounts[parentFolder.accountId].email}
                                                                </div>

                                                                <div className="template-folder-item">
                                                                    {!!parentFolder.iconLink ?
                                                                        <img src={parentFolder.iconLink}/>
                                                                        :
                                                                        <i className="dashicons dashicons-category"></i>
                                                                    }
                                                                    <span
                                                                        className="template-folder-name">{!!parentFolder.name ? parentFolder.name : getRootFolders(parentFolder, accounts[parentFolder.accountId])}</span>
                                                                </div>

                                                                <div className="dashicons dashicons-no-alt"
                                                                     onClick={() => setEditData({
                                                                         ...editData,
                                                                         parentFolder: null
                                                                     })}>
                                                                <span
                                                                    className="screen-reader-text">{wp.i18n.__('Remove', 'integrate-google-drive')}</span>
                                                                </div>

                                                            </div>
                                                        }

                                                        <button
                                                            data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                                            className={`igd-btn ${selectParent ? 'btn-warning' : 'btn-primary'}`}
                                                            onClick={() => {
                                                                if (!isPro) {
                                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable auto private folders creation for the user.', 'integrate-google-drive'));

                                                                    return;
                                                                }

                                                                setSelectParent(!selectParent)
                                                            }}>
                                                            <i className="dashicons dashicons-open-folder"></i>

                                                            {selectParent ?
                                                                <span>{wp.i18n.__('Cancel', 'integrate-google-drive')}</span>
                                                                :
                                                                <span>{!!parentFolder ? wp.i18n.__('Change Parent Folder', 'integrate-google-drive') : wp.i18n.__('Select Parent Folder', 'integrate-google-drive')}</span>
                                                            }
                                                        </button>

                                                        {!isPro &&
                                                            <Tooltip
                                                                effect="solid"
                                                                place="right"
                                                                backgroundColor="#FF9F10"
                                                                className={"igd-tooltip"}
                                                            />
                                                        }

                                                    </div>

                                                    <p className="description">{wp.i18n.__("Select the parent folder where the automatically created private folders will be created.", "integrate-google-drive")}</p>
                                                </div>
                                            </div>

                                            {/* Template Folder */}
                                            <div className="settings-field">
                                                <h4 className="settings-field-label">{wp.i18n.__("Template Folder", "integrate-google-drive")} </h4>
                                                <div className="settings-field-content">
                                                    <div className="template-folder-wrap">

                                                        {!!templateFolder &&
                                                            <div className="template-folder">
                                                                <div className="template-folder-account">
                                                                    {accounts[templateFolder.accountId].email}
                                                                </div>

                                                                <div className="template-folder-item">
                                                                    {!!templateFolder.iconLink ?
                                                                        <img src={templateFolder.iconLink}/>
                                                                        :
                                                                        <i className="dashicons dashicons-category"></i>
                                                                    }
                                                                    <span
                                                                        className="template-folder-name">{!!templateFolder.name ? templateFolder.name : getRootFolders(templateFolder, accounts[templateFolder.accountId])}</span>
                                                                </div>

                                                                <div className="dashicons dashicons-no-alt"
                                                                     onClick={() => setEditData({
                                                                         ...editData,
                                                                         templateFolder: null
                                                                     })}>
                                                                <span
                                                                    className="screen-reader-text">{wp.i18n.__('Remove', 'integrate-google-drive')}</span>
                                                                </div>
                                                            </div>
                                                        }

                                                        <button
                                                            data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                                            className={`igd-btn ${selectTemplate ? 'btn-warning' : 'btn-primary'}`}
                                                            onClick={() => {

                                                                if (!isPro) {
                                                                    showProModal(wp.i18n.__('Upgrade to Pro to use User Private Folders', 'integrate-google-drive'));
                                                                    return;
                                                                }

                                                                setSelectTemplate(!selectTemplate)
                                                            }}>
                                                            <i className="dashicons dashicons-open-folder"></i>

                                                            {selectTemplate ?
                                                                <span>{wp.i18n.__('Cancel', 'integrate-google-drive')}</span>
                                                                :
                                                                <span>{!!templateFolder ? wp.i18n.__('Change Template Folder', 'integrate-google-drive') : wp.i18n.__('Select Template Folder', 'integrate-google-drive')}</span>
                                                            }
                                                        </button>

                                                        {!isPro &&
                                                            <Tooltip
                                                                effect="solid"
                                                                place="right"
                                                                backgroundColor="#FF9F10"
                                                                className={"igd-tooltip"}
                                                            />
                                                        }

                                                    </div>

                                                    {!!parentFolder && !!templateFolder && parentFolder.accountId !== templateFolder.accountId &&
                                                        <div className="template-folder-error">
                                                            <i className="dashicons dashicons-warning"></i>
                                                            <span>{wp.i18n.__('Template folder and parent folder must be from the same account.', 'integrate-google-drive')}</span>
                                                        </div>
                                                    }

                                                    {!!parentFolder && !!templateFolder && parentFolder.id === templateFolder.id &&
                                                        <div className="template-folder-error">
                                                            <i className="dashicons dashicons-warning"></i>
                                                            <span>{wp.i18n.__(`Template folder and parent folder can't be the same.`, 'integrate-google-drive')}</span>
                                                        </div>
                                                    }

                                                    <p className="description">{wp.i18n.__("Select the template folder that will be copied to the new private folder.", "integrate-google-drive")}</p>
                                                </div>
                                            </div>

                                            <div className={`igd-notice igd-notice-info`}>
                                                <div className="igd-notice-content">
                                                    <strong>Note:</strong> {wp.i18n.__('Additional private folder settings are available in the', 'integrate-google-drive')}

                                                    <a href={`${igd.adminUrl}/admin.php?page=integrate-google-drive-settings&tab=privateFolders`}>{wp.i18n.__('Settings Page', 'integrate-google-drive')}</a>

                                                </div>
                                            </div>
                                        </>
                                    }

                                </div>
                            </div>
                        }

                    </div>
                </div>
            }

            {/* ACF Dynamic Field Files */}
            {isWooCommerce !== 'download' && !privateFolders && !allFolders && !isSelectFiles && !isLMS &&
                <div className={`settings-field field-acf-dynamic-files ${!isPro || !isAcfEnabled ? 'disabled' : ''}`}>

                    <h4 className="settings-field-label">{wp.i18n.__("Use ACF field Files", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <FormToggle
                            data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                            data-tooltip-id={"igd-pro-tooltip"}
                            checked={isPro && acfDynamicFiles}
                            className={!isPro ? 'disabled' : ''}
                            onChange={() => {

                                if (!isPro) {
                                    showProModal(wp.i18n.__('Upgrade to Pro to use ACF field files', 'integrate-google-drive'));
                                    return;
                                }

                                if (!isAcfEnabled) {
                                    Swal.fire({
                                        title: wp.i18n.__('ACF integration is not enabled', 'integrate-google-drive'),
                                        text: wp.i18n.__("Enable ACF integration from the plugin settings page to access and utilize files selected from ACF fields.", 'integrate-google-drive'),
                                        icon: 'warning',
                                        showConfirmButton: false,
                                        showCloseButton: true,
                                        customClass: {container: 'igd-swal'},
                                    });

                                    return;
                                }

                                setEditData({...editData, acfDynamicFiles: !acfDynamicFiles});
                            }}
                        />

                        <p className="description">
                            {wp.i18n.__("Enable this option to use the ACF field files dynamically as the source for the module.", "integrate-google-drive")}

                            <a href="https://softlabbd.com/docs/how-to-use-acf-dynamic-files-as-module-source-files"
                               target="_blank" className="">
                                {wp.i18n.__('Documentation', 'integrate-google-drive')}
                                <i className="dashicons dashicons-editor-help"></i>
                            </a>

                        </p>

                        {!!acfDynamicFiles &&
                            <div className="settings-field-sub">
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("ACF Field Key", "integrate-google-drive")} </h4>

                                    <div className="settings-field-content">
                                        <input
                                            type={`text`}
                                            value={acfFieldKey}
                                            onChange={(e) => setEditData({...editData, acfFieldKey: e.target.value})}
                                        />

                                        <p className="description">{wp.i18n.__("Enter the ACF field name to use the files from the ACF field.", "integrate-google-drive")}
                                            <br/>
                                            {wp.i18n.__("The field value will be inherited from the post/page based on the field name where the module will be embedded.", "integrate-google-drive")}
                                        </p>

                                    </div>
                                </div>
                            </div>
                        }

                    </div>
                </div>
            }

            {/* Enable Upload Folder Selection */}
            {(isUploader && !privateFolders) &&
                <div className={`settings-field field-upload-folder-selection ${!isPro ? 'disabled' : ''}`}>

                    <h4 className="settings-field-label">{wp.i18n.__("Enable Folder Selection", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <FormToggle
                            data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                            data-tooltip-id={"igd-pro-tooltip"}
                            checked={isPro && uploadFolderSelection}
                            className={!isPro ? 'disabled' : ''}
                            onChange={() => {

                                if (!isPro) {
                                    showProModal(wp.i18n.__('Upgrade to enable dynamic upload folder selection', 'integrate-google-drive'));
                                    return;
                                }

                                setEditData({...editData, uploadFolderSelection: !uploadFolderSelection});
                            }}
                        />

                        <p className="description">
                            {wp.i18n.__("Enable this option to allow users to select the upload folder and upload files to the selected folder.", "integrate-google-drive")}

                            <a href="https://softlabbd.com/docs/how-to-enable-dynamic-folders-selection-when-uploading-files"
                               target="_blank">
                                {wp.i18n.__('Documentation', 'integrate-google-drive')}
                                <i className="dashicons dashicons-editor-help"></i>
                            </a>

                        </p>

                        {!!uploadFolderSelection &&
                            <div className="settings-field-sub">

                                {/* Select Upload Folders */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Select Upload Folders", "integrate-google-drive")} </h4>

                                    <div className="settings-field-content">

                                        <div className="template-folder">
                                            {
                                                uploadFolders.map((folder, index) => {
                                                    let {id, name, iconLink} = folder;

                                                    iconLink = iconLink || `${igd.pluginUrl}/assets/images/icons/folder.png`;

                                                    return (
                                                        <div key={index} className="template-folder-item">

                                                            <span className="folder-index">{index + 1}. </span>

                                                            <img src={iconLink}/>

                                                            <span className="template-folder-name">{name}</span>

                                                            <div className="dashicons dashicons-no-alt"
                                                                 onClick={() => {
                                                                     const newFolders = uploadFolders.filter(folder => folder.id !== id);

                                                                     setEditData({
                                                                         ...editData,
                                                                         uploadFolders: newFolders,
                                                                         folders: newFolders,
                                                                     });
                                                                 }}
                                                            ></div>
                                                        </div>
                                                    )
                                                })
                                            }
                                        </div>

                                        <button
                                            data-tooltip-content="PRO Feature"
                                            data-tooltip-id={'specific-folders'}
                                            className="igd-btn btn-success"
                                            onClick={() => {
                                                if (!igd.isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable dynamic upload folder selection', 'integrate-google-drive'));

                                                    return;
                                                }

                                                setShowSelectFoldersModal(true);

                                            }}
                                        >
                                            <i className="dashicons dashicons-open-folder"></i>
                                            <span>{wp.i18n.__('Select Folders', 'integrate-google-drive')}</span>
                                        </button>

                                        <Modal
                                            isOpen={showSelectFoldersModal}
                                            onClose={() => setShowSelectFoldersModal(false)}
                                            target={document.querySelector('.igd-shortcode-builder-form') || document.body}
                                        >
                                            <div id="igd-select-files" className="igd-module-builder-modal-wrap">
                                                <ModuleBuilderModal
                                                    initData={{folders: uploadFolders}}
                                                    onUpdate={data => {

                                                        const {folders = []} = data;

                                                        setEditData(data => ({
                                                            ...data,
                                                            uploadFolders: folders.map(folder => ({
                                                                id: folder.id,
                                                                accountId: folder.accountId,
                                                                name: folder.name,
                                                                description: folder.description,
                                                                iconLink: folder.iconLink,
                                                                type: folder.type,
                                                                shortcutDetails: folder.shortcutDetails,
                                                            })),
                                                            folders,
                                                        }));

                                                        setShowSelectFoldersModal(false);
                                                    }}
                                                    onClose={() => setShowSelectFoldersModal(false)}
                                                    isSelectFiles
                                                    selectionType="folders"
                                                />
                                            </div>
                                        </Modal>

                                        <p className={"description"}>{wp.i18n.__("Select the upload folders for the users to select the folder to upload files.", "integrate-google-drive")}</p>
                                    </div>
                                </div>

                                {/* Select Default Upload Folder */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Select Default Folder", "integrate-google-drive")} </h4>

                                    <div className="settings-field-content">
                                        <select
                                            value={folders.length ? folders[0].id : ''}
                                            onChange={(e) => {
                                                const folder = uploadFolders.find(folder => folder.id === e.target.value);

                                                setEditData({
                                                    ...editData,
                                                    folders: [folder]
                                                });
                                            }}
                                        >
                                            {uploadFolders.length ?
                                                uploadFolders.map((folder, index) => {
                                                        return (
                                                            <option key={index} value={folder.id}>{folder.name}</option>
                                                        )
                                                    }
                                                )
                                                :
                                                <option
                                                    value="">{wp.i18n.__("No Folder Selected", "integrate-google-drive")}</option>
                                            }
                                        </select>

                                        <p className={"description"}>
                                            {wp.i18n.__("Select the default folder where the files will be uploaded if the user doesn't select any folder.", "integrate-google-drive")}
                                        </p>

                                    </div>
                                </div>

                                {/* Folder Selection Label */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Folder Selection Label", "integrate-google-drive")} </h4>

                                    <div className="settings-field-content">
                                        <input
                                            type={`text`}
                                            value={folderSelectionLabel}
                                            onChange={(e) => setEditData({
                                                ...editData,
                                                folderSelectionLabel: e.target.value
                                            })}
                                        />

                                        <p className={"description"}>
                                            {wp.i18n.__("Set the label for the folder selection.", "integrate-google-drive")}
                                        </p>

                                    </div>
                                </div>

                            </div>
                        }

                    </div>
                </div>
            }

            {/* Video Type */}
            {(isLMS || isMedia) &&
                <div className="settings-field">
                    <h4 className="settings-field-label">{wp.i18n.__('Video Type', 'integrate-google-drive')}</h4>

                    <div className="settings-field-content">
                        <ButtonGroup>

                            <Button
                                variant={allowEmbedPlayer ? 'primary' : 'secondary'}
                                size={'default'}
                                onClick={() => setEditData({...editData, allowEmbedPlayer: true})}
                                icon={`embed-video`}
                                text={wp.i18n.__('Embed', 'integrate-google-drive')}
                                label={wp.i18n.__('Embed', 'integrate-google-drive')}
                            />

                            <Button
                                variant={!allowEmbedPlayer ? 'primary' : 'secondary'}
                                size={'default'}
                                onClick={() => setEditData({...editData, allowEmbedPlayer: false})}
                                icon={`video-alt3`}
                                text={wp.i18n.__('Direct Media', 'integrate-google-drive')}
                                label={wp.i18n.__('Direct Media', 'integrate-google-drive')}
                            />
                        </ButtonGroup>

                        <p className="description">{wp.i18n.__('Select the video player style for the LMS module.', 'integrate-google-drive')}</p>

                        <div className="igd-notice igd-notice-info">
                            <div className="igd-notice-content">
                                <div style={{marginBottom: '5px'}}>
                                    <strong>{wp.i18n.__("Embed", "integrate-google-drive")}</strong> → {wp.i18n.__("Plays the video using Google Drive's native embed player, ensuring smooth playback without server load. This method requires setting the video permission to public.", "integrate-google-drive")}
                                </div>
                                <div>
                                    <strong>{wp.i18n.__("Direct Media", "integrate-google-drive")}</strong> → {wp.i18n.__("Streams the video through your server as a proxy, which may slow down loading. No permission changes are required for the video.", "integrate-google-drive")}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            }

            {/* Folder Files */}
            {(isListModule) &&
                <div className="settings-field field-folder-files">

                    <h4 className="settings-field-label">{wp.i18n.__("Generate Folder File Links", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={folderFiles}
                            onChange={() => setEditData({...editData, folderFiles: !folderFiles})}
                        />

                        <p className="description">{wp.i18n.__("Enable to generate links for the files inside the selected folders.", "integrate-google-drive")}</p>
                    </div>
                </div>
            }

            {/*--- Select sources wrap ---*/}
            {(!allFolders || !!isSelectFiles || !!isLMS) && !privateFolders && !acfDynamicFiles && !uploadFolderSelection &&
                <div ref={browserWrapRef} className="igd-select-sources-wrap">

                    {isWooCommerce !== 'download' && !isLMS && !isSelectFiles &&
                        <div className="source-title-wrap">
                            {!!sourceTitle && <h4 className="igd-select-sources-title">{sourceTitle}</h4>}
                            {!!sourceDescription &&
                                <p className="igd-select-sources-description">{sourceDescription}</p>}
                        </div>
                    }

                    {/*--- File Browser ---*/}
                    <App
                        isShortcodeBuilder={isModuleBuilder ? 'module-builder' : true}
                        shortcodeBuilderType={type}
                        isSelectFiles={isSelectFiles}
                        selectionType={selectionType}

                        isLMS={isLMS}
                        isWooCommerce={isWooCommerce}

                        selectedFolders={selectedFolders}
                        setSelectedFolders={setSelectedFolders}

                        initParentFolder={initFolder}
                    />

                    {/*--- Selected Folder List ---*/}
                    {!isInlineSelect && !isLMS &&
                        <div className="igd-selected-list">

                            <div className="igd-selected-list-header">

                                <span
                                    className="header-title">({folders.length}) {wp.i18n._n("Item Selected", "Items Selected", folders.length, "integrate-google-drive")}</span>

                                {!!folders && !!folders.length && <button
                                    className="igd-btn btn-danger"
                                    onClick={() => {
                                        setSelectedFolders([]);

                                        setEditData(editData => ({
                                            ...editData,
                                            folders: [],
                                            parentFolder: null,
                                            templateFolder: null,
                                        }));
                                    }}
                                >
                                    <span>{wp.i18n.__("Clear", "integrate-google-drive")}</span>
                                </button>}

                            </div>

                            {/* DND LIST (no external lib) */}
                            <div className="igd-dnd-list">
                                {selectedFolders.length > 0 &&
                                    getUniqueSorted().map((item, index) => {
                                        const {id, iconLink, name, accountId} = item;
                                        const isOver = overIndex === index;
                                        const isInvalidOver = invalidOverIndex === index;

                                        return (
                                            <div
                                                key={id}
                                                className={
                                                    "igd-dnd-item" +
                                                    (isOver ? " is-over" : "") +
                                                    (isInvalidOver ? " is-invalid-over" : "")
                                                }
                                                draggable
                                                onDragStart={(e) => handleDragStart(e, index)}
                                                onDragOver={(e) => handleDragOver(e, index)}
                                                onDrop={(e) => handleDrop(e, index)}
                                                onDragEnd={handleDragEnd}
                                            >
                                                <div className="selected-item">
                                                    {folders.length > 1 && (
                                                        <>
                                                            <span className="selected-item-index">{index + 1}.</span>
                                                            <svg
                                                                className="drag-file-item"
                                                                width="24"
                                                                height="24"
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                viewBox="0 0 24 24"
                                                                aria-hidden="true"
                                                                focusable="false"
                                                            >
                                                                <path
                                                                    d="M8 7h2V5H8v2zm0 6h2v-2H8v2zm0 6h2v-2H8v2zm6-14v2h2V5h-2zm0 8h2v-2h-2v2zm0 6h2v-2h-2v2z"></path>
                                                            </svg>
                                                        </>
                                                    )}

                                                    <div
                                                        className="file-item"
                                                        onClick={() => {
                                                            if (item.parents) {
                                                                setInitFolder((pervFolder) => ({
                                                                    ...pervFolder,
                                                                    id: item.parents[0],
                                                                    accountId: item.accountId,
                                                                }));
                                                            }
                                                        }}
                                                    >
                                                        {iconLink ? (
                                                            <img src={iconLink}/>
                                                        ) : (
                                                            <i className="dashicons dashicons-category"></i>
                                                        )}
                                                        <span className="item-name">
                                                            {!!name ? name : getRootFolders(item, accounts[accountId])}
                                                          </span>
                                                    </div>

                                                    <span
                                                        className="remove-item dashicons dashicons-no-alt"
                                                        onClick={() => {
                                                            setSelectedFolders((fs) => fs.filter((i) => i !== item));
                                                        }}
                                                    ></span>
                                                </div>
                                            </div>
                                        );
                                    })}
                            </div>

                            {(!folders || !folders.length) && <div className="no-files-message">
                                <i className="dashicons dashicons-info"></i>
                                <span>{wp.i18n.__("No items selected", "integrate-google-drive")}.</span>
                            </div>}

                            {/* Message */}
                            {isUploader && <p>
                                {wp.i18n.__("Choose a single upload folder for file uploads.", "integrate-google-drive")}

                                {!!folders.length &&
                                    <span>{wp.i18n.__('To change it, deselect the current folder and select a new one.', 'integrate-google-drive')}</span>}
                            </p>}

                            {isEmbed &&
                                <p>{wp.i18n.__("If folder is selected, all the files of the folder will be embedded.", "integrate-google-drive")}
                                    <br/>
                                    <b>Note:</b> {wp.i18n.__("Embed files must be public", "integrate-google-drive")}
                                </p>
                            }

                        </div>
                    }

                </div>
            }

        </div>
    )
}