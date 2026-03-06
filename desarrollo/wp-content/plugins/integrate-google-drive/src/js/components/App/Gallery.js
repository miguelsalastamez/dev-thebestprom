import ReactPhotoGallery from "react-photo-gallery";
import AppContext from "../../contexts/AppContext";
import EmptyPlaceholder from "./placeholder/EmptyPlaceholder";
import preview from "../../includes/preview";

import {
    isFolder,
    isImageType,
    getThumb,
    isRootFolder,
    isVideoType,
    useMounted,
    humanFileSize,
    getPrivateImageUrl,
    removeLastFolderSession,
} from "../../includes/functions";
import {Tooltip} from "react-tooltip";

const {useEffect, useState, useContext} = React;

const SelectionCheckbox = ({id, activeFiles, setActiveFiles, files}) => {

    const file = files.find(file => file.id === id);
    const isActive = activeFiles.find(item => item.id === id);

    return (
        <span className={`file-item-checkbox ${isActive ? 'checked' : ''}`}
              onClick={(e) => {
                  e.stopPropagation();

                  if (isActive) {
                      setActiveFiles(activeFiles => activeFiles.filter(activeFile => activeFile.id !== file.id));
                  } else {
                      setActiveFiles([...activeFiles, file]);
                  }
              }}
        >
            <span className={`box`}></span>
        </span>
    )
}

