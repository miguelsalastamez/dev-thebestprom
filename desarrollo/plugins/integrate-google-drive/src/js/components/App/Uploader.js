import AppContext from "../../contexts/AppContext";
import {getTypeIcon, getFileRelativePath, isFolder} from "../../includes/functions";

const {useState, useEffect, useRef, useContext} = React;
export default function Uploader() {

    const context = useContext(AppContext);

    let {
        shortcodeId,
        activeAccount = igd.activeAccount,
        notifications,
        folders,
        filters,
        maxFiles,
        maxFileSize,
        minFileSize,
        activeFolder,
        isUpload,
        setIsUpload,
        isFormUploader,
        isRequired,
        showUploadLabel,
        uploadLabelText,
        uploadFileName,
        folderNameTemplate,
        isWooCommerceUploader,
        wcItemId,
        wcOrderId,
        wcProductId,
        initUploadedFiles = [],
        enableFolderUpload = !!activeFolder, // check if shortcode or admin browser component
        uploadImmediately = !!activeFolder || isWooCommerceUploader,
        overwrite,
        showUploadConfirmation,
        uploadConfirmationMessage,
        nonce,
        enableUploadDescription = !folders, // activate for file browser
        uploadFolderSelection,
        uploadFolders = [],
        folderSelectionLabel,
        privateFolders,
    } = context;

    const {allowExtensions, allowAllExtensions, allowExceptExtensions} = filters || {};

    const filesRef = useRef(initUploadedFiles);

    const [uploadedFiles, setUploadedFiles] = useState(initUploadedFiles); // array of Uploaded Google Drive files
    const [inProgress, setInProgress] = useState([]);
    const [queue, setQueue] = useState([]);

    const [percentage, setPercentage] = useState(0);
    const [isComplete, setIsComplete] = useState(false);
    const [pausedItems, setPausedItems] = useState([]);

    const uploaderRef = useRef(null);
    const uploaderElementRef = useRef(null);

    const submitBtnRef = useRef(null);
    const submitBtnTextRef = useRef(null);

    // Get destination folder
    const uploadFolderRef = useRef(activeFolder || (folders?.length ? folders[0] : null));

    if (!uploadFolderRef.current) {
        const folderItems = !!folders ? folders.filter(item => isFolder(item)) : [];

        uploadFolderRef.current = !!folderItems && !!folderItems.length ? folderItems[0] : {
            id: 'root',
            accountId: activeAccount.id,
        };
    }

    const mayUploadNext = (file) => {

        if (!file) return;

        const files = filesRef.current;

        const currentFileIndex = files.findIndex(item => item.id === file.id);
        const nextFileIndex = currentFileIndex + 1;

        if (nextFileIndex < files.length) {
            processUpload(files[nextFileIndex]);
        }
    }

    const pluploadAddFilters = () => {
        plupload.buildUrl = url => url;

        plupload.addFileFilter('file_ext', handleFileExtension);

        plupload.addFileFilter('min_file_size', handleFileSize);

        plupload.addFileFilter('max_files', handleMaxFiles);
    }

    const handleFileExtension = function (extensions, file, cb) {
        if (!extensions) return cb(true);

        const ext = file.name.split('.').pop().toLowerCase(); // Convert file extension to lowercase

        let shouldUpload = allowAllExtensions
            ? !extensions.split(',').map(item => item.trim().toLowerCase()).includes(ext) // Convert allowed extensions to lowercase
            : extensions.split(',').map(item => item.trim().toLowerCase()).includes(ext);

        if (!shouldUpload) {
            this.trigger('Error', {code: 'EXT_ERROR', file});
            cb(false);
        } else {
            cb(true);
        }
    }

    const handleFileSize = function (minSize, file, cb) {
        minSize = minSize * 1024 * 1024;
        if (!minSize) return cb(true);
        if (file.size < minSize) {
            this.trigger('Error', {code: 'SIZE_MIN_ERROR', file});
            cb(false);
        } else {
            cb(true);
        }
    }

    const handleMaxFiles = function (maxFiles, file, cb) {
        if (!maxFiles) return cb(true);

        let uploadedFilesLength = this.files.length;

        if (initUploadedFiles.length) {
            uploadedFilesLength = uploaderElementRef.current.querySelectorAll('.file-list-item.uploaded').length;
        }

        if (uploadedFilesLength >= maxFiles) {
            this.trigger('Error', {code: 'FILES_MAX_ERROR', file});
            cb(false);
        } else {
            cb(true);
        }
    }

    const handleError = (code, file) => {
        let msg;

        switch (code) {
            case -600:
                msg = wp.i18n.__(`File size exceeds the maximum upload size.`, 'integrate-google-drive') + `(${maxFileSize ? `${parseInt(maxFileSize)}mb` : 0})`;
                break;
            case 'SIZE_MIN_ERROR':
                msg = wp.i18n.__(`File size is less than the minimum upload size.`, 'integrate-google-drive') + `(${minFileSize}mb)`;
                break;
            case 'EXT_ERROR':
                msg = wp.i18n.__('This file type is not allowed', 'integrate-google-drive');
                break;
            case 'FILES_MAX_ERROR':
                msg = wp.i18n.__('You can not upload more than', 'integrate-google-drive') + ` ${maxFiles} ${wp.i18n.__('files', 'integrate-google-drive')}`;
                break;
            default:
                msg = file.error;
                break;
        }

        file.error = msg;

        // update filesRef
        if (filesRef.current.find(item => item.id === file.id)) {
            const fileIndex = filesRef.current.findIndex(item => item.id === file.id);
            filesRef.current[fileIndex] = file;
        } else {
            filesRef.current = [...filesRef.current, file];
        }

        setInProgress([...inProgress]);

    }

    const handleFileUploaded = (file, result) => {
        // Remove from inProgress
        setInProgress(prev => prev.filter(item => item.id !== file.id));

        // Check for the next update
        mayUploadNext(file);

        let uploadedFile = JSON.parse(result.response);

        if (!uploadedFile) return;

        uploadedFile = {
            ...uploadedFile,
            type: uploadedFile.mimeType,
            accountId: uploadFolderRef.current?.accountId,
            pluploadId: file.id,
            path: enableFolderUpload && getFileRelativePath(file), // Only used when folder upload is enabled and move the folder to entry folder
        };

        let postData = {file: uploadedFile};

        if (isWooCommerceUploader) {
            postData.wcItemId = wcItemId;
            postData.wcOrderId = wcOrderId;
            postData.wcProductId = wcProductId;
        }

        setUploadedFiles(prev => [...prev, uploadedFile]);

        // remove file from queue
        setQueue(prev => prev.filter(item => item.id !== file.id));

        // post process uploaded file
        wp.ajax.post('igd_file_uploaded', {
            shortcodeId,
            ...postData,
            nonce: nonce || igd.nonce,
        });

    };

    const processUpload = (file) => {
        if (!file) return;

        uploaderRef.current.stop();
        setPercentage(0);

        setInProgress(prev => {
            return prev.find(item => item.id === file.id) ? prev : [...prev, file];
        });

        const path = getFileRelativePath(file);

        wp.ajax.post('igd_get_upload_url', {
            shortcodeId,
            data: {
                name: file.name,
                queueIndex: filesRef.current.findIndex(item => item.id === file.id) + 1,
                size: file.size,
                type: file.type,
                fileId: file.id,
                description: file.description,
                uploadFileName,
                folderNameTemplate,
                folderId: uploadFolderRef.current?.id,
                accountId: uploadFolderRef.current?.accountId,
                path: path && path.substring(0, path.lastIndexOf("\/") + 1),
                overwrite,
                isWooCommerceUploader,
                wcProductId,
                wcOrderId,
                wcItemId,
            },
            nonce: igd.nonce,
        }).done((url) => {
            uploaderRef.current.setOption('url', url);
            uploaderRef.current.start();
        }).fail((error) => {
            console.log(error);

            mayUploadNext(file);

            setQueue(queue.filter(item => item.id !== file.id));
            setInProgress(queue.filter(item => item.id !== file.id));

            setPercentage(0);

            // Set error to filesRef
            filesRef.current = filesRef.current.map(item => {
                if (item.id === file.id) {
                    item.error = error?.error || error;
                }
                return item;
            });

            if (isFormUploader) {
                //check if the file is the last file in the queue
                if (queue.length === 1) {
                    if (submitBtnRef.current?.length) {
                        // Change submit button text
                        if (submitBtnRef.current.is('input')) {
                            submitBtnRef.current.val(submitBtnTextRef.current);
                        } else {
                            submitBtnRef.current.text(submitBtnTextRef.current);
                        }
                    }
                }
            }

        });
    }

    const getUploaderOptions = () => {

        const options = {
            browse_button: uploaderElementRef.current.querySelector('.browse-files'),
            drop_element: uploaderElementRef.current,
            multipart: false,
            multi_selection: !maxFiles || maxFiles > 1,
            filters: {
                max_files: maxFiles,
                file_ext: allowAllExtensions ? allowExceptExtensions : allowExtensions,
                max_file_size: maxFileSize ? `${parseInt(maxFileSize)}mb` : 0,
                min_file_size: minFileSize,
            },
            init: {

                FilesAdded: (uploader, files) => {

                    setIsComplete(false);

                    // add folder id to files
                    files = files.map(file => {
                        file.folder = uploadFolderRef.current;
                        return file;
                    });

                    setQueue(prev => [...prev, ...files]);

                    // update filesRef
                    filesRef.current = [...filesRef.current, ...files];

                    setInProgress(prev => {
                        let newInProgress = [...prev];

                        // Check the updated inProgress length here
                        if (uploadImmediately && !prev.length) {
                            processUpload(files[0]);

                            newInProgress = [...prev, files[0]];
                        }

                        return newInProgress;
                    });

                },

                FilesRemoved: (uploader, removed) => {
                    setQueue(prev => prev.filter(item => !removed.find(removedItem => removedItem.id === item.id)));

                    // update filesRef
                    filesRef.current = filesRef.current.filter(item => !removed.find(removedItem => removedItem.id === item.id));

                },

                FileUploaded: (uploader, file, result) => {
                    handleFileUploaded(file, result)
                },

                UploadProgress: (uploader, file) => {
                    setPercentage(file.percent);
                },

                UploadComplete: () => {

                    setInProgress([]);
                    setQueue([]);

                    setTimeout(() => {
                        setIsComplete(true);
                    }, (!isFormUploader && showUploadConfirmation) ? 1000 : 0);

                },

                Error: (uploader, error) => handleError(error.code, error.file),
            }
        };

        // Ensure the correct handling of allowed file extensions
        if (!allowAllExtensions && allowExtensions) {
            options.filters.mime_types = [
                {title: "Allowed files", extensions: allowExtensions.split(',').map(ext => ext.trim()).join(',')}
            ];
        }

        return options;
    }

    const pluploadInit = () => {
        pluploadAddFilters();

        uploaderRef.current = new plupload.Uploader(getUploaderOptions());

        uploaderRef.current.init();

        if (enableFolderUpload) {
            const folderUpload = new mOxie.FileInput({
                browse_button: uploaderElementRef.current.querySelector('.browse-folder'),
                directory: true
            });

            folderUpload.init();

            folderUpload.onchange = () => {
                uploaderRef.current.addFile(folderUpload.files);
            };
        }
    }

    // Init plupload
    useEffect(() => {
        pluploadInit();

        return () => {
            if (uploaderRef.current) {
                uploaderRef.current.destroy();
            }
        }
    }, []);

    // Handle Form Files
    const mapFiles = (files) => {
        return files.map(item => ({
            id: item.id,
            accountId: item.accountId,
            name: item.name,
            iconLink: item.iconLink,
            thumbnailLink: item.thumbnailLink,
            size: item.size,
            parents: item.parents,
            path: enableFolderUpload && item.path,
        }));
    };

    const updateFormFiles = () => {
        const uploaderEl = uploaderElementRef.current;
        if (!uploaderEl) return;

        const form = uploaderEl.closest('form, .frm-fluent-form');
        if (!form) return;

        const uploadList = uploaderEl.closest('.igd')?.nextElementSibling;
        const files = mapFiles(uploadedFiles);

        if (!uploadList) return;

        if (['fluentforms', 'formidableforms', 'gravityforms'].includes(isFormUploader)) {
            uploadList.value = JSON.stringify(files);
        } else {
            // ⚠️ Value format is critical for form validation – do not alter format
            const formattedValue = files.map(file => {
                const viewLink = `https://drive.google.com/file/d/${file.id}/view`;
                return `${file.name} — ( ${viewLink} )`;
            }).join(", \n\n");

            uploadList.value = formattedValue;
        }

        // Manually trigger 'change' event
        uploadList.dispatchEvent(new Event('input', {bubbles: true}));
        uploadList.dispatchEvent(new Event('change', {bubbles: true}));

        form.dispatchEvent(new Event('input', {bubbles: true}));
        form.dispatchEvent(new Event('change', {bubbles: true}));

        if (!uploadImmediately && !form.querySelector('.file-list-item.active')) {
            const submitBtn = form.querySelector('[type=submit]');
            submitBtnRef.current = submitBtn;

            if (submitBtn) {
                if (submitBtn.tagName === 'INPUT') {
                    submitBtn.value = submitBtnTextRef.current;
                } else {
                    submitBtn.textContent = submitBtnTextRef.current;
                }

                submitBtn.click();
            } else {
                form.submit();
            }
        }
    };

    // Handle complete
    useEffect(() => {

        if (!isComplete || !uploadedFiles.length) return;

        // Trigger upload complete event
        document.dispatchEvent(new CustomEvent('igd_upload_complete', {
            detail: {
                files: uploadedFiles,
                folderId: uploadFolderRef.current?.id,
            }
        }));

        if (notifications && notifications.uploadNotification) {
            wp.ajax.post('igd_notification', {
                files: uploadedFiles,
                notifications,
                type: 'upload',
                nonce: nonce || igd.nonce,
            });
        }

        if (isFormUploader) {
            updateFormFiles();
        }

    }, [isComplete]);

    // Upload Processor
    useEffect(() => {
        if (!queue.length || inProgress.length) return;

        // Process immediate upload
        if (!isFormUploader || uploadImmediately) {
            return;
        }

        // Process form upload
        if (!uploaderElementRef.current) return;

        const form = jQuery(uploaderElementRef.current).closest('form');

        if (!form.length) {
            return;
        }

        submitBtnRef.current = jQuery(form).find(':submit');

        if (!submitBtnRef.current.length) return;

        const submitBtnParent = submitBtnRef.current.parent();
        submitBtnRef.current.addClass('igd-disabled');

        const handleSubmitClick = function (e) {
            e.preventDefault();

            const isInput = submitBtnRef.current.is('input');

            submitBtnTextRef.current = isInput ? submitBtnRef.current.val() : submitBtnRef.current.text();

            if (isInput) {
                submitBtnRef.current.val(wp.i18n.__('Uploading Files...', 'integrate-google-drive'));
            } else {
                submitBtnRef.current.text(wp.i18n.__('Uploading Files...', 'integrate-google-drive'));
            }

            processUpload(queue[0]);
        }

        submitBtnParent.on('click', handleSubmitClick);

        return () => {
            if (submitBtnRef.current) {
                submitBtnParent.off('click', handleSubmitClick);
                submitBtnRef.current.removeClass('igd-disabled');
            }
        }

    }, [queue]);

    // Handle Form Reset
    useEffect(() => {
        if (!isFormUploader) return;

        const form = jQuery(uploaderElementRef.current).closest('form');

        const resetForm = function (e) {

            filesRef.current = [];

            setQueue([]);
            setInProgress([]);
            setUploadedFiles([]);

        }

        form.on('reset', resetForm);

        return () => form.off('reset', resetForm);

    }, []);

    const showConfirmation = !isFormUploader && !isWooCommerceUploader && !uploadImmediately && showUploadConfirmation && uploadedFiles.length && isComplete;

    const itemCount = queue.length || filesRef.current?.filter(item => !item.error).length;

    const status = !queue.length
        ? wp.i18n.__('Item(s) Uploaded', 'integrate-google-drive')
        : !uploadImmediately && !inProgress.length
            ? wp.i18n.__('Item(s) Selected', 'integrate-google-drive')
            : wp.i18n.__('Item(s) Uploading...', 'integrate-google-drive');

    const handleDescriptionUpdate = (isUploaded, file) => {
        const description = file.description;
        const descriptionUpdated = file.descriptionUpdated;

        if (isUploaded && description && file.hasOwnProperty('descriptionUpdated') && !descriptionUpdated) {
            const uploadedFile = uploadedFiles.find(
                (item) => item.pluploadId === file.id || item.id === file.id
            );

            Swal.fire({
                title: false,
                text: wp.i18n.__('Description has been updated.', 'integrate-google-drive'),
                icon: 'success',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                toast: true,
                customClass: {container: 'igd-swal save-settings-toast'},
                position: 'top-end',
            });

            const postData = {
                shortcodeId,
                id: uploadedFile.id,
                accountId: uploadedFile.accountId,
                description,
                nonce: nonce || igd.nonce,
            }

            if (isWooCommerceUploader) {
                postData.wcItemId = wcItemId;
                postData.wcOrderId = wcOrderId;
                postData.wcProductId = wcProductId;
            }

            wp.ajax.post('igd_update_description', postData)
                .done((response) => {
                    // update filesRef
                    filesRef.current = filesRef.current.map(item => {
                        if (item.id === file.id) {
                            item.descriptionUpdated = true;
                        }
                        return item;
                    });

                })
                .fail((error) => {
                    console.log(error);
                });

        }
    };

    return (
        <div
            onDragEnter={() => uploaderElementRef.current.classList.add('drag-active')}
            onDragLeave={() => uploaderElementRef.current.classList.remove('drag-active')}
            onDrop={() => uploaderElementRef.current.classList.remove('drag-active')}
            ref={uploaderElementRef}
            className={`igd-file-uploader igd-module-uploader ${isFormUploader ? ' igd-form-uploader' : ''} ${!!isRequired && !queue.length && !filesRef.current.length ? 'required-error' : ''}  ${showConfirmation ? 'show-confirmation' : ''} `}
        >

            {/* Body */}
            <div className="igd-file-uploader-body">

                {/* Upload confirmation */}
                {!!showConfirmation &&
                    <div className={`upload-confirmation`}>
                        <div className="upload-confirmation-message"
                             dangerouslySetInnerHTML={{__html: uploadConfirmationMessage}}></div>
                        {(!maxFiles || maxFiles > uploadedFiles.length) &&
                            <button type="button" className="igd-btn btn-primary"
                                    onClick={() => setIsComplete(false)}>
                                {wp.i18n.__("Upload More Files", "integrate-google-drive")}
                            </button>
                        }
                    </div>
                }

                <div className="igd-file-uploader-inner">

                    {showUploadLabel &&
                        <h4 className={`igd-file-uploader-label`}>{uploadLabelText}</h4>}


                    {/* Upload folder selection */}
                    {(!privateFolders && uploadFolderSelection) &&
                        <div className="upload-folder-selection">
                        <span className="upload-folder-selection-text">{folderSelectionLabel}</span>

                            <select
                                onChange={(e) => {
                                    const folderId = e.target.value;

                                    // update uploadFolderRef
                                    uploadFolderRef.current = {id: folderId, accountId: activeAccount.id};
                                }}
                            >
                                {(uploadFolders).map(folder => (
                                    <option key={folder.id} value={folder.id}
                                            selected={folder.id === uploadFolderRef.current.id}
                                    >
                                        {folder.name}
                                    </option>
                                ))}

                            </select>
                        </div>
                    }

                    <i className="dashicons dashicons-cloud-upload"></i>

                    <p>{wp.i18n.__("Drag and drop files here", "integrate-google-drive")}</p>
                    <p className="or">{wp.i18n.__("OR", "integrate-google-drive")}</p>

                    <div className="igd-file-uploader-buttons">

                        <button type="button" className={`browse-files`}
                                onMouseOver={() => {
                                    if (navigator.userAgent.match(/(iPad|iPhone|iPod)/g)) {
                                        uploaderRef.current.refresh();
                                    }
                                }}
                        >
                            <svg width="24" height="24" viewBox="0 0 1024 1024"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M842.24 355.413333l-232.106667-256a42.666667 42.666667 0 0 0-31.573333-14.08h-298.666667A107.946667 107.946667 0 0 0 170.666667 192v640A107.946667 107.946667 0 0 0 279.893333 938.666667h464.213334A107.946667 107.946667 0 0 0 853.333333 832V384a42.666667 42.666667 0 0 0-11.093333-28.586667zM597.333333 213.333333l116.906667 128h-85.333333a33.706667 33.706667 0 0 1-31.573334-36.266666z m146.773334 640H279.893333a22.613333 22.613333 0 0 1-23.893333-21.333333v-640a22.613333 22.613333 0 0 1 23.893333-21.333333H512v134.4A119.04 119.04 0 0 0 627.626667 426.666667H768v405.333333a22.613333 22.613333 0 0 1-23.893333 21.333333z"
                                    fill="currentColor"/>
                                <path
                                    d="M597.333333 554.666667h-42.666666v-42.666667a42.666667 42.666667 0 0 0-85.333334 0v42.666667h-42.666666a42.666667 42.666667 0 0 0 0 85.333333h42.666666v42.666667a42.666667 42.666667 0 0 0 85.333334 0v-42.666667h42.666666a42.666667 42.666667 0 0 0 0-85.333333z"
                                    fill="currentColor"/>
                            </svg>
                            <span>{wp.i18n.__('Browse Files', 'integrate-google-drive')}</span>
                        </button>

                        {enableFolderUpload &&
                            <button type="button" className={`browse-folder`}
                                    onMouseOver={() => {
                                        if (navigator.userAgent.match(/(iPad|iPhone|iPod)/g)) {
                                            uploaderRef.current.refresh();
                                        }
                                    }}
                            >
                                <svg width="24" height="24" viewBox="0 0 1024 1024"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M597.333333 554.666667h-42.666666v-42.666667a42.666667 42.666667 0 0 0-85.333334 0v42.666667h-42.666666a42.666667 42.666667 0 0 0 0 85.333333h42.666666v42.666667a42.666667 42.666667 0 0 0 85.333334 0v-42.666667h42.666666a42.666667 42.666667 0 0 0 0-85.333333z"
                                        fill="currentColor"/>
                                    <path
                                        d="M832 300.8h-298.666667L421.12 165.12a42.666667 42.666667 0 0 0-32.853333-15.786667H192A105.386667 105.386667 0 0 0 85.333333 253.013333v517.973334a105.386667 105.386667 0 0 0 106.666667 103.68h640a105.386667 105.386667 0 0 0 106.666667-103.68V404.48a105.386667 105.386667 0 0 0-106.666667-103.68z m21.333333 469.333333a19.626667 19.626667 0 0 1-21.333333 18.346667h-640a19.626667 19.626667 0 0 1-21.333333-18.346667V253.013333a19.626667 19.626667 0 0 1 21.333333-18.346666h176.213333l110.933334 135.68a42.666667 42.666667 0 0 0 32.853333 15.786666h320a19.626667 19.626667 0 0 1 21.333333 18.346667z"
                                        fill="currentColor"/>
                                </svg>
                                <span>{wp.i18n.__('Browse Folder', 'integrate-google-drive')}</span>
                            </button>
                        }

                    </div>

                    {/*--- Close ---*/}
                    {isUpload &&
                        <button
                            className={`cancel-upload igd-btn ${isComplete ? 'btn-success' : 'btn-danger'}`}
                            onClick={() => setIsUpload(false)}
                            title={`${wp.i18n.__('Close Uploader', 'integrate-google-drive')}`}
                            aria-label={`${wp.i18n.__('Close Uploader', 'integrate-google-drive')}`}
                        >
                            {isComplete ?
                                <>
                                    <i className="dashicons dashicons-saved"></i>
                                    <span>{wp.i18n.__('Done', 'integrate-google-drive')}</span>
                                </>
                                :
                                <>
                                    <i className="dashicons dashicons-no-alt"></i>
                                    <span>{wp.i18n.__('Cancel', 'integrate-google-drive')}</span>
                                </>
                            }
                        </button>
                    }

                    <div className="upload-info">
                        {!!minFileSize &&
                            <span
                                className="max-size-label">{wp.i18n.__("Min File Size:", "integrate-google-drive")} {minFileSize}MB</span>
                        }

                        {!!maxFileSize &&
                            <span
                                className="max-size-label">{wp.i18n.__("Max File Size:", "integrate-google-drive")} {maxFileSize}MB</span>
                        }

                    </div>

                </div>
            </div>

            {/* File list wrapper */}
            {!!filesRef.current.length && !showConfirmation &&
                <div className={`file-list-wrapper`}>

                    {/* Header */}
                    <div className="file-list-header">
                        <span className="file-count">{itemCount}</span>
                        <span className="file-status-text">{status}</span>
                    </div>

                    {/* File list */}
                    <div className="file-list">
                        {[...new Set(filesRef.current)].map(file => {

                            const {id, name, size, type, error, description} = file;

                            const isInProgress = inProgress.find(item => item.id === id);
                            const isUploaded = uploadedFiles.find(item => item.id === id || item.pluploadId === id);
                            const isPaused = pausedItems.find(item => item.id === id);

                            const relativePath = getFileRelativePath(file);

                            const renderMediaPreview = (type, file) => {
                                let previewURL = getTypeIcon(type);

                                if (file.id) {
                                    if (file.thumbnailLink) {
                                        previewURL = file.thumbnailLink;
                                    }
                                } else if (type?.startsWith('image/')) {
                                    previewURL = URL.createObjectURL(file?.getNative());
                                }

                                return (
                                    <img
                                        width={32}
                                        height={32}
                                        src={previewURL}
                                    />
                                );
                            };

                            return (
                                <div
                                    key={id}
                                    className={`file-list-item ${isInProgress ? 'active' : ''} ${isUploaded ? 'uploaded' : ''}`}
                                    id={id}>

                                    {renderMediaPreview(type, file)}

                                    <div className="file-info">
                                        <div className="upload-item">
                                            <span
                                                className="upload-item-name">{relativePath ? relativePath : name}</span>
                                            <span className="upload-item-size">({plupload.formatSize(size)})</span>
                                        </div>

                                        <div className={`file-info-percentage`}>
                                            {!!isUploaded && !error && <i className="dashicons dashicons-saved"></i>}

                                            {!isUploaded && isInProgress && !error && !isPaused &&
                                                <div className="igd-spinner"></div>}

                                            {!isUploaded && !!isInProgress && !!percentage &&
                                                <span className="percentage">{percentage}%</span>
                                            }

                                            {/* Pause Upload */}
                                            {!isUploaded && isInProgress && !isPaused && !error &&
                                                <i className="dashicons dashicons-controls-pause"
                                                   onClick={() => {
                                                       uploaderRef.current.stop();
                                                       setPausedItems([...pausedItems, file]);
                                                   }}
                                                ></i>
                                            }

                                            {/* Start Upload */}
                                            {!isUploaded && isPaused && !error &&
                                                <i className="dashicons dashicons-controls-play"
                                                   onClick={() => {
                                                       uploaderRef.current.start();
                                                       setPausedItems(pausedItems.filter(item => item.id !== id));
                                                   }}
                                                ></i>
                                            }

                                            <i className="remove-file dashicons dashicons-no-alt"
                                               onClick={() => {
                                                   uploaderRef.current.removeFile(file);

                                                   setQueue(files => files.filter(item => item.id !== id));

                                                   // update filesRef
                                                   filesRef.current = filesRef.current.filter(item => item.id !== id);

                                                   if (isUploaded) {
                                                       setUploadedFiles(uploadedFiles => uploadedFiles.filter(item => item.id !== isUploaded.id));

                                                       const postData = {
                                                           id: isUploaded.id,
                                                           account_id: isUploaded.accountId,
                                                           nonce: igd.nonce,
                                                       }

                                                       if (isWooCommerceUploader) {
                                                           postData.isWooCommerceUploader = true;
                                                           postData.wcProductId = wcProductId;
                                                           postData.wcOrderId = wcOrderId;
                                                           postData.wcItemId = wcItemId;
                                                       }

                                                       wp.ajax.post('igd_upload_remove_file', {
                                                           shortcodeId,
                                                           ...postData,
                                                           nonce: nonce || igd.nonce,
                                                       });

                                                   } else {
                                                       if (isInProgress) {
                                                           uploaderRef.current.stop();
                                                           setPercentage(0);
                                                           mayUploadNext(file);
                                                       }

                                                       uploaderRef.current.removeFile(file);
                                                       uploaderRef.current.setOption('url', '');

                                                       setInProgress(prev => prev.filter(item => item.id !== id));
                                                   }
                                               }}
                                            ></i>
                                        </div>

                                        {error ? <span className={`file-info-error`}>{error}</span> :
                                            <div className="file-info-progress"
                                                 style={{'--percentage': !!isInProgress ? `${!!percentage ? percentage : 2}%` : 0}}>
                                                <span className="file-info-progress-bar"></span>
                                            </div>
                                        }

                                        {(enableUploadDescription && !error) &&
                                            <textarea
                                                className="file-description"
                                                placeholder={wp.i18n.__('Add a description...', 'integrate-google-drive')}
                                                rows={2}
                                                disabled={isInProgress || isPaused || error}
                                                onChange={(e) => {
                                                    const description = e.target.value;

                                                    // update filesRef
                                                    filesRef.current = filesRef.current.map(item => {
                                                        if (item.id === id) {
                                                            item.description = description;
                                                            item.descriptionUpdated = false;
                                                        }
                                                        return item;
                                                    });

                                                    // Update queue files
                                                    setQueue((prev) =>
                                                        prev.map((item) =>
                                                            item.id === id ? {
                                                                ...item,
                                                                description,
                                                                descriptionUpdated: false
                                                            } : item
                                                        )
                                                    );


                                                }}
                                                autoFocus={true}
                                                onBlur={() => handleDescriptionUpdate(isUploaded, file)}
                                                onKeyDown={(e) => {
                                                    if (e.key === 'Enter') {
                                                        e.preventDefault();
                                                        handleDescriptionUpdate(isUploaded, file);
                                                    }
                                                }}
                                                value={description}
                                            ></textarea>
                                        }

                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Upload button */}
                    {!!queue.length && !isFormUploader && !isWooCommerceUploader && !uploadImmediately &&
                        <button
                            type={'button'}
                            className={`igd-btn start-upload`}
                            onClick={() => {
                                processUpload(queue[0]);
                            }}
                        >
                            <i className={`dashicons dashicons-cloud-upload`}></i>

                            {!!inProgress.length ? wp.i18n.__('Uploading Files...', 'integrate-google-drive') : wp.i18n.__('Start Upload', 'integrate-google-drive')}
                        </button>
                    }


                </div>
            }

        </div>
    )
}

