import {Tooltip} from "react-tooltip";

import AppContext from "../../../contexts/AppContext";
import preview from "../../../includes/preview";

import {
    deleteFiles,
    handleDownload,
    isFolder,
    isRootFolder,
    setListingView,
} from "../../../includes/functions";

import {handleShare} from "../../ContextMenu/ShareModal";

const {useContext, useState} = React;

export default function BodyAction() {

    const context = useContext(AppContext);

    let {
        shortcodeId,
        nonce,
        activeAccount,
        files = [],
        setFiles,
        activeFiles,
        setActiveFiles,
        activeFolder,
        setActiveFolder,
        setAllFiles,
        isShortcodeBuilder,
        isList,
        shortcodeBuilderType,
        permissions,
        setIsOptions,
        show,
        hideAll,
        getFiles,
        notifications,
        sort, setSort,
        showSorting,
        setIsUpload,
        showRefresh,
        activeFile,
        setIsList,
        isBulkSelect, setIsBulkSelect,
        isOptions,
        showDetails, setShowDetails,
        setActiveAccount,
        selection,
    } = context;

    // Check types
    const isBrowser = 'browser' === shortcodeBuilderType;
    const isUploader = 'uploader' === shortcodeBuilderType;
    const isGallery = 'gallery' === shortcodeBuilderType;
    const isEmbed = 'embed' === shortcodeBuilderType;
    const isSearch = 'search' === shortcodeBuilderType;
    const isMedia = 'media' === shortcodeBuilderType;
    const isSlider = 'slider' === shortcodeBuilderType;
    const isReview = 'review' === shortcodeBuilderType;

    // Sorts
    const sorts = {
        name: wp.i18n.__('Name', 'integrate-google-drive'),
        size: wp.i18n.__('Size', 'integrate-google-drive'),
        created: wp.i18n.__('Created', 'integrate-google-drive'),
        updated: wp.i18n.__('Modified', 'integrate-google-drive'),
    }
    const directions = {
        asc: wp.i18n.__('Ascending', 'integrate-google-drive'),
        desc: wp.i18n.__('Descending', 'integrate-google-drive'),
    }

    // Handle sort tooltip
    const [isSortTooltipOpen, setSortTooltipOpen] = useState(false);

    // Check should upload
    const shouldShowUpload = !isShortcodeBuilder && (!permissions || permissions.upload) && !!activeFolder && (!isRootFolder(activeFolder.id, activeAccount) || 'root' === activeFolder.id) && !activeFiles.length;

    // Check should download
    const shouldShowDownload = (!permissions || permissions.download) && activeFiles.length > 0 && !isReview;

    // Check should show delete
    const shouldShowDelete = (!permissions || permissions.delete) && !['shared', 'shared-drives', 'computers',].includes(activeFolder?.id) && activeFiles.length > 0;

    // Check should show refresh
    const shouldShowRefresh = showRefresh && (!isSearch || isShortcodeBuilder) && activeFolder;

    // Check should show preview
    const shouldShowPreview = (!permissions || permissions.preview) && activeFiles.filter(item => item.permissions?.canPreview && !isFolder(item)).length > 0 && !isReview;

    // Check should show view change
    const shouldShowViewChange = (!permissions || permissions.view) && !isGallery;

    // Check should show details
    const shouldShowDetails = !isShortcodeBuilder && (!permissions || permissions.details) && !isGallery && activeFiles.length === 1;

    // Check should show photo proof
    const shouldShowBulkSelect = !isShortcodeBuilder && (!permissions || (permissions['download'] && (permissions['zipDownload'])));

    // Check should show direct link
    const shouldShowDirectLink = !isShortcodeBuilder && !permissions && activeFiles.length === 1;

    return (
        <div className="body-action">

            {/*------------- Sort -------------*/}
            {showSorting && (!isSearch || isShortcodeBuilder) && files.length > 0 &&
                <button
                    type={`button`}
                    className={`body-action-item action-sort`}
                    title={wp.i18n.__('Sort', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Sort', 'integrate-google-drive')}
                    onClick={() => setSortTooltipOpen(!isSortTooltipOpen)}
                >
                    <img className={`action-item-icon`} src={`${igd.pluginUrl}/assets/images/file-browser/sort.svg`}
                         alt="Sort"/>

                    <span className={`action-item-label`}>{sorts[sort.sortBy]}</span>

                    <i className={`dashicons dashicons-arrow-${'asc' === sort.sortDirection ? 'down' : 'up'}`}></i>

                    <Tooltip
                        anchorSelect={`.body-action-item.action-sort`}
                        isOpen={isSortTooltipOpen}
                        setIsOpen={setSortTooltipOpen}
                        openEvents={['click']}
                        variant={'light'}
                        className="igd-sort-modal igd-tooltip"
                        place="bottom"
                        border={`1px solid #ddd`}
                        clickable={true}
                    >
                        <div className="igd-sort-modal-inner">

                            {/* Sort By */}
                            <div className="igd-sort-modal-inner-section">
                                    <span
                                        className="igd-sort-modal-inner-section-title">{wp.i18n.__('SORT BY', 'integrate-google-drive')}</span>

                                {
                                    Object.keys(sorts).map(key => (
                                        <div
                                            key={key}
                                            className={`sort-item ${sort.sortBy === key ? 'active' : ''}`}
                                            onClick={() => setSort({...sort, sortBy: key})}
                                        >
                                            <i className="dashicons dashicons-saved"></i>
                                            <span>{sorts[key]}</span>
                                        </div>
                                    ))
                                }

                            </div>

                            {/* Sort Direction */}
                            <div className="igd-sort-modal-inner-section">
                                    <span
                                        className="igd-sort-modal-inner-section-title">{wp.i18n.__('SORT DIRECTION', 'integrate-google-drive')}</span>

                                {
                                    Object.keys(directions).map(key => (
                                        <div
                                            key={key}
                                            className={`sort-item ${sort.sortDirection === key ? 'active' : ''}`}
                                            onClick={() => setSort(sort => ({...sort, sortDirection: key}))}
                                        >
                                            <i className="dashicons dashicons-saved"></i>
                                            <span>{directions[key]}</span>
                                        </div>
                                    ))
                                }
                            </div>

                        </div>
                    </Tooltip>
                </button>
            }

            {/*------------- Bulk Select -----------*/}
            {shouldShowBulkSelect && files.length > 0 &&
                <button
                    type={`button`}
                    className={`body-action-item action-bulk`}
                    onClick={() => {
                        setIsBulkSelect(isBulkSelect => !isBulkSelect);
                    }}
                    title={wp.i18n.__('Bulk Select', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Bulk Select', 'integrate-google-drive')}
                >
                    <span
                        className={`action-item-label`}>{isBulkSelect ? `${activeFiles.length} ${wp.i18n.__('Selected', 'integrate-google-drive')}` : wp.i18n.__('Bulk Select', 'integrate-google-drive')}</span>

                    <span className={`file-item-checkbox ${isBulkSelect ? 'checked' : ''}`}>
                        <span className={`box`}></span>
                    </span>

                </button>
            }

            {/*------------- Preview -----------*/}
            {shouldShowPreview &&
                <button
                    type={`button`}
                    className={`body-action-item action-preview`}
                    onClick={(e) => {
                        const previewItems = activeFiles.filter(item => item.permissions?.canPreview && !isFolder(item));

                        if (!previewItems.length) return;
                        preview(e, previewItems[0].id, previewItems, permissions, notifications, false, shortcodeId, nonce);
                    }}
                    title={wp.i18n.__('Preview', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Preview', 'integrate-google-drive')}
                >
                    <i className={`action-item-icon dashicons dashicons-visibility`}></i>

                    <span className={`action-item-label`}>{wp.i18n.__('Preview', 'integrate-google-drive')}</span>
                </button>
            }

            {/*--------- Submit Photo Proof & Review Selections  --------*/}
            {!isShortcodeBuilder && (isReview || (isGallery && permissions?.photoProof)) &&
                <button
                    type={`button`}
                    className={`body-action-item action-photo-proof ${!activeFiles.length ? 'disabled' : ''} photo-proofing-btn`}
                    onClick={() => {

                        if (activeFiles.length < 1) {
                            Swal.fire({
                                title: wp.i18n.__('Error', 'integrate-google-drive'),
                                text: wp.i18n.__('Please select at least one file.', 'integrate-google-drive'),
                                icon: 'error',
                                showConfirmButton: true,
                                confirmButtonText: wp.i18n.__('OK', 'integrate-google-drive'),
                                customClass: {
                                    container: 'igd-swal'
                                },
                            });

                            return;
                        }

                        if (permissions.photoProofMaxSelection > 0 && activeFiles.length > permissions.photoProofMaxSelection) {
                            Swal.fire({
                                title: wp.i18n.__('Error', 'integrate-google-drive'),
                                text: wp.i18n.sprintf(wp.i18n.__('You can not select more than %s files.', 'integrate-google-drive'), permissions.photoProofMaxSelection),
                                icon: 'error',
                                showConfirmButton: true,
                                confirmButtonText: wp.i18n.__('OK', 'integrate-google-drive'),
                                customClass: {container: 'igd-swal'},
                            });

                            return;
                        }

                        Swal.fire({
                            title: wp.i18n.sprintf(wp.i18n.__('Approve Selection (%s selected)', 'integrate-google-drive'), activeFiles.length),
                            text: wp.i18n.__('Send the selected files to the author with an optional message.', 'integrate-google-drive'),
                            input: 'textarea',
                            inputValue: selection?.message || '',
                            inputAttributes: {
                                autocapitalize: 'off',
                                placeholder: wp.i18n.__('Enter a message to send to the author', 'integrate-google-drive'),
                            },
                            showCancelButton: false,
                            confirmButtonText: selection ? wp.i18n.__('Update', 'integrate-google-drive') : wp.i18n.__('Approve', 'integrate-google-drive'),
                            showLoaderOnConfirm: true,
                            showCloseButton: true,
                            customClass: {container: 'igd-swal proof-swal'},
                            preConfirm: (message) => {

                                return wp.ajax.post('igd_photo_proof', {
                                    shortcodeId,
                                    message,
                                    selected: activeFiles,
                                    selection,
                                    page: window.location.href,
                                    nonce: nonce || igd.nonce,
                                }).done(() => {
                                    Swal.fire({
                                        title: wp.i18n.__('Proofing Submitted', 'integrate-google-drive'),
                                        text: wp.i18n.__('Your selection has been submitted to the author.', 'integrate-google-drive'),
                                        icon: 'success',
                                        timer: 4000,
                                        timerProgressBar: true,
                                        customClass: {container: 'igd-swal'},
                                    });
                                }).fail(error => {
                                    Swal.fire({
                                        title: wp.i18n.__('Error!', 'integrate-google-drive'),
                                        text: error?.message || wp.i18n.__('An error occurred while sending the files.', 'integrate-google-drive'),
                                        icon: 'error',
                                        showConfirmButton: true,
                                        confirmButtonText: 'OK',
                                        customClass: {container: 'igd-swal'},
                                    });
                                });
                            },
                        });

                    }}
                    title={wp.i18n.__('Photo Proofing', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Photo Proofing', 'integrate-google-drive')}
                >

                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M17.8666 6.53333C17.7333 6.66666 17.4666 6.8 17.3333 7.06666C13.8666 9.86666 10.3999 12.8 6.93328 15.6C6.66661 15.8667 6.26661 15.8667 5.99995 15.7333C4.79995 15.2 3.46661 14.5333 2.26661 14C1.33328 13.6 1.06661 12.6667 1.59995 12C1.73328 11.8667 1.86661 11.6 2.13328 11.6C5.86661 9.6 9.59995 7.6 13.3333 5.46666C15.8666 4.13333 18.2666 2.8 20.7999 1.46666C21.3333 1.2 21.7333 1.2 22.2666 1.46666C22.6666 1.73333 22.9333 2.26666 22.7999 2.8C21.5999 8.66666 20.2666 14.4 19.0666 20.2667C18.9333 21.2 17.9999 21.6 17.1999 21.2C16.3999 20.8 15.4666 20.4 14.6666 20C14.5333 19.8667 14.3999 19.8667 14.2666 20C13.0666 20.8 11.8666 21.7333 10.5333 22.5333C10.2666 22.5333 9.99995 22.6667 9.86661 22.6667C9.33328 22.8 8.93328 22.4 8.93328 21.8667C8.93328 20.5333 8.93328 19.3333 8.93328 18C8.93328 17.6 8.93328 17.2 8.93328 16.6667C8.93328 16.4 9.06661 16.1333 9.19995 16C11.7333 13.3333 14.1333 10.5333 16.6666 7.86666C17.0666 7.46666 17.4666 7.06666 17.7333 6.66666C17.7333 6.66666 17.8666 6.66666 17.8666 6.53333Z"
                            fill="#031E38"/>
                    </svg>

                    <span
                        className={`action-item-label`}>{selection ? wp.i18n.__('Update Selection', 'integrate-google-drive') : (permissions?.photoProofBtnText || wp.i18n.__('Submit Selections', 'integrate-google-drive'))}</span>

                    {activeFiles.length > 0 &&
                        <span className="selection-count">({activeFiles.length})</span>
                    }

                </button>
            }

            {/*------------- Download -----------*/}
            {shouldShowDownload &&
                <button
                    type={`button`}
                    className={`body-action-item action-download`}
                    onClick={() => {
                        if (activeFiles.length === 1 && !isFolder(activeFiles[0])) {
                            window.location.href = `${igd.ajaxUrl}?action=igd_download&id=${activeFiles[0].id}&accountId=${activeFiles[0]['accountId']}&shortcodeId=${shortcodeId}&nonce=${nonce || igd.nonce}`;
                        } else {
                            handleDownload(true, activeFiles, activeFile, permissions, shortcodeId, nonce);
                        }
                    }}
                    title={wp.i18n.__('Download', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Download', 'integrate-google-drive')}
                >

                    <i className="dashicons dashicons-download action-item-icon"></i>

                    <span className={`action-item-label`}>{wp.i18n.__('Download', 'integrate-google-drive')}</span>
                </button>
            }

            {/*------------- Direct Link -----------*/}
            {shouldShowDirectLink &&
                <button
                    type={`button`}
                    className={`body-action-item action-direct-link`}
                    onClick={() => handleShare(activeFiles[0], true)}
                    title={wp.i18n.__('Generate Direct Link', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Generate Direct Link', 'integrate-google-drive')}
                >
                    <i className={`action-item-icon dashicons dashicons-admin-links`}/>

                    <span className={`action-item-label`}>{wp.i18n.__('Direct Link', 'integrate-google-drive')}</span>
                </button>
            }

            {/*------------- Delete -----------*/}
            {shouldShowDelete &&
                <button
                    type={`button`}
                    className={`body-action-item action-delete`}
                    onClick={() => deleteFiles({
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
                    })}
                    title={wp.i18n.__('Delete', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Delete', 'integrate-google-drive')}
                >
                    <i className={`action-item-icon dashicons dashicons-trash`}/>

                    <span className={`action-item-label`}>{wp.i18n.__('Delete', 'integrate-google-drive')}</span>
                </button>
            }

            {/*------------- Upload -----------*/}
            {shouldShowUpload &&
                <button
                    type={`button`}
                    className={`body-action-item action-upload`}
                    onClick={() => {
                        setIsUpload(isUpload => {

                            if (!isUpload) {
                                setTimeout(() => {
                                    const browseBtn = document.querySelector('.igd-file-uploader-buttons .browse-files');

                                    if (browseBtn) {
                                        browseBtn.click();
                                    }

                                }, 100);
                            }

                            return !isUpload;
                        });


                    }}
                    title={wp.i18n.__('Upload', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Upload', 'integrate-google-drive')}
                >
                    <i className={`action-item-icon dashicons dashicons-cloud-upload`}/>
                    <span className={`action-item-label`}>{wp.i18n.__('Upload', 'integrate-google-drive')}</span>
                </button>
            }

            {/*------------- Refresh -----------*/}
            {shouldShowRefresh &&
                <button
                    type={`button`}
                    className={`body-action-item action-update`}
                    onClick={() => {
                        if (activeFolder) {
                            getFiles(activeFolder, 'refresh');
                        }
                    }}
                    title={wp.i18n.__('Refresh', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Refresh', 'integrate-google-drive')}
                >
                    <i className={`action-item-icon dashicons dashicons-update`}/>

                    <span className={`action-item-label`}>{wp.i18n.__('Refresh', 'integrate-google-drive')}</span>
                </button>
            }

            {/*------------- Details -----------*/}
            {shouldShowDetails &&
                <button
                    type={`button`}
                    className={`body-action-item action-details`}
                    onClick={() => {
                        setShowDetails(showDetails => !showDetails);
                        localStorage.setItem('igd_show_details', showDetails ? 1 : 0);
                    }}
                    title={wp.i18n.__('Details', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Details', 'integrate-google-drive')}
                >
                    <i className={`action-item-icon dashicons dashicons-info-outline`}></i>
                </button>
            }

            {/*------------- Listing View -----------*/}
            {shouldShowViewChange && files.length > 0 &&
                <button
                    type={`button`}
                    className={`body-action-item action-view`}
                    onClick={() => {
                        setIsList(!isList);
                        setListingView(shortcodeId, isList ? 'grid' : 'list');
                    }}
                    title={wp.i18n.__('Listing View', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Listing View', 'integrate-google-drive')}
                >
                    <i className={`action-item-icon dashicons dashicons-${isList ? 'grid-view' : 'list-view'}`}></i>

                </button>
            }

            {/*------------- Options -----------*/}
            <button
                type={`button`}
                className={`body-action-item action-options`}
                onClick={(e) => {
                    e.preventDefault();

                    setIsOptions(!isOptions);

                    if (isOptions) {
                        hideAll();
                    } else {
                        show(e);
                    }

                }}
                title={wp.i18n.__('Options', 'integrate-google-drive')}
                aria-label={wp.i18n.__('Options', 'integrate-google-drive')}
            >
                <i className={`action-item-icon dashicons dashicons-ellipsis`}></i>
            </button>

        </div>
    )
}