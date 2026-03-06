import ShortcodeBuilderContext from "../../../contexts/ShortcodeBuilderContext";
import FilterUsers from "./FilterUsers";
import {showProModal} from "../../../includes/ProModal";
import {Tooltip} from "react-tooltip";
import CreatableSelect from "react-select/creatable";
import ReactSelect from "react-select";
import CommonUploadFields from "./Fields/CommonUploadFields";
import NamingTemplate from "./Fields/NamingTemplate";

const {FormToggle, ButtonGroup, Button} = wp.components;
const {useState, useEffect, useContext} = React;

export default function () {
    const {isPro, settings} = igd;

    const context = useContext(ShortcodeBuilderContext);
    const {editData, setEditData, isFormBuilder, isModuleBuilder} = context;

    const defaultReviewTags = [
        {
            label: wp.i18n.__('Approved', 'integrate-google-drive'),
            value: 'approved',
            color: '#22c55e', // modern green
        },
        {
            label: wp.i18n.__('Rejected', 'integrate-google-drive'),
            value: 'rejected',
            color: '#ef4444', // modern red
        },
        {
            label: wp.i18n.__('Pending', 'integrate-google-drive'),
            value: 'pending',
            color: '#f59e0b', // modern amber
        },
        {
            label: wp.i18n.__('Needs Review', 'integrate-google-drive'),
            value: 'needs_review',
            color: '#3b82f6', // modern blue
        },
    ];

    const [reviewTagOptions, setReviewTagOptions] = useState(defaultReviewTags);

    const restrictionPeriods = [
        {
            key: 'day',
            text: wp.i18n.__('Per Day', "integrate-google-drive"),
            label: wp.i18n.__('Restrict Downloads on a Daily Basis', "integrate-google-drive"),
            icon: 'schedule',
        },
        {
            key: 'week',
            text: wp.i18n.__('Per Week', "integrate-google-drive"),
            label: wp.i18n.__('Restrict Downloads on a Weekly Basis', "integrate-google-drive"),
            icon: 'schedule',
        },
        {
            key: 'month',
            text: wp.i18n.__('Per Month', "integrate-google-drive"),
            label: wp.i18n.__('Restrict Downloads on a Monthly Basis', "integrate-google-drive"),
            icon: 'schedule',
        },
    ];

    const {
        type,

        preview = true,
        inlinePreview = type !== 'list',
        allowPreviewPopout = !isMedia,
        showPreviewThumbnails,
        mediaPreview = settings.mediaPreview || 'embed',
        previewUsers = ['everyone'],

        rename = false,
        renameUsers = ['everyone'],

        newFolder = false,
        newFolderUsers = ['everyone'],

        moveCopy = false,
        moveCopyUsers = ['everyone'],

        canDelete = false,
        deleteUsers = ['everyone'],

        upload = isFormBuilder,
        uploadUsers = ['everyone'],

        photoProof = false,
        photoProofBtnText = wp.i18n.__('Submit Selections', 'integrate-google-drive'),
        photoProofMaxSelection,
        photoProofUsers = ['everyone'],

        download = 'review' !== type,
        folderDownload = false,
        zipDownload = false,
        showFileSizeField = true,
        downloadUsers = ['everyone'],

        enableDownloadLimits,
        restrictionPeriod = settings.restrictionPeriod || 'day',
        downloadLimits = settings.downloadLimits,
        downloadsPerFile = settings.downloadsPerFile,
        zipDownloadLimits = settings.zipDownloadLimits,
        bandwidthLimits = settings.bandwidthLimits,
        limitExcludedUsers = ['administrator'],
        limitExcludeAllUsers = [],
        limitExcludedExceptUsers = [],

        details = false,
        detailsUsers = ['everyone'],

        viewSwitch = true,

        allowShare = false,
        shareUsers = ['everyone'],

        createDoc = false,
        createDocumentUsers = ['everyone'],

        edit = false,
        editUsers = ['everyone'],

        copyLink = false,
        copyLinkUsers = ['everyone'],

        allowSearch = 'search' === type,
        searchUsers = ['everyone'],
        fullTextSearch = true,
        initialSearchTerm = '',

        comment = false,
        commentMethod = 'facebook',
        commentUsers = ['everyone'],

        linkListStyle = "default",
        linkButtonText = wp.i18n.__('View', 'integrate-google-drive'),
        listDownloadButtonText = wp.i18n.__('Download', 'integrate-google-drive'),
        listEditButtonText = wp.i18n.__('Edit', 'integrate-google-drive'),

        privateFolders,
        displayFor = privateFolders ? 'loggedIn' : 'everyone',
        displayUsers = ['everyone'],
        displayEveryone,
        displayExcept = [],
        showAccessDeniedMessage = true,
        displayLogin = true,
        enablePasswordProtection,
        password,
        reviewEnableTags,
        reviewTags = reviewTagOptions,
    } = editData;


    const isBrowser = 'browser' === type;
    const isMedia = 'media' === type;
    const isGallery = 'gallery' === type;
    const isSlider = 'slider' === type;
    const isSearch = 'search' === type;
    const isUploader = 'uploader' === type;
    const isEmbed = 'embed' === type;
    const isListModule = 'list' === type;
    const isReview = 'review' === type;

    // Users data
    const [userData, setUserData] = useState(null);

    const usersOptions = userData && [
        {label: wp.i18n.__('Everyone', "integrate-google-drive"), value: 'everyone'},

        ...Object.keys(userData.roles).map(key => {

            return {
                label: `${key} (role)`,
                value: key
            }
        }),

        ...userData.users.map(({username, email, id}) => {

            return {
                label: `${username} (${email})`,
                value: parseInt(id)
            }
        }),
    ];

    useEffect(() => {
        wp.ajax.post('igd_get_users_data', {
            nonce: igd.nonce,
        }).then((data) => setUserData(data));
    }, []);

    return (
        <div className="shortcode-module-body">

            {isBrowser || isMedia || isGallery || isSlider || isSearch &&
                <h3 className="settings-field-title">{wp.i18n.__("File Manipulation", "integrate-google-drive")}</h3>
            }

            {/* Upload */}
            {(isUploader || isBrowser) &&
                <div className="settings-field">
                    <h3 className="settings-field-label">
                        <i className="dashicons dashicons-upload"></i>
                        {wp.i18n.__("Upload", "integrate-google-drive")}
                    </h3>

                    <div className="settings-field-content">
                        {!isUploader &&
                            <FormToggle
                                checked={upload}
                                onChange={() => setEditData({...editData, upload: !upload})}
                            />
                        }

                        <p className="description">{wp.i18n.__("Allow users to upload files.", "integrate-google-drive")}</p>

                        <div className="settings-field-sub">

                            {(!isBrowser || upload) && <CommonUploadFields/>}

                            {/* Filter Upload Users */}
                            {upload && !!userData &&
                                <FilterUsers
                                    usersOptions={usersOptions}
                                    values={uploadUsers}
                                    onChange={selected => setEditData({
                                        ...editData,
                                        uploadUsers: [...selected.map(item => item.value)]
                                    })}
                                    description={wp.i18n.__("Select users & roles who can upload files.", "integrate-google-drive")}
                                />
                            }
                        </div>

                    </div>
                </div>
            }

            {/* Photo Proofing */}
            {(isGallery || isReview) &&
                <div className="settings-field">
                    <div className="settings-field-label">
                        <i className="dashicons dashicons-format-gallery"></i>
                        {isGallery ?
                            wp.i18n.__("Photo Proofing", "integrate-google-drive") :
                            wp.i18n.__("Proof Selections", "integrate-google-drive")
                        }
                    </div>

                    <div className="settings-field-content">

                        {isGallery &&
                            <>
                                <FormToggle
                                    data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                    data-tooltip-id={"photo-proofing-tooltip"}
                                    checked={isPro && photoProof}
                                    className={!isPro ? 'disabled' : ''}
                                    onChange={() => {
                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to Pro to photo proofing', 'integrate-google-drive'));
                                            return;
                                        }

                                        // If photo proofing is enabled, enable notification
                                        if (!photoProof) {
                                            setEditData(editData => ({...editData, enableNotification: true}));
                                        }

                                        setEditData(editData => ({...editData, photoProof: !photoProof}));

                                    }}
                                />

                                {!isPro &&
                                    <Tooltip
                                        id={"photo-proofing-tooltip"}
                                        effect="solid"
                                        place="right"
                                        variant={"warning"}
                                        className={"igd-tooltip"}
                                    />
                                }

                                <p className="description">{wp.i18n.__("Allow users to select images and send them to the admin.", "integrate-google-drive")}</p>
                            </>
                        }

                        {isPro && (photoProof || isReview) &&
                            <div className="settings-field-sub">

                                {/* Photo Proof Button Text */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Button Text", "integrate-google-drive")}</h4>

                                    <div className="settings-field-content">

                                        <input
                                            type={"text"}
                                            value={photoProofBtnText}
                                            onChange={e => {
                                                if (!isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable photo proofing', 'integrate-google-drive'));
                                                    return;
                                                }

                                                setEditData({
                                                    ...editData,
                                                    photoProofBtnText: e.target.value
                                                })

                                            }}
                                        />

                                        <p className="description">{wp.i18n.__("Enter the text for the proof selections submit button.", "integrate-google-drive")}</p>

                                    </div>
                                </div>

                                {/* Maximum Selection */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Maximum Selection", "integrate-google-drive")}</h4>

                                    <div className="settings-field-content">

                                        <input
                                            type={"number"}
                                            value={photoProofMaxSelection}
                                            onChange={e => {
                                                if (!isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to enable photo proofing', 'integrate-google-drive'));
                                                    return;
                                                }

                                                setEditData({
                                                    ...editData,
                                                    photoProofMaxSelection: e.target.value
                                                })

                                            }}
                                            min={0}
                                        />

                                        <p className="description">{wp.i18n.__("Enter the maximum number of items that can be selected. Leave empty for no limit.", "integrate-google-drive")}</p>

                                    </div>
                                </div>

                                {/* Enable Tags */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Enable Tags", "integrate-google-drive")}</h4>

                                    <div className="settings-field-content">
                                        <FormToggle
                                            checked={reviewEnableTags}
                                            onChange={() => setEditData({
                                                ...editData,
                                                reviewEnableTags: !reviewEnableTags
                                            })}
                                        />

                                        <p className="description">{wp.i18n.__('Allow users to add tags to their selections.', 'integrate-google-drive')}</p>

                                        {reviewEnableTags &&
                                            <div className="settings-field-sub">

                                                <div className="settings-field">

                                                    {/* Folder Download */}
                                                    <h4 className="settings-field-label">{wp.i18n.__("Available Tags", "integrate-google-drive")}</h4>

                                                    <div className="settings-field-content">

                                                        {(() => {

                                                            // Modern color palette (static, outside the component if reusable)
                                                            const MODERN_COLORS = [
                                                                '#22c55e', '#16a34a', '#4ade80', '#ef4444', '#dc2626',
                                                                '#f59e0b', '#eab308', '#fde047', '#3b82f6', '#2563eb',
                                                                '#60a5fa', '#a855f7', '#8b5cf6', '#c084fc', '#ec4899',
                                                                '#f472b6', '#10b981', '#0ea5e9', '#06b6d4', '#14b8a6',
                                                            ];

                                                            // Utility: Generate a fallback color if all presets are used
                                                            const generateRandomColor = () => {
                                                                const hue = Math.floor(Math.random() * 360);
                                                                return `hsl(${hue}, 70%, 60%)`;
                                                            };

                                                            // Utility: Generate a unique color
                                                            const generateUniqueColor = (usedColors) => {
                                                                return MODERN_COLORS.find(color => !usedColors.includes(color)) || generateRandomColor();
                                                            };

                                                            const handleChange = (selected) => {
                                                                setEditData(prev => ({
                                                                    ...prev,
                                                                    reviewTags: selected,
                                                                }));
                                                            };

                                                            const handleCreate = (inputValue) => {
                                                                const usedColors = reviewTags.map(tag => tag.color);
                                                                const color = generateUniqueColor(usedColors);

                                                                const newTag = {
                                                                    label: inputValue,
                                                                    value: inputValue.toLowerCase().replace(/\s+/g, '_'),
                                                                    color,
                                                                };

                                                                setReviewTagOptions(prev => [...prev, newTag]);

                                                                setEditData(prev => ({
                                                                    ...prev,
                                                                    reviewTags: [...reviewTags, newTag]
                                                                }));
                                                            };

                                                            return (
                                                                <CreatableSelect
                                                                    isMulti
                                                                    options={reviewTagOptions}
                                                                    value={reviewTags}
                                                                    className="igd-select"
                                                                    classNamePrefix="igd-select"
                                                                    onChange={handleChange}
                                                                    onCreateOption={handleCreate}
                                                                    placeholder={wp.i18n.__("Create and select the tags that users can use.", "integrate-google-drive")}
                                                                    isClearable={false}
                                                                />
                                                            );
                                                        })()}

                                                        <p className="description">{wp.i18n.__('Create and select the tags that users can use.', 'integrate-google-drive')}</p>

                                                    </div>
                                                </div>

                                            </div>
                                        }

                                    </div>
                                </div>

                                {/* Filter PhotoProof Users */}
                                {!!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={photoProofUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            photoProofUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can select files for proofing.", "integrate-google-drive")}
                                    />
                                }

                            </div>
                        }

                    </div>

                </div>
            }

            {/* Preview */}
            {(isBrowser || isSlider || isSearch || isGallery || isMedia || isListModule || isReview) &&
                <div className="settings-field">
                    <h4 className="settings-field-label">
                        <i className="dashicons dashicons-visibility"></i>
                        {wp.i18n.__("Preview", "integrate-google-drive")}
                    </h4>

                    <div className="settings-field-content">

                        {!isGallery && !isMedia &&
                            <>
                                <FormToggle
                                    checked={preview}
                                    onChange={() => setEditData({...editData, preview: !preview})}
                                />

                                <p className="description">{wp.i18n.__("Allow users to preview files.", "integrate-google-drive")}</p>
                            </>
                        }

                        {preview &&
                            <div className="settings-field-sub">

                                {/* Inline Preview */}
                                {!isSlider &&
                                    <div className="settings-field">

                                        {/* Inline Preview */}
                                        {!isMedia &&
                                            <>
                                                <h4 className="settings-field-label">{wp.i18n.__("Inline Preview", "integrate-google-drive")}</h4>

                                                <div className="settings-field-content">
                                                    <FormToggle
                                                        checked={inlinePreview}
                                                        onChange={() => setEditData({
                                                            ...editData,
                                                            inlinePreview: !inlinePreview
                                                        })}
                                                    />

                                                    <p className="description">{wp.i18n.__('Open preview in a pop-up lightbox. If disabled, the preview will be opened in Google Drive.', 'integrate-google-drive')}</p>
                                                </div>
                                            </>
                                        }

                                        {inlinePreview &&
                                            <>

                                                {/* Pop-out */}
                                                <h4 className="settings-field-label">{wp.i18n.__("Allow Pop-out", "integrate-google-drive")}</h4>

                                                <div className="settings-field-content">
                                                    <FormToggle
                                                        checked={allowPreviewPopout}
                                                        onChange={() => setEditData({
                                                            ...editData,
                                                            allowPreviewPopout: !allowPreviewPopout
                                                        })}
                                                    />

                                                    <p className="description">{wp.i18n.__('Allow users to preview the file in Google Drive\'s native viewer by clicking the pop-out button.', 'integrate-google-drive')}</p>
                                                </div>


                                                {/* Media Preview Mode */}
                                                <h4 className="settings-field-label">{wp.i18n.__("Media Preview Mode", "integrate-google-drive")}</h4>

                                                <div className="settings-field-content">
                                                    <ButtonGroup className="igd-button-group">
                                                        <Button
                                                            size={"default"}
                                                            variant={mediaPreview === 'direct' ? 'primary' : 'secondary'}
                                                            isPrimary={mediaPreview === 'direct'}
                                                            isSecondary={mediaPreview !== 'direct'}
                                                            onClick={() => setEditData({
                                                                ...editData,
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
                                                            onClick={() => setEditData({
                                                                ...editData,
                                                                mediaPreview: 'embed'
                                                            })}
                                                        >
                                                            {wp.i18n.__('Google Drive Embed', 'integrate-google-drive')}
                                                        </Button>
                                                    </ButtonGroup>

                                                    <p className="description">{wp.i18n.__("Choose how images, audio and video files are displayed: direct media (browser-native) or Google Drive's native embed viewer.", "integrate-google-drive")}</p>
                                                </div>

                                                {/* Hide Preview Thumbnails */}
                                                {!isMedia && !isListModule &&
                                                    <>
                                                        <h4 className="settings-field-label">{wp.i18n.__("Show Preview Thumbnails", "integrate-google-drive")}</h4>
                                                        <div className="settings-field-content">
                                                            <FormToggle
                                                                checked={showPreviewThumbnails}
                                                                onChange={() => setEditData({
                                                                    ...editData,
                                                                    showPreviewThumbnails: !showPreviewThumbnails
                                                                })}
                                                            />
                                                            <p className="description">{wp.i18n.__('Show/ hide the file thumbnails at the bottom of the lightbox preview.', 'integrate-google-drive')}</p>
                                                        </div>
                                                    </>
                                                }

                                            </>
                                        }

                                    </div>
                                }

                                {/* View Button text */}
                                {["2", "4"].includes(linkListStyle) && isListModule &&
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("View Button Text", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">

                                            <input
                                                type={'text'}
                                                value={linkButtonText}
                                                onChange={(e) => setEditData({
                                                    ...editData,
                                                    linkButtonText: e.target.value
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Enter the button text for the view button.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>
                                }

                                {/* Filter Preview Users */}
                                {!!userData && !isGallery && !isMedia &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={previewUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            previewUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can preview files.", "integrate-google-drive")}
                                    />
                                }

                            </div>
                        }

                    </div>

                </div>
            }

            {/* Download */}
            {(isBrowser || isMedia || isSlider || isGallery || isSearch || isListModule) &&
                <div className="settings-field">
                    <div className="settings-field-label">
                        <i className="dashicons dashicons-download"></i>
                        {wp.i18n.__("Download", "integrate-google-drive")}
                    </div>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={download}
                            onChange={() => setEditData({...editData, download: !download})}
                        />

                        <p className="description">{wp.i18n.__("Allow users to download files.", "integrate-google-drive")}</p>

                        {!!download &&
                            <div className="settings-field-sub">

                                {(isBrowser || isGallery || isSearch) &&
                                    <div className="settings-field">

                                        {/* Folder Download */}
                                        <h4 className="settings-field-label">{wp.i18n.__("Folder Download", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <FormToggle
                                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                                data-tooltip-id={"folder-download-tooltip"}
                                                checked={isPro && folderDownload}
                                                className={!isPro ? 'disabled' : ''}
                                                onChange={() => {

                                                    if (!isPro) {
                                                        showProModal(wp.i18n.__('Upgrade to Pro to enable folder download.', 'integrate-google-drive'));
                                                        return;
                                                    }

                                                    setEditData({...editData, folderDownload: !folderDownload})
                                                }}
                                            />

                                            {!isPro &&
                                                <Tooltip
                                                    id={"folder-download-tooltip"}
                                                    effect="solid"
                                                    place="right"
                                                    variant={"warning"}
                                                    className={"igd-tooltip"}
                                                />
                                            }

                                            <p className="description">{wp.i18n.__("Allow users to download entire folders as a ZIP file.", "integrate-google-drive")}</p>

                                        </div>

                                        {/* Zip Download */}
                                        <h4 className="settings-field-label">{wp.i18n.__("Multiple Files Download", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <FormToggle
                                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                                data-tooltip-id={"zip-download-tooltip"}
                                                checked={isPro && zipDownload}
                                                className={!isPro ? 'disabled' : ''}
                                                onChange={() => {

                                                    if (!isPro) {
                                                        showProModal(wp.i18n.__('Upgrade to Pro to enable multiple files download.', 'integrate-google-drive'));
                                                        return;
                                                    }

                                                    setEditData({...editData, zipDownload: !zipDownload})
                                                }}
                                            />

                                            {!isPro &&
                                                <Tooltip
                                                    id={"zip-download-tooltip"}
                                                    effect="solid"
                                                    place="right"
                                                    variant={"warning"}
                                                    className={"igd-tooltip"}
                                                />
                                            }

                                            <p className="description">{wp.i18n.__("Allow users to download multiple files as a ZIP file.", "integrate-google-drive")}</p>

                                            <div className="igd-notice igd-notice-warning">
                                                <p className="igd-notice-content">
                                                    {wp.i18n.__("The Google Drive API does not support ZIP creation on the fly. Therefore, the ZIP file needs to be created (temporarily) on your server. For that reason, it is not recommended to enable this setting when you are working with large files or folders.", "integrate-google-drive")}
                                                </p>
                                            </div>

                                        </div>

                                    </div>
                                }

                                {(isBrowser || isSearch) &&
                                    <div className="settings-field">

                                        {/* Show File Size Field */}
                                        <h4 className="settings-field-label">{wp.i18n.__("Show File Size Field", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <FormToggle
                                                checked={showFileSizeField}
                                                onChange={() => setEditData({
                                                    ...editData,
                                                    showFileSizeField: !showFileSizeField
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Show/ hide the file size field in the file list.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>
                                }

                                {/* Download Button text */}
                                {["2", "4"].includes(linkListStyle) && isListModule &&
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Download Button Text", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">

                                            <input
                                                type={'text'}
                                                value={listDownloadButtonText}
                                                onChange={(e) => setEditData({
                                                    ...editData,
                                                    listDownloadButtonText: e.target.value
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Enter the button text for the download button.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>
                                }

                                {/* Filter Download Users */}
                                {!!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={downloadUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            downloadUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can download files.", "integrate-google-drive")}
                                    />
                                }

                            </div>
                        }

                    </div>

                </div>
            }

            {isBrowser &&
                <>
                    {/* Delete */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">
                            <i className="dashicons dashicons-trash"></i>
                            {wp.i18n.__("Delete", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={canDelete}
                                onChange={() => setEditData({...editData, canDelete: !canDelete})}
                            />

                            <p className="description">{wp.i18n.__("Allow users to delete files and folders.", "integrate-google-drive")}</p>

                            <div className="settings-field-sub">

                                {/* Filter Delete Users */}
                                {canDelete && !!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={deleteUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            deleteUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can delete files & folders.", "integrate-google-drive")}
                                    />
                                }
                            </div>

                        </div>
                    </div>

                    {/* New Folder */}
                    <div className="settings-field">
                        <h3 className="settings-field-label">
                            <i className="dashicons dashicons-open-folder"></i>
                            {wp.i18n.__("New Folder", "integrate-google-drive")}</h3>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={newFolder}
                                onChange={() => setEditData({...editData, newFolder: !newFolder})}
                            />

                            <p className="description">{wp.i18n.__("Allow users to create new folders.", "integrate-google-drive")}</p>

                            <div className="settings-field-sub">

                                {/* Filter Delete Users */}
                                {newFolder && !!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={newFolderUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            newFolderUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can create folders.", "integrate-google-drive")}
                                    />
                                }
                            </div>

                        </div>
                    </div>

                    {/* Move/ Copy */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">
                            <i className="dashicons dashicons-admin-page"></i>
                            {wp.i18n.__("Move/ Copy", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={moveCopy}
                                onChange={() => setEditData({...editData, moveCopy: !moveCopy})}
                            />

                            <p className="description">{wp.i18n.__("Allow users to move/ copy files and folders.", "integrate-google-drive")}</p>

                            <div className="settings-field-sub">

                                {/* Filter Move/Copy Users */}
                                {moveCopy && !!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={moveCopyUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            moveCopyUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can move/ copy files.", "integrate-google-drive")}
                                    />
                                }
                            </div>

                        </div>
                    </div>

                    {/* Rename */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">
                            <i className="dashicons dashicons-edit"></i>
                            {wp.i18n.__("Rename", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={rename}
                                onChange={() => setEditData({...editData, rename: !rename})}
                            />

                            <p className="description">{wp.i18n.__("Allow users to rename files and folders.", "integrate-google-drive")}</p>

                            <div className="settings-field-sub">

                                {/* Filter Rename Users */}
                                {rename && !!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={renameUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            renameUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can rename files.", "integrate-google-drive")}
                                    />
                                }
                            </div>

                        </div>
                    </div>

                    {/* Share */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">
                            <i className="dashicons dashicons-share"></i>
                            {wp.i18n.__("Allow Share", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={allowShare}
                                onChange={() => setEditData({...editData, allowShare: !allowShare})}
                            />

                            <p className="description">{wp.i18n.__("Allow users to share files.", "integrate-google-drive")}</p>

                            <div className="settings-field-sub">

                                {/* Filter Share Users */}
                                {allowShare && !!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={shareUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            shareUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can share files.", "integrate-google-drive")}
                                    />
                                }
                            </div>

                        </div>
                    </div>

                    {/* Direct Link */}
                    <div className="settings-field">
                        <h3 className="settings-field-label">
                            <i className="dashicons dashicons-admin-links"></i>
                            {wp.i18n.__("Copy Links", "integrate-google-drive")}
                        </h3>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={copyLink}
                                onChange={() => setEditData({...editData, copyLink: !copyLink})}
                            />

                            <p className="description">{wp.i18n.__("Allow users to generate direct links for files on your website.", "integrate-google-drive")}</p>

                            <div className="settings-field-sub">

                                {/* Filter Share Users */}
                                {copyLink && !!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={copyLinkUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            copyLinkUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can share files.", "integrate-google-drive")}
                                    />
                                }
                            </div>

                        </div>
                    </div>

                    {/* View Details */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">
                            <i className="dashicons dashicons-info-outline"></i>
                            {wp.i18n.__("View Details", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={details}
                                onChange={() => setEditData({...editData, details: !details})}
                            />

                            <p className="description">{wp.i18n.__("Allow users to view file details (owner, created & modified date, etc).", "integrate-google-drive")}</p>

                            <div className="settings-field-sub">

                                {/* Filter Details Users */}
                                {details && !!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={detailsUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            detailsUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who view the file details.", "integrate-google-drive")}
                                    />
                                }
                            </div>

                        </div>
                    </div>

                    {/* Create Documents */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">
                            <i className="dashicons dashicons-plus"></i>
                            {wp.i18n.__("Create Documents", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={createDoc}
                                onChange={() => setEditData({...editData, createDoc: !createDoc})}
                            />

                            <p className="description">{wp.i18n.__("Allow users to create Google Docs and Office documents.", "integrate-google-drive")}</p>

                            <div className="settings-field-sub">

                                {/* Filter Edit Users */}
                                {createDoc && !!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={createDocumentUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            createDocumentUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can create documents.", "integrate-google-drive")}
                                    />
                                }
                            </div>

                        </div>
                    </div>

                </>
            }

            {/* Edit Documents */}
            {(isBrowser || isListModule) &&
                <div className="settings-field">
                    <h4 className="settings-field-label">
                        <i className="dashicons dashicons-edit-large"></i>
                        {wp.i18n.__("Edit Documents", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={edit}
                            onChange={() => setEditData({...editData, edit: !edit})}
                        />

                        <p className="description">{wp.i18n.__("Allow users to edit Google Docs and Office documents.", "integrate-google-drive")}</p>

                        {edit &&
                            <div className="settings-field-sub">

                                {/* Edit Button text */}
                                {["2", "4"].includes(linkListStyle) && isListModule &&
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Edit Button Text", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">

                                            <input
                                                type={'text'}
                                                value={listEditButtonText}
                                                onChange={(e) => setEditData({
                                                    ...editData,
                                                    listEditButtonText: e.target.value
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Enter the button text for the edit button.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>
                                }

                                {/* Filter Edit Users */}
                                {!!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={editUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            editUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can edit documents.", "integrate-google-drive")}
                                    />
                                }
                            </div>
                        }

                    </div>
                </div>
            }

            {/* Comments */}
            {(isBrowser || isGallery || isSearch) &&
                <div className="settings-field">
                    <h4 className="settings-field-label">
                        <i className="dashicons dashicons-admin-comments"></i>
                        {wp.i18n.__("Allow Comments", "integrate-google-drive")}
                    </h4>

                    <div className="settings-field-content">
                        <FormToggle
                            data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                            data-tooltip-id={"comments-tooltip"}
                            checked={isPro && comment}
                            className={!isPro ? 'disabled' : ''}
                            onChange={() => {

                                if (!isPro) {
                                    showProModal(wp.i18n.__('Upgrade to Pro to enable comments', 'integrate-google-drive'));
                                    return;
                                }

                                setEditData({...editData, comment: !comment})
                            }}
                        />

                        {!isPro &&
                            <Tooltip
                                id={"comments-tooltip"}
                                effect="solid"
                                place="right"
                                variant={"warning"}
                                className={"igd-tooltip"}
                            />
                        }

                        <p className="description">{wp.i18n.__("Allow users to comment on files while they view the file in lightbox preview.", "integrate-google-drive")}</p>

                        {comment &&
                            <div className="settings-field-sub">

                                {/* Comment method */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Comment Method", "integrate-google-drive")}</h4>

                                    <div className="settings-field-content">
                                        <ButtonGroup>
                                            <Button isPrimary={'facebook' === commentMethod}
                                                    isSecondary={'facebook' !== commentMethod}
                                                    onClick={() => setEditData({
                                                        ...editData,
                                                        commentMethod: 'facebook'
                                                    })}
                                            >
                                                <span>{wp.i18n.__("Facebook", "integrate-google-drive")}</span>
                                            </Button>

                                            <Button isPrimary={'disqus' === commentMethod}
                                                    isSecondary={'disqus' !== commentMethod}
                                                    onClick={() => setEditData({...editData, commentMethod: 'disqus'})}
                                            >
                                                <span>{wp.i18n.__("Disqus", "integrate-google-drive")}</span>
                                            </Button>

                                        </ButtonGroup>

                                        <p className="description">{wp.i18n.__("Select the comment method you want to use.", "integrate-google-drive")}</p>
                                    </div>
                                </div>

                                {/* Filter Comment Users */}
                                {!!userData &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={commentUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            commentUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can comments on files.", "integrate-google-drive")}
                                    />
                                }
                            </div>
                        }

                    </div>
                </div>
            }

            {/* View Switching */}
            {(isBrowser || isSearch) &&
                <div className="settings-field">
                    <h4 className="settings-field-label">
                        <i className="dashicons dashicons-screenoptions"></i>
                        {wp.i18n.__("View Switching", "integrate-google-drive")}
                    </h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={viewSwitch}
                            onChange={() => {
                                setEditData({...editData, viewSwitch: !viewSwitch})
                            }}
                        />

                        <p className="description">{wp.i18n.__("Allow users to switch between grid and list view of the file listing.", "integrate-google-drive")}</p>

                    </div>
                </div>
            }

            {/* Search */}
            {(isBrowser || isGallery || isMedia || isSearch || isReview) &&
                <div className="settings-field field-allow-search">
                    <h4 className="settings-field-label">
                        <i className="dashicons dashicons-search"></i>

                        {isSearch ?
                            wp.i18n.__("Search Options", "integrate-google-drive")
                            :
                            wp.i18n.__("Allow Search", "integrate-google-drive")
                        }
                    </h4>

                    <div className="settings-field-content">

                        {!isSearch &&
                            <>
                                <FormToggle
                                    data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                    data-tooltip-id={"search-tooltip"}
                                    checked={isPro && allowSearch}
                                    className={!isPro ? 'disabled' : ''}
                                    onChange={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to Pro to enable search', 'integrate-google-drive'));
                                            return;
                                        }

                                        setEditData({...editData, allowSearch: !allowSearch})
                                    }}
                                />

                                {!isPro &&
                                    <Tooltip
                                        id={"search-tooltip"}
                                        effect="solid"
                                        place="right"
                                        variant={"warning"}
                                        className={"igd-tooltip"}
                                    />
                                }

                                <p className="description">{wp.i18n.__("Allow users to search for files.", "integrate-google-drive")}</p>
                            </>
                        }

                        {allowSearch &&
                            <div className="settings-field-sub">

                                {/* FullText Search */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Full-text Search", "integrate-google-drive")}</h4>

                                    <div className="settings-field-content">
                                        <FormToggle
                                            checked={fullTextSearch}
                                            onChange={() => setEditData({
                                                ...editData,
                                                fullTextSearch: !fullTextSearch
                                            })}
                                        />

                                        <p className="description">{wp.i18n.__("Allow to search in file content, descriptions, tags and other metadata.", "integrate-google-drive")}</p>
                                    </div>
                                </div>

                                {/* Initial Search Term */}
                                <NamingTemplate
                                    value={initialSearchTerm}
                                    onUpdate={(initialSearchTerm) => setEditData(editData => ({
                                        ...editData,
                                        initialSearchTerm
                                    }))}
                                    type={'search'}
                                />

                                {/* Filter Search Users */}
                                {(!isSearch && !!userData) &&
                                    <FilterUsers
                                        usersOptions={usersOptions}
                                        values={searchUsers}
                                        onChange={selected => setEditData({
                                            ...editData,
                                            searchUsers: [...selected.map(item => item.value)]
                                        })}
                                        description={wp.i18n.__("Select users & roles who can search for files.", "integrate-google-drive")}
                                    />
                                }

                            </div>
                        }

                    </div>
                </div>
            }

            {/* Usage Limits */}
            {!isReview && !isUploader && (!isListModule || download) && !isEmbed &&
                <>
                    <h3 className="settings-field-title field-visibility">{wp.i18n.__("Usage Limits", "integrate-google-drive")}</h3>

                    {/* Enable Usage Limits */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Enable Usage Limits", 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                data-tooltip-id={'igd-pro-tooltip'}
                                checked={isPro && enableDownloadLimits}
                                onChange={() => {

                                    if (!isPro) {
                                        showProModal(wp.i18n.__('Upgrade to Pro to enable usage limits', 'integrate-google-drive'));
                                        return;
                                    }

                                    setEditData(data => ({...data, enableDownloadLimits: !enableDownloadLimits}));
                                }}
                                className={!isPro ? 'disabled' : ''}
                            />

                            {!isPro &&
                                <Tooltip
                                    id={'igd-pro-tooltip'}
                                    effect="solid"
                                    place="right"
                                    variant={"warning"}
                                    className={"igd-tooltip"}
                                />
                            }

                            <p className="description">
                                {wp.i18n.__("Enable download restrictions for users to control the download access for this module.", 'integrate-google-drive')}

                                <a href="https://softlabbd.com/docs/how-to-configure-usage-limit-settings"
                                   target="_blank" className="">
                                    {wp.i18n.__('Documentation', 'integrate-google-drive')}
                                    <i className="dashicons dashicons-editor-help"></i>
                                </a>
                            </p>

                            {settings.enableDownloadLimits &&
                                <div className="igd-notice igd-notice-info">
                                    <div className="igd-notice-content">
                                        <div>{wp.i18n.__("Usage limits are enabled globally, but you can also enable usage limits specifically for this module.", "integrate-google-drive")}</div>
                                    </div>
                                </div>
                            }

                        </div>
                    </div>

                    {(!!enableDownloadLimits || !isPro) && (
                        <>

                            {/* Restrictions Period */}
                            <div className="settings-field">

                                <h4 className="settings-field-label">{wp.i18n.__("Restrictions Period", "integrate-google-drive")}</h4>

                                <div className="settings-field-content">

                                    <ButtonGroup>
                                        {restrictionPeriods.map(({key, text, label, icon}) => (
                                            <Button
                                                key={key}
                                                variant={key === restrictionPeriod ? 'primary' : 'secondary'}
                                                onClick={() => {
                                                    setEditData({...editData, restrictionPeriod: key});
                                                }}
                                                text={text}
                                                label={label}
                                            />
                                        ))}
                                    </ButtonGroup>

                                    <p className="description">{wp.i18n.__("Select the period for which the download limits will be applied. This will restrict the number of downloads, bandwidth, and other limits based on the selected period.", "integrate-google-drive")}</p>

                                </div>
                            </div>

                            {/* Download Limits */}
                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("Download Limits", 'integrate-google-drive')}</h4>

                                <div className="settings-field-content">
                                    <input
                                        data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                        data-tooltip-id={'igd-pro-tooltip'}
                                        type="number"
                                        value={downloadLimits}
                                        onChange={(e) => setEditData(data => ({
                                            ...data,
                                            downloadLimits: e.target.value
                                        }))}
                                        className={`regular-text ${!isPro ? 'disabled' : ''}`}
                                        disabled={!isPro}
                                    />

                                    <p className="description">{wp.i18n.__("Set the maximum number of files a user can download within the selected period. Keep blank for unlimited.", 'integrate-google-drive')}</p>
                                </div>
                            </div>

                            {/* Download Limits per File */}
                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("Download Limits per File", 'integrate-google-drive')}</h4>

                                <div className="settings-field-content">
                                    <input
                                        data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                        data-tooltip-id={'igd-pro-tooltip'}
                                        type="number"
                                        className="regular-text"
                                        value={downloadsPerFile}
                                        onChange={(e) => setEditData(data => ({
                                            ...data,
                                            downloadsPerFile: e.target.value
                                        }))}
                                        disabled={!isPro}
                                    />

                                    <p className="description">{wp.i18n.__("Set the maximum number of times the same file can be downloaded by a user within the selected period. Keep blank for unlimited.", 'integrate-google-drive')}</p>
                                </div>
                            </div>

                            {/* ZIP Download Limits */}
                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("ZIP Download Limits", 'integrate-google-drive')}</h4>

                                <div className="settings-field-content">
                                    <input
                                        data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                        data-tooltip-id="igd-pro-tooltip"
                                        type="number"
                                        className="regular-text"
                                        value={zipDownloadLimits}
                                        onChange={(e) => setData({...data, zipDownloadLimits: e.target.value})}
                                        disabled={!isPro}
                                    />

                                    <p className="description">{wp.i18n.__("Set the number of ZIP files a user can download within the selected period. Leave blank for unlimited.", 'integrate-google-drive')}</p>
                                </div>
                            </div>

                            {/* Bandwidth Limit/Day */}
                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("Bandwidth Limits (in MB)", 'integrate-google-drive')}</h4>

                                <div className="settings-field-content">
                                    <input
                                        data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                        data-tooltip-id={'igd-pro-tooltip'}
                                        type="number" className="regular-text"
                                        value={bandwidthLimits}
                                        onChange={(e) => setEditData(data => ({
                                            ...data,
                                            bandwidthLimits: e.target.value
                                        }))}
                                        disabled={!isPro}
                                    />

                                    <p className="description">{wp.i18n.__("Set the maximum bandwidth (in MB) allowed per user during the selected period. Leave blank for unlimited.", 'integrate-google-drive')}</p>
                                </div>
                            </div>

                            {/* Filter Users & Roles */}
                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("Exclude Users & Roles", "integrate-google-drive")}</h4>

                                <div className="settings-field-content">
                                    {!!userData &&
                                        <div className="filter-users">
                                            <div className="filter-users-section-wrap">
                                                <ReactSelect
                                                    isDisabled={!isPro || limitExcludeAllUsers}
                                                    isMulti
                                                    placeholder={"Select users & roles"}
                                                    options={usersOptions}
                                                    value={usersOptions.filter(item => limitExcludedUsers.includes(item.value))}
                                                    onChange={selected => {
                                                        setEditData(data => ({
                                                            ...data,
                                                            limitExcludedUsers: [...selected.map(item => item.value)]
                                                        }));
                                                    }}
                                                    className="igd-select"
                                                    classNamePrefix="igd-select"
                                                    styles={{
                                                        multiValue: (base, state) => {
                                                            return state.data.value === 'administrator' ? {
                                                                ...base,
                                                                backgroundColor: "gray"
                                                            } : base;
                                                        },
                                                        multiValueLabel: (base, state) => {
                                                            return state.data.value === 'administrator'
                                                                ? {
                                                                    ...base,
                                                                    color: "white",
                                                                    paddingRight: 6
                                                                }
                                                                : base;
                                                        },
                                                        multiValueRemove: (base, state) => {
                                                            return state.data.value === 'administrator' ? {
                                                                ...base,
                                                                display: "none"
                                                            } : base;
                                                        }
                                                    }}
                                                />

                                                <p className="description">{wp.i18n.__("Select the roles and users who will be excluded from the download restrictions.", "integrate-google-drive")}</p>
                                            </div>

                                            <div className="filter-users-section-wrap">
                                                <div className="filter-users-section">
                                                    <span
                                                        className="filter-users-section-label">{wp.i18n.__("Exclude All :", "integrate-google-drive")} </span>

                                                    <FormToggle
                                                        checked={isPro && limitExcludeAllUsers}
                                                        onChange={() => {
                                                            setEditData(data => ({
                                                                ...data,
                                                                limitExcludeAllUsers: !limitExcludeAllUsers
                                                            }))
                                                        }}
                                                        className={!isPro ? 'disabled' : ''}
                                                    />
                                                </div>

                                                <div className="filter-users-section">
                                                    <span
                                                        className="filter-users-section-label">{wp.i18n.__("Except : ", "integrate-google-drive")}</span>

                                                    <ReactSelect
                                                        isDisabled={!isPro || !limitExcludeAllUsers}
                                                        isMulti
                                                        placeholder={"Select users & roles"}
                                                        options={usersOptions.filter(item => item.value !== 'everyone')}
                                                        value={usersOptions.filter(item => limitExcludedExceptUsers.includes(item.value))}
                                                        onChange={selected => setEditData(data => ({
                                                            ...data,
                                                            limitExcludedExceptUsers: [...selected.map(item => item.value)]
                                                        }))}
                                                        className="igd-select"
                                                        classNamePrefix="igd-select"
                                                    />
                                                </div>

                                                <p className="description">{wp.i18n.__("When activated, the download restrictions will be only applied to the selected roles and users.", "integrate-google-drive")}</p>
                                            </div>

                                        </div>
                                    }
                                </div>
                            </div>

                        </>
                    )}
                </>
            }

            {/* Password Protection */}
            {!isFormBuilder && isModuleBuilder?.type !== 'woocommerce' && (
                <>
                    <h3 className="settings-field-title field-visibility">{wp.i18n.__("Password Protection", "integrate-google-drive")}</h3>

                    {/* Enable Password Protection */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Enable Password Protection", 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                data-tooltip-id={'igd-pro-tooltip'}
                                checked={isPro && enablePasswordProtection}
                                onChange={() => {

                                    if (!isPro) {
                                        showProModal(wp.i18n.__('Upgrade to Pro to enable password protection.', 'integrate-google-drive'));
                                        return;
                                    }

                                    setEditData(data => ({
                                        ...data,
                                        enablePasswordProtection: !enablePasswordProtection
                                    }));
                                }}
                                className={!isPro ? 'disabled' : ''}
                            />

                            {!isPro &&
                                <Tooltip
                                    id={'igd-pro-tooltip'}
                                    place="right"
                                    variant={"warning"}
                                    className={"igd-tooltip"}
                                />
                            }

                            <p className="description">
                                {wp.i18n.__(`Enable password protection for this module. Users will need to enter the password to access the module.`, 'integrate-google-drive')}

                                <a href="" target="_blank" className="">
                                    {wp.i18n.__('Documentation', 'integrate-google-drive')}
                                    <i className="dashicons dashicons-editor-help"></i>
                                </a>
                            </p>

                            {enablePasswordProtection &&

                                <div className={`settings-field-sub`}>

                                    {/* Password */}
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Password", 'integrate-google-drive')}</h4>

                                        <div className="settings-field-content">
                                            <input
                                                type="text"
                                                value={password}
                                                onChange={(e) => setEditData(data => ({
                                                    ...data,
                                                    password: e.target.value
                                                }))}
                                            />

                                            <p className="description">{wp.i18n.__("Set the password for this module.", 'integrate-google-drive')}</p>
                                        </div>
                                    </div>

                                </div>
                            }

                            <div className="igd-notice igd-notice-info">
                                <div className="igd-notice-content">
                                    <h5>
                                        {wp.i18n.__("To auto-unlock the module automatically, append", "integrate-google-drive")}{" "}
                                        <code>?module_pass=password</code>{" "}
                                        {wp.i18n.__("to your page URL.", "integrate-google-drive")}
                                    </h5>
                                </div>
                            </div>

                            <div className="igd-notice igd-notice-info">
                                <div className="igd-notice-content">
                                    <h5>{wp.i18n.__("You can configure the password protected screen message from the settings page:", "integrate-google-drive")}</h5>
                                    <a href={`${igd.adminUrl}/admin.php?page=integrate-google-drive-settings&tab=security`}
                                       target="_blank" rel="noopener noreferrer">
                                        {wp.i18n.__("Settings > Security > Password Protected Screen", "integrate-google-drive")}
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </>
            )}

            {/* Visibility */}
            <h3 className="settings-field-title field-visibility">{wp.i18n.__("Visibility", "integrate-google-drive")}</h3>

            {/* Display For */}
            {
                <div className="settings-field">

                    <h4 className="settings-field-label">
                        <i className="dashicons dashicons-visibility"></i>
                        {wp.i18n.__("Display for", "integrate-google-drive")}
                    </h4>

                    <div className="settings-field-content">

                        <div className="filter-users-group">
                            <ButtonGroup>
                                <Button
                                    isPrimary={'everyone' === displayFor && !privateFolders}
                                    isSecondary={'everyone' !== displayFor || privateFolders}
                                    onClick={() => {

                                        if (privateFolders) {

                                            Swal.fire({
                                                title: wp.i18n.__("Private Files Enabled", "integrate-google-drive"),
                                                text: wp.i18n.__("You cannot set visibility to 'Everyone' when using private files as the source.", "integrate-google-drive"),
                                                icon: "warning",
                                                confirmButtonText: wp.i18n.__("OK", "integrate-google-drive"),
                                                toast: true,
                                                position: "center",
                                                showConfirmButton: true,
                                                timer: 5000,
                                                customClass: {
                                                    container: "igd-swal",
                                                }
                                            });

                                            return;
                                        }

                                        setEditData({...editData, displayFor: 'everyone'});

                                    }}
                                >
                                    <span>{wp.i18n.__("Everyone", "integrate-google-drive")}</span>
                                </Button>

                                <Button
                                    isPrimary={'loggedIn' === displayFor || privateFolders}
                                    isSecondary={'loggedIn' !== displayFor}
                                    onClick={() => setEditData({...editData, displayFor: 'loggedIn'})}
                                >
                                    <span>{wp.i18n.__("Logged In", "integrate-google-drive")}</span>
                                </Button>

                            </ButtonGroup>
                        </div>

                        <p className="description">{wp.i18n.__("Select the user roles and specific users who are allowed to access the module.", "integrate-google-drive")}</p>

                        {/* Filter users and roles */}
                        {(displayFor === 'loggedIn' || privateFolders) &&
                            <div className="settings-field-sub">
                                {!!userData ?
                                    <div className="filter-users">

                                        <h4 className={`filter-users-title`}>{wp.i18n.__("Filter Users & Roles:", "integrate-google-drive")}</h4>

                                        <div className="filter-users-section-wrap">
                                            <ReactSelect
                                                isDisabled={displayEveryone}
                                                isMulti
                                                placeholder={"Select users & roles"}
                                                options={usersOptions}
                                                value={usersOptions.filter(item => displayUsers.includes(item.value))}
                                                onChange={selected => setEditData({
                                                    ...editData,
                                                    displayUsers: [...selected.map(item => item.value)]
                                                })}
                                                className="igd-select"
                                                classNamePrefix="igd-select"
                                            />

                                            <p className="description">{wp.i18n.__("Select the users and user roles who can see the module.", "integrate-google-drive")}</p>
                                        </div>

                                        <div className="filter-users-section-wrap">
                                            <div className="filter-users-section">
                                            <span
                                                className="filter-users-section-label">{wp.i18n.__("Everyone :", "integrate-google-drive")} </span>
                                                <FormToggle
                                                    checked={displayEveryone}
                                                    onChange={() => setEditData({
                                                        ...editData,
                                                        displayEveryone: !displayEveryone
                                                    })}
                                                />
                                            </div>

                                            <div className="filter-users-section">
                                            <span
                                                className="filter-users-section-label">{wp.i18n.__("Except", "integrate-google-drive")}</span>
                                                <ReactSelect
                                                    isDisabled={!displayEveryone}
                                                    isMulti
                                                    placeholder={"Select users & roles"}
                                                    options={usersOptions.filter(item => item.value !== 'everyone')}
                                                    value={usersOptions.filter(item => displayExcept.includes(item.value))}
                                                    onChange={selected => setEditData({
                                                        ...editData,
                                                        displayExcept: [...selected.map(item => item.value)]
                                                    })}
                                                    className="igd-select"
                                                    classNamePrefix="igd-select"
                                                />
                                            </div>

                                            <p className="description">{wp.i18n.__("When activated, the module will be visible to everyone except for those specifically exempted.", "integrate-google-drive")}</p>
                                        </div>

                                    </div>
                                    :
                                    <div className="igd-spinner spinner-large"></div>
                                }
                            </div>
                        }

                    </div>
                </div>
            }

            {/* Show Access Denied Message */}
            {(privateFolders || displayFor === 'loggedIn') &&
                <div className="settings-field">

                    <h4 className="settings-field-label">
                        <i className="dashicons dashicons-welcome-comments"></i>
                        {wp.i18n.__("Show Denied Message", "integrate-google-drive")}
                    </h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={showAccessDeniedMessage}
                            onChange={() => setEditData({
                                ...editData,
                                showAccessDeniedMessage: !showAccessDeniedMessage
                            })}
                        />

                        <p className="description">{wp.i18n.__("Show a message to users who don't have access to the module.", "integrate-google-drive")}</p>

                        <div className="igd-notice igd-notice-info">
                            <div className="igd-notice-content">
                                <h5>{wp.i18n.__("You can configure the access denied message from the settings page:", "integrate-google-drive")}</h5>
                                <a href={`${igd.adminUrl}/admin.php?page=integrate-google-drive-settings&tab=security`}
                                   target="_blank" rel="noopener noreferrer">
                                    {wp.i18n.__("Settings > Security > Access Denied Message", "integrate-google-drive")}
                                </a>
                            </div>
                        </div>

                    </div>

                </div>
            }

            {/* Display Login Screen */}
            {(privateFolders || displayFor === 'loggedIn') &&
                <div className="settings-field">

                    <h4 className="settings-field-label">
                        <i className="dashicons dashicons-admin-users"></i>
                        {wp.i18n.__("Display Login Screen", "integrate-google-drive")}
                    </h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={displayLogin}
                            onChange={() => setEditData({...editData, displayLogin: !displayLogin})}
                        />

                        <p className="description">{wp.i18n.__("Display a login message for the modules when authentication is required..", "integrate-google-drive")}</p>

                        <div className="igd-notice igd-notice-info">
                            <div className="igd-notice-content">
                                <h5>{wp.i18n.__("You can configure the login message and login URL from the settings page:", "integrate-google-drive")}</h5>
                                <a href={`${igd.adminUrl}/admin.php?page=integrate-google-drive-settings&tab=security`}
                                   target="_blank" rel="noopener noreferrer">
                                    {wp.i18n.__("Settings > Security > Login Screen", "integrate-google-drive")}
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            }


        </div>
    )
}
