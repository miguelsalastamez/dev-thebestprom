import {Tooltip} from "react-tooltip";
import AppContext from "../../contexts/AppContext";
import {decodeHTML, isHomeFolder, removeLastFolderSession} from "../../includes/functions";

const {useContext, useState, useEffect, useRef} = React;

export default function Breadcrumb() {

    const {rememberLastFolder = true} = igd.settings;

    const context = useContext(AppContext);

    const {
        breadcrumbs,
        setBreadcrumbs,
        initFolders,
        allFiles,
        setFiles,
        activeFolder,
        setActiveFolder,
        setActiveFile,
        listFiles,
        initParentFolder,
        show,
        isShortcodeBuilder,
        activeAccount,
        setIsOptions,
        setActiveFiles,
        shortcodeId,
    } = context;

    const breadcrumbRef = useRef(null);

    const [breadcrumbItems, setBreadcrumbItems] = useState({
        rootItem: '',
        collapsedItems: [],
        lastItems: [],
    });

    useEffect(() => {

        const itemsKeys = breadcrumbs ? Object.keys(breadcrumbs) : [];

        setBreadcrumbItems({
            rootItem: itemsKeys.length > 2 ? itemsKeys[0] : '',
            collapsedItems: itemsKeys.length > 3 ? itemsKeys.slice(1, -2) : [],
            lastItems: itemsKeys.length ? itemsKeys.slice(-2) : [],
        });

        const calculateItems = () => {
            if (!breadcrumbRef.current) return;

            const breadcrumbWidth = breadcrumbRef.current.offsetWidth;
            const children = breadcrumbRef.current.children;
            if (!children) return;

            const childrenWidth = Array.from(children).reduce(
                (sum, child) => sum + child.offsetWidth,
                0
            );

            const itemsKeys = breadcrumbs ? Object.keys(breadcrumbs) : [];

            if (childrenWidth > breadcrumbWidth) {
                const rootItem = '';
                const collapsedItems = itemsKeys.slice(0, -1);
                const lastItems = [itemsKeys[itemsKeys.length - 1]];

                setBreadcrumbItems({
                    rootItem,
                    collapsedItems,
                    lastItems,
                });
            }
        }

        setTimeout(() => {
            calculateItems();

            // Add a resize listener to handle window resizing
            window.addEventListener("resize", calculateItems);
        })

        return () => window.removeEventListener("resize", calculateItems);

    }, [breadcrumbs]);

    const OpenContextMenu = (e) => {
        e.preventDefault();
        e.stopPropagation();

        setIsOptions(false);
        setActiveFiles([]);
        setActiveFile(activeFolder);

        show(e);
    };

    // Handle breadcrumb options tooltip
    const [isTooltipOpen, setTooltipOpen] = useState(false);

    const {rootItem, collapsedItems, lastItems} = breadcrumbItems;

    return (
        <div className="igd-breadcrumb" ref={breadcrumbRef}>
            <button
                type="button"
                className="breadcrumb-item"
                onClick={(e) => {
                    if (initFolders) {
                        if (initParentFolder) {
                            setActiveFolder(initParentFolder);
                            listFiles(initParentFolder);
                        } else {
                            setActiveFolder(null);
                            setFiles(allFiles[''] || initFolders);
                        }
                    } else {
                        setActiveFolder(null);
                        setFiles([]);
                    }

                    setBreadcrumbs({});

                    if (rememberLastFolder) {
                        removeLastFolderSession(shortcodeId);
                    }
                }}
            >
                <i className="dashicons dashicons-admin-home"></i>
                <span>{wp.i18n.__("Home", "integrate-google-drive")}</span>
            </button>

            {!!activeFolder && (
                <>
                    {!!rootItem && (
                        <button
                            type="button"
                            className={`breadcrumb-item ${
                                !isShortcodeBuilder && activeFolder.id === rootItem ? "active" : ""
                            }`}
                            onClick={(e) => {
                                if (activeFolder.id === rootItem) {
                                    OpenContextMenu(e);
                                } else {
                                    listFiles({
                                        id: rootItem,
                                        name: breadcrumbs[rootItem],
                                        accountId: activeFolder.accountId,
                                    });
                                }
                            }}
                        >
                            <span>{decodeHTML(breadcrumbs[rootItem])}</span>
                        </button>
                    )}

                    {!!collapsedItems.length && (
                        <>
                            <button
                                type="button"
                                className="breadcrumb-item breadcrumb-options"
                                onClick={() => setTooltipOpen(!isTooltipOpen)}
                            >
                                <i className="dashicons dashicons-ellipsis"></i>
                                <i className="dashicons dashicons-arrow-right-alt2"></i>
                            </button>

                            <Tooltip
                                anchorSelect={`.breadcrumb-options`}
                                isOpen={isTooltipOpen}
                                setIsOpen={setTooltipOpen}
                                place="bottom"
                                variant="light"
                                openEvents={["click"]}
                                clickable={true}
                                className="collapsed-breadcrumbs igd-tooltip"
                                content={() =>
                                    collapsedItems.map((id) => (
                                        <div
                                            key={id}
                                            className="collapsed-breadcrumbs-item"
                                            onClick={(e) => {
                                                listFiles({
                                                    id,
                                                    name: breadcrumbs[id],
                                                    accountId: activeFolder.accountId,
                                                });
                                            }}
                                        >
                                            <span>{breadcrumbs[id]}</span>
                                        </div>
                                    ))
                                }
                                afterShow={() => {
                                    breadcrumbRef.current.parentElement.style.zIndex = 9;
                                }}
                                afterHide={() => {
                                    breadcrumbRef.current.parentElement.style.zIndex = 2;
                                }}
                            />
                        </>
                    )}

                    {lastItems.map((id) => {

                        return (
                            <button
                                type="button"
                                key={id}
                                className={`breadcrumb-item ${
                                    isHomeFolder(activeFolder.id, activeAccount) ? ""
                                        : !isShortcodeBuilder && activeFolder.id === id ? "active" : ""
                                }`}
                                onClick={(e) => {
                                    if (isHomeFolder(activeFolder.id, activeAccount)) return;

                                    if (activeFolder.id === id) {
                                        OpenContextMenu(e);
                                    } else {
                                        listFiles({
                                            id,
                                            name: breadcrumbs[id],
                                            accountId: activeFolder.accountId,
                                        });
                                    }
                                }}
                            >
                                <span>{decodeHTML(breadcrumbs[id])}</span>
                            </button>
                        )
                    })}
                </>
            )}
        </div>
    );
}
