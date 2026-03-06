import {getRootFolders, isFolder} from "../../includes/functions";

const {useState, useEffect} = React;

export default function CopyModal({context}) {

    const {
        shortcodeId,
        activeAccount,
        setAllFiles,
        allFiles,
        initFolders,
        activeFiles,
        activeFile,
        isOptions,
        nonce,
    } = context;

    const files = isOptions ? activeFiles : [activeFile];

    const [loading, setLoading] = useState(null);
    const [childFolders, setChildFolders] = useState(null);
    const [selectedFolder, setSelectedFolder] = useState(null);
    const [openFolders, setOpenFolders] = useState([]);
    const [isCopying, setIsCopying] = useState(false);

    // Initially get the root folders
    useEffect(() => {
        const initFolder = {
            id: 'root',
            accountId: activeAccount['id'],
        }

        getFolders(initFolder).then(data => {
            setOpenFolders([initFolder]);
        });

    }, []);

    function getFolders(folder) {
        setLoading(folder.id);

        return wp.ajax.post('igd_get_files', {
            shortcodeId,
            data: {folder,},
            nonce: nonce || igd.nonce,
        }).done((data) => {
            let {files} = data;

            files = files.filter(file => isFolder(file));

            setChildFolders(folders => ({...folders, [folder.id]: files}));
        }).fail(error => {
            console.log(error);
        }).always(() => {
            setLoading(null);
        });
    }

    function copyFiles() {
        setIsCopying(true);

        wp.ajax.post('igd_copy_file', {
            shortcodeId,
            files: files,
            folder_id: selectedFolder['id'],
            nonce: nonce || igd.nonce,
        }).done((files) => {
            Swal.fire({
                title: wp.i18n.__('Success', 'integrate-google-drive'),
                text: wp.i18n._n(
                    'File moved successfully',
                    'Files moved successfully',
                    files.length,
                    'integrate-google-drive'
                ),
                icon: 'success',
                toast: true,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
            });

            if (allFiles[selectedFolder['id']]) {
                const items = [...allFiles[selectedFolder['id']], ...files];
                setAllFiles(prevFiles => ({...prevFiles, [selectedFolder['id']]: items}));
            }

        }).fail((error) => {
            console.log(error);

            Swal.fire({
                title: wp.i18n.__('Error', 'integrate-google-drive'),
                text: wp.i18n.__('Error copying file(s)', 'integrate-google-drive'),
                icon: 'error',
                toast: true,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        }).always(() => {
            setIsCopying(false);
        })
    }

    let isDisabled = false;
    if (!!selectedFolder && ['computers', 'shared', 'starred', 'shared-drives'].includes(selectedFolder['id'])) {
        isDisabled = true;
    }

    let rootFolders = getRootFolders(false, activeAccount);

    if (initFolders) {
        rootFolders = initFolders.filter(item => isFolder(item));
    }

    return (
        <div className="move-folders-wrap">
            <div className="move-folders">
                {
                    rootFolders.map(item => {
                        const {id, name, iconLink} = item;

                        const isOpen = openFolders.find(item => item['id'] === id);
                        const isActive = !!selectedFolder && selectedFolder['id'] === id;

                        return (
                            <>
                                <div key={id} className={`move-folder ${isActive ? 'active' : ''}`}
                                     onClick={() => {
                                         if (isActive) {
                                             setSelectedFolder(null);
                                         } else {
                                             setSelectedFolder(item);
                                         }
                                     }}>

                                    <i className={`dashicons dashicons-arrow-${isOpen ? 'down' : 'right'}`}
                                       onClick={(e) => {
                                           e.stopPropagation();

                                           setOpenFolders(openFolders => isOpen ? openFolders.filter(folder => folder['id'] !== id) : [...openFolders, item])

                                           if (isOpen || (!!childFolders && !!childFolders[id])) return;

                                           getFolders(item)
                                       }}></i>


                                    <img src={iconLink} alt={name}/>

                                    <div className={`file-item-checkbox ${isActive ? 'checked' : ''}`}>
                                        <span className={`box`}></span>
                                    </div>

                                    <span>{name}</span>
                                    {loading === id && <div className="igd-spinner"></div>}
                                </div>

                                {isOpen &&
                                    <FolderList
                                        folders={childFolders[id] ? childFolders[id] : []}
                                        childFolders={childFolders}
                                        selectedFolder={selectedFolder}
                                        setSelectedFolder={setSelectedFolder}
                                        getFolders={getFolders}
                                        openFolders={openFolders}
                                        setOpenFolders={setOpenFolders}
                                        loading={loading}
                                    />
                                }
                            </>
                        )
                    })
                }

            </div>

            <button disabled={isDisabled} type={'button'}
                    className={`igd-btn ${isDisabled ? 'disabled' : 'btn-primary'}`}
                    onClick={copyFiles}>
                {isCopying && <div className="igd-spinner"></div>}
                {isCopying ? wp.i18n.sprintf(wp.i18n.__('Copying %s files', 'integrate-google-drive'), files.length) : wp.i18n.__('Copy', 'integrate-google-drive')}
            </button>
        </div>

    )

}


function FolderList({
                        folders,
                        childFolders,
                        selectedFolder,
                        setSelectedFolder,
                        getFolders,
                        openFolders,
                        setOpenFolders,
                        loading
                    }) {
    return (
        folders.length ?
            folders.map(item => {

                const isOpen = openFolders.find(folder => folder['id'] === item['id']);
                const isActive = !!selectedFolder && selectedFolder['id'] === item['id'];

                return (
                    <div key={item['id']} className="move-folder-wrap">
                        <div className={`move-folder ${isActive ? 'active' : ''}`}
                             onClick={() => {
                                 if (isActive) {
                                     setSelectedFolder(null);
                                 } else {
                                     setSelectedFolder(item);
                                 }
                             }}>

                            <i className={`dashicons dashicons-arrow-${isOpen ? 'down' : 'right'}`}
                               onClick={(e) => {
                                   e.stopPropagation();

                                   if (isOpen) {
                                       setOpenFolders(openFolders => openFolders.filter(folder => folder['id'] !== item['id']))
                                   } else {
                                       setOpenFolders(openFolders => [...openFolders, item])
                                   }

                                   if (isOpen || (!!childFolders && !!childFolders[item['id']])) return;

                                   getFolders(item)
                               }}> </i>

                            <img src={item.iconLink} alt={item.name}/>
                            <div className={`file-item-checkbox ${isActive ? 'checked' : ''}`}>
                                <span className={`box`}></span>
                            </div>

                            <span>{item.name}</span>

                            {loading === item['id'] && <div className="igd-spinner"></div>}
                        </div>

                        {isOpen && childFolders[item['id']] &&
                            <FolderList
                                folders={childFolders[item['id']]}
                                childFolders={childFolders}
                                getFolders={getFolders}
                                setSelectedFolder={setSelectedFolder}
                                selectedFolder={selectedFolder}
                                openFolders={openFolders}
                                setOpenFolders={setOpenFolders}
                                loading={loading}
                            />
                        }

                    </div>
                )
            })
            :
            !loading &&
            <div className={`move-folder-wrap empty`}>
                <i className="dashicons dashicons-warning"></i>
                <span>{wp.i18n.__('No folders found!', 'integrate-google-drive')}</span>
            </div>

    )
}