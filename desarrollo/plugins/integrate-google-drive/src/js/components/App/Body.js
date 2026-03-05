import preloaders from "../../includes/preloaders";
import {Tooltip} from "react-tooltip";

import Uploader from "./Uploader";
import RootFolders from "./RootFolders";
import Gallery from "./Gallery";
import Placeholder from "./placeholder/Placeholder";
import SearchPlaceholder from "./placeholder/SearchPlaceholder";
import SearchResultsInfo from "./SearchResultsInfo";
import EmptyPlaceholder from "./placeholder/EmptyPlaceholder";
import AppContext from "../../contexts/AppContext";
import Pagination from "../../includes/Pagination/Pagination";
import BodyAction from "./Body/BodyAction";
import preview from "../../includes/preview";

import {
    isFolder,
    humanFileSize,
    isRootFolder,
    getThumb,
    isShortcut,
    useLazyLoad,
    useHandleListingResize,
    formatDate,
    getPrivateImageUrl,
    isTouchScreen,
    isAudioVideoType,
    removeLastFolderSession,
} from "../../includes/functions";


const {useEffect, useRef, useContext} = React;

export default function Body() {

    const {
        preloader = 'default',
        rememberLastFolder = true,
        customPreloader,
    } = igd.settings;

    const isTutorAttachmentSelector = document.querySelector('.igd-tutor-attachment-modal');

    const context = useContext(AppContext);

    let {
        shortcodeId,
        nonce,
        isLoading,
        activeAccount,
        initFolders,
        files = [],
        setFiles,
        activeFiles,
        setActiveFiles,
        allFiles,
        setAllFiles,
        breadcrumbs,
        activeFolder,
        setActiveFolder,
        isShortcodeBuilder,
        selectedFolders,
        setSelectedFolders,
        isList,
        showLastModified,
        showFileSizeField,
        shortcodeBuilderType,
        isUpload,
        permissions, isSearchResults,
        isMobile,
        setIsOptions,
        listFiles,
        show,
        hideAll,
        isSelectFiles,
        selectionType,
        setActiveFile,
        isLMS,
        isWooCommerce,
        getFiles,
        notifications,
        lazyLoad,
        lazyLoadNumber,
        lazyLoadType,
        searchBoxText,
        isBulkSelect,
        showHeader,
        searchResults,
    } = context;

    // Check types
    const isBrowser = 'browser' === shortcodeBuilderType;
    const isReview = 'review' === shortcodeBuilderType;
    const isUploader = 'uploader' === shortcodeBuilderType;
    const isGallery = 'gallery' === shortcodeBuilderType;
    const isEmbed = 'embed' === shortcodeBuilderType;
    const isSearch = 'search' === shortcodeBuilderType;
    const isMedia = 'media' === shortcodeBuilderType;
    const isSlider = 'slider' === shortcodeBuilderType;
    const isListModule = 'list' === shortcodeBuilderType;

    // Check if single selection
    let isSingleSelection = (isUploader && selectedFolders.length);

    if (selectedFolders?.length > 0) {
        if (isSelectFiles) {
            if (['single', 'parent', 'template'].includes(selectionType)) {
                isSingleSelection = true;
            }
        }
    }

    // Check if the file could be selected
    let canSelectFile = (!isShortcodeBuilder && !initFolders) || [!!isBrowser, !!isReview, !!isMedia, !!isSlider, !!isGallery, !!isEmbed, !!isListModule, !!isLMS, !!isWooCommerce, !!isReview, (!!isSelectFiles && !selectionType)].includes(true);

    const shouldUpload = !!activeFolder && (!isRootFolder(activeFolder.id, activeAccount) || 'root' === activeFolder.id);

    // Handle listing width
    const containerRef = useRef();

    // List class
    const listClass = useHandleListingResize(containerRef, isList);

    // Handle Scroll Lazy Load
    if (lazyLoad && lazyLoadType === 'scroll' && (isShortcodeBuilder || !isSearch)) {
        useLazyLoad(containerRef, getFiles, isLoading, activeFolder, isSearchResults, isShortcodeBuilder, hideAll);
    }

    if (lazyLoad && lazyLoadType !== 'pagination' && activeFolder?.pageNumber > 0) {
        if (files.length < ((activeFolder?.pageNumber - 1) * lazyLoadNumber)) {
            setActiveFolder({...activeFolder, pageNumber: 0});
        }
    }

    const breadcrumbKeys = !!breadcrumbs ? Object.keys(breadcrumbs) : [];

    let foundLastFolder = false;

    const shouldShowSearchPlaceholder = !isLoading && ((!isShortcodeBuilder && isSearch) || isSearchResults) && !activeFolder && !files.length;

    // Ignore reset active files for review and gallery selections
    const ignoreResetSelections = isReview || (isGallery && permissions?.photoProof);

    // Drag-select state
    const isDraggingRef = useRef(false);
    const suppressClickRef = useRef(false); // prevents the post-drag click from clearing selection
    const dragStart = useRef({x: 0, y: 0});
    const dragThreshold = 5; // px before we treat it as a drag

    useEffect(() => {

        if (isTouchScreen() || !files.length || !containerRef.current) return;

        if (
            permissions
            && !isReview
            && !permissions?.canDelete
            && !permissions?.move
            && !(permissions?.download && permissions?.zipDownload)
        ) return;

        const area = containerRef.current.querySelector('.file-list');
        if (!area) return;

        // Ensure area is a positioning context for the absolute selection box
        const prevPos = area.style.position;
        if (getComputedStyle(area).position === 'static') {
            area.style.position = 'relative';
        }

        // Selection rectangle (absolute inside .file-list)
        const sel = document.createElement('div');
        sel.className = 'igd-selection-rect';
        sel.style.cssText = 'display:none;position:absolute;pointer-events:none;';
        area.appendChild(sel);

        let started = false;
        let raf = null;

        const getItems = () => area.querySelectorAll('.file-item');

        const rectsOverlap = (a, b) =>
            !(a.right < b.left || a.left > b.right || a.bottom < b.top || a.top > b.bottom);

        const normalizeRect = (x1, y1, x2, y2) => {
            const left = Math.min(x1, x2);
            const top = Math.min(y1, y2);
            const width = Math.abs(x2 - x1);
            const height = Math.abs(y2 - y1);
            return {left, top, width, height, right: left + width, bottom: top + height};
        };

        // Convert viewport (client) to area coords (handles inner scrolling)
        const toAreaCoords = (clientX, clientY) => {
            const r = area.getBoundingClientRect();
            return {
                x: clientX - r.left + area.scrollLeft,
                y: clientY - r.top + area.scrollTop,
            };
        };

        const addUniqueFiles = (curr, next) => [
            ...curr,
            ...next.filter(f => !curr.some(x => x.id === f.id)),
        ];

        const handleMouseDown = (e) => {
            // only left button; ignore ctrl/cmd modified
            if (e.button !== 0 || e.ctrlKey || e.metaKey) return;
            if (!area.contains(e.target)) return;

            // ignore clicks on interactive UI inside items
            const interactive = e.target.closest(
                '.file-item-options, .file-item-checkbox, .review-tag-modal, .igd-context-menu'
            );
            if (interactive) return;

            isDraggingRef.current = false;
            const {x, y} = toAreaCoords(e.clientX, e.clientY);
            dragStart.current = {x, y};
            started = false;

            // prep selection box
            sel.style.display = 'none';
            sel.style.left = `${x}px`;
            sel.style.top = `${y}px`;
            sel.style.width = '0px';
            sel.style.height = '0px';

            document.body.classList.add('igd-noselect');

            window.addEventListener('mousemove', handleMouseMove, {passive: false});
            window.addEventListener('mouseup', handleMouseUp, {passive: true});
        };

        const handleMouseMove = (e) => {
            e.preventDefault(); // stop native text selection

            const {x: sx, y: sy} = dragStart.current;
            const {x, y} = toAreaCoords(e.clientX, e.clientY);

            if (!started) {
                const dx = Math.abs(x - sx);
                const dy = Math.abs(y - sy);
                if (dx < dragThreshold && dy < dragThreshold) return;
                started = true;
                isDraggingRef.current = true;
            }

            const r = normalizeRect(sx, sy, x, y);
            sel.style.display = 'block';
            sel.style.left = `${r.left}px`;
            sel.style.top = `${r.top}px`;
            sel.style.width = `${r.width}px`;
            sel.style.height = `${r.height}px`;

            // live hover highlight (throttled)
            if (!raf) {
                raf = requestAnimationFrame(() => {
                    raf = null;
                    const areaRect = area.getBoundingClientRect();
                    getItems().forEach(el => {
                        const ir = el.getBoundingClientRect();
                        // item rect in area coords
                        const itemRect = {
                            left: ir.left - areaRect.left + area.scrollLeft,
                            top: ir.top - areaRect.top + area.scrollTop,
                            right: ir.right - areaRect.left + area.scrollLeft,
                            bottom: ir.bottom - areaRect.top + area.scrollTop,
                        };
                        el.classList.toggle('igd-selecting', rectsOverlap(r, itemRect));
                    });
                });
            }
        };

        const handleMouseUp = () => {
            window.removeEventListener('mousemove', handleMouseMove);
            window.removeEventListener('mouseup', handleMouseUp);
            document.body.classList.remove('igd-noselect');

            const wasDragging = isDraggingRef.current;

            // IMPORTANT: read rect BEFORE hiding (offsets become 0 when hidden)
            const aRect = {
                left: sel.offsetLeft,
                top: sel.offsetTop,
                right: sel.offsetLeft + sel.offsetWidth,
                bottom: sel.offsetTop + sel.offsetHeight,
            };

            // hide and clear hover classes
            sel.style.display = 'none';
            getItems().forEach(el => el.classList.remove('igd-selecting'));

            if (!wasDragging) {
                isDraggingRef.current = false;
                return;
            }

            // suppress the click that follows this mouseup (prevents “flash unselect”)
            suppressClickRef.current = true;
            setTimeout(() => {
                isDraggingRef.current = false;
            }, 0); // defer flag to next tick

            if (aRect.right <= aRect.left || aRect.bottom <= aRect.top) return;

            const areaRect = area.getBoundingClientRect();
            const pickedEls = Array.from(getItems()).filter(el => {
                const ir = el.getBoundingClientRect();
                const itemRect = {
                    left: ir.left - areaRect.left + area.scrollLeft,
                    top: ir.top - areaRect.top + area.scrollTop,
                    right: ir.right - areaRect.left + area.scrollLeft,
                    bottom: ir.bottom - areaRect.top + area.scrollTop,
                };
                return rectsOverlap(aRect, itemRect);
            });

            if (!pickedEls.length) return;

            // Map DOM -> file objects
            let selectedFiles = pickedEls
                .map(el => files.find(f => f.id === el.getAttribute('data-id')))
                .filter(Boolean);

            // Only folders when cannot select files
            if (!canSelectFile) {
                selectedFiles = selectedFiles.filter(f => isFolder(f));
            }

            if (isShortcodeBuilder) {
                setSelectedFolders(prev => addUniqueFiles(prev, selectedFiles));
            } else {
                setActiveFiles(prev => addUniqueFiles(prev, selectedFiles));
            }
        };

        // Capture-phase click suppressor to eat the post-drag click
        const handleClickCapture = (e) => {
            if (!suppressClickRef.current) return;
            suppressClickRef.current = false;
            e.stopImmediatePropagation?.();
            e.stopPropagation();
            e.preventDefault();
        };

        area.addEventListener('mousedown', handleMouseDown);
        area.addEventListener('click', handleClickCapture, true); // capture!

        return () => {
            area.removeEventListener('mousedown', handleMouseDown);
            area.removeEventListener('click', handleClickCapture, true);
            if (sel && sel.parentNode) sel.parentNode.removeChild(sel);
            if (prevPos === '') area.style.position = '';
            if (raf) cancelAnimationFrame(raf);
            document.body.classList.remove('igd-noselect');
            window.removeEventListener('mousemove', handleMouseMove);
            window.removeEventListener('mouseup', handleMouseUp);
        };
    }, [files]);

    return (
        <div ref={containerRef}
             className={`igd-body ${isLoading ? (isLoading === 'lazy' && 'pagination' !== lazyLoadType ? 'lazy-loading' : 'loading') : ''} ${isBulkSelect ? 'bulk-select' : ''} `}>

            {/*---- Body Action ----*/}
            {showHeader && !isLoading && (!isSearch || isSearchResults) && <BodyAction/>}

            {/*--- Search result info ---*/}
            {isSearchResults && !isLoading && <SearchResultsInfo/>}

            {/*--- File List ---*/}
            {(!isGallery || isShortcodeBuilder) &&
                <div
                    className={`file-list ${isList ? 'list-view' : ''} ${listClass} ${!files.length && !isRootFolder(activeFolder, activeAccount) ? 'empty' : ''}`}

                    onClick={() => {
                        if (isDraggingRef.current || suppressClickRef.current || isBulkSelect || ignoreResetSelections) return;

                        const timeout = document.querySelector('.igd-context-menu') ? 300 : 0;
                        setTimeout(() => {
                            setActiveFile(null);
                            setActiveFiles([]);
                        }, timeout);
                    }}

                    onContextMenu={(e) => {
                        e.preventDefault();

                        setIsOptions(false);

                        if (!isBulkSelect && !ignoreResetSelections) {
                            setActiveFiles([]);
                        }

                        if (isRootFolder(activeFolder.id, activeAccount) && 'root' !== activeFolder.id) return;

                        setActiveFile(activeFolder);

                        show(e);
                    }}
                >

                    {/*------------ List View Header --------------*/}
                    {
                        (!isGallery && isList && !!files.length && (showLastModified || showFileSizeField)) &&
                        <div className="list-view-header">
                            <span className="col-name">{wp.i18n.__('Name', 'integrate-google-drive')}</span>

                            {showFileSizeField &&
                                <span className="col-size">{wp.i18n.__('Size', 'integrate-google-drive')}</span>
                            }

                            {showLastModified &&
                                <span className="col-modified">{wp.i18n.__('Modified', 'integrate-google-drive')}</span>
                            }
                        </div>
                    }

                    {/*------------- Root Placeholder -------------*/}
                    {(!activeFolder || (!activeFolder && !!initFolders)) && (!isSearch || isShortcodeBuilder) && !isSearchResults &&
                        <RootFolders/>
                    }

                    {/*--------------- Previous folder ------------*/}
                    {(activeFolder && breadcrumbKeys.length > 0) &&
                        <div className={`go-prev file-item folder-item`}
                             onClick={(e) => {
                                 setActiveFile(null);

                                 if (initFolders) {

                                     if (breadcrumbKeys.length > 1) {
                                         const lastFolderId = breadcrumbKeys[breadcrumbKeys.length - 2];
                                         const lastFolderParents = [breadcrumbKeys[breadcrumbKeys.length - 3]];

                                         const lastFolder = {
                                             id: lastFolderId,
                                             name: breadcrumbs[lastFolderId],
                                             accountId: activeAccount.id,
                                             parents: lastFolderParents,
                                         }

                                         listFiles(lastFolder);

                                     } else {

                                         if (rememberLastFolder) {
                                             removeLastFolderSession(shortcodeId);
                                         }

                                         setActiveFolder(null);
                                         setFiles(allFiles[''] || searchResults || initFolders);
                                     }

                                 } else {

                                     if (breadcrumbKeys.length > 1) {

                                         const lastFolderId = breadcrumbKeys[breadcrumbKeys.length - 2];
                                         const lastFolderParents = [breadcrumbKeys[breadcrumbKeys.length - 3]];

                                         const lastFolder = {
                                             id: lastFolderId,
                                             name: breadcrumbs[lastFolderId],
                                             accountId: activeAccount.id,
                                             parents: lastFolderParents,
                                         }

                                         listFiles(lastFolder);

                                     } else {
                                         setFiles([]);
                                         setActiveFolder(null);
                                     }

                                 }

                             }}
                        >
                            <i className="dashicons dashicons-arrow-left-alt2"></i>
                            <span>{wp.i18n.__('Previous folder', 'integrate-google-drive')}</span>
                        </div>
                    }

                    {/*--- File List ---*/}
                    {
                        !!files.length && files.map((file, index) => {

                            const {
                                id,
                                name,
                                type,
                                iconLink = '',
                                thumbnailLink,
                                size,
                                updated,
                            } = file;

                            if (!id) return;

                            const isDir = isFolder(file);

                            const isActive = !isShortcodeBuilder ? activeFiles.find(item => item?.id === id) : selectedFolders.find(item => item.id === id);

                            // Check if the item is selected
                            const isSelected = selectedFolders && !!selectedFolders.find(item => item.id === id);

                            // Check if the item is selectable
                            let isSelectable = isShortcodeBuilder && (canSelectFile || isDir) && (!isLMS || !isDir) && (!isTutorAttachmentSelector || !isDir);

                            if (isSingleSelection) {
                                isSelectable = (selectedFolders[0].id || selectedFolders[0]) === id;
                            }

                            // Image sizes
                            const icon = iconLink?.replace('/16/', `/64/`);
                            const thumbnail = getThumb(file, 'small');

                            // Check the last folder
                            const isLastFolder = !foundLastFolder && isDir && !files.slice(index + 1).some(isFolder);
                            if (isLastFolder) foundLastFolder = true;

                            const reviewTag = !isShortcodeBuilder && isReview && (file?.reviewTag || files.find(item => item.id === id)?.reviewTag);

                            return (
                                <>
                                    {/*----------- Folders & Files divider ------------*/}
                                    {isLastFolder && !isList && <div className="folder-file-divider"></div>}

                                    <div
                                        key={id}
                                        data-id={id}
                                        className={`file-item ${isDir ? 'folder-item' : ''}  ${isLastFolder ? 'folder-item-last' : ''} ${(isActive || isSelected) ? 'active' : ''}`}

                                        onClick={(e) => {
                                            e.stopPropagation();

                                            hideAll();

                                            // Handle mobile click
                                            if (isMobile) {

                                                if (isDir) {
                                                    listFiles(file);
                                                } else {
                                                    if (isShortcodeBuilder) {
                                                        setSelectedFolders(selectedFolders => [...selectedFolders, file]);
                                                    } else {
                                                        setActiveFile(file);

                                                        preview(e, id, files, permissions, notifications, false, shortcodeId, nonce,);
                                                    }
                                                }

                                                return;
                                            }

                                            // Open folder on click
                                            if ((!e.detail || e.detail == 1) && !isShortcodeBuilder) {
                                                if ((e.ctrlKey || e.shiftKey || e.metaKey) && !isUploader) {
                                                    setActiveFiles(activeFiles => activeFiles.find(activeFile => activeFile.id === file.id) ? activeFiles.filter(activeFile => activeFile.id !== file.id) : [...activeFiles, file]);
                                                } else if (isDir) {
                                                    listFiles(file);
                                                } else {
                                                    setActiveFile(file);
                                                    preview(e, id, files, permissions, notifications, false, shortcodeId, nonce,);
                                                }
                                            } else if (isDir) {
                                                listFiles(file);
                                            } else if (isShortcodeBuilder && isSelectable) {
                                                if (isSelected) {
                                                    setSelectedFolders(selectedFolders => [...selectedFolders.filter(item => item.id != file.id)]);
                                                } else {
                                                    setSelectedFolders(selectedFolders => [...selectedFolders, file]);
                                                }
                                            }

                                            console.log(file);

                                        }}

                                        onDoubleClick={(e) => {
                                            e.stopPropagation();

                                            if (isShortcodeBuilder) return;

                                            if (isDir) {
                                                listFiles(file);
                                            } else {
                                                setActiveFile(file);
                                                preview(e, id, files, permissions, notifications, false, shortcodeId, nonce);
                                            }

                                        }}

                                        onContextMenu={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();

                                            hideAll();

                                            setIsOptions(false);

                                            if (file['shared-drives']) return;

                                            setActiveFile(file);

                                            show(e);
                                        }}

                                        title={name}

                                    >

                                        {/* Thumbnail - Show if the file and not is list view */}
                                        {!isDir && !isList &&
                                            <div className="igd-file-thumbnail-wrap">
                                                <img
                                                    src={thumbnail}
                                                    alt={name}
                                                    loading={'lazy'}
                                                    className={`igd-file-thumbnail ${thumbnailLink ? 'has-thumbnail' : ''}`}
                                                    referrerPolicy={'no-referrer'}
                                                    onError={({currentTarget}) => {
                                                        const currentSrc = currentTarget.src;
                                                        const newSrc = getPrivateImageUrl(file, 'medium');

                                                        if (currentSrc !== newSrc) {
                                                            currentTarget.src = newSrc;
                                                        }
                                                    }}
                                                />
                                            </div>
                                        }

                                        {/* Playback icon for video files  */}
                                        {!isList && isAudioVideoType(file) &&
                                            <i className={`dashicons dashicons-controls-play file-playback-icon`}></i>}

                                        {/* Footer Info */}
                                        <div className={`file-item-footer`}>

                                            <div className="file-icon-wrap">
                                                <img className={`file-icon`} referrerPolicy={'no-referrer'} src={icon}
                                                     alt={'icon'}/>
                                                {
                                                    isShortcut(type) &&
                                                    <svg className={`shortcut-icon`} viewBox="0 0 16 16" fill="none"
                                                         focusable="false" xmlns="http://www.w3.org/2000/svg" width="16px"
                                                         height="16px">
                                                        <circle cx="8" cy="8" r="8" fill="white"></circle>
                                                        <path d="M10,3H6V4H8.15A5,5,0,0,0,10,13V12A4,4,0,0,1,9,4.65V7h1Z"
                                                              fill="#5F6368"></path>
                                                    </svg>
                                                }

                                                {/*--- File Checkbox ---*/}
                                                {(() => {
                                                    const canDownload = permissions?.download && permissions?.zipDownload;
                                                    const canDownloadFolder = !isDir || permissions?.folderDownload;
                                                    const canReviewSelect = isReview && (!isDir || permissions?.reviewFolderSelection);

                                                    const showCheckbox =
                                                        (!isShortcodeBuilder && (!permissions || (canDownload && canDownloadFolder))) || isSelectable || canReviewSelect;

                                                    if (!showCheckbox) return null;

                                                    const handleCheckboxClick = (e) => {
                                                        e.stopPropagation();

                                                        if (!isShortcodeBuilder) {

                                                            if (
                                                                (isReview || (isGallery && permissions?.photoProof)) &&
                                                                permissions.photoProofMaxSelection > 0 &&
                                                                activeFiles.length >= permissions.photoProofMaxSelection
                                                            ) {

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

                                                            setActiveFile(null);

                                                            setActiveFiles((prev) =>
                                                                isActive
                                                                    ? prev.filter((activeFile) => activeFile.id !== file.id)
                                                                    : [...prev, file]
                                                            );

                                                        } else {

                                                            setSelectedFolders(selectedFolders => isSelected
                                                                ? selectedFolders.filter((item) => item.id !== file.id)
                                                                : [...selectedFolders, file]
                                                            );

                                                        }

                                                    };

                                                    return (
                                                        <div
                                                            className={`file-item-checkbox ${isActive ? 'checked' : ''}`}
                                                            onClick={handleCheckboxClick}
                                                        >
                                                            <span className="box"></span>
                                                        </div>
                                                    );
                                                })()}

                                            </div>

                                            <span className="file-item-name">{name}</span>

                                            {showFileSizeField &&
                                                (isList ?
                                                        <span
                                                            className="file-item-size">{!!size ? humanFileSize(size) : '—'}</span>
                                                        : (!permissions || permissions.download) && !isDir && !!size &&
                                                        <span className="file-item-size">{humanFileSize(size)}</span>
                                                )
                                            }

                                            {/* File Options */}
                                            {!isShortcodeBuilder && (!permissions ||
                                                    (permissions['download'] && (!isDir || permissions['folderDownload'])) ||
                                                    permissions['details'] ||
                                                    permissions['delete'] ||
                                                    permissions['rename'] ||
                                                    permissions['move'] ||
                                                    permissions['copy'] ||
                                                    permissions['edit'] ||
                                                    permissions['preview']
                                                ) &&
                                                <span className="file-item-options"
                                                      onClick={(e) => {
                                                          e.stopPropagation();

                                                          setIsOptions(false);

                                                          setActiveFile(file);
                                                          show(e);
                                                      }}
                                                >
                                                    <i className="dashicons dashicons-ellipsis"></i>
                                                </span>
                                            }
                                        </div>

                                        {/* Date and size for list-view */}
                                        {isList && showLastModified &&
                                            <span className="file-item-date">{formatDate(updated, true)}</span>
                                        }

                                        {/* Review & Approve Tag */}
                                        {isReview && !isDir && permissions?.reviewEnableTags &&
                                            <>

                                                <div className={`file-item-review-tag tag-${id}`}
                                                     onClick={(e) => {
                                                         e.stopPropagation();
                                                     }}
                                                     onContextMenu={(e) => e.stopPropagation()}
                                                     onDoubleClick={(e => e.stopPropagation())}
                                                     style={{background: reviewTag?.color}}
                                                >
                                                    <i className="dashicons dashicons-tag review-tag-icon"></i>
                                                    {reviewTag &&
                                                        <>
                                                            <span
                                                                className={`review-tag-label`}>{reviewTag?.label}</span>

                                                            <i className={`dashicons dashicons-no-alt`}
                                                               onClick={(e) => {
                                                                   e.stopPropagation();

                                                                   const clearReviewTag = (list) =>
                                                                       list.map(item =>
                                                                           item.id === file.id ? {
                                                                               ...item,
                                                                               reviewTag: null
                                                                           } : item
                                                                       );

                                                                   setFiles(clearReviewTag);
                                                                   setActiveFiles(clearReviewTag);
                                                                   setAllFiles(prevFiles => ({
                                                                       ...prevFiles,
                                                                       [activeFolder?.id || ""]: clearReviewTag(prevFiles[activeFolder?.id || ""])
                                                                   }));

                                                               }}

                                                            ></i>
                                                        </>
                                                    }

                                                    <Tooltip
                                                        anchorSelect={`.file-item-review-tag.tag-${id}`}
                                                        variant={'light'}
                                                        className="review-tag-modal igd-tooltip"
                                                        place="bottom"
                                                        border={`1px solid #ddd`}
                                                        clickable={true}
                                                        afterShow={() => {
                                                            document.querySelector(`.file-item-review-tag.tag-${id}`).closest('.file-item').style.zIndex = 9999;
                                                        }}
                                                        afterHide={() => {
                                                            document.querySelector(`.file-item-review-tag.tag-${id}`).closest('.file-item').style.zIndex = 'unset';
                                                        }}
                                                    >
                                                        <div className="review-tag-modal-inner">
                                                            {
                                                                permissions?.reviewTags?.map((tag) => {
                                                                    const {label, value, color} = tag;

                                                                    return (

                                                                        <div
                                                                            key={label}
                                                                            className={`tag-item ${reviewTag?.value === value ? 'active' : ''}`}
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();

                                                                                const updateReviewTag = (list) => {
                                                                                    return (
                                                                                        list.map(item =>
                                                                                            item.id === file.id ? {
                                                                                                ...item,
                                                                                                reviewTag: tag
                                                                                            } : item
                                                                                        )
                                                                                    )
                                                                                }

                                                                                setFiles(updateReviewTag);
                                                                                setActiveFiles(updateReviewTag);

                                                                                setAllFiles(prevFiles => ({
                                                                                    ...prevFiles,
                                                                                    [activeFolder?.id || ""]: updateReviewTag(prevFiles[activeFolder?.id || ""])
                                                                                }));

                                                                            }}
                                                                        >
                                                                            <i className="dashicons dashicons-saved"></i>
                                                                            <span style={{color}}>{label}</span>
                                                                        </div>
                                                                    )
                                                                })
                                                            }
                                                        </div>
                                                    </Tooltip>

                                                </div>
                                            </>
                                        }

                                    </div>

                                </>
                            )
                        })
                    }

                </div>
            }

            {/*--------- Gallery --------*/}
            {(!isShortcodeBuilder && isGallery) && <Gallery/>}

            {/*--------- Load More --------*/}
            {(!!files.length && lazyLoad && lazyLoadType === 'button' && activeFolder?.pageNumber > 0) &&
                <button
                    className={`igd-btn btn-primary igd-load-more ${!files.length ? 'igd-hidden' : ''}`}
                    onClick={(e) => {
                        e.stopPropagation();
                        getFiles(activeFolder, 'lazy');
                    }}
                >
                    {isLoading && <span className={`igd-spinner`}></span>}
                    {isLoading ? wp.i18n.__('Loading...', 'integrate-google-drive') : wp.i18n.__('Load more', 'integrate-google-drive')}
                </button>
            }

            {/*------- Pagination -------*/}
            {(!isSearchResults && !!files.length && lazyLoad && lazyLoadType === 'pagination' && activeFolder?.count > lazyLoadNumber) &&
                <Pagination
                    className={"igd-pagination"}
                    pageCount={(activeFolder?.pageNumber <= 1 && files.length < lazyLoadNumber) ? 1 : Math.ceil(activeFolder?.count / lazyLoadNumber)}
                    currentPage={activeFolder?.pageNumber || 1}
                    onPageChange={page => {
                        setActiveFolder({...activeFolder, pageNumber: page});
                        getFiles({...activeFolder, pageNumber: page}, 'lazy');

                        const fileBrowserParent = containerRef.current.closest('.igd-file-browser');

                        if (fileBrowserParent) {
                            fileBrowserParent.scrollIntoView({behavior: 'smooth'});
                        }

                    }}
                />
            }

            {/*--------- Empty Placeholder --------*/}
            {
                (
                    !isLoading
                    && (initFolders || (activeFolder && (!isRootFolder(activeFolder.id, activeAccount) || activeFolder.id === 'root')))
                    && !files.length
                    && !isSearchResults
                    && (isShortcodeBuilder || !isGallery)
                    && (isShortcodeBuilder || !isSearch || activeFolder)
                )
                && <EmptyPlaceholder/>
            }

            {/*--------- Uploader --------*/}
            {!isSearch && !isSearchResults && !isShortcodeBuilder && !isGallery && shouldUpload && isUpload
                && <Uploader/>
            }

            {/*--------- Root Placeholder --------*/}
            {!isSearchResults && !isLoading && !files.length && activeFolder && isRootFolder(activeFolder.id, activeAccount) &&
                <Placeholder activeFolder={activeFolder}/>
            }

            {/*--------- Search placeholder --------*/}
            {shouldShowSearchPlaceholder &&
                <SearchPlaceholder isSearchResults={isSearchResults} searchBoxText={searchBoxText}/>
            }

            {/*---------- Preloader ----------*/}
            {isLoading && 'none' !== preloader && !!preloaders[preloader] &&
                <div className={`loading-wrap`}
                     dangerouslySetInnerHTML={{__html: customPreloader ? `<img src=${customPreloader} alt="Loading..."/>` : preloaders[preloader].svg}}
                ></div>
            }

        </div>
    )
}