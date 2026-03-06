import {Tooltip} from "react-tooltip";
import AppContext from "../../contexts/AppContext";
import SearchBar from "./SearchBar";
import Breadcrumb from "./Breadcrumb";
import {showProModal} from "../../includes/ProModal";

import {
    base64Decode,
    loadAvatar,
    openAddAccountWindow,
} from "../../includes/functions";

const {useContext, useState} = React;

export default function Header() {

    const context = useContext(AppContext);

    const {
        accounts,
        activeAccount,
        setActiveAccount,

        initFolders, // Identify if shortcode or admin file browser
        allFolders,
        isShortcodeBuilder,
        activeFolder,
        isSearch,
        setShowSidebar,

        permissions,
        showBreadcrumbs,
        breadcrumbs,
    } = context;

    const isPro = igd.isPro;

    // Handle accounts tooltip
    const [isTooltipOpen, setTooltipOpen] = useState(false);

    return (
        <div className="igd-file-browser-header">

            {/*--------- Sidebar Toggle --------*/}
            {!initFolders &&
                <button
                    type={`button`}
                    className={`header-action-item action-sidebar-toggle`}
                    onClick={() => setShowSidebar(showSidebar => !showSidebar)}
                    title={wp.i18n.__('Toggle Sidebar', 'integrate-google-drive')}
                    aria-label={wp.i18n.__('Toggle Sidebar', 'integrate-google-drive')}
                >
                    <i className="dashicons dashicons-menu-alt3"></i>
                </button>
            }

            {/*----------------- Breadcrumb -------------*/}
            {(!isSearch || isShortcodeBuilder) && showBreadcrumbs && Object.keys(breadcrumbs).length > 0 &&
                <Breadcrumb/>}


            {/*--------- Search --------*/}
            {(initFolders || activeFolder) && ((!permissions || permissions.allowSearch) || isSearch) &&
                <SearchBar/>
            }

            {/*----------- User -----------*/}
            {(!initFolders || allFolders || (igd.isAdmin && activeAccount?.is_specific_folders)) &&
                <>
                    <button
                        type={`button`}
                        className="header-action-item action-accounts user-box"
                        onClick={() => setTooltipOpen(!isTooltipOpen)}
                        title={wp.i18n.__(`Switch Account`, 'integrate-google-drive')}
                        aria-label={wp.i18n.__(`Switch Account`, 'integrate-google-drive')}
                    >

                        <img
                            referrerPolicy={`no-referrer`}
                            className={`user-image`} src={activeAccount.photo}
                            onError={({currentTarget}) => loadAvatar(currentTarget, activeAccount.email)}
                        />

                        <img src={`${igd.pluginUrl}/assets/images/file-browser/arrow-down.svg`}
                             className={`user-arrow`}
                        />
                    </button>

                    <Tooltip
                        anchorSelect={`.user-box`}
                        isOpen={isTooltipOpen}
                        setIsOpen={setTooltipOpen}
                        openEvents={['click']}
                        place="bottom"
                        variant="light"
                        clickable={true}
                        border={`1px solid #ddd`}
                        resizeHide={false}
                        className="user-box-modal-wrap igd-tooltip"
                        afterShow={() => {
                            document.querySelector('.igd-file-browser-header').style.zIndex = 9;
                        }}
                        afterHide={() => {
                            document.querySelector('.igd-file-browser-header').style.zIndex = 2;
                        }}
                    >
                        <div className="user-box-modal">
                            <span
                                className={`user-box-modal-title`}>{wp.i18n.__("Switch Account", 'integrate-google-drive')}</span>

                            {
                                Object.keys(accounts).map(key => {

                                    const {id, name, photo, email} = accounts[key];

                                    const isActive = activeAccount.id === id;

                                    return (
                                        <div
                                            key={key}
                                            className={`user-box-account ${isActive ? 'active' : ''}`}
                                            onClick={() => {
                                                setActiveAccount(accounts[key]);
                                            }}
                                        >

                                            <img
                                                referrerPolicy={`no-referrer`}
                                                onError={({currentTarget}) => loadAvatar(currentTarget, email)}
                                                src={photo}
                                            />

                                            <div className="account-info">
                                                <span className="account-name">{name}</span>
                                                <span className="account-email">{email}</span>
                                            </div>

                                            {isActive &&
                                                <i className="dashicons dashicons-saved active-badge"></i>
                                            }

                                        </div>
                                    )
                                })
                            }

                            {!isShortcodeBuilder && !initFolders &&
                                <>
                                    <button
                                        data-tooltip-content="Multiple Accounts - PRO"
                                        data-tooltip-id="addAccountPromo"
                                        className={'igd-btn btn-primary'}
                                        onClick={() => {
                                            if (!isPro && !!Object.keys(accounts).length) {
                                                showProModal(wp.i18n.__('Upgrade to PRO to add multiple accounts.', 'integrate-google-drive'));
                                                return;
                                            }

                                            if (!!igd.authUrl) {
                                                openAddAccountWindow();
                                            } else {
                                                window.location = igd.adminUrl + '/admin.php?page=integrate-google-drive-settings'
                                            }
                                        }}
                                    >
                                        <i className="dashicons dashicons-plus"></i>
                                        <span>{wp.i18n.__("Add account", 'integrate-google-drive')}</span>
                                    </button>

                                    {!isPro && !!Object.keys(accounts).length &&
                                        <Tooltip id="addAccountPromo" effect="solid" place="right"
                                                 variant={'warning'} className="igd-tooltip"/>
                                    }
                                </>
                            }
                        </div>
                    </Tooltip>

                </>
            }

        </div>
    )
}