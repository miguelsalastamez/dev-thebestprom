export default function Tree({
                                 folders,
                                 childFolders,
                                 getChildFolders,
                                 openFolders,
                                 setOpenFolders,
                                 activeFolder,
                                 isLoading,
                                 listFiles,
                             }) {

    const handleFolderClick = (folder) => {
        const {id} = folder;

        if (!childFolders[id]) {
            getChildFolders(folder);
        }

        setOpenFolders(openFolders.includes(id) ? openFolders.filter(item => item !== id) : [...openFolders, id]);
    }

    return (
        <div className={`sub-item`}>
            {
                folders?.map(item => {
                    const {id, name, iconLink} = item;
                    const isActiveFolder = id === activeFolder?.id;
                    const isOpen = openFolders.includes(id);

                    return (
                        <div className="tree-item-wrap">
                            <div
                                key={id}
                                className={`${isActiveFolder ? 'active' : ''} tree-item folder-item`}
                                onClick={() => {
                                    // setActiveFolder(item);
                                    listFiles(item);
                                }}
                            >
                                {(isLoading !== id) &&
                                    <i className={`dashicons ${isOpen ? `dashicons-arrow-down-alt2` : `dashicons-arrow-right-alt2`}`}
                                       onClick={(e) => {
                                           e.stopPropagation();

                                           handleFolderClick(item)
                                       }}></i>
                                }

                                {isLoading === id && <div className="igd-spinner"></div>}

                                <img src={iconLink} alt={name}/>
                                <span>{name}</span>
                            </div>

                            {isOpen &&
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

                        </div>
                    )
                })
            }
        </div>
    )
}