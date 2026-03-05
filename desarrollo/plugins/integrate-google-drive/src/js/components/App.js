import {useContextMenu} from "react-contexify";
import {AppProvider} from "../contexts/AppContext";
import Header from "./App/Header";
import Sidebar from "./App/Sidebar";
import Body from "./App/Body";
import Details from "./App/Details";
import ContextMenu from "./ContextMenu/ContextMenu";
import AddAccountPlaceholder from "./App/placeholder/AddAccountPlaceholder";

import {
    getListingView,
    getRootFolders,
    isAudioVideoType,
    isFolder,
    isImageType,
    isRootFolder,
    isVideoType,
    getSessionLastFolder,
    removeLastFolderSession,
    setLastFolderSession,
    useMounted, sortFiles,
} from "../includes/functions";

const {useState, useEffect, useMemo, useRef} = React;

const {settings, isAdmin, userAccessData} = igd;

let {
    rememberLastFolder = true,
    adminLazyLoad = true,
    adminLazyLoadType = 'pagination',
    adminLazyLoadNumber = 100,
} = settings;

rememberLastFolder = rememberLastFolder || isAdmin;

export default function App({
                                shortcodeId,
                                nonce,

                                initParentFolder,
                                isShortcodeBuilder,
                                selectedFolders = [],
                                setSelectedFolders,
                                allFolders,
                                privateFolders,
                                initFolders,
                                filters,
                                shortcodeBuilderType,
                                isList: initIsList,
                                showLastModified = !shortcodeBuilderType,
                                showFileSizeField = true,
                                fileNumbers,
                                sort: initSort,
                                permissions,
                                notifications,
                                showHeader = isShortcodeBuilder || !shortcodeBuilderType,
                                showRefresh = true,
                                showSorting = true,
                                showBreadcrumbs = true,
                                searchBoxText,

                                galleryLayout,
                                galleryAspectRatio,
                                galleryColumns,
                                galleryHeight,
                                galleryMargin,
                                galleryView,
                                galleryFolderView,
                                thumbnailCaption,
                                galleryOverlay,
                                overlayDisplayType,
                                galleryOverlayTitle,
                                galleryOverlayDescription,
                                galleryOverlaySize,
                                galleryImageSize,
                                galleryCustomSizeWidth,
                                galleryCustomSizeHeight,

                                isSelectFiles,
                                selectionType,
                                initialSearchTerm,
                                isLMS,
                                isWooCommerce,
                                lazyLoad = adminLazyLoad,
                                lazyLoadNumber = adminLazyLoadNumber,
                                lazyLoadType = adminLazyLoadType,
                                accounts = igd.accounts,
                                account = igd.activeAccount,
                                selection,
                                isFormUploader,
                                uploadFileName = '%file_name%%file_extension%',
                            }) {

    const [activeAccount, setActiveAccount] = useState(account);

    if (!initFolders) {

        if (userAccessData) {
            initParentFolder = userAccessData.initParentFolder;
            initFolders = userAccessData.initFolders;
        } else {

            if (activeAccount.is_specific_folders) {
                initFolders = activeAccount.specific_folders;
            } else if (allFolders) {
                initFolders = getRootFolders(null, activeAccount);
            }

        }

    }

    const isSearch = 'search' === shortcodeBuilderType && !isShortcodeBuilder;
    const isGallery = 'gallery' === shortcodeBuilderType;
    const isMedia = 'media' === shortcodeBuilderType;
    const isUploader = 'uploader' === shortcodeBuilderType;
    const isReview = 'review' === shortcodeBuilderType;

    // Handle remember last folder
    let lastFolder = null;
    if (!isSearch && rememberLastFolder) {
        lastFolder = getSessionLastFolder(shortcodeId, initParentFolder);
    }

    const [files, setFiles] = useState(!isSearch && !!initFolders ? initFolders : []);

    let initAllFiles = [];
    if (!isSearch && initFolders) {
        initAllFiles = {[initParentFolder?.id || '']: initFolders};
    }

    const [allFiles, setAllFiles] = useState(initAllFiles);

    const ignoreResetSelections = isReview || (isGallery && permissions?.photoProof);
    const [activeFiles, setActiveFiles] = useState([]);

    const [activeFile, setActiveFile] = useState(null);
    const [isLoading, setIsLoading] = useState((!initFolders && !initParentFolder) && !isSearch);
    const [breadcrumbs, setBreadcrumbs] = useState({});
    const [isSearchResults, setIsSearchResults] = useState(false);
    const [isUpload, setIsUpload] = useState(false);
    const [isMobile, setIsMobile] = useState(window.innerWidth <= 768);
    const [showSidebar, setShowSidebar] = useState(!isMobile && !initFolders);
    const [isOptions, setIsOptions] = useState(false);
    const [isBulkSelect, setIsBulkSelect] = useState(false);
    const [selectAll, setSelectAll] = useState(false);

    const searchKeywordRef = useRef(initialSearchTerm);
    const [searchKeyword, setSearchKeyword] = useState(initialSearchTerm);
    const [searchResults, setSearchResults] = useState(null);

    // Handle initializes show details
    const defaultShowDetails = !isMobile && '1' == localStorage.getItem('igd_show_details') && !initFolders;
    const [showDetails, setShowDetails] = useState(defaultShowDetails);

    // Handle initialize active root and folder
    let initActiveFolder = null;

    if (initFolders) {
        if (initParentFolder) {
            initActiveFolder = initParentFolder;
        }
    } else {
        initActiveFolder = getRootFolders('root', activeAccount);
    }

    if (lastFolder) {
        initActiveFolder = lastFolder;
    }

    const [activeFolder, setActiveFolder] = useState(initActiveFolder);

    // Sort
    const defaultSort = {
        sortBy: 'name',
        sortDirection: 'asc'
    };

    const localSort = localStorage.getItem('igd_sort') ? JSON.parse(localStorage.getItem('igd_sort')) : defaultSort;
    const [sort, setSort] = useState(initSort ?? localSort);

    // View
    let savedView = getListingView(shortcodeId || 'admin');

    if (savedView) {
        initIsList = savedView === 'list';
    }

    const [isList, setIsList] = useState(initIsList);

    const isMounted = useMounted();

    // Mount
    useEffect(() => {

        if (!Object.keys(accounts).length) return;

        // Initial search
        if (!!permissions && permissions.allowSearch && !!initialSearchTerm) {
            searchFiles(initialSearchTerm);
            return;
        }

        // Return if search module
        if (isSearch && !isShortcodeBuilder) return;

        // return init module
        if (initFolders || initParentFolder) {

            if (rememberLastFolder && lastFolder) {
                getFiles(lastFolder, 'last');
            }

            return; // always should return
        }

        // Load Files
        getFiles();

    }, []);

    // Handle module_refresh URL parameter - module_refresh files if URL parameter is present
    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const refresh = urlParams.get('module_refresh');

        if (refresh) {
            getFiles(activeFolder, 'module_refresh');
        }
    }, []);

    // Handle account switch
    useEffect(() => {
        if (!isMounted) return;

        igd.activeAccount = activeAccount;

        if (initFolders) {
            setShowSidebar(false);
            setAllFiles(initFolders);
            setFiles(initFolders);
            setActiveFolder(null);
        } else {
            setIsLoading('switch');
            setAllFiles({});

            if (!initFolders && !isShortcodeBuilder) {
                setShowSidebar(true);
            }
        }


        wp.ajax.post('igd_switch_account', {
            shortcodeId,
            id: activeAccount.id,
            nonce: nonce || igd.nonce,
        }).done(() => {

            if (initFolders) {
                if (initFolders.length === 1) {
                    listFiles(initFolders[0]);
                }

                return;
            }

            const rootFolder = {
                id: 'root',
                accountId: activeAccount.id,
                name: wp.i18n.__('My Drive', 'integrate-google-drive'),
            };

            getFiles(rootFolder, 'switch');

        }).fail((error) => {
            console.log(error);
        });

    }, [activeAccount.id]);

    // Onchange Sort
    useEffect(() => {
        if (!isMounted) return;

        localStorage.setItem('igd_sort', JSON.stringify(sort));

        // Load Files
        getFiles(activeFolder, 'sort');

    }, [sort]);

    // Check screen sizes
    useEffect(() => {
        const handleResize = () => {
            setIsMobile(window.innerWidth < 768);
        }

        window.addEventListener('resize', handleResize);

        return (() => {
            window.removeEventListener('resize', handleResize);
        })

    }, []);

    // Handle initParentFolder change
    useEffect(() => {
        if (!isMounted) return;

        if (!initParentFolder.id || initParentFolder.id === activeFolder?.id) return;

        // Load Files
        listFiles(initParentFolder);

    }, [initParentFolder]);

    useEffect(() => {
        if (!initFolders || !breadcrumbs) return;

        const breadcrumbKeys = Object.keys(breadcrumbs);

        if (!breadcrumbKeys.length) return;

        const activeFolderId = activeFolder?.id || activeFolder;

        // Clone breadcrumbs to avoid direct mutation
        let updatedBreadcrumbs = {...breadcrumbs};

        // Handle initial parent folder removal
        if (initParentFolder?.id) {
            if (breadcrumbKeys.includes(initParentFolder.id)) {
                delete updatedBreadcrumbs[initParentFolder.id];
            }

            if (breadcrumbKeys[breadcrumbKeys.length - 1] === initParentFolder.id) {
                setBreadcrumbs({});
                return; // Exit early since breadcrumbs are cleared
            }
        }

        // Find active breadcrumb key
        const activeBreadcrumbKey = breadcrumbKeys.findIndex(key => key === activeFolderId);

        // Filter out breadcrumbs before the active folder if they don't exist in initFolders
        if (activeBreadcrumbKey !== -1) {
            for (let i = 0; i < activeBreadcrumbKey; i++) {
                if (!initFolders.some(item => item.id === breadcrumbKeys[i])) {
                    delete updatedBreadcrumbs[breadcrumbKeys[i]];
                } else {
                    break;
                }
            }
        }

        // Update state only if breadcrumbs have changed
        if (JSON.stringify(updatedBreadcrumbs) !== JSON.stringify(breadcrumbs)) {
            setBreadcrumbs(updatedBreadcrumbs);
        }

    }, [breadcrumbs, activeFolder, initFolders, initParentFolder]);

    // Handle all selections
    useEffect(() => {
        if (!isMounted) return;

        if (isShortcodeBuilder) {
            let newSelectedFiles;

            if (selectAll) {
                const allFiles = [...selectedFolders, ...files];
                const seen = new Set();

                newSelectedFiles = allFiles.filter(file => {

                    if (seen.has(file.id)) {
                        return false;
                    } else {
                        seen.add(file.id);
                        return true;
                    }
                });

            } else {
                newSelectedFiles = selectedFolders.filter(selectedFile =>
                    !files.some(file => file.id === selectedFile.id)
                );
            }

            setSelectedFolders(newSelectedFiles);

        } else if (!isGallery) {
            setActiveFiles(selectAll ? (permissions ? files.filter(file => !(isFolder(file) && !permissions.folderDownload)) : files) : []);
        }

    }, [selectAll]);

    // Handle bulk select
    useEffect(() => {
        if (!isMounted || isBulkSelect) return;

        setSelectAll(null);
        setActiveFiles([]);
    }, [isBulkSelect]);

    // Deselect all on folder change
    useEffect(() => {

        if (!isMounted) return;

        setSelectAll(false);

        if (!isBulkSelect && !ignoreResetSelections) {
            setActiveFiles([]);
        }

        if (activeFolder) {
            setIsSearchResults(false);
        }

    }, [activeFolder]);

    /**
     * Get files by the folder
     */
    function getFiles(folder, type = '') {

        // Set active files to empty
        setActiveFile(null);

        if (!isBulkSelect && !ignoreResetSelections) {
            setActiveFiles([]);
        }

        setIsSearchResults(false);

        setIsLoading(type ? type : true);

        if (initFolders) {
            if (!folder) {
                setIsLoading(false);
                setActiveFolder(null);

                if ('sort' === type) {
                    setFiles(sortFiles(files, sort));
                } else {
                    setFiles(allFiles[''] || initFolders);
                }

                return;
            } else {
                if (privateFolders) {
                    folder['isPrivate'] = true;
                } else if (isSearch && !initFolders.find(item => item.id === folder.id)) {
                    folder['folders'] = initFolders;
                }
            }
        } else {
            if (!folder) {
                if (lastFolder) {
                    if (lastFolder.accountId === activeAccount.id) {
                        folder = lastFolder;
                    }
                }
            }

            // If no last folder, set root folder
            if (!folder) {
                folder = getRootFolders('root', activeAccount);
            }
        }

        // Reset page number if not lazy load type
        if ('lazy' !== type) {
            folder.pageNumber = 1;
        }

        // Handle filters
        if (filters || isShortcodeBuilder) {

            if (isShortcodeBuilder && !filters) {
                filters = {};
            }

            if (isGallery) {
                filters.isGallery = true;
            } else if (isMedia) {
                filters.isMedia = true;
            } else if (isLMS) {
                filters.onlyVideo = true;
            } else if (isUploader || 'folders' === selectionType) {
                filters.onlyFolders = true;
            } else if ('table' === shortcodeBuilderType) {
                filters.onlyTables = true;
            }
        }

        wp.ajax.post('igd_get_files', {
            shortcodeId,
            data: {
                folder: folder,
                sort: sort,
                refresh: 'refresh' === type,
                //from_server: isShortcodeBuilder || (!!initFolders && !!initParentFolder && initParentFolder['id'] === folder['id']),
                from_server: initFolders && initParentFolder?.id === folder?.id,
                fileNumbers,
                limit: lazyLoad ? lazyLoadNumber : 0,
                filters,
            },
            nonce: nonce || igd.nonce,
        }).done((data) => {

            let {files: items = [], breadcrumbs, error, nextPageNumber = 0, count} = data;

            if (error) {

                if ('refresh' !== type) {

                    if (document.querySelector('.igd-swal')) {
                        Swal.showValidationMessage(error);
                    } else {
                        Swal.fire({
                            html: error,
                            icon: 'error',
                            confirmButtonText: wp.i18n.__('Ok', 'integrate-google-drive'),
                            customClass: {container: 'igd-swal'},
                        });
                    }
                }

                return;
            }

            // Set last folder in session storage
            if (items.length && rememberLastFolder) {
                setLastFolderSession(folder, shortcodeId);
            }

            // Set init folders if last open folder and empty
            if (!items.length && rememberLastFolder) {
                removeLastFolderSession(shortcodeId);
            }

            if ('lazy' === type && 'pagination' !== lazyLoadType && activeFolder && activeFolder.id === folder.id) {
                items = [...files, ...items];
            }

            // Don't display any files if only folder selection
            if (isUploader || 'folders' === selectionType) {
                items = items.filter(item => isFolder(item));
            }

            // If not audio video item return if is media player
            if (isMedia) {
                items = items.filter(item => isAudioVideoType(item) || isFolder(item));
            }

            // If is LMS video selection
            if (isLMS) {
                items = items.filter(item => isVideoType(item) || isFolder(item));
            }

            // If is Gallery then filter only images and videos
            if (isGallery) {
                items = items.filter(item => isImageType(item) || isVideoType(item) || isFolder(item));
            }

            setFiles(items);

            // Update nextPageNumber, count for lazy load
            // Determine if there's a need to update the pageNumber
            const shouldUpdatePageNumber = lazyLoad && lazyLoadType !== 'pagination';

            // Check if there's a need to update the count
            const shouldUpdateCount = count > 0 && count > items.length;

            // Proceed with the update if either condition is true
            if (shouldUpdatePageNumber || shouldUpdateCount) {
                // Prepare the updated folder object, updating fields as necessary based on conditions
                const updatedFolder = {
                    ...folder,
                    ...(shouldUpdatePageNumber && {pageNumber: nextPageNumber}), // Update pageNumber if required
                    ...(shouldUpdateCount && {count: count}) // Update count if required
                };

                folder = updatedFolder;
            }

            // Update the state with the new folder information
            setAllFiles(prevFiles => ({...prevFiles, [folder.id]: [...items, folder]}));

            // Set active folder
            setActiveFolder(folder);

            if (breadcrumbs) {
                setBreadcrumbs({...breadcrumbs});
            }

        }).fail(error => {
            console.log(error);

            setActiveFolder({...folder, pageNumber: 0});

            if (document.querySelector('.igd-swal')) {
                Swal.showValidationMessage(error);
            } else {
                Swal.fire({
                    title: wp.i18n.__('Error!', 'integrate-google-drive'),
                    text: typeof error === 'string' ? error : wp.i18n.__('Something went wrong! Please try again later.', 'integrate-google-drive'),
                    icon: 'error',
                    confirmButtonText: wp.i18n.__('Ok', 'integrate-google-drive'),
                    customClass: {container: 'igd-swal'},
                });
            }

        }).always(() => setIsLoading(false));

    }

    function listFiles(folder) {

        const folderId = folder.id;

        setActiveFile(null);

        if (!isBulkSelect && !ignoreResetSelections) {
            setActiveFiles([]);
        }

        if (allFiles[folderId]) {
            const folderFiles = allFiles[folderId];

            folder = folderFiles.find(item => item.id === folder.id) || folder;

            setActiveFolder(folder);
            setFiles(folderFiles.filter(item => item.id !== folderId));

            rememberLastFolder && setLastFolderSession(activeFolder, shortcodeId);

            function getBreadcrumbs(folderId) {

                if (isRootFolder(folderId, activeAccount)) {
                    const rootFolder = getRootFolders(folderId, activeAccount);
                    return {[folderId]: rootFolder.name};
                }

                const breadcrumbKeys = Object.keys(breadcrumbs);
                const folder = folderFiles.find(item => item.id === folderId);

                if (folder) {
                    const items = {[folderId]: folder.name};
                    const folderParents = folder['parents'] || [];

                    // If is search, set breadcrumbs based on initial search results
                    if (isSearch && !isShortcodeBuilder && searchResults) {
                        if (folderParents.length && !(searchResults && searchResults.find(item => item.id === folderId))) {
                            return {...getBreadcrumbs(folderParents[0]), ...items};
                        }
                    } else {
                        if (folderParents.length && !(initFolders && initFolders.find(item => item.id === folderId))) {
                            return {...getBreadcrumbs(folderParents[0]), ...items};
                        }
                    }

                    return items;
                }

                if (breadcrumbKeys.includes(folderId)) {
                    const index = breadcrumbKeys.indexOf(folderId);
                    return Object.fromEntries(breadcrumbKeys.slice(0, index + 1).map(key => [key, breadcrumbs[key]]));
                }

                return {};
            }

            setBreadcrumbs({...getBreadcrumbs(folderId)});

        } else {
            getFiles(folder);
        }
    }

    function searchFiles(searchKeyword) {
        searchKeywordRef.current = searchKeyword;

        let folders = !isSearch && activeFolder ? [activeFolder] : initFolders.filter(item => isFolder(item));

        // Set active files to empty
        setActiveFile(null);

        if (!isBulkSelect && !ignoreResetSelections) {
            setActiveFiles([]);
        }

        // Set uploader false
        setIsUpload(false);

        let initFilesResults = [];

        if (initFolders?.length) {
            // Search in initFolders by the keyword and merge with search results files
            initFilesResults = initFolders.filter(file => file.name.toLowerCase().includes(searchKeyword.toLowerCase()));

            if (!folders.length) {
                setFiles(initFilesResults);
                setSearchResults(initFilesResults);
                return;
            }

        }

        setIsLoading(true);

        wp.ajax.post('igd_search_files', {
            shortcodeId,
            folders,
            sort,
            fileNumbers,
            keyword: searchKeyword,
            accountId: activeAccount['id'],
            isPrivate: privateFolders,
            fullTextSearch: permissions?.fullTextSearch,
            filters,
            nonce: nonce || igd.nonce,
        }).done((data) => {

            let {files, error} = data;

            if (!!error) {
                Swal.fire({
                    html: error,
                    icon: 'error',
                    confirmButtonText: wp.i18n.__('Ok', 'integrate-google-drive'),
                    customClass: {container: 'igd-swal'},
                });

                return;
            }


            if (initFilesResults.length) {
                files = [...initFilesResults, ...files];
            }

            // If not audio video item return if is media player
            if (isMedia) {
                files = files.filter(item => isAudioVideoType(item) || isFolder(item));
            }

            // If is LMS video selection
            if (isLMS) {
                files = files.filter(item => isVideoType(item) || isFolder(item));
            }

            if (isGallery) {
                files = files.filter(item => isImageType(item) || isVideoType(item) || isFolder(item));
            }

            // unique files
            const seen = new Set();
            files = files.filter(file => {
                if (seen.has(file.id)) {
                    return false;
                } else {
                    seen.add(file.id);
                    return true;
                }
            });

            setFiles(files);
            setSearchResults(files);

        }).fail(error => {
            console.log(error);

            Swal.fire({
                title: wp.i18n.__('Error!', 'integrate-google-drive'),
                text: typeof error === 'string' ? error : wp.i18n.__('Something went wrong! Please try again later.', 'integrate-google-drive'),
                icon: 'error',
                confirmButtonText: 'Ok',
                customClass: {container: 'igd-swal'},
            });
        }).always(() => {
            setIsLoading(false);
        });

        // Send search notification
        if (notifications && notifications.searchNotification) {
            wp.ajax.post('igd_notification', {
                files: folders,
                keyword: searchKeyword,
                notifications,
                type: 'search',
                nonce: nonce || igd.nonce,
            });
        }

    }

    // Handle Context Menu
    // Render a unique id for each context menu
    const contextMenuId = useMemo(() => `igd-context-menu-${Date.now()}`, []);
    const {show, hideAll} = useContextMenu({id: contextMenuId});

    // Hide react tooltip and context menu on scroll
    useEffect(() => {
        const hideTooltipMenu = () => {
            hideAll();
        }

        const element = document.querySelector('.igd-shortcode-builder-form');

        if (element) {
            element.addEventListener('scroll', hideTooltipMenu);
        }

        return () => {
            if (element) {
                element.removeEventListener('scroll', hideTooltipMenu);
            }
        }

    }, []);

    // Handle uploaded files
    useEffect(() => {
        const handleUploaded = ({detail}) => {
            const {folderId, files: newFiles} = detail;

            if (!activeFolder?.id || activeFolder.id !== folderId || !Array.isArray(newFiles)) return;

            const filterUnique = (existingFiles) => {
                const existingIds = new Set(existingFiles.map((f) => f.id));
                return newFiles.filter((f) => !existingIds.has(f.id));
            };

            setFiles((prev) => [...prev, ...filterUnique(prev)]);

            setAllFiles((prev) => ({
                ...prev,
                [folderId]: [...(prev[folderId] || []), ...filterUnique(prev[folderId] || [])],
            }));
        };

        document.addEventListener('igd_upload_complete', handleUploaded);
        return () => document.removeEventListener('igd_upload_complete', handleUploaded);
    }, [activeFolder]);

    // Handle selection active files
    useEffect(() => {
        if (isShortcodeBuilder || (!isReview && !isGallery) || !selection) return;

        setActiveFiles(selection.files);
    }, []);

    return (
        !!Object.keys(accounts).length ?

            <AppProvider
                value={{
                    accounts,
                    shortcodeId,
                    setActiveAccount,
                    activeAccount,
                    activeFiles,
                    setActiveFiles,
                    activeFile,
                    setActiveFile,
                    activeFolder,
                    setActiveFolder,
                    permissions,
                    notifications,
                    shortcodeBuilderType,
                    isList, setIsList,
                    showDetails, setShowDetails,
                    setIsUpload,
                    getFiles,
                    isShortcodeBuilder,
                    isMobile,
                    allFolders,
                    initFolders,
                    isSearch,
                    setShowSidebar,
                    sort,
                    setSort,
                    setFiles,
                    setAllFiles,
                    breadcrumbs,
                    setBreadcrumbs,
                    isSearchResults,
                    setIsSearchResults,
                    searchResults,
                    setSearchResults,
                    isOptions,
                    setIsOptions,
                    listFiles,
                    showBreadcrumbs,
                    showRefresh,
                    showSorting,
                    initParentFolder,
                    isUpload,
                    isLoading,
                    files,
                    allFiles,
                    selectedFolders,
                    setSelectedFolders,
                    filters,
                    showLastModified,
                    showFileSizeField,
                    searchBoxText,

                    galleryLayout,
                    galleryAspectRatio,
                    galleryColumns,
                    galleryHeight,
                    galleryMargin,
                    galleryView,
                    galleryFolderView,
                    thumbnailCaption,
                    galleryOverlay,
                    overlayDisplayType,
                    galleryOverlayTitle,
                    galleryOverlayDescription,
                    galleryOverlaySize,
                    galleryImageSize,
                    galleryCustomSizeWidth,
                    galleryCustomSizeHeight,

                    show,
                    hideAll,
                    contextMenuId,
                    isSelectFiles,
                    selectionType,
                    isBulkSelect, setIsBulkSelect,
                    selectAll, setSelectAll,
                    searchFiles,
                    initialSearchTerm,

                    searchKeywordRef,
                    searchKeyword,
                    setSearchKeyword,
                    isLMS,
                    isWooCommerce,
                    lazyLoad,
                    lazyLoadNumber,
                    lazyLoadType,
                    nonce,

                    selection,
                    isFormUploader,
                    showHeader,
                    uploadFileName,
                }}
            >
                <div className="igd-file-browser">

                    {/*------- Context Menu ------*/}
                    <ContextMenu/>

                    {/*----------- Header ------------*/}
                    {showHeader && (!isSearch || isSearchResults) && <Header/>}

                    <div className="igd-file-browser-body">

                        {/*----------- Sidebar ------------*/}
                        {showSidebar && <Sidebar/>}

                        <Body/>

                        {/*------ Details -----*/}
                        {!isShortcodeBuilder && showDetails && <Details/>}

                    </div>
                </div>
            </AppProvider>

            :

            (!initFolders ? <AddAccountPlaceholder/> : null)
    )
}