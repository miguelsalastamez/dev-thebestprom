import AppContext from "../../contexts/AppContext";
import Tree from "./Body/Tree";

import {getRootFolders, isFolder, useMounted} from "../../includes/functions";

const {useContext, useState, useEffect} = React;

export default function RootFolders({isSidebar, isCollapsed}) {

    const context = useContext(AppContext);
    const {
        activeAccount,
        initFolders,
        activeFolder,
        setActiveFolder,
        selectedFolders,
        shortcodeBuilderType,
        listFiles,
        setSelectedFolders,
        selectionType,
    } = context;

    // Check types
    const isUploader = 'uploader' === shortcodeBuilderType;
    const isEmbed = 'embed' === shortcodeBuilderType;
    const isSlider = 'slider' === shortcodeBuilderType;
    const isListModule = 'list' === shortcodeBuilderType;

    let items = getRootFolders(false, activeAccount);

    if (initFolders) {
        items = items.filter(item => initFolders.includes(item.id));
    }

    let canSelect = shortcodeBuilderType && !isListModule && !isEmbed && !isSlider;

    if (isUploader) {
        canSelect = !selectedFolders.length || selectedFolders[0].id === 'root';
    } else if ('parent' === selectionType) {
        canSelect = true;
    }

    const [childFolders, setChildFolders] = useState({});
    const [openFolders, setOpenFolders] = useState([]);

    const [isLoading, setIsLoading] = useState(null);

    const getParentFolders = (folder) => {
        setIsLoading(folder.id);

        return wp.ajax.post('igd_get_parent_folders', {
            folder,
            nonce: igd.nonce,
        }).done((folders) => {

            // Check if folders object is empty
            if (Object.keys(folders).length === 0) return;

            Object.keys(folders).forEach(folderId => {

                // Add parent folders object to open folders
                setOpenFolders(openFolders => [...openFolders, folderId]);

                // Set child folders
                const children = folders[folderId]['children'];
                const filteredChildren = children?.filter(file => isFolder(file)) || [];
                setChildFolders(childFolders => ({...childFolders, [folderId]: filteredChildren}));
            });

        }).fail(error => {
            console.log(error);
        }).always(() => {
            setIsLoading(null);
        });
    }

    const getChildFolders = (folder) => {
        if (!folder) return;

        setOpenFolders(openFolders => [...openFolders, folder.id]);

        setIsLoading(folder.id);

        return wp.ajax.post('igd_get_files', {
            data: {folder,},
            nonce: igd.nonce,
        }).done((data) => {
            let {files} = data;

            const folders = files.filter(file => isFolder(file));
            setChildFolders(items => ({...items, [folder.id]: folders || []}));

        }).fail(error => {
            console.log(error);
        }).always(() => {
            setIsLoading(null);
        });
    }

    // Initially load parent folders
    useEffect(() => {
        if (!activeFolder || !isSidebar || isCollapsed) return;
        getParentFolders(activeFolder);
    }, []);

    // Load child folders on folder change
    useEffect(() => {
        if (!activeFolder || !isSidebar || isCollapsed) return;
        getChildFolders(activeFolder);
    }, [activeFolder]);

    return (
        items.map(item => {
            const {id, name, iconLink} = item;

            const isSelected = selectedFolders && !!selectedFolders.find(item => item.id === id);

            const isOpen = openFolders.includes(id);

            return (
                <>
                    <div
                        key={id}
                        className={`${id === activeFolder?.id ? 'active' : ''} file-item root-item folder-item`}

                        onClick={(e) => {
                            e.stopPropagation();
                            setActiveFolder(item);
                            listFiles(item);
                        }}
                    >

                        {isSidebar && ['root', 'shared-drives', 'computers'].includes(id) && isLoading !== id &&
                            <i
                                className={`dashicons ${isOpen ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-right-alt2'}`}
                                onClick={(e) => {
                                    e.stopPropagation();

                                    if (!childFolders[id]) {
                                        getChildFolders(item);
                                    }

                                    setOpenFolders(openFolders => openFolders.includes(id) ? openFolders.filter(folder => folder !== id) : [...openFolders, id]);
                                }}
                            ></i>
                        }

                        {isLoading === id && <div className="igd-spinner"></div>}


                        {/* File Item Checkbox */}
                        {!isSidebar && canSelect && 'root' === id &&
                            <div
                                className={`file-item-checkbox ${selectedFolders.find(item => item.id === id) ? 'checked' : ''}`}
                                onClick={(e) => {
                                    e.stopPropagation();

                                    if (isSelected) {
                                        setSelectedFolders(selectedFolders => [...selectedFolders.filter(item => item.id !== id)]);
                                    } else {
                                        setSelectedFolders(selectedFolders => [...selectedFolders, item]);
                                    }

                                }}
                            >
                                <span className={`box`}></span>
                            </div>
                        }

                        <img src={iconLink} alt={name}/>
                        <span>{name}</span>

                    </div>

                    {(isSidebar && isOpen && !isCollapsed) &&
                        <Tree
                            folders={childFolders[id]}
                            childFolders={childFolders}
                            getChildFolders={getChildFolders}
                            openFolders={openFolders}
                            setOpenFolders={setOpenFolders}
                            activeFolder={activeFolder}
                            isLoading={isLoading}
                            listFiles={listFiles}
                        />
                    }

                </>
            );
        })
    )
}