import AppContext from "../../contexts/AppContext";

import {useMounted} from "../../includes/functions";

const {useState, useRef, useEffect, useContext} = React;

export default function SearchBar() {

    const context = useContext(AppContext);
    const {
        isSearch,
        searchFiles, setFiles,
        listFiles,
        activeFolder,
        setActiveFolder,
        setSearchResults,
        setIsSearchResults,
        initFolders,
        searchKeyword, setSearchKeyword,
        initialSearchTerm,
        isMobile,
        setActiveFiles,
        setActiveFile,
        allFiles,
    } = context;

    const timeOutRef = useRef(null);
    const [error, setError] = useState(false);
    const searchRef = useRef();

    const [isActive, setIsActive] = useState(isSearch || initialSearchTerm);

    const handleSubmit = (e) => {
        e && e.preventDefault();

        if (!searchKeyword) {
            Swal.fire({
                title: wp.i18n.__('Error', 'integrate-google-drive'),
                text: wp.i18n.__('Please enter a keyword to search', 'integrate-google-drive'),
                icon: 'error',
                confirmButtonText: 'Ok',
                showCloseButton: true,
                customClass: {container: 'igd-swal'},
            });

            setError(true);
            return;
        }

        if (isSearch) {
            setActiveFolder(null);
        }

        setError(false);
        setSearchResults(null);
        setIsSearchResults(true);

        searchFiles(searchKeyword);
    }

    const handleChange = (e) => {
        setSearchKeyword(e.target.value);
    }

    const isMounted = useMounted();

    // Handle search keyword
    useEffect(() => {
        if (!isMounted) return;
        if (!searchKeyword) return;

        //if character length is less than 3, don't search
        if (searchKeyword.length < 5) return;

        timeOutRef.current = setTimeout(() => {
            handleSubmit();
        }, 1500);

        return () => {
            clearTimeout(timeOutRef.current);
        }
    }, [searchKeyword]);

    return (
        <form
            ref={searchRef}
            className={`igd-search-bar ${isActive ? 'active' : ''} ${error ? 'error' : ''}`}
            onSubmit={handleSubmit}
        >

            {(isSearch || !!searchKeyword || (isMobile && isActive)) &&
                <div className={`search-dismiss`}
                     onClick={() => {
                         setActiveFile([]);
                         setActiveFiles([]);

                         if (!isSearch) {
                             setIsActive(!isActive);
                         }

                         setSearchKeyword('');
                         setError(false);

                         setSearchResults(null);
                         setIsSearchResults(null);

                         if (activeFolder) {
                             if (isSearch) {
                                 setActiveFolder(null);
                                 setFiles([]);
                             } else {
                                 listFiles(activeFolder);
                             }
                         } else {
                             if (initFolders && !isSearch) {
                                 setFiles(allFiles['']);
                             } else {
                                 setFiles([]);
                             }
                         }

                     }}
                >
                    <svg width="14" height="14" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1.5 1.5L10.5 10.5M1.5 10.5L10.5 1.5L1.5 10.5Z" stroke="#BABABA" strokeWidth="2"
                              strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                </div>
            }

            <input type="text"
                   placeholder={wp.i18n.__('Enter a keyword to search', 'integrate-google-drive')}
                   value={searchKeyword}
                   onChange={handleChange}
            />

            <button
                type={'submit'}
                className="header-action-item action-search search-submit"
                onClick={(e) => {
                    e.preventDefault();

                    if (!isActive && isMobile) {
                        setIsActive(true);
                        searchRef.current?.querySelector('input').focus();
                    } else {
                        handleSubmit(e);
                    }

                }}
            >

                <svg width="20" height="20" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="9.7659" cy="9.76639" r="8.98856" stroke="#3D3D3D" strokeWidth="1.5"
                            strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M16.0176 16.4849L19.5416 19.9997" stroke="#3D3D3D" strokeWidth="1.5" strokeLinecap="round"
                          strokeLinejoin="round"/>
                </svg>
            </button>

        </form>
    )
}