export default function Gallery() {

    const {rememberLastFolder = true} = igd.settings;

    const context = useContext(AppContext);

    const {
        shortcodeId,
        galleryLayout,
        galleryColumns,
        galleryAspectRatio,
        galleryHeight,
        galleryMargin,
        galleryView,
        galleryFolderView,
        galleryOverlay,
        overlayDisplayType,
        galleryOverlayTitle,
        galleryOverlayDescription,
        galleryOverlaySize,
        galleryImageSize,
        galleryCustomSizeWidth,
        galleryCustomSizeHeight,

        activeAccount,
        files,
        allFiles,
        setAllFiles,
        listFiles,
        breadcrumbs,
        initFolders,
        setFiles,
        activeFolder,
        setActiveFolder,
        permissions,
        setActiveFiles,
        activeFiles,
        selectAll,
        isLoading,
        notifications,
        nonce,
    } = context;

    const breadcrumbKeys = !!breadcrumbs && Object.keys(breadcrumbs);

    const [folderImages, setFolderImages] = useState({});

    const [folders, setFolders] = useState([]);
    const [images, setImages] = useState([]);

    // Filter images
    useEffect(() => {
        if (!files?.length) {
            setFolders([]);
            setImages([]);
            return;
        }

        const folderItems = files.filter(file => isFolder(file) || ['root', 'shared', 'computers', 'starred', 'shared-drives'].includes(file.id));
        setFolders(folderItems);

        const imageItems = files.filter(file => isImageType(file) || isVideoType(file)).map(image => {

            const {id, accountId, type, description, name, size, metaData = {}} = image;

            const item = {
                id,
                accountId,
                type,
                name,
                description,
                size,
                src: getThumb(image, galleryImageSize, {w: galleryCustomSizeWidth, h: galleryCustomSizeHeight}),
            }

            if ('grid' !== galleryLayout) {

                if (!metaData.width || isVideoType(image)) {
                    metaData.width = 600;
                }

                if (!metaData.height || isVideoType(image)) {
                    metaData.height = 400;
                }

                item.width = metaData.width;
                item.height = metaData.height;
            }

            return item;

        });

        setImages(imageItems);

    }, [files]);

    useEffect(() => {
        if (!folders.length) return;
        if ('title' === galleryFolderView) return;

        folders.forEach(folder => {
            if (isRootFolder(folder['id'], activeAccount)) return;
            getFolderImages(folder);
        });

    }, [folders, activeAccount]);

    const getFolderImages = (folder) => {

        function initGallery(gallery, index) {
            const $gallery = jQuery(gallery);
            setTimeout(() => {
                if (gallery.dataset.intervalId) {
                    clearInterval(gallery.dataset.intervalId);
                }

                gallery.dataset.intervalId = setInterval(() => {
                    const images = $gallery.find('img');
                    const activeImage = images.filter('.active').length ? images.filter('.active') : images.first();

                    if (!activeImage.length) return;

                    const nextImage = activeImage.next().length ? activeImage.next() : images.first();

                    images.removeClass('active');
                    nextImage.addClass('active');
                }, 3000);
            }, 1500 * index);
        }

        wp.ajax.post('igd_get_files', {
            data: {folder},
            nonce: nonce || igd.nonce,
        }).done((data) => {

            const {files, error} = data;

            if (error) {
                Swal.fire({
                    html: error,
                    icon: 'error',
                    confirmButtonText: wp.i18n.__('Ok', 'integrate-google-drive'),
                    customClass: {container: 'igd-swal'},
                });

                return;
            }

            let images = files.filter(item => isImageType(item) || isVideoType(item));
            images = images.slice(0, 10);

            setFolderImages(prevState => ({...prevState, [folder['id']]: images}));

            document.querySelectorAll('.igd-gallery-folder-images').forEach(initGallery);

        }).fail((error) => console.log(error));
    }

    const handleClick = (e, id) => {
        const items = files.filter(file => isImageType(file) || isVideoType(file));

        preview(e, id, items, permissions, notifications, true, shortcodeId, nonce);
    }

    const isMounted = useMounted();

    // Handle Select All
    useEffect(() => {
        if (!isMounted) return;

        if (selectAll) {
            setActiveFiles(selectAll ? (files.filter(file => !(isFolder(file) && !permissions.folderDownload))) : []);
        } else {
            setActiveFiles([]);
        }

    }, [selectAll]);

    const columnCount = () => {
        const {innerWidth} = window;

        if (innerWidth < 768) {
            return galleryColumns['xs'];
        } else if (innerWidth < 992) {
            return galleryColumns['sm'];
        } else if (innerWidth < 1200) {
            return galleryColumns['md'];
        } else if (innerWidth < 1600) {
            return galleryColumns['lg'];
        } else if (innerWidth >= 1920) {
            return galleryColumns['xl'];
        }

        return galleryColumns['md'];
    }

    const [column, setColumn] = useState(columnCount());

    useEffect(() => {
        const handleResize = () => {
            setColumn(columnCount());
        }

        window.addEventListener('resize', handleResize);

        return () => {
            window.removeEventListener('resize', handleResize);
        }
    }, []);


    return (

        <>
            <div
                className={`igd-module-gallery gallery-view-${galleryView} gallery-layout-${galleryLayout}`}
                style={{
                    '--column-width': `calc(${(100 / column)}% - ${galleryMargin * 2}px)`,
                    '--aspect-ratio': `${galleryAspectRatio}`,
                }}
            >

                <div className={`file-list`}>
                    {/*--------------- Previous folder ------------*/}
                    {(!!breadcrumbKeys.length && !!activeFolder) &&
                        <div className={`go-prev file-item folder-item`}
                            style={{ margin: `${galleryMargin}px` }}
                            onClick={(e) => {
                                if (breadcrumbKeys.length > 1) {
                                    const lastFolderId = breadcrumbKeys[breadcrumbKeys.length - 2];
                                    const lastFolder = {
                                        id: lastFolderId,
                                        name: breadcrumbs[lastFolderId],
                                        accountId: activeAccount['id'],
                                    }

                                    listFiles(lastFolder);
                                } else {
                                    setActiveFolder('');
                                    setFiles(initFolders);

                                    if (rememberLastFolder) {
                                        removeLastFolderSession(shortcodeId);
                                    }
                                }
                            }}
                        >
                            <i className="dashicons dashicons-arrow-left-alt"></i>
                            <span>{wp.i18n.__('Previous folder', 'integrate-google-drive')}</span>
                        </div>
                    }

                    {!!folders.length &&
                        folders.map(folder => {
                            let {id, name, iconLink} = folder;

                            const isActive = activeFiles.find(item => item['id'] === id);

                            let icon = iconLink?.replace('/16/', `/64/`);

                            return (
                                <div
                                    key={id}
                                    className={`file-item ${isActive ? 'active' : ''} ${'title' === galleryFolderView ? 'folder-item' : ''}`}
                                    style={{margin: `${galleryMargin}px`}}
                                    onClick={() => listFiles(folder)}>

                                    {'thumbnail' === galleryFolderView &&
                                        <div className={`igd-gallery-folder-images`}>
                                            {
                                                !!folderImages[id] && folderImages[id].length > 0 ?
                                                    folderImages[id].map((image, i) => {
                                                        const {id, name} = image;

                                                        const thumbnail = getThumb(image, galleryImageSize, {
                                                            w: galleryCustomSizeWidth,
                                                            h: galleryCustomSizeHeight
                                                        });

                                                        return (
                                                            <img
                                                                key={id}
                                                                className={`${i === 0 ? 'active' : ''}`}
                                                                onError={(e) => {
                                                                    e.target.src = icon;
                                                                }}
                                                                src={thumbnail}
                                                                alt={name}
                                                                referrerPolicy={'no-referrer'}
                                                            />
                                                        );

                                                    })
                                                    : <img className={`active`} src={icon} alt={name}
                                                           referrerPolicy={'no-referrer'}/>
                                            }
                                        </div>
                                    }

                                    <div className="file-item-footer">

                                        <div className="file-icon-wrap">

                                            <img className={`file-icon`} src={icon} alt={name}
                                                 referrerPolicy={'no-referrer'}/>

                                            {!isRootFolder(id, activeAccount) && ((permissions.download && permissions.zipDownload && permissions['folderDownload']) || permissions.photoProof) &&
                                                <SelectionCheckbox
                                                    id={id}
                                                    activeFiles={activeFiles}
                                                    setActiveFiles={setActiveFiles}
                                                    files={files}
                                                />
                                            }

                                        </div>

                                        <span>{name}</span>
                                    </div>

                                </div>
                            )
                        })
                    }
                </div>

                {!!images.length &&
                    <ReactPhotoGallery
                        photos={images}
                        margin={parseInt(galleryMargin)}
                        targetRowHeight={'justified' === galleryLayout && galleryHeight}
                        columns={column}
                        direction={'masonry' === galleryLayout ? 'column' : 'row'}
                        renderImage={({photo, margin, direction, top, left}) => {

                            const {name, description, src, size, id, accountId, width, height} = photo;

                            const style = {
                                display: 'block',
                                margin: `${margin}px`,
                                width: `${width}px`,
                                height: `${height}px`,
                            }

                            if (direction === 'column') {
                                style.position = 'absolute';
                                style.left = `${left}px`;
                                style.top = `${top}px`;
                            }

                            const isActive = activeFiles?.find(item => item.id === id);

                            const reviewTag = (photo?.reviewTag || files?.find(item => item?.id === id)?.reviewTag);

                            return (
                                <div className={`igd-gallery-item ${isActive ? 'active' : ''}`}
                                     style={style}
                                     onClick={e => handleClick(e, id)}
                                >
                                    {((permissions.download && permissions.zipDownload) || permissions.photoProof) &&
                                        <SelectionCheckbox
                                            id={id}
                                            activeFiles={activeFiles}
                                            setActiveFiles={setActiveFiles}
                                            files={files}
                                        />
                                    }

                                    {/* Review & Approve Tag */}
                                    {permissions?.reviewEnableTags &&
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
                                                        <span className={`review-tag-label`}>{reviewTag?.label}</span>

                                                        <i className={`dashicons dashicons-no-alt`}
                                                           onClick={(e) => {
                                                               e.stopPropagation();

                                                               const clearReviewTag = (list) =>
                                                                   list.map(item =>
                                                                       item.id === id ? {
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
                                                                                        item.id === id ? {
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

                                    <img
                                        src={src}
                                        alt={name}
                                        referrerPolicy="no-referrer"
                                        onError={({currentTarget}) => {
                                            const currentSrc = currentTarget.src;

                                            const newSrc = getPrivateImageUrl({id, accountId}, 'medium');

                                            if (currentSrc !== newSrc) {
                                                currentTarget.src = newSrc;
                                            }
                                        }}
                                    />

                                    {/* Playback Icon */}
                                    {isVideoType(photo) &&
                                        <i className={`dashicons dashicons-controls-play file-playback-icon`}></i>}

                                    {!!galleryOverlay &&
                                        <div className={`igd-gallery-item-overlay type-${overlayDisplayType}`}>

                                            {!!galleryOverlayTitle &&
                                                <div
                                                    className="overlay-title">{name.replace(/\.(jpg|jpeg|png|gif|webp|bmp|tiff|svg)$/i, "")}</div>
                                            }

                                            {!!galleryOverlayDescription && !!description &&
                                                <p className="overlay-description">{description}</p>
                                            }

                                            {!!galleryOverlaySize &&
                                                <span className="overlay-size">{humanFileSize(size)}</span>
                                            }

                                        </div>
                                    }

                                </div>
                            );
                        }}
                    />
                }

            </div>

            {isMounted && !isLoading && !folders.length && !images.length && <EmptyPlaceholder/>}
        </>

    )

}