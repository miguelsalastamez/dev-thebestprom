import ShortcodeBuilderContext from "../../contexts/ShortcodeBuilderContext";
import {Tooltip} from "react-tooltip";
import Sources from "./Form/Sources";
import Filters from "./Form/Filters";
import Advanced from "./Form/Advanced";
import Notifications from "./Form/Notifications";
import Permissions from "./Form/Permissions";
import {isMobile} from "../../includes/functions";

const {useState, useContext, useEffect} = React;

export default function Form() {

    const isTutorAttachmentSelector = document.querySelector('.igd-tutor-attachment-modal');

    const context = useContext(ShortcodeBuilderContext);

    const {
        editData,
        updateShortcode,
        updating,
        isEditor,
        builderType,
        isInlineSelect,
        isSelectFiles,
        isLMS,
        isWooCommerce,
        selectionType,
        isDirty,
    } = context;

    const {id, type} = editData;

    const isBrowser = 'browser' === type;
    const isUploader = 'uploader' === type;
    const isGallery = 'gallery' === type;
    const isMedia = 'media' === type;
    const isSearch = 'search' === type;
    const isEmbed = 'embed' === type;
    const isSlider = 'slider' === type;
    const isListModule = 'list' === type;

    const isEmbedConfig = isEditor && 'embed' === builderType;

    const tabs = {
        sources: wp.i18n.__('Source', "integrate-google-drive"),
        filters: wp.i18n.__('Filter', "integrate-google-drive"),
        advanced: wp.i18n.__('Advanced', "integrate-google-drive"),
        notifications: wp.i18n.__('Notifications', "integrate-google-drive"),
        permissions: wp.i18n.__('Permissions', "integrate-google-drive"),
    }

    const tabKeys =  Object.keys(tabs).filter(key => {

        if (( isEmbedConfig || isWooCommerce === 'download') && ['permissions'].includes(key)) return false;

        if ((isSelectFiles || isLMS) && 'sources' !== key) return false;

        if ((isWooCommerce === 'download' || isEmbed) && ['notifications'].includes(key)) return false;

        if (isWooCommerce === 'download' && 'filters' === key) return false;

        return true;
    });

    let initTab = sessionStorage.getItem(`igd_shortcode_builder_tab_${id}`);
    if (!initTab) {
        initTab = !!id && tabKeys.includes('sources') ? 'sources' : tabKeys[0];
    }

    const [tab, setTab] = useState(initTab);
    const tabIndex = tabKeys.indexOf(tab);

    const initCollapsed = localStorage.getItem('igd_shortcode_builder_sidebar_collapsed') === 'true';
    const [isCollapsed, setCollapsed] = useState(!isMobile() && initCollapsed);

    useEffect(() => {
        if (!id) return;

        // smooth scroll to top
        const element = document.querySelector('.igd-shortcode-builder');

        if (element) {
            element.scrollIntoView({behavior: 'smooth'});
        }

        // store current tab
        sessionStorage.setItem(`igd_shortcode_builder_tab_${id}`, tab);

    }, [tab]);

    let tabTitle = wp.i18n.__('Select Files and Folders', 'integrate-google-drive');
    let tabDescription = wp.i18n.__('Select the files & folders to display in the file browser.', 'integrate-google-drive');

    if ('sources' === tab) {

        if (isUploader) {
            tabTitle = wp.i18n.__('Select Upload Folder', 'integrate-google-drive');
            tabDescription = wp.i18n.__('Select the folder where the files will be uploaded.', 'integrate-google-drive');
        }

        if (isGallery) {
            tabTitle = wp.i18n.__('Select Files and Folders', 'integrate-google-drive');
            tabDescription = wp.i18n.__('Select the files and folders to display in the gallery.', 'integrate-google-drive');
        }

        if (isMedia) {
            tabTitle = wp.i18n.__('Select Files and Folders', 'integrate-google-drive');
            tabDescription = wp.i18n.__('Select the folders and audio/ video files to display and play in the media player.', 'integrate-google-drive');
        }

        if (isSearch) {
            tabTitle = wp.i18n.__('Select Folders', 'integrate-google-drive');
            tabDescription = wp.i18n.__('Select the folders to search in.', 'integrate-google-drive');
        }

        if (isEmbed) {
            tabTitle = wp.i18n.__('Select Files', 'integrate-google-drive');
            tabDescription = wp.i18n.__('Select the files to embed. You can also select folders to embed all the files of the folder.', 'integrate-google-drive');
        }

        if (isListModule) {
            tabTitle = wp.i18n.__('Select Files', 'integrate-google-drive');
            tabDescription = wp.i18n.__('Select the files to display in the list.', 'integrate-google-drive');
        }

        if (isTutorAttachmentSelector) {
            tabTitle = wp.i18n.__('Select Files', 'integrate-google-drive');
            tabDescription = wp.i18n.__('Select the files to attach in the course.', 'integrate-google-drive');
        }

        if (isWooCommerce === 'download') {
            tabTitle = wp.i18n.__('Select Files & Folders', 'integrate-google-drive');
            tabDescription = wp.i18n.__('Select the files and folders to attach in the product for download.', 'integrate-google-drive');
        }

    }

    if ('filters' === tab) {
        tabTitle = wp.i18n.__('Filters', 'integrate-google-drive');

        if (isBrowser) {
            tabDescription = wp.i18n.__('Show/ hide files and folders and filter them by extensions and names to not display in the file browser.', 'integrate-google-drive');
        } else if (isUploader) {
            tabDescription = wp.i18n.__('Filter the files to upload by extensions', 'integrate-google-drive');
        } else if (isGallery) {
            tabDescription = wp.i18n.__('Show/ hide files and folders and filter them by extensions and names to not display in the gallery.', 'integrate-google-drive');
        } else if (isSlider) {
            tabDescription = wp.i18n.__('Show/ hide files and filter them by extensions and names to not display in the slider.', 'integrate-google-drive');
        } else if (isMedia) {
            tabDescription = wp.i18n.__('Show/ hide files and folders and filter them by extensions and names to not display in the media player.', 'integrate-google-drive');
        } else if (isSearch) {
            tabDescription = wp.i18n.__('Show/ hide files and folders and filter them by extensions and names to not display in the search results.', 'integrate-google-drive');
        }
    }

    if ('advanced' === tab) {
        tabTitle = wp.i18n.__('Advanced Options', 'integrate-google-drive');
        tabDescription = wp.i18n.__('Advanced options to customize the module.', 'integrate-google-drive');
    }

    if ('notifications' === tab) {
        tabTitle = wp.i18n.__('Email Notifications', 'integrate-google-drive');
        tabDescription = wp.i18n.__('Receive email notifications for various user activities (upload, download, delete, etc).', 'integrate-google-drive');
    }

    if ('permissions' === tab) {
        tabTitle = wp.i18n.__('Permissions', 'integrate-google-drive');
        tabDescription = wp.i18n.__('Set access permissions for the module.', 'integrate-google-drive');
    }

    return (
        <div className={`igd-shortcode-builder-form ${isEditor ? 'editor-mode' : ''}`}>

            {/* Form Tabs */}
            {!isSelectFiles && tabKeys.length > 1 &&
                <div className={`shortcode-builder-sidebar ${isCollapsed ? 'sidebar-collapsed' : ''} tab-${tab}`}>

                    {/*----- Sidebar Collapse ----*/}
                    {!isMobile() &&
                        <>
                            <img src={igd.pluginUrl + '/assets/images/shortcode-builder/arrow.svg'}
                                 className="sidebar-collapser"
                                 onClick={() => {
                                     setCollapsed(!isCollapsed);

                                     localStorage.setItem('igd_shortcode_builder_sidebar_collapsed', !isCollapsed);
                                 }}
                            />

                            <Tooltip
                                anchorSelect={'.sidebar-collapser'}
                                place="right"
                                className={"igd-tooltip"}
                                content={isCollapsed ? wp.i18n.__('Expand', 'integrate-google-drive') : wp.i18n.__('Collapse', 'integrate-google-drive')}
                                delayShow={300}
                            />
                        </>
                    }

                    <div className={`shortcode-tabs ${isEditor ? 'edit-mode' : ''}`}>
                        {
                            tabKeys.map((key, i) => {

                                return (
                                    <div key={key}
                                         className={`shortcode-tab-wrap ${tabIndex >= i ? 'active' : ''}`}
                                         onClick={() => setTab(key)}>

                                        <div key={key} className={`shortcode-tab ${key}`}>
                                            <span className={`tab-icon icon-${key}`}></span>
                                            <span className="tab-name">{tabs[key]}</span>
                                        </div>

                                        {!!isCollapsed &&
                                            <Tooltip
                                                anchorSelect={`.shortcode-tab.${key}`}
                                                place="right"
                                                className={"igd-tooltip"}
                                                content={tabs[key]}
                                                delayShow={300}
                                            />
                                        }

                                    </div>
                                )
                            })
                        }
                    </div>
                </div>
            }

            <div className="shortcode-builder-content">

                <div className="shortcode-module">

                    {!isInlineSelect && 'folders' !== selectionType && !isLMS &&
                        <div className="shortcode-module-header">

                            <div className="module-title">
                                <h2>{tabTitle}</h2>
                                <span>{tabDescription}</span>
                            </div>

                        </div>
                    }

                    {/* Sources */}
                    {'sources' === tab && <Sources/>}

                    {/* Filters */}
                    {'filters' === tab && <Filters/>}

                    {/* Advanced */}
                    {'advanced' === tab && <Advanced/>}

                    {/* Notifications */}
                    {'notifications' === tab && <Notifications/>}

                    {/* Permissions */}
                    {'permissions' === tab && <Permissions/>}
                </div>

                {/* Form Footer */}
                {!isSelectFiles && tabKeys.length > 1 &&
                    <div className="form-footer">

                        {/* Back */}
                        <button
                            data-tooltip-content={wp.i18n.__('Previous step', "integrate-google-drive")}
                            data-tooltip-id={'previous'}
                            type={'button'}
                            disabled={tabIndex === 0}
                            className={`igd-btn ${tabIndex === 0 ? 'disabled' : 'btn-link'}`}
                            onClick={() => setTab(tabKeys[tabIndex - 1])}
                        >
                            <i className={`dashicons dashicons-arrow-left-alt`}></i>
                            <span>{wp.i18n.__("Back", "integrate-google-drive")}</span>
                            <Tooltip
                                id="previous"
                                effect="solid"
                                place="top"
                                className={"igd-tooltip"}
                            />
                        </button>

                        {/* Save Changes */}
                        <button type={'button'} className={`igd-btn btn-primary ${isDirty ? '' : 'disabled'}`}
                                onClick={() => updateShortcode(editData)}>
                            {updating ? <div className="igd-spinner"></div> :
                                <i className={`dashicons dashicons-saved`}></i>}
                            <span>{isDirty ? wp.i18n.__("Save changes", "integrate-google-drive") : wp.i18n.__("Saved", "integrate-google-drive")}</span>
                        </button>

                        {/* Next Tab */}
                        <button
                            data-tooltip-content={wp.i18n.__('Next step', "integrate-google-drive")}
                            data-tooltip-id={'next'}
                            type={'button'}
                            className={`igd-btn ${tabIndex === tabKeys.length - 1 ? 'btn-primary' : 'btn-link'}`}
                            onClick={() => {
                                if (tabIndex === tabKeys.length - 1) {
                                    updateShortcode(editData, true);
                                } else {
                                    setTab(tabKeys[tabIndex + 1]);
                                }
                            }}>

                            {tabIndex === tabKeys.length - 1 ?
                                <>
                                    <i className={`dashicons dashicons-saved`}></i>
                                    <span>{wp.i18n.__("Finish", "integrate-google-drive")}</span>
                                </>
                                :
                                <>
                                    <span>{wp.i18n.__("Next", "integrate-google-drive")}</span>
                                    <i className={`dashicons dashicons-arrow-right-alt`}></i>

                                    <Tooltip
                                        id="next"
                                        effect="solid"
                                        place="top"
                                        className={"igd-tooltip"}
                                    />
                                </>
                            }

                        </button>

                    </div>
                }

            </div>
        </div>
    )
}