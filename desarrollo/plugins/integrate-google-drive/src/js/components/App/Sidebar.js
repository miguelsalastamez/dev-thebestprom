import AppContext from "../../contexts/AppContext";
import RootFolders from "../App/RootFolders";

const {useRef, useContext, useState} = window.React;

export default function Sidebar() {
    const context = useContext(AppContext);
    const {
        isMobile,
        isShortcodeBuilder
    } = context;

    const sidebarRef = useRef(null);

    const initCollapsed = localStorage.getItem('igd_file_browser_sidebar_collapsed') ? localStorage.getItem('igd_file_browser_sidebar_collapsed') === 'true' : isShortcodeBuilder;
    const [isCollapsed, setCollapsed] = useState(initCollapsed);

    return (
        <div ref={sidebarRef} className={`igd-sidebar-wrap ${(!isMobile && isCollapsed) ? 'sidebar-collapsed' : ''}`}>
            <div className="igd-sidebar">

                {/*----- Collapser -----*/}
                {!isMobile &&
                    <button
                        type={'button'}
                        className="sidebar-collapser"
                        onClick={() => {
                            setCollapsed(!isCollapsed);

                            localStorage.setItem('igd_file_browser_sidebar_collapsed', !isCollapsed);
                        }}
                    >
                        <img src={igd.pluginUrl + '/assets/images/shortcode-builder/arrow.svg'}/>
                    </button>
                }

                {/* Folder List */}
                <div className="sidebar-folders">

                    {/* Root Folders */}
                    <RootFolders isSidebar isCollapsed={isCollapsed}/>

                </div>

            </div>
        </div>
    )
}