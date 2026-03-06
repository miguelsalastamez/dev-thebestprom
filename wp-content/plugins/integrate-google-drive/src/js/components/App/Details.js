import {formatDate, getMimeType, humanFileSize, isRootFolder} from "../../includes/functions";
import AppContext from "../../contexts/AppContext";


const {useContext, useState, useEffect} = React;

export default function Details() {

    const context = useContext(AppContext);
    const {
        shortcodeId,
        files,
        activeAccount,
        activeFile,
        setShowDetails,
        activeFolder,
        setFiles,
        listFiles,
        setAllFiles,
        initParentFolder,
        initFolders,
        nonce,
        activeFiles,
    } = context;

    const [activeItem, setActiveItem] = useState(null);

    const {
        id,
        accountId,
        name,
        iconLink,
        type,
        size,
        owner,
        updated,
        created,
        description,
        parents,
    } = activeItem || {};

    const [location, setLocation] = useState(null);

    const [isEditDescription, setIsEditDescription] = useState(false);
    const [editDescription, setEditDescription] = useState('');

    const saveDescription = () => {

        const items = files.map(item => item.id === id ? {...item, description: editDescription} : item);

        setFiles([...items]);
        setActiveItem({...activeItem, description: editDescription});
        setAllFiles(prevFiles => ({...prevFiles, [activeFolder?.id]: [...items]}));

        wp.ajax.post('igd_update_description', {
            shortcodeId,
            id,
            accountId,
            description: editDescription,
            nonce: nonce || igd.nonce,
        }).done(() => {
            Swal.fire({
                title: wp.i18n.__('Updated!', 'integrate-google-drive'),
                text: wp.i18n.__('Description has been updated.', 'integrate-google-drive'),
                icon: 'success',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                toast: true,
                customClass: {container: 'igd-swal'},
                position: 'top-end',
            });

        });

    }

    useEffect(() => {
        setActiveItem(null);

        if (activeFile) {
            setActiveItem(activeFile);
        } else if (activeFiles.length > 0) {
            setActiveItem(activeFiles[0]);
        } else if (activeFolder && !isRootFolder(activeFolder.id, activeAccount)) {
            setActiveItem(activeFolder);
        }

    }, [activeFile, activeFiles, activeFolder]);

    useEffect(() => {

        if (!activeItem?.parents) {
            setLocation(null);
            return;
        }

        const parentId = activeItem.parents[0];
        if (activeFolder?.id === parentId) {
            setLocation(activeFolder);
            return;
        }

        setLocation('loading');

        // Get parent file
        wp.ajax.post('igd_get_file', {
            shortcodeId,
            id: parentId,
            accountId,
            nonce: nonce || igd.nonce,
        }).done(file => {
            setLocation(file);
        }).fail(error => {
            console.log(error);

            setLocation(null);
        });

    }, [activeItem, activeFolder]);

    const icon = iconLink?.replace('/16/', '/64/');

    const items = [
        {
            key: 'type',
            label: wp.i18n.__('Type', 'integrate-google-drive'),
            value: getMimeType(type)
        },
        {
            key: 'size',
            label: wp.i18n.__('Size', 'integrate-google-drive'),
            value: size && humanFileSize(size)
        },
        {
            key: 'owner',
            label: wp.i18n.__('Owner', 'integrate-google-drive'),
            value: owner,
        },
        {
            key: 'updated',
            label: wp.i18n.__('Updated', 'integrate-google-drive'),
            value: updated && formatDate(updated)
        },
        {
            key: 'created',
            label: wp.i18n.__('Created', 'integrate-google-drive'),
            value: created && formatDate(created)
        },

    ];

    const adjustWidth = () => {

        // Cache jQuery selectors
        const $detailsWrap = jQuery('.igd-details-wrap'); // Assuming the class is '.details-wrap'
        const detailsWidth = $detailsWrap.outerWidth();

        const fileBrowserBody = $detailsWrap.parents('.igd-file-browser').find('.igd-body');
        const $sidebarWrap = $detailsWrap.parents('.igd-file-browser').find('.igd-sidebar-wrap');

        // Calculate the initial width
        let newWidth = `calc(100% - ${detailsWidth}px)`;

        // If .igd-sidebar-wrap exists, include its width in the calculation
        if ($sidebarWrap.length > 0) {
            const sidebarWidth = $sidebarWrap.outerWidth();

            newWidth = `calc(100% - ${detailsWidth + sidebarWidth}px)`;
        }

        // Apply the calculated width
        fileBrowserBody.css('width', newWidth);

        return () => {
            // Reset the width on cleanup
            fileBrowserBody.css('width', '100%');
        };
    };

    useEffect(() => {
        // Return the cleanup function to be called on component unmount
        return adjustWidth();
    }, []);

    return (
        <div className="igd-details-wrap">
            <div className="igd-details">

                <i className="close-details dashicons dashicons-no"
                   onClick={() => {
                       setShowDetails(false);
                       localStorage.removeItem('igd_show_details');
                   }}
                ></i>

                {(activeItem && !isRootFolder(activeItem.id, activeAccount)) ?
                    <>
                        <div className="details-item name">
                            <img src={icon}/>
                            <span>{name}</span>
                        </div>

                        {
                            items.map(item => {
                                const {key, label, value} = item;

                                if (!value) return;

                                return (
                                    <div
                                        key={key}
                                        className={`details-item field-${key}`}>
                                        <span className="details-item-label">{label}</span>
                                        <span className="details-item-value">{value}</span>
                                    </div>
                                )
                            })
                        }

                        {/* Dimension */}
                        {activeItem?.imageMediaMetadata?.width && activeItem?.imageMediaMetadata?.height &&
                            <div
                                className={`details-item field-dimension`}>
                                <span
                                    className="details-item-label">{wp.i18n.__('Dimension', 'integrate-google-drive')}</span>
                                <span
                                    className="details-item-value">{activeItem?.imageMediaMetadata?.width} x {activeItem?.imageMediaMetadata?.height}</span>
                            </div>
                        }

                        {/* Location */}
                        {(!!parents?.length && activeFolder && activeFolder.id !== initParentFolder?.id) &&
                            <div className={`details-item field-location`}>
                            <span
                                className="details-item-label">{wp.i18n.__('Location', 'integrate-google-drive')}</span>

                                {location &&
                                    <div className="details-item-value">
                                        {location === 'loading' ?
                                            <span className="igd-spinner"></span>
                                            :
                                            <div className="location-wrap"
                                                 onClick={() => {
                                                     listFiles(location);
                                                 }}
                                            >
                                                <img src={location.iconLink}/>
                                                <span>{location.name}</span>
                                            </div>
                                        }
                                    </div>
                                }

                            </div>
                        }

                        {/* Description */}
                        {(activeItem?.description || !initFolders) &&
                            <div className={`details-item field-description`}>
                            <span
                                className="details-item-label">{wp.i18n.__('Description', 'integrate-google-drive')}</span>

                                {!initFolders &&
                                    <i className={`dashicons ${isEditDescription ? 'dashicons-saved' : 'dashicons-edit'}`}
                                       onClick={() => {
                                           if (isEditDescription) {
                                               saveDescription();
                                               setIsEditDescription(false);
                                           } else {
                                               setIsEditDescription(true);
                                               setEditDescription(description);
                                           }
                                       }}
                                    ></i>
                                }

                                {!isEditDescription &&
                                    (!!description ?
                                            <span className="details-item-value">{description}</span>
                                            :
                                            <span
                                                className="description-placeholder">{wp.i18n.__('Add description', 'integrate-google-drive')}</span>
                                    )
                                }

                                {!initFolders && isEditDescription &&
                                    <textarea
                                        onChange={(e) => setEditDescription(e.target.value)}
                                        value={editDescription}
                                        rows={4}
                                    ></textarea>
                                }


                            </div>
                        }

                    </>
                    :
                    <div className="details-placeholder">
                        <i className="dashicons dashicons-pressthis"></i>
                        <span>{wp.i18n.__('Select a file or folder to view its details.', 'integrate-google-drive')}</span>
                    </div>
                }
            </div>
        </div>
    )
}