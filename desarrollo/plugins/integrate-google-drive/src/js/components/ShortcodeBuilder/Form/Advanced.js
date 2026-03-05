import {Tooltip} from "react-tooltip";
import ShortcodeBuilderContext from "../../../contexts/ShortcodeBuilderContext";
import GalleryOverlay from "./GalleryOverlay";
import {showProModal} from "../../../includes/ProModal";

const {
    ButtonGroup,
    Button,
    FormToggle,
    RangeControl,
    SelectControl,
    TextControl,
    __experimentalRadio: Radio,
    __experimentalRadioGroup: RadioGroup,
} = wp.components;

const {useState, useContext, useEffect} = React;

export default function Advanced() {
    const {isPro} = igd;

    const context = useContext(ShortcodeBuilderContext);

    const {editData, setEditData, isFormBuilder, isWooCommerce, isModuleBuilder} = context;

    const sorts = {
        name: wp.i18n.__('Name', "integrate-google-drive"),
        size: wp.i18n.__('Size', "integrate-google-drive"),
        created: wp.i18n.__('Created Date', "integrate-google-drive"),
        updated: wp.i18n.__('Modified Date', "integrate-google-drive"),
        random: wp.i18n.__('Random', "integrate-google-drive"),
    }

    const directions = {
        asc: wp.i18n.__('Ascending', "integrate-google-drive"),
        desc: wp.i18n.__('Descending', "integrate-google-drive"),
    };

    const galleryLayouts = [
        {
            key: 'justified',
            text: wp.i18n.__('Justified', "integrate-google-drive"),
            label: wp.i18n.__('Display Gallery Items in Justified Style', "integrate-google-drive"),
            icon: 'layout',
        },
        {
            key: 'grid',
            text: wp.i18n.__('Grid', "integrate-google-drive"),
            label: wp.i18n.__('Display Gallery Items in a Grid Style', "integrate-google-drive"),
            icon: 'grid-view',
        },
        {
            key: 'masonry',
            text: wp.i18n.__('Masonry', "integrate-google-drive"),
            label: wp.i18n.__('Display Gallery Items in a Masonry Style', "integrate-google-drive"),
            icon: 'screenoptions',
        },
    ];

    const {
        type = igd.isPro ? 'browser' : 'gallery',
        moduleWidth = '100%',
        moduleHeight = '',
        embedWidth = '100%',
        embedHeight,
        galleryHeight = 300,
        galleryMargin = 5,
        galleryColumns = {
            xs: 1,
            sm: 2,
            md: 3,
            lg: 4,
            xl: 5,
        },
        galleryView = 'rounded',
        galleryLayout = 'justified',
        galleryFolderView = 'title',
        galleryAspectRatio = '1/1',
        galleryImageSize = 'medium',
        galleryCustomSizeWidth,
        galleryCustomSizeHeight,

        showHeader = true,
        showBreadcrumbs = true,
        directImage = false,
        showFileName = false,
        allowEmbedPopout = true,
        embedType = 'readOnly',
        showRefresh = true,
        showSorting = true,

        showPlaylist = true,
        openedPlaylist = true,
        playlistAutoplay = true,
        playlistThumbnail = true,
        playlistNumber = true,
        playlistPosition = 'left',
        allowEmbedPlayer = true,
        showPlaylistToggle = true,
        nextPrevious = true,
        rewindForward = false,
        playlistStyle = 'grid',
        playlistItemDuration = true,
        playlistItemSize,
        playlistItemDate,

        sort = {
            sortBy: 'name',
            sortDirection: 'asc',
        },
        initialFilesSorting,
        view = 'list',
        lazyLoad = true,
        lazyLoadNumber = 100,
        lazyLoadType = 'pagination',
        showLastModified = false,
        defaultClickAction = 'view',
        linkListStyle = "default",

        searchBoxText = wp.i18n.__('Search for files & content', 'integrate-google-drive'),

        // Uploader
        uploadImmediately,
        uploadBtnText = wp.i18n.__('Upload Files', 'integrate-google-drive'),
        showUploadLabel,
        uploadLabelText = wp.i18n.__('Upload Files', 'integrate-google-drive'),
        showUploadConfirmation = true,
        uploadConfirmationMessage = `<h3>${wp.i18n.__('Upload successful!', 'integrate-google-drive')}</h3><p>${wp.i18n.__('Your file(s) have been uploaded. Thank you for your submission!', 'integrate-google-drive')}</p>`,
        uploadBoxDescription,

        slideHeight = '300px',
        sliderImageSize = 'medium',
        sliderCustomSizeWidth,
        sliderCustomSizeHeight,
        slidesPerPage = {
            xs: 1,
            sm: 2,
            md: 3,
            lg: 4,
            xl: 5,
        },
        slideAutoplay = true,
        slideAutoplaySpeed = 3000,
        slideDots = true,
        slideArrows = true,
        slideGap = 5,

        woocommerceRedirect,
        woocommerceAddPermission,

    } = editData;

    const isBrowser = 'browser' === type;
    const isReview = 'review' === type;
    const isUploader = 'uploader' === type;
    const isGallery = 'gallery' === type;
    const isSearch = 'search' === type;
    const isMedia = 'media' === type;
    const isEmbed = 'embed' === type;
    const isSlider = 'slider' === type;
    const isListModule = 'list' === type;

    //Render upload confirmation message editor
    const editorId = 'upload-confirmation-message';

    const editorConfig = {
        wpautop: true,
        toolbar1: 'formatselect,bold,italic,strikethrough,forecolor,backcolor,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,media,spellchecker,fullscreen,wp_adv',
        toolbar2: 'underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
        plugins: 'lists,fullscreen,paste,wpautoresize,wpdialogs,wpeditimage,wpgallery,wplink,wptextpattern,wpview,wordpress,wpemoji,media,textcolor,hr',
        menubar: false,
        branding: false,
        height: 150,
        wp_adv_height: 48,
        setup: editor => {
            editor.on('change', () => {
                const content = editor.getContent();
                setEditData(prevEditData => ({...prevEditData, uploadConfirmationMessage: content}));
            });
        }
    };

    const quicktagsConfig = {
        buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close',
    }

    const config = {
        tinymce: editorConfig,
        quicktags: quicktagsConfig,
        mediaButtons: true,
    };

    // Render classic editor for upload confirmation message
    useEffect(() => {
        const isDiviEditor = document.body.classList.contains('et-fb');

        if (isDiviEditor || isFormBuilder || !isUploader || !showUploadConfirmation || !isPro || !wp.editor) return;

        setTimeout(() => {
            wp.editor.remove(editorId);
            wp.domReady(() => wp.editor?.initialize(editorId, config))
        }, 100)

        return () => {
            if (document.getElementById(editorId)) {
                wp.editor.remove(editorId);
            }
        }

    }, [showUploadConfirmation]);

    // Device handler state
    const [columnDevice, setColumnDevice] = useState('lg');

    return (
        <div className="shortcode-module-body">

            {/* Module Container */}
            {!isWooCommerce && !isSlider && !isEmbed && !isListModule &&
                <div className="settings-field">

                    <h4 className="settings-field-label">{wp.i18n.__("Module Container", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <div className="settings-field-sub">

                            {/* Module Width */}
                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("Container Width", "integrate-google-drive")}</h4>
                                <div className="settings-field-content">
                                    <input
                                        type="text"
                                        value={moduleWidth}
                                        onChange={e => setEditData({...editData, moduleWidth: e.target.value})}
                                        placeholder="100%"
                                    />

                                    {/* Module Height */}
                                    <h4 className="settings-field-label">{wp.i18n.__("Container Height", "integrate-google-drive")}</h4>

                                    <input
                                        type="text"
                                        value={moduleHeight}
                                        onChange={e => setEditData({...editData, moduleHeight: e.target.value})}
                                    />

                                    <p className="description">
                                        {wp.i18n.__("Set the module container’s width and height using any valid CSS unit (e.g., 360px, 780px, 80%).", "integrate-google-drive")}
                                        <br/>
                                        {wp.i18n.__("Leave blank to use the default size.", "integrate-google-drive")}
                                    </p>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            }

            {isEmbed &&
                <>
                    {/* Embed Width */}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Embed iframe Width", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <input
                                type="text"
                                value={embedWidth}
                                onChange={e => setEditData({...editData, embedWidth: e.target.value})}
                                placeholder="100%"
                            />

                            <p className="description">{wp.i18n.__("Set embed iframe width. You can use any valid CSS unit (pixels, percentage), eg '360px', '780px', '100%'. Keep blank for default value.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                    {/* Embed Height */}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Embed iframe Height", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <input
                                type="text"
                                value={embedHeight}
                                onChange={e => setEditData({...editData, embedHeight: e.target.value})}
                                placeholder="auto"
                            />

                            <p className="description">{wp.i18n.__("Set embed iframe height. You can use any valid CSS unit (pixels, percentage), eg '360px', '780px', '100%'. Keep blank for default value.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                    {/* Show File Name */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Show File Name", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={showFileName}
                                onChange={() => setEditData({...editData, showFileName: !showFileName})}
                            />

                            <p className="description">{wp.i18n.__("Show/ hide the file name at the top of the file.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                    {/* Direct Media Display */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__('Direct Media Display', 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">

                            <FormToggle
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                data-tooltip-id={"direct-media-display-tooltip"}
                                checked={isPro && directImage}
                                onChange={() => {

                                    if (!isPro) {
                                        showProModal(wp.i18n.__('Upgrade to Pro to display Audio, Video and Images directly without embed.', 'integrate-google-drive'));
                                        return;
                                    }

                                    setEditData({...editData, directImage: !directImage})
                                }}
                                className={!isPro ? 'disabled' : ''}
                            />

                            {!isPro &&
                                <Tooltip
                                    id={"direct-media-display-tooltip"}
                                    effect="solid"
                                    place="right"
                                    className={"igd-tooltip"}
                                    variant={"warning"}
                                />
                            }


                            <p className="description">{wp.i18n.__('Display Audio, Video and Image files directly without embedding them into an iframe.', 'integrate-google-drive')}</p>

                            <div className="igd-notice igd-notice-info">
                                <div className="igd-notice-content">
                                    <p className="description">
                                        {wp.i18n.__('Occasionally, audio and video files may not play correctly within an embedded player. Enable this option to resolve the issue by displaying media files directly, bypassing the embed.', 'integrate-google-drive')}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    {/* Embed Type */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__('Embed Type', 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">

                            <ButtonGroup data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}>

                                <Button
                                    variant={'readOnly' === embedType ? 'primary' : 'secondary'}
                                    icon={`visibility`}
                                    label={wp.i18n.__('The document will open in read-only mode, preventing any changes.', 'integrate-google-drive')}
                                    text={wp.i18n.__('Read Only', 'integrate-google-drive')}
                                    onClick={() => {
                                        setEditData({...editData, embedType: 'readOnly'})
                                    }}
                                />

                                <Button
                                    variant={'editable' === embedType ? 'primary' : 'secondary'}
                                    icon={`edit`}
                                    label={wp.i18n.__('The document will open in editable mode, allowing users to make changes.', 'integrate-google-drive')}
                                    text={wp.i18n.__('Editable', 'integrate-google-drive')}
                                    onClick={() => {
                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to Pro to change the embed type.', 'integrate-google-drive'));
                                            return;
                                        }
                                        setEditData({...editData, embedType: 'editable'})
                                    }}
                                />

                                <Button
                                    variant={'fullEditable' === embedType ? 'primary' : 'secondary'}
                                    icon={`welcome-write-blog`}
                                    label={wp.i18n.__('The document will appear in editable mode with expanded toolbars and features.', 'integrate-google-drive')}
                                    text={wp.i18n.__('Full Editable', 'integrate-google-drive')}
                                    onClick={() => {
                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to Pro to change the embed type.', 'integrate-google-drive'));
                                            return;
                                        }
                                        setEditData({...editData, embedType: 'fullEditable'})
                                    }}
                                />

                            </ButtonGroup>

                            {!isPro &&
                                <Tooltip
                                    effect="solid"
                                    place="right"
                                    backgroundColor="#FF9F10"
                                    className={"igd-tooltip"}
                                />
                            }

                            <p className="description">{wp.i18n.__('Select the embed type for the selected documents.', 'integrate-google-drive')}</p>

                            <div className="igd-notice igd-notice-info">
                                <div className="igd-notice-content">
                                    <p>
                                        <strong>Read
                                            Only</strong> → {wp.i18n.__("The document will be displayed in a read-only mode.", "integrate-google-drive")}
                                    </p>
                                    <p>
                                        <strong>Editable</strong> → {wp.i18n.__("The document will be displayed in an editable mode.", "integrate-google-drive")}
                                    </p>
                                    <p>
                                        <strong>Full
                                            Editable</strong> → {wp.i18n.__("The document will be displayed in an editable mode with extended edit tool-bars and features.", "integrate-google-drive")}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    {/* Allow Pop-out */}
                    {'readOnly' === embedType &&
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__('Allow Pop-out', 'integrate-google-drive')}</h4>

                            <div className="settings-field-content">
                                <FormToggle
                                    checked={allowEmbedPopout}
                                    onChange={() => setEditData({...editData, allowEmbedPopout: !allowEmbedPopout})}
                                />

                                <p className="description">{wp.i18n.__('In the embed document view (e.g. a pdf) there is a pop-out button that when clicked will bring the user to the Google Drive view of the document, with full features to download, print, etc. ', 'integrate-google-drive')}</p>

                                <div className="igd-notice igd-notice-info">
                                    <div className="igd-notice-content">
                                        <p className="description">
                                            {wp.i18n.__('If the pop-out option is disabled, users will be able to view the embedded document only on your website and not on Google Drive.', 'integrate-google-drive')}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    }
                </>
            }

            {isGallery &&
                <>
                    {/* Layout */}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Layout", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <ButtonGroup
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                data-tooltip-id={'igd-pro-tooltip'}
                            >
                                {galleryLayouts.map(({key, text, label, icon}) => (
                                    <Button
                                        key={key}
                                        variant={key === galleryLayout ? 'primary' : 'secondary'}
                                        onClick={() => {
                                            if (!isPro) {
                                                showProModal(wp.i18n.__('Upgrade to change the gallery layout.', 'integrate-google-drive'));
                                                return;
                                            }
                                            setEditData({...editData, galleryLayout: key});
                                        }}
                                        disabled={!isPro && key !== 'justified'}
                                        text={text}
                                        label={label}
                                        icon={icon}
                                    />
                                ))}
                            </ButtonGroup>

                            <p className="description">{wp.i18n.__("Select the layout for the gallery.", "integrate-google-drive")}</p>

                            <div className="settings-field-sub">

                                {/* Columns */}
                                {'justified' !== galleryLayout &&
                                    <div className="settings-field">

                                        <h4 className="settings-field-label">{wp.i18n.__("Columns", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">

                                            <div className="column-devices">
                                                <ButtonGroup
                                                    data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                                >
                                                    {['xs', 'sm', 'md', 'lg', 'xl',].map(device => {

                                                            const label = device === 'xs' ? 'Mobile' : device === 'sm' ? 'Tablet' : device === 'md' ? 'Laptop' : device === 'lg' ? 'Desktop' : device === 'xl' ? 'Large Desktop' : '';

                                                            return (
                                                                <Button isPrimary={device === columnDevice}
                                                                        isSecondary={device !== columnDevice}
                                                                        onClick={() => {
                                                                            setColumnDevice(device)
                                                                        }}>
                                                                    <span>{label}</span>
                                                                </Button>
                                                            )
                                                        }
                                                    )}
                                                </ButtonGroup>

                                            </div>

                                            <RangeControl
                                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                                value={galleryColumns[columnDevice]}
                                                onChange={column => {
                                                    setEditData({
                                                        ...editData,
                                                        galleryColumns: {...galleryColumns, [columnDevice]: column}
                                                    });
                                                }}
                                                min={1}
                                                max={20}
                                                marks={[
                                                    {value: 1, label: '1'},
                                                    {value: 4, label: '4'},
                                                    {value: 8, label: '8'},
                                                    {value: 12, label: '12'},
                                                    {value: 16, label: '16'},
                                                    {value: 20, label: '20'},
                                                ]}
                                                allowReset={true}
                                                resetFallbackValue={
                                                    columnDevice === 'xs' ? 1 :
                                                        columnDevice === 'sm' ? 2 :
                                                            columnDevice === 'md' ? 3 :
                                                                columnDevice === 'lg' ? 4 :
                                                                    columnDevice === 'xl' ? 5 : 3
                                                }
                                            />

                                            {!isPro &&
                                                <Tooltip
                                                    effect="solid"
                                                    place="right"
                                                    backgroundColor="#FF9F10"
                                                    className={"igd-tooltip"}
                                                />
                                            }

                                            <p className="description">{wp.i18n.__('Set the number of columns to display on each device.', 'integrate-google-drive')}</p>
                                        </div>
                                    </div>
                                }

                                {/* Row Height */}
                                {'justified' === galleryLayout &&
                                    <div className="settings-field">

                                        <h4 className="settings-field-label">{wp.i18n.__("Row Height", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">

                                            <RangeControl
                                                value={galleryHeight}
                                                onChange={(galleryHeight) => setEditData({...editData, galleryHeight})}
                                                allowReset={true}
                                                resetFallbackValue={300}
                                                min={0}
                                                max={1000}
                                                marks={[
                                                    {value: 0, label: '0'},
                                                    {value: 1000, label: '1000'},
                                                ]}
                                                step={10}
                                            />

                                            <p className="description">{wp.i18n.__("The ideal height you want your grid rows to be. It won't set it exactly to this as plugin adjusts the row height to get the correct width. Leave empty for default value.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>
                                }

                                {/* Aspect Ratio */}
                                {'grid' === galleryLayout &&
                                    <div className="settings-field">

                                        <h4 className="settings-field-label">{wp.i18n.__("Aspect Ratio", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">

                                            <ButtonGroup>

                                                {
                                                    ['1/1', '3/2', '4/3', '9/16', '16/9', '21/9'].map(aspectRatio => {
                                                        return (
                                                            <Button isPrimary={aspectRatio === galleryAspectRatio}
                                                                    isSecondary={aspectRatio !== galleryAspectRatio}
                                                                    onClick={() => setEditData({
                                                                        ...editData,
                                                                        galleryAspectRatio: aspectRatio
                                                                    })}
                                                            >
                                                                <span>{aspectRatio.replace('/', ':')}</span>
                                                            </Button>
                                                        )
                                                    })
                                                }

                                            </ButtonGroup>

                                            <p className="description">{wp.i18n.__("Select the aspect ratio for the gallery items.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>
                                }
                            </div>

                        </div>
                    </div>

                    {/* Margin */}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Margin", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <RangeControl
                                value={galleryMargin}
                                onChange={(galleryMargin) => setEditData({...editData, galleryMargin})}
                                allowReset={true}
                                resetFallbackValue={5}
                                min={0}
                                max={100}
                                marks={[
                                    {value: 0, label: '0'},
                                    {value: 100, label: '100'},
                                ]}
                            />

                            <p className="description">{wp.i18n.__("The margin between each image in the gallery. Leave empty for default value.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                    {/* Thumbnail Size */}
                    <div className="settings-field field-gallery-image-size">

                        <h4 className="settings-field-label">{wp.i18n.__("Image Thumbnail Size", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <SelectControl
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                data-tooltip-id={'igd-pro-tooltip'}
                                value={galleryImageSize}
                                onChange={(galleryImageSize) => {
                                    if (!isPro) {
                                        showProModal(wp.i18n.__('Upgrade to change the gallery image size.', 'integrate-google-drive'));
                                        return;
                                    }

                                    setEditData({...editData, galleryImageSize})
                                }}

                                options={[
                                    {label: wp.i18n.__('Small - 300x300', 'integrate-google-drive'), value: 'small'},
                                    {label: wp.i18n.__('Medium - 600x400', 'integrate-google-drive'), value: 'medium'},
                                    {label: wp.i18n.__('Large - 1024x768', 'integrate-google-drive'), value: 'large'},
                                    {label: wp.i18n.__('Full', 'integrate-google-drive'), value: 'full'},
                                    {label: wp.i18n.__('Custom', 'integrate-google-drive'), value: 'custom'},
                                ]}
                                disabled={!isPro}
                            />

                            {!isPro &&
                                <Tooltip
                                    id={'igd-pro-tooltip'}
                                    effect="solid"
                                    place="right"
                                    variant={`warning`}
                                />
                            }

                            <p className="description">{wp.i18n.__("Select the thumbnail size for the gallery images.", "integrate-google-drive")}</p>

                            {'custom' === galleryImageSize &&
                                <div className="settings-field-sub">
                                    <div className="settings-field">
                                        <h4 className={'settings-field-label'}>{wp.i18n.__('Custom Size', 'integrate-google-drive')}</h4>

                                        <div className="settings-field-content">

                                            <div className="gallery-custom-size-wrap">
                                                <TextControl
                                                    value={galleryCustomSizeWidth}
                                                    onChange={(galleryCustomSizeWidth) => setEditData({
                                                        ...editData,
                                                        galleryCustomSizeWidth
                                                    })}
                                                    placeholder={wp.i18n.__('Width', 'integrate-google-drive')}
                                                    type={'number'}
                                                    min={0}
                                                />

                                                <TextControl
                                                    value={galleryCustomSizeHeight}
                                                    onChange={(galleryCustomSizeHeight) => setEditData({
                                                        ...editData,
                                                        galleryCustomSizeHeight
                                                    })}
                                                    placeholder={wp.i18n.__('Height', 'integrate-google-drive')}
                                                    type={'number'}
                                                    min={0}
                                                />
                                            </div>

                                            <p className="description">{wp.i18n.__("Set the custom thumbnail size width and height for the gallery images.", "integrate-google-drive")}</p>

                                        </div>
                                    </div>
                                </div>
                            }
                        </div>
                    </div>

                    {/* Thumbnail View */}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Thumbnail View", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <ButtonGroup
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                data-tooltip-id={'igd-pro-tooltip'}
                            >
                                <Button isPrimary={'rounded' === galleryView}
                                        isSecondary={'square' === galleryView}
                                        onClick={() => {

                                            if (!isPro) {
                                                showProModal(wp.i18n.__('Upgrade to change the gallery view.', 'integrate-google-drive'));
                                                return;
                                            }

                                            setEditData({...editData, galleryView: 'rounded'});
                                        }}
                                >
                                    <i className="dashicons dashicons-grid-view"></i>
                                    <span>{wp.i18n.__("Rounded", "integrate-google-drive")}</span>
                                </Button>

                                <Button
                                    isPrimary={'square' === galleryView}
                                    isSecondary={'rounded' === galleryView}
                                    disabled={!isPro}
                                    onClick={() => {
                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to change the gallery view.', 'integrate-google-drive'));
                                            return;
                                        }

                                        setEditData({...editData, galleryView: 'square'});
                                    }}
                                >
                                    <i className="dashicons dashicons-screenoptions"></i>
                                    <span>{wp.i18n.__("Square", "integrate-google-drive")}</span>
                                </Button>
                            </ButtonGroup>

                            <p className="description">{wp.i18n.__("Select the image thumbnail view style for the gallery.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                    {/* Folder View */}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Folder View", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <ButtonGroup
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                data-tooltip-id={'igd-pro-tooltip'}
                            >
                                <Button isPrimary={'title' === galleryFolderView}
                                        isSecondary={'thumbnail' === galleryFolderView}
                                        onClick={() => {
                                            if (!isPro) {
                                                showProModal(wp.i18n.__('Upgrade to change the gallery folder view.', 'integrate-google-drive'));
                                                return;
                                            }

                                            setEditData({...editData, galleryFolderView: 'title'});
                                        }}
                                >
                                    <i className="dashicons dashicons-info"></i>
                                    <span>{wp.i18n.__("Title", "integrate-google-drive")}</span>
                                </Button>

                                <Button isPrimary={'thumbnail' === galleryFolderView}
                                        isSecondary={'title' === galleryFolderView}
                                        disabled={!isPro}
                                        onClick={() => {
                                            if (!isPro) {
                                                showProModal(wp.i18n.__('Upgrade to change the gallery folder view.', 'integrate-google-drive'));
                                                return;
                                            }

                                            setEditData({...editData, galleryFolderView: 'thumbnail'});
                                        }}
                                >
                                    <i className="dashicons dashicons-format-gallery"></i>
                                    <span>{wp.i18n.__("Thumbnail", "integrate-google-drive")}</span>
                                </Button>
                            </ButtonGroup>

                            <p className="description">{wp.i18n.__("Select the folders view style for the gallery.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                    {/* Show Overlay */}
                    <GalleryOverlay editData={editData} setEditData={setEditData}/>

                </>
            }

            {/*--- File Browser View ---*/}
            {!isWooCommerce && (isBrowser || isReview || isSearch) &&
                <div className="settings-field">

                    <h4 className="settings-field-label">{wp.i18n.__("Browser View", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">

                        <ButtonGroup>
                            <Button
                                isPrimary={'grid' === view}
                                isSecondary={'list' === view}
                                onClick={() => setEditData({...editData, view: 'grid'})}
                            >
                                <i className="dashicons dashicons-grid-view"></i>
                                <span>{wp.i18n.__("Grid", "integrate-google-drive")}</span>
                            </Button>

                            <Button
                                isPrimary={'list' === view}
                                isSecondary={'grid' === view}
                                onClick={() => setEditData({...editData, view: 'list'})}
                            >
                                <i className="dashicons dashicons-list-view"></i>
                                <span>{wp.i18n.__("List", "integrate-google-drive")}</span>
                            </Button>
                        </ButtonGroup>

                        <p className="description">{wp.i18n.__("Select the file browser view.", "integrate-google-drive")}</p>

                        <div className="settings-field-sub">
                            {'list' === view &&
                                <div className="settings-field">
                                    {/* Show Last Modified Field */}
                                    <h4 className="settings-field-label">{wp.i18n.__("Show Last Modified Field", "integrate-google-drive")}</h4>

                                    <div className="settings-field-content">
                                        <FormToggle
                                            checked={showLastModified}
                                            onChange={() => setEditData({
                                                ...editData,
                                                showLastModified: !showLastModified
                                            })}
                                        />

                                        <p className="description">{wp.i18n.__("Show/ hide the file last modified date field in the list view.", "integrate-google-drive")}</p>
                                    </div>

                                </div>
                            }

                        </div>
                    </div>

                </div>
            }

            {/*--- Search Box Text ---*/}
            {isSearch &&
                <div className="settings-field">

                    <h4 className="settings-field-label">{wp.i18n.__("Search Box Text", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <input type="text" value={searchBoxText}
                               onChange={e => setEditData({
                                   ...editData,
                                   searchBoxText: e.target.value
                               })}/>
                        <p className="description">{wp.i18n.__("Set the search box text.", "integrate-google-drive")}</p>
                    </div>
                </div>
            }

            {/*--- Enable Lazy load ---*/}
            {!isWooCommerce && (isBrowser || isReview || isGallery) &&
                <div className="settings-field">
                    <h4 className="settings-field-label">
                        {wp.i18n.__("Enable Files Lazy Loading", "integrate-google-drive")}
                    </h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={lazyLoad}
                            onChange={() => setEditData({...editData, lazyLoad: !lazyLoad})}
                        />

                        <p className="description">
                            {wp.i18n.__(
                                "Enable lazy loading to improve performance by loading files only when needed.",
                                "integrate-google-drive"
                            )}
                        </p>

                        {lazyLoad && (
                            <div className="settings-field-sub">

                                {/* Loading Type */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">
                                        {wp.i18n.__("Lazy Load Method", "integrate-google-drive")}
                                    </h4>

                                    <div className="settings-field-content">
                                        <ButtonGroup>
                                            <Button
                                                isPrimary={lazyLoadType === 'pagination'}
                                                onClick={() =>
                                                    setEditData({...editData, lazyLoadType: 'pagination'})
                                                }
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                     viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M2.533 12c.8.8 1.467 1.467 2.267 2.267.4.4.933.933 1.333 1.333.267.267.267.534.134.934-.133.267-.4.534-.667.534s-.533-.133-.667-.267c-.667-.667-1.467-1.467-2.133-2.134-.667-.8-1.2-1.333-1.733-1.866-.533-.534-.533-1.2 0-1.733C2.267 9.733 3.6 8.533 4.8 7.2c.267-.133.533-.267.933-.133.267.133.533.267.667.667.133.4 0 .667-.267.8C4.933 9.6 3.867 10.667 2.933 11.733L2.533 12Z"
                                                        fill="#2FB44B"
                                                    />
                                                    <path
                                                        d="M21.467 12c-1.2-1.2-2.267-2.267-3.467-3.467-.4-.4-.4-.8-.133-1.067.267-.4.934-.533 1.334-.133.8.8 1.733 1.733 2.533 2.533.4.4.8.8 1.2 1.2.534.534.534 1.067 0 1.6C21.6 14 20.4 15.333 19.067 16.533c-.267.267-.8.267-1.067 0-.4-.267-.4-.8-.133-1.067.133-.133.266-.133.4-.266 1.066-1.067 2.133-2.134 3.2-3.2Z"
                                                        fill="#2FB44B"
                                                    />
                                                    <path
                                                        d="M12 13.067a1.066 1.066 0 1 1 0-2.134 1.066 1.066 0 0 1 0 2.134Z"
                                                        fill="#2FB44B"/>
                                                    <path
                                                        d="M8.133 13.067a1.067 1.067 0 1 1 0-2.134 1.067 1.067 0 0 1 0 2.134Z"
                                                        fill="#2FB44B"/>
                                                    <path
                                                        d="M16.133 13.067a1.067 1.067 0 1 1 0-2.134 1.067 1.067 0 0 1 0 2.134Z"
                                                        fill="#2FB44B"/>
                                                </svg>
                                                <span>{wp.i18n.__("Pagination", "integrate-google-drive")}</span>
                                            </Button>

                                            <Button
                                                isPrimary={lazyLoadType === 'button'}
                                                onClick={() =>
                                                    setEditData({...editData, lazyLoadType: 'button'})
                                                }
                                            >
                                                <i className="dashicons dashicons-plus"></i>
                                                <span>{wp.i18n.__("Load More Button", "integrate-google-drive")}</span>
                                            </Button>

                                            <Button
                                                isPrimary={lazyLoadType === 'scroll'}
                                                onClick={() =>
                                                    setEditData({...editData, lazyLoadType: 'scroll'})
                                                }
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                     viewBox="0 0 18 18" fill="none">
                                                    <path
                                                        d="M13.75 13.19c.58-.58 1.13-1.13 1.68-1.68.21-.21.45-.31.76-.23.53.14.73.76.39 1.19-.04.05-.08.09-.12.13-1 .95-1.95 1.9-2.9 2.85-.4.4-.82.4-1.22 0-.97-.97-1.94-1.94-2.91-2.91-.24-.24-.3-.56-.17-.85.13-.29.43-.47.74-.45.21.01.38.1.52.25.51.51 1.02 1.02 1.53 1.53v-.06c0-3.31 0-6.61 0-9.92 0-.46.29-.79.71-.82.42-.03.76.27.79.7v.09c0 3.27 0 6.54 0 9.81v.12Z"
                                                        fill="white"
                                                    />
                                                </svg>
                                                <span>{wp.i18n.__("Scroll", "integrate-google-drive")}</span>
                                            </Button>
                                        </ButtonGroup>

                                        <p className="description">
                                            {wp.i18n.__(
                                                "Choose how files should be loaded when lazy loading is enabled.",
                                                "integrate-google-drive"
                                            )}
                                        </p>

                                        <div className="igd-notice igd-notice-info loading-method-info">
                                            <div className="igd-notice-content">
                                                <p>
                                                    <code>Scroll</code> → {wp.i18n.__("Automatically loads more files as the user scrolls.", "integrate-google-drive")}
                                                </p>
                                                <p>
                                                    <code>Pagination</code> → {wp.i18n.__("Displays files in separate pages for easier navigation.", "integrate-google-drive")}
                                                </p>
                                                <p>
                                                    <code>Load More
                                                        Button</code> → {wp.i18n.__("Loads additional files when the button is clicked.", "integrate-google-drive")}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Lazy load count */}
                                <div className="settings-field">
                                    <h4 className="settings-field-label">
                                        {wp.i18n.__("Files per Load", "integrate-google-drive")}
                                    </h4>

                                    <div className="settings-field-content">
                                        <RangeControl
                                            value={lazyLoadNumber}
                                            onChange={(lazyLoadNumber) =>
                                                setEditData({...editData, lazyLoadNumber})
                                            }
                                            allowReset
                                            resetFallbackValue={100}
                                            min={0}
                                            max={500}
                                            marks={[
                                                {value: 0, label: "0"},
                                                {value: 500, label: "500"},
                                            ]}
                                            step={10}
                                        />

                                        <p className="description">
                                            {wp.i18n.__(
                                                "Set how many files to load each time lazy loading is triggered.",
                                                "integrate-google-drive"
                                            )}
                                        </p>
                                    </div>
                                </div>

                            </div>
                        )}
                    </div>
                </div>
            }

            {/*--- Show Header ---*/}
            {!isWooCommerce && (isBrowser || isReview || isGallery || isSearch) &&
                <div className="settings-field">

                    <h4 className="settings-field-label">{wp.i18n.__("Show Header", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={showHeader}
                            onChange={() => setEditData({...editData, showHeader: !showHeader})}
                        />

                        <p className="description">{wp.i18n.__("Show/ hide the file browser header.", "integrate-google-drive")}</p>

                        <div className="settings-field-sub">

                            {/* Show Breadcrumbs */}
                            {showHeader &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Show Breadcrumbs Navigation", "integrate-google-drive")}</h4>

                                    <div className="settings-field-content">
                                        <FormToggle
                                            checked={showBreadcrumbs}
                                            onChange={() => setEditData({
                                                ...editData,
                                                showBreadcrumbs: !showBreadcrumbs
                                            })}
                                        />

                                        <p className="description">{wp.i18n.__("Show/ hide the breadcrumbs folder navigation in the header.", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                            {/* Show Sorting */}
                            {!isSearch && showHeader &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Show Sorting Button", "integrate-google-drive")}</h4>

                                    <div className="settings-field-content">
                                        <FormToggle
                                            checked={showSorting}
                                            onChange={() => setEditData({
                                                ...editData,
                                                showSorting: !showSorting
                                            })}
                                        />

                                        <p className="description">{wp.i18n.__("Show/ hide the files sorting options button in the header", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                            {/* Show Refresh */}
                            {!isSearch && showHeader &&
                                <div className="settings-field">
                                    <h4
                                        className="settings-field-label">{wp.i18n.__("Show Refresh Button", "integrate-google-drive")}</h4>

                                    <div className="settings-field-content">
                                        <FormToggle
                                            checked={showRefresh}
                                            onChange={() => setEditData({
                                                ...editData,
                                                showRefresh: !showRefresh
                                            })}
                                        />

                                        <p className="description">{wp.i18n.__("Show/ hide the files refresh (sync) button in the header.", "integrate-google-drive")}</p>

                                        <div className="igd-notice igd-notice-info">
                                            <div className="igd-notice-content">
                                                <h5>
                                                    {wp.i18n.__("To refresh the module files automatically, append", "integrate-google-drive")}{" "}
                                                    <code>?module_refresh=1</code>{" "}
                                                    {wp.i18n.__("to your page URL.", "integrate-google-drive")}
                                                </h5>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            }


                        </div>

                    </div>
                </div>
            }

            {isMedia &&
                <>
                    {/*--- Show Next/ Previous ---*/}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Show Next & Previous", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={nextPrevious}
                                onChange={() => setEditData({...editData, nextPrevious: !nextPrevious})}
                            />

                            <p className="description">{wp.i18n.__("Show/hide the next & previous buttons in the player. Enables navigation between media items in the playlist.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                    {/*--- Show Rewind/ Fast Forward ---*/}
                    {!allowEmbedPlayer &&
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Show Rewind & Forward", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">
                                <FormToggle
                                    checked={rewindForward}
                                    onChange={() => setEditData({...editData, rewindForward: !rewindForward})}
                                />

                                <p className="description">{wp.i18n.__("Show/hide the rewind & forward buttons in the player. Allows users to quickly skip backward or forward in the video.", "integrate-google-drive")}</p>
                            </div>
                        </div>
                    }

                    {/*--- Show Playlist Button ---*/}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Show Playlist", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={showPlaylist}
                                onChange={() => setEditData({...editData, showPlaylist: !showPlaylist})}
                            />

                            <p className="description">{wp.i18n.__("Show/hide the playlist in the player.", "integrate-google-drive")}</p>

                            {showPlaylist &&
                                <div className="settings-field-sub">

                                    {/* Playlist Style */}
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__('Playlist Style', 'integrate-google-drive')}</h4>

                                        <div className="settings-field-content">
                                            <RadioGroup
                                                label="Player Style"
                                                checked={playlistStyle}
                                                onChange={playlistStyle => {
                                                    setEditData({...editData, playlistStyle})
                                                }}>
                                                <Radio
                                                    value="list">
                                                    <i className="dashicons dashicons-list-view"></i>
                                                    {wp.i18n.__('List', 'integrate-google-drive')}</Radio>
                                                <Radio
                                                    value="grid">
                                                    <i className="dashicons dashicons-grid-view"></i>
                                                    {wp.i18n.__('Grid', 'integrate-google-drive')}</Radio>
                                            </RadioGroup>

                                            <p className="description">{wp.i18n.__('Select the playlist style in the player.', 'integrate-google-drive')}</p>
                                        </div>
                                    </div>

                                    {/* Playlist Position */}
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__('Playlist Position', 'integrate-google-drive')}</h4>

                                        <div className="settings-field-content">
                                            <RadioGroup
                                                label="Player Position"
                                                checked={playlistPosition}
                                                onChange={playlistPosition => {
                                                    setEditData({...editData, playlistPosition})
                                                }}>
                                                <Radio
                                                    value="left">{wp.i18n.__('Left', 'integrate-google-drive')}</Radio>
                                                <Radio
                                                    value="right">{wp.i18n.__('Right', 'integrate-google-drive')}</Radio>
                                                <Radio
                                                    value="bottom">{wp.i18n.__('Bottom', 'integrate-google-drive')}</Radio>
                                            </RadioGroup>

                                            <p className="description">{wp.i18n.__('Select the playlist position in the player.', 'integrate-google-drive')}</p>


                                        </div>
                                    </div>

                                    {/* Opened Playlist */}
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Opened Playlist", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <FormToggle
                                                checked={openedPlaylist}
                                                onChange={() => setEditData({
                                                    ...editData,
                                                    openedPlaylist: !openedPlaylist
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Should be the playlist opened by default.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>

                                    {/* Playlist Toggle */}
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Show Playlist Toggle", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <FormToggle
                                                checked={showPlaylistToggle}
                                                onChange={() => setEditData({
                                                    ...editData,
                                                    showPlaylistToggle: !showPlaylistToggle
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Show/hide the playlist toggle button in the player controls.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>

                                    {/* Playlist Autoplay */}
                                    {!allowEmbedPlayer &&
                                        <div className="settings-field">
                                            <h4 className="settings-field-label">{wp.i18n.__("Playlist Autoplay", "integrate-google-drive")}</h4>

                                            <div className="settings-field-content">
                                                <FormToggle
                                                    checked={playlistAutoplay}
                                                    onChange={() => setEditData({
                                                        ...editData,
                                                        playlistAutoplay: !playlistAutoplay
                                                    })}
                                                />

                                                <p className="description">{wp.i18n.__("Start playing next item automatically in the playlist once the current item is ended.", "integrate-google-drive")}</p>
                                            </div>
                                        </div>
                                    }

                                    {/* Show Thumbnail */}
                                    {'list' === playlistStyle &&
                                        <div className="settings-field">
                                            <h4 className="settings-field-label">{wp.i18n.__("Show Thumbnail", "integrate-google-drive")}</h4>

                                            <div className="settings-field-content">
                                                <FormToggle
                                                    checked={playlistThumbnail}
                                                    onChange={() => setEditData({
                                                        ...editData,
                                                        playlistThumbnail: !playlistThumbnail
                                                    })}
                                                />

                                                <p className="description">{wp.i18n.__("Show/hide the thumbnail in the playlist.", "integrate-google-drive")}</p>
                                            </div>
                                        </div>
                                    }

                                    {/* Show Playlist Number */}
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Show Number Prefix", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <FormToggle
                                                checked={playlistNumber}
                                                onChange={() => setEditData({
                                                    ...editData,
                                                    playlistNumber: !playlistNumber
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Show/hide the numeric prefix in the playlist items.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>

                                    {/* Show Duration */}
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Show Duration", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <FormToggle
                                                checked={playlistItemDuration}
                                                onChange={() => setEditData({
                                                    ...editData,
                                                    playlistItemDuration: !playlistItemDuration
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Show /hide the duration of each item in the playlist.", "integrate-google-drive")}</p>

                                            <div className="igd-notice igd-notice-info">
                                                <div className="igd-notice-content">
                                                    <p><strong>Note
                                                        : </strong>{wp.i18n.__("Duration is only available for video files.", "integrate-google-drive")}
                                                    </p>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    {/* Show File Size */}
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Show File Size", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <FormToggle
                                                checked={playlistItemSize}
                                                onChange={() => setEditData({
                                                    ...editData,
                                                    playlistItemSize: !playlistItemSize
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Show /hide the file size of each item in the playlist.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>

                                    {/* Show Modified Date */}
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Show Modified Date", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <FormToggle
                                                checked={playlistItemDate}
                                                onChange={() => setEditData({
                                                    ...editData,
                                                    playlistItemDate: !playlistItemDate
                                                })}
                                            />

                                            <p className="description">{wp.i18n.__("Show /hide the modified date of each item in the playlist.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>

                                </div>
                            }

                        </div>
                    </div>
                </>
            }

            {isUploader &&
                <>

                    {/* Upload Button Text */}
                    {isWooCommerce &&
                        <div className="settings-field">

                            <h4 className="settings-field-label">{wp.i18n.__("Upload Button Text", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">
                                <input type="text" value={uploadBtnText}
                                       onChange={e => setEditData({
                                           ...editData,
                                           uploadBtnText: e.target.value
                                       })}/>
                                <p className="description">{wp.i18n.__("Enter the button text, which will trigger the upload box.", "integrate-google-drive")}</p>
                            </div>
                        </div>
                    }

                    {/* Show Upload Label */}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Show Upload Box Label", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <FormToggle
                                checked={showUploadLabel}
                                onChange={() => setEditData({
                                    ...editData,
                                    showUploadLabel: !showUploadLabel
                                })}
                            />

                            <p className="description">{wp.i18n.__("Show a label text above the upload box.", "integrate-google-drive")}</p>

                            {/* Upload Label Text */}
                            {showUploadLabel &&
                                <div className="settings-field-sub">
                                    <div className="settings-field">

                                        <h4 className="settings-field-label">{wp.i18n.__("Label Text", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">
                                            <input type="text" value={uploadLabelText}
                                                   onChange={e => setEditData({
                                                       ...editData,
                                                       uploadLabelText: e.target.value
                                                   })}/>
                                            <p className="description">{wp.i18n.__("Enter the uploader label text.", "integrate-google-drive")}</p>
                                        </div>
                                    </div>
                                </div>
                            }

                        </div>
                    </div>

                    {/* Upload Immediately */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Upload Immediately", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <FormToggle
                                checked={uploadImmediately}
                                onChange={() => setEditData({
                                    ...editData,
                                    uploadImmediately: !uploadImmediately
                                })}
                            />

                            <p className="description">{wp.i18n.__("Start uploading files immediately after they are selected.", "integrate-google-drive")}</p>

                            {!!isFormBuilder &&

                                <div className="igd-notice igd-notice-info">
                                    <div className="igd-notice-content">
                                        <strong>{wp.i18n.__('Note: ', 'integrate-google-drive')}</strong>
                                        {wp.i18n.__('For multi-step forms, must be enabled this option to proceed to the next step.', 'integrate-google-drive')}
                                    </div>
                                </div>
                            }

                        </div>
                    </div>

                    {/* Show Upload Confirmation */}
                    {!isFormBuilder && !uploadImmediately && !isWooCommerce &&
                        <div className="settings-field">

                            <h4 className="settings-field-label">{wp.i18n.__("Show Upload Confirmation", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">

                                <FormToggle
                                    checked={showUploadConfirmation}
                                    onChange={() => setEditData({
                                        ...editData,
                                        showUploadConfirmation: !showUploadConfirmation
                                    })}
                                />

                                <p className="description">{wp.i18n.__("Show/ hide the upload confirmation message after upload is complete.", "integrate-google-drive")}</p>

                                {/* Confirmation Message */}
                                {showUploadConfirmation &&
                                    <div className="settings-field-sub">
                                        <div
                                            className="settings-field field-upload-confirmation-message">

                                            <h4 className="settings-field-label">{wp.i18n.__("Confirmation Message", "integrate-google-drive")}</h4>

                                            <div className="settings-field-content">
                                                <p className="description">{wp.i18n.__("Enter the upload confirmation message.", "integrate-google-drive")}</p>

                                                <textarea
                                                    value={uploadConfirmationMessage}
                                                    id={'upload-confirmation-message'}
                                                    onChange={(e) => {
                                                        setEditData(data => ({
                                                            ...data,
                                                            uploadConfirmationMessage: e.target.value
                                                        }))
                                                    }}
                                                />


                                            </div>
                                        </div>
                                    </div>
                                }

                            </div>
                        </div>
                    }

                    {/* Upload Box Description */}
                    {isWooCommerce &&
                        <div className="settings-field field-upload-box-description">

                            <h4 className="settings-field-label">{wp.i18n.__("Upload Box Description", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">

                                <textarea
                                    value={uploadBoxDescription}
                                    id={'upload-confirmation-message'}
                                    className={`igd-textarea`}
                                    onChange={(e) => {
                                        setEditData(data => ({
                                            ...data,
                                            uploadBoxDescription: e.target.value
                                        }))
                                    }}
                                />

                                <p className="description">{wp.i18n.__("Enter a description for the upload box.", "integrate-google-drive")}</p>

                            </div>
                        </div>
                    }

                </>
            }

            {isListModule &&
                <>
                    {/*--- List style ---*/}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("List Style", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <ButtonGroup
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                className={`igd-link-list-style`}
                            >

                                <Button
                                    variant={linkListStyle === 'default' ? 'primary' : 'secondary'}
                                    label={wp.i18n.__('Default Style', 'integrate-google-drive')}
                                    onClick={() => {
                                        setEditData({...editData, linkListStyle: 'default'});
                                    }}
                                >
                                    <img
                                        src={`${igd.pluginUrl}/assets/images/shortcode-builder/list-styles/default.svg`}
                                        alt={'Default'}/>
                                    <span>{wp.i18n.__('Link', 'integrate-google-drive')}</span>
                                </Button>

                                <Button
                                    variant={linkListStyle == '1' ? 'primary' : 'secondary'}
                                    label={wp.i18n.__('List Style', 'integrate-google-drive')}
                                    onClick={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to Pro to change the list style.', 'integrate-google-drive'));
                                            return;
                                        }

                                        setEditData({...editData, linkListStyle: '1'});
                                    }}
                                    className={!isPro ? 'pro-feature disabled' : ''}
                                >
                                    <img
                                        src={`${igd.pluginUrl}/assets/images/shortcode-builder/list-styles/view-list.svg`}/>
                                    <span>{wp.i18n.__('List', 'integrate-google-drive')}</span>
                                </Button>


                                <Button
                                    variant={linkListStyle == '2' ? 'primary' : 'secondary'}
                                    label={wp.i18n.__('List Button', 'integrate-google-drive')}
                                    onClick={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to Pro to change the list style.', 'integrate-google-drive'));
                                            return;
                                        }

                                        setEditData({...editData, linkListStyle: '2'});
                                    }}
                                    className={!isPro ? 'pro-feature disabled' : ''}
                                >
                                    <img
                                        src={`${igd.pluginUrl}/assets/images/shortcode-builder/list-styles/view-button.svg`}/>
                                    <span>{wp.i18n.__('List Button', 'integrate-google-drive')}</span>
                                </Button>

                                <Button
                                    variant={linkListStyle == '3' ? 'primary' : 'secondary'}
                                    label={wp.i18n.__('List Alt', 'integrate-google-drive')}
                                    onClick={() => {
                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to Pro to change the list style.', 'integrate-google-drive'));
                                            return;
                                        }

                                        setEditData({...editData, linkListStyle: '3'});
                                    }}
                                    className={!isPro ? 'pro-feature disabled' : ''}
                                >
                                    <img
                                        src={`${igd.pluginUrl}/assets/images/shortcode-builder/list-styles/view-list-alt.svg`}/>
                                    <span>{wp.i18n.__('List Alt', 'integrate-google-drive')}</span>
                                </Button>

                                <Button
                                    variant={linkListStyle == '4' ? 'primary' : 'secondary'}
                                    label={wp.i18n.__('List Button Alt', 'integrate-google-drive')}
                                    onClick={() => {
                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to Pro to change the list style.', 'integrate-google-drive'));
                                            return;
                                        }

                                        setEditData({...editData, linkListStyle: '4'});
                                    }}
                                    className={!isPro ? 'pro-feature disabled' : ''}
                                >
                                    <img
                                        src={`${igd.pluginUrl}/assets/images/shortcode-builder/list-styles/view-button-alt.svg`}/>
                                    <span>{wp.i18n.__('List Button Alt', 'integrate-google-drive')}</span>
                                </Button>

                            </ButtonGroup>

                            {!isPro &&
                                <Tooltip
                                    anchorSelect={`.pro-feature`}
                                    effect="solid"
                                    place="top"
                                    variant={"warning"}
                                    className="igd-tooltip"
                                    content={wp.i18n.__('PRO feature', 'integrate-google-drive')}
                                />
                            }

                            <p className="description">{wp.i18n.__(`Select a style for the view links list.`, 'integrate-google-drive')}</p>

                        </div>
                    </div>

                    {/*--- Default Click Action ---*/}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Default Click Action", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <RadioGroup
                                label="Default Click Action"
                                checked={defaultClickAction}
                                onChange={defaultClickAction => {
                                    setEditData({...editData, defaultClickAction})
                                }}
                                className={`igd-default-click-action`}
                            >
                                <Radio value="view">
                                    <i className={`dashicons dashicons-visibility`}></i>
                                    <span>{wp.i18n.__('View', 'integrate-google-drive')}</span>
                                </Radio>
                                <Radio value="download">
                                    <i className={`dashicons dashicons-download`}></i>
                                    <span>{wp.i18n.__('Download', 'integrate-google-drive')}</span>
                                </Radio>
                                <Radio value="edit">
                                    <i className={`dashicons dashicons-edit`}></i>
                                    <span>{wp.i18n.__('Edit', 'integrate-google-drive')}</span>
                                </Radio>
                            </RadioGroup>

                            <p className="description">{wp.i18n.__("Set the default click action for the list items.", "integrate-google-drive")}</p>
                        </div>
                    </div>
                </>
            }

            {isSlider &&
                <>
                    {/* Slide Height */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Slide Height", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <input
                                type={`text`}
                                value={slideHeight}
                                onChange={(e) => {
                                    setEditData({...editData, slideHeight: e.target.value});
                                }}
                            />

                            <p className="description">{wp.i18n.__("Set the height of the carousel slide. You can use any valid CSS unit (pixels, percentage), eg '360px', '780px', '80%'. Keep blank for default value.", "integrate-google-drive")}</p>

                        </div>
                    </div>

                    {/* Image Size */}
                    <div className="settings-field field-gallery-image-size">

                        <h4 className="settings-field-label">{wp.i18n.__("Image Size", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <SelectControl
                                value={sliderImageSize}
                                onChange={(sliderImageSize) => setEditData({...editData, sliderImageSize})}
                                options={[
                                    {label: wp.i18n.__('Small - 300x300', 'integrate-google-drive'), value: 'small'},
                                    {label: wp.i18n.__('Medium - 600x400', 'integrate-google-drive'), value: 'medium'},
                                    {label: wp.i18n.__('Large - 1024x768', 'integrate-google-drive'), value: 'large'},
                                    {label: wp.i18n.__('Full', 'integrate-google-drive'), value: 'full'},
                                    {label: wp.i18n.__('Custom', 'integrate-google-drive'), value: 'custom'},
                                ]}
                            />

                            <p className="description">{wp.i18n.__("Select the thumbnail size for the slider images.", "integrate-google-drive")}</p>

                            {'custom' === sliderImageSize &&
                                <div className="settings-field-sub">
                                    <div className="settings-field">
                                        <h4 className={'settings-field-label'}>{wp.i18n.__('Custom Size', 'integrate-google-drive')}</h4>

                                        <div className="settings-field-content">

                                            <div className="gallery-custom-size-wrap">
                                                <TextControl
                                                    value={sliderCustomSizeWidth}
                                                    onChange={(sliderCustomSizeWidth) => setEditData({
                                                        ...editData,
                                                        sliderCustomSizeWidth
                                                    })}
                                                    placeholder={wp.i18n.__('Width', 'integrate-google-drive')}
                                                    type={'number'}
                                                    min={0}
                                                />

                                                <TextControl
                                                    value={sliderCustomSizeHeight}
                                                    onChange={(sliderCustomSizeHeight) => setEditData({
                                                        ...editData,
                                                        sliderCustomSizeHeight
                                                    })}
                                                    placeholder={wp.i18n.__('Height', 'integrate-google-drive')}
                                                    type={'number'}
                                                    min={0}
                                                />
                                            </div>

                                            <p className="description">{wp.i18n.__("Set the custom thumbnail size width and height for the slider images.", "integrate-google-drive")}</p>

                                        </div>
                                    </div>
                                </div>
                            }
                        </div>
                    </div>

                    {/* Slides Per Page */}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Slides per Page", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <div className="column-devices">
                                <ButtonGroup>
                                    {['xs', 'sm', 'md', 'lg', 'xl',].map(device => {

                                            const label = device === 'xs' ? 'Mobile' : device === 'sm' ? 'Tablet' : device === 'md' ? 'Laptop' : device === 'lg' ? 'Desktop' : device === 'xl' ? 'Large Desktop' : '';

                                            return (
                                                <Button isPrimary={device === columnDevice}
                                                        isSecondary={device !== columnDevice}
                                                        onClick={() => setColumnDevice(device)}>
                                                    <span>{label}</span>
                                                </Button>
                                            )
                                        }
                                    )}
                                </ButtonGroup>

                            </div>

                            <RangeControl
                                value={slidesPerPage[columnDevice]}
                                onChange={column => setEditData({
                                    ...editData,
                                    slidesPerPage: {...slidesPerPage, [columnDevice]: column}
                                })}
                                min={1}
                                max={12}
                                marks={[
                                    {value: 1, label: '1'},
                                    {value: 4, label: '4'},
                                    {value: 8, label: '8'},
                                    {value: 12, label: '12'},
                                ]}
                                allowReset={true}
                                resetFallbackValue={
                                    columnDevice === 'xs' ? 1 :
                                        columnDevice === 'sm' ? 2 :
                                            columnDevice === 'md' ? 3 :
                                                columnDevice === 'lg' ? 4 :
                                                    columnDevice === 'xl' ? 5 : undefined
                                }
                            />

                            <p className="description">{wp.i18n.__(`Set the number of slides per page for each device.`, "integrate-google-drive")}</p>

                            <p className="description">
                                {wp.i18n.__(`Mobile: < 576px, Tablet: ≥ 576px, Laptop: ≥ 768px, Desktop: ≥ 992px, Large Desktop: ≥ 1200px`, "integrate-google-drive")}
                            </p>

                        </div>
                    </div>

                    {/* Gap */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Gap", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <RangeControl
                                value={slideGap}
                                onChange={(slideGap) => setEditData({...editData, slideGap})}
                                min={0}
                                max={50}
                                marks={[
                                    {value: 0, label: '0'},
                                    {value: 50, label: '50'},
                                ]}
                                allowReset={true}
                                resetFallbackValue={5}
                            />

                            <p className="description">{wp.i18n.__("Set the gap between slides in pixels.", "integrate-google-drive")}</p>
                        </div>

                    </div>

                    {/* Slide Autoplay */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Slide Autoplay", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={slideAutoplay}
                                onChange={() => setEditData({
                                    ...editData,
                                    slideAutoplay: !slideAutoplay
                                })}
                            />

                            <p className="description">{wp.i18n.__(`Enable autoplay for the slider.`, "integrate-google-drive")}</p>


                            {slideAutoplay &&
                                <div className="settings-field-sub">
                                    <div className="settings-field">
                                        <h4 className="settings-field-label">{wp.i18n.__("Autoplay Speed", "integrate-google-drive")}</h4>

                                        <div className="settings-field-content">

                                            <RangeControl
                                                value={slideAutoplaySpeed}
                                                onChange={slideAutoplaySpeed => setEditData({
                                                    ...editData,
                                                    slideAutoplaySpeed
                                                })}
                                                min={1000}
                                                max={10000}
                                                marks={[
                                                    {value: 1000, label: '1'},
                                                    {value: 2000, label: '2'},
                                                    {value: 3000, label: '3'},
                                                    {value: 4000, label: '5'},
                                                    {value: 5000, label: '10'},
                                                ]}
                                                allowReset={true}
                                                resetFallbackValue={3}
                                            />

                                            <p className="description">{wp.i18n.__("Set the autoplay speed in seconds.", "integrate-google-drive")}</p>
                                        </div>

                                    </div>
                                </div>
                            }

                        </div>
                    </div>

                    {/* Slide Dots */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Slide Dots Navigation", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={slideDots}
                                onChange={() => setEditData({...editData, slideDots: !slideDots})}
                            />

                            <p className="description">{wp.i18n.__("Enable dots navigation for the slider.", "integrate-google-drive")}</p>

                        </div>
                    </div>

                    {/* Slide Arrows */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Slide Arrows Navigation", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={slideArrows}
                                onChange={() => setEditData({...editData, slideArrows: !slideArrows})}
                            />

                            <p className="description">{wp.i18n.__("Enable arrows navigation for the slider.", "integrate-google-drive")}</p>

                        </div>
                    </div>

                    {/* Show Overlay */}
                    <GalleryOverlay editData={editData} setEditData={setEditData}/>

                </>
            }

            {isWooCommerce === 'download' &&
                <>
                    {/* WooCommerce Redirection */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Enable Redirection", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                checked={woocommerceRedirect}
                                onChange={() => setEditData({
                                    ...editData,
                                    woocommerceRedirect: !woocommerceRedirect
                                })}
                            />

                            <p className="description">{wp.i18n.__(`Enable to redirect the user directly to the Google Drive file instead of downloading the file.`, "integrate-google-drive")}</p>

                        </div>
                    </div>

                    {/* WooCommerce Add Permission */}
                    {woocommerceRedirect &&
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Add User Permission", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">
                                <FormToggle
                                    checked={woocommerceAddPermission}
                                    onChange={() => setEditData({
                                        ...editData,
                                        woocommerceAddPermission: !woocommerceAddPermission
                                    })}
                                />

                                <p className="description">
                                    {wp.i18n.__("Enable to add permission for the purchase email address to the file.", "integrate-google-drive")}
                                </p>

                                <div className="igd-notice igd-notice-info">
                                    <div className="igd-notice-content">
                                        <p>
                                            <code>Note:</code> → {wp.i18n.__("The purchase email address must be a Gmail address; otherwise, permissions will not be granted.", "integrate-google-drive")}
                                        </p>
                                    </div>
                                </div>


                            </div>

                        </div>
                    }

                </>
            }

            {/*--- Sorting ---*/}
            {!isWooCommerce && (isBrowser || isReview || isGallery || isSearch || isMedia || isSlider) &&
                <div className="settings-field sort-field">
                    <h4 className="settings-field-label">{wp.i18n.__("Sorting", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">

                        <div className="sort-field-section-wrap">

                            {/* Sort By */}
                            <div className="sort-field-section">
                            <span
                                className="sort-field-section-label">{wp.i18n.__("SORT BY", "integrate-google-drive")}</span>

                                {
                                    Object.keys(sorts).map(key => (
                                        <div
                                            key={key}
                                            className={`sort-item ${sort.sortBy === key ? "active" : ""}`}
                                            onClick={() => setEditData({
                                                ...editData,
                                                sort: {...sort, sortBy: key}
                                            })}>
                                            <i className="dashicons dashicons-saved"></i>
                                            <span>{sorts[key]}</span>
                                        </div>
                                    ))
                                }

                            </div>

                            {/* Sort Direction */}
                            {'random' !== sort.sortBy &&
                                <div className="sort-field-section">
                                    <span
                                        className="sort-field-section-label">{wp.i18n.__("SORT DIRECTION", "integrate-google-drive")}</span>
                                    {
                                        Object.keys(directions).map(key => (
                                            <div
                                                key={key}
                                                className={`sort-item ${sort.sortDirection === key ? "active" : ""}`}
                                                onClick={() => setEditData({
                                                    ...editData,
                                                    sort: {...sort, sortDirection: key}
                                                })}
                                            >
                                                <i className="dashicons dashicons-saved"></i>
                                                <span>{directions[key]}</span>
                                            </div>
                                        ))
                                    }
                                </div>
                            }

                        </div>

                        <p className="description">{wp.i18n.__("Select the default sorting and direction for files.", "integrate-google-drive")}</p>

                        {!isSearch &&
                            <div className="settings-field-sub">
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Enable Initial Files Sorting", "integrate-google-drive")}</h4>

                                    <FormToggle
                                        checked={initialFilesSorting}
                                        onChange={() => setEditData({
                                            ...editData,
                                            initialFilesSorting: !initialFilesSorting
                                        })}
                                    />

                                    <p className="description">{wp.i18n.__("Enable to allow sort the initially selected files.", "integrate-google-drive")}</p>
                                </div>
                            </div>
                        }

                    </div>
                </div>
            }

        </div>
    )
}