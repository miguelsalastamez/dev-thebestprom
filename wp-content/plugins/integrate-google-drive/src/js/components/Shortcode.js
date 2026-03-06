import App from "./App";
import Uploader from "./App/Uploader";
import {initShortcode, isAudioVideoType, isFolder} from "../includes/functions";
import {AppProvider} from "../contexts/AppContext";
import List from "./Shortcode/List";

const {useEffect, useState} = React;

export default function IgdShortcode({data, isPreview}) {


    const [isLoading, setIsLoading] = useState(false);
    const [content, setContent] = useState('');

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

    const {
        id,

        // Type
        type,

        // Sources
        allFolders,
        privateFolders,
        folders = [],

        // Filters
        filters,

        // Advanced
        galleryLayout = 'justified',
        galleryAspectRatio = '1/1',
        galleryColumns = {
            xs: 1,
            sm: 2,
            md: 3,
            lg: 4,
            xl: 5,
        },
        galleryHeight = 300,
        galleryMargin = 5,
        galleryView = "rounded",
        galleryFolderView = "title",
        galleryOverlay = true,
        overlayDisplayType = 'hover',
        galleryOverlayTitle = true,
        galleryOverlayDescription = true,
        galleryOverlaySize,
        galleryImageSize = 'medium',
        galleryCustomSizeWidth,
        galleryCustomSizeHeight,

        view = 'search' === type ? 'list' : 'grid',
        lazyLoad = true,
        lazyLoadNumber = 100,
        lazyLoadType = 'pagination',
        showLastModified,
        showFileSizeField = true,
        showHeader = true,
        showRefresh = true,
        showSorting = true,
        showBreadcrumbs = true,
        searchBoxText = wp.i18n.__('Search for files & content', 'integrate-google-drive'),

        nextPrevious = true,
        rewindForward = false,
        allowEmbedPlayer = true,
        playlistStyle = 'grid',
        playlistItemDuration = true,
        playlistItemSize,
        playlistItemDate,
        showPlaylistToggle = true,

        showPlaylist = true,
        playlistThumbnail = true,
        playlistNumber = true,
        playlistAutoplay = true,
        openedPlaylist = true,
        playlistPosition = 'bottom',

        fileNumbers,
        sort,
        maxFiles,
        maxFileSize,
        minFileSize,

        notifications,


        // Uploader
        enableUploadDescription,
        uploadImmediately,
        isFormUploader,
        isRequired,
        isWooCommerceUploader,
        wcItemId,
        wcOrderId,
        wcProductId,
        uploadedFiles = [],
        initParentFolder,
        enableFolderUpload,
        overwrite,
        showUploadLabel,
        uploadLabelText = wp.i18n.__('Upload Files', 'integrate-google-drive'),
        uploadFileName,
        showUploadConfirmation = true,
        uploadConfirmationMessage = `<h3>${wp.i18n.__('Upload successful!', 'integrate-google-drive')}</h3> <p>${wp.i18n.__('Your file(s) have been uploaded. Thank you for your submission!', 'integrate-google-drive')}</p>`,
        uploadFolderSelection,
        uploadFolders = [],
        folderSelectionLabel = wp.i18n.__('Choose Upload Folder', 'integrate-google-drive'),

        // Slider
        sliderImageSize = 'medium',
        sliderCustomSizeWidth,
        sliderCustomSizeHeight,
        slideDescription,
        slideHeight = '300px',
        slidesPerPage = {
            xs: 1,
            sm: 2,
            md: 3,
            lg: 4,
            xl: 5,
        },
        slidesToScroll = 1,
        slideAutoplay = true,
        slideAutoplaySpeed = 3000,
        slideDots = true,
        slideArrows = true,
        slideGap = 5,

        account,
        nonce,

        linkListStyle = "default",
        defaultClickAction = 'view',
        linkButtonText = wp.i18n.__('View', 'integrate-google-drive'),
        listDownloadButtonText = wp.i18n.__('Download', 'integrate-google-drive'),
        listEditButtonText = wp.i18n.__('Edit', 'integrate-google-drive'),

        // Permissions
        preview = true,
        inlinePreview = 'list' !== type,
        allowPreviewPopout = true,
        showPreviewThumbnails,
        mediaPreview = 'embed',

        // Permissions
        copyLink,
        createDoc,
        edit,
        newFolder,
        rename,
        details,
        moveCopy: copy,
        moveCopy: move,
        canDelete,
        upload = isFormUploader,
        download = true,
        folderDownload,
        zipDownload,
        allowShare,
        viewSwitch = true,
        allowSearch = 'search' === type,
        fullTextSearch = true,
        initialSearchTerm = '',
        comment,
        commentMethod = 'facebook',
        photoProof,
        photoProofBtnText,
        photoProofMaxSelection,
        reviewEnableTags,
        reviewTags = defaultReviewTags,
        selection,
        showAccessDeniedMessage = true,
        displayLogin = true,
        accounts = {},
    } = data;

    let initFolders = null;

    if (!allFolders) {
        initFolders = folders;
    }

    // Handle Preview
    useEffect(() => {
        if (!isPreview) return;

        setIsLoading(true);

        wp.ajax.post('igd_get_shortcode_content', {
            id,
            nonce: igd.nonce,
        })
            .done((data) => setContent(data))
            .fail((error) => console.error(error))
            .always(() => {
                setIsLoading(false);

                setTimeout(() => {
                    initShortcode();
                }, 100);
            });

    }, [isPreview, data]);

    return (
        <>
            {isPreview && isLoading && <div className="igd-spinner spinner-large"></div>}
            {isPreview && !isLoading &&
                <div className="preview-inner" dangerouslySetInnerHTML={{__html: content}}></div>}

            {!isPreview &&
                <>
                    {/*--- Browser, Gallery ---*/}
                    {['browser', 'gallery', 'search', 'review'].includes(type) &&
                        <App
                            shortcodeId={id}
                            accounts={accounts}
                            account={account}
                            nonce={nonce}

                            galleryLayout={galleryLayout}
                            galleryAspectRatio={galleryAspectRatio}
                            galleryColumns={galleryColumns}

                            galleryOverlay={galleryOverlay}
                            overlayDisplayType={overlayDisplayType}
                            galleryOverlayTitle={galleryOverlayTitle}
                            galleryOverlayDescription={galleryOverlayDescription}
                            galleryOverlaySize={galleryOverlaySize}

                            galleryImageSize={galleryImageSize}
                            galleryCustomSizeWidth={galleryCustomSizeWidth}
                            galleryCustomSizeHeight={galleryCustomSizeHeight}

                            galleryHeight={galleryHeight}
                            galleryMargin={galleryMargin}
                            galleryView={galleryView}
                            galleryFolderView={galleryFolderView}
                            initParentFolder={initParentFolder}
                            allFolders={allFolders}
                            privateFolders={privateFolders}
                            initFolders={initFolders}
                            searchBoxText={searchBoxText}

                            filters={filters}

                            isList={'list' === view}
                            lazyLoad={lazyLoad}
                            lazyLoadNumber={lazyLoadNumber}
                            lazyLoadType={lazyLoadType}
                            showLastModified={showLastModified}
                            showFileSizeField={showFileSizeField}
                            showHeader={showHeader}
                            showRefresh={showRefresh}
                            showSorting={showSorting}
                            showBreadcrumbs={showBreadcrumbs}
                            fileNumbers={fileNumbers}
                            sort={sort}
                            shortcodeBuilderType={type}
                            initialSearchTerm={initialSearchTerm}

                            permissions={{
                                preview,
                                inlinePreview,
                                showPreviewThumbnails,
                                mediaPreview,
                                allowPreviewPopout,
                                copyLink,
                                newFolder,
                                rename,
                                move,
                                copy,
                                upload,
                                download,
                                folderDownload,
                                zipDownload,
                                details,
                                view: viewSwitch,
                                'delete': canDelete,
                                share: allowShare,
                                allowSearch,
                                createDoc,
                                edit,
                                fullTextSearch,
                                comment,
                                commentMethod,

                                photoProof,
                                photoProofBtnText,
                                photoProofMaxSelection,

                                reviewEnableTags,
                                reviewTags,
                            }}

                            notifications={notifications}
                            selection={selection}

                            isFormUploader={isFormUploader}
                            uploadFileName={uploadFileName}
                            showAccessDeniedMessage={showAccessDeniedMessage}
                            displayLogin={displayLogin}
                        />
                    }


                    {/* Uploader */}
                    {'uploader' === type &&
                        <AppProvider
                            value={{
                                shortcodeId: id,
                                accounts,
                                activeAccount: account,
                                folders,
                                filters,
                                maxFiles,
                                maxFileSize,
                                minFileSize,
                                enableFolderUpload,
                                isFormUploader,
                                isRequired,
                                showUploadLabel,
                                uploadLabelText,
                                uploadFileName,
                                isWooCommerceUploader,
                                wcItemId,
                                wcOrderId,
                                wcProductId,
                                notifications,
                                initUploadedFiles: uploadedFiles,
                                enableUploadDescription,
                                uploadImmediately,
                                overwrite,
                                showUploadConfirmation,
                                uploadConfirmationMessage,
                                nonce,
                                uploadFolderSelection,
                                uploadFolders,
                                folderSelectionLabel,
                                privateFolders,
                            }}
                        >
                            <Uploader/>
                        </AppProvider>
                    }

                    {/*--- List ----*/}
                    {/* Added view, download module backward compatibility */}
                    {['list', 'view', 'download'].includes(type) &&
                        <List
                            folders={folders}
                            linkListStyle={linkListStyle}
                            defaultClickAction={defaultClickAction}
                            linkButtonText={linkButtonText}
                            listDownloadButtonText={listDownloadButtonText}
                            listEditButtonText={listEditButtonText}
                            shortcodeId={id}
                            nonce={nonce}
                            notifications={notifications}
                            permissions={{
                                preview,
                                inlinePreview,
                                allowPreviewPopout,
                                download,
                                zipDownload,
                                edit,
                            }}
                        />
                    }

                </>
            }
        </>
    )
}