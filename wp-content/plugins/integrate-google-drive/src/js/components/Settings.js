import Accounts from "./Settings/Accounts";
import Advanced from "./Settings/Advanced";
import SyncSettings from "./Settings/SyncSettings";
import Integrations from "./Settings/Integrations";
import StatisticsSettings from "./Settings/StatisticsSettings";
import PrivateFoldersSettings from "./Settings/PrivateFoldersSettings";
import Appearance from "./Settings/Appearance";
import Tools from "./Settings/Tools";
import UserAccess from "./Settings/UserAccess";
import Performance from "./Settings/Performance";
import UsageLimit from "./Settings/UsageLimit";

import {useMounted, base64Encode, isMobile} from "../includes/functions";
import Security from "./Settings/Security";

const {useState, useEffect, useRef, useCallback} = React;

export default function Settings() {

    const [data, setData] = useState(igd.settings);

    let tabs = [
        {
            key: 'accounts',
            title: wp.i18n.__('Accounts', 'integrate-google-drive'),
        },
        {
            key: 'advanced',
            title: wp.i18n.__('Advanced', 'integrate-google-drive'),
        },
        {
            key: 'appearance',
            title: wp.i18n.__('Appearance', 'integrate-google-drive'),
        },

        {
            key: 'privateFolders',
            title: wp.i18n.__('Private Folders', 'integrate-google-drive'),
        },
        {
            key: 'userAccess',
            title: wp.i18n.__('User Access', 'integrate-google-drive'),
        },

        {
            key: 'usageLimit',
            title: wp.i18n.__('Usage Limit', 'integrate-google-drive'),
        },

        {
            key: 'security',
            title: wp.i18n.__('Security', 'integrate-google-drive'),
        },


        {
            key: 'statistics',
            title: wp.i18n.__('Statistics', 'integrate-google-drive'),
        },

        {
            key: 'sync',
            title: wp.i18n.__('Synchronization', 'integrate-google-drive'),
        },
        {
            key: 'performance',
            title: wp.i18n.__('Performance', 'integrate-google-drive'),
        },

        {
            key: 'integrations',
            title: wp.i18n.__('Integrations', 'integrate-google-drive'),
        },

        {
            key: 'tools',
            title: wp.i18n.__('Tools', 'integrate-google-drive'),
        },
    ];

    const {integrations = []} = data;

    tabs = tabs.filter(item => {
        if ((item.key === 'mediaLibrary' && !integrations.includes('media-library')) ||
            (item.key === 'woocommerce' && !integrations.includes('woocommerce')) ||
            (item.key === 'dokan' && !integrations.includes('dokan'))) {
            return false;
        }

        return true;
    });

    const [updating, setUpdating] = useState(false);

    // Get URL tab parameter
    const urlParams = new URLSearchParams(window.location.search);
    const urlTab = urlParams.get('tab');

    const [tab, setTab] = useState(urlTab || localStorage.getItem('igd_settings_tab') || 'accounts');

    const isMounted = useMounted();
    useEffect(() => {
        if (!isMounted) return;

        localStorage.setItem('igd_settings_tab', tab);

        // smooth scroll to top
        window.scrollTo({top: 0, behavior: 'smooth'});

    }, [tab]);

    // Automatically save the settings when the data is updated
    const prevDataRef = useRef(data);
    const saveSettingsRef = useRef(null); // To store the save timeout

    const saveSettings = (settingsData) => {
        setUpdating(true);

        return wp.ajax.post('igd_save_settings', {
            settings: base64Encode(JSON.stringify(settingsData)),
            nonce: igd.nonce,
        }).done(() => {
            Swal.fire({
                title: false,
                text: wp.i18n.__('Settings saved successfully.', 'integrate-google-drive'),
                icon: 'success',
                toast: true,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                position: 'top-end',
                customClass: {container: 'igd-swal save-settings-toast'},
            });

            if (settingsData.enableStatistics !== prevDataRef.current.enableStatistics) {
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }

        }).fail((error) => {
            console.log(error);

            Swal.fire({
                title: 'Error',
                text: wp.i18n.__('An error occurred while saving the settings.', 'integrate-google-drive'),
                icon: 'error',
            });

        }).always(() => {
            setUpdating(false);
        });
    }

    // Debounce the save settings function
    const debounceSaveSettings = useCallback((newData, delay = 500, shouldReload = false) => {
        clearTimeout(saveSettingsRef.current); // Clear any existing save timeout

        saveSettingsRef.current = setTimeout(() => {
            saveSettings(newData).then(() => {
                if (shouldReload) {
                    location.reload();
                }
            });
        }, delay);
    }, []);

    useEffect(() => {
        if (!data.autoSave) return;

        const prevData = prevDataRef.current;
        let hasChanged = false;
        let shouldReload = false;
        let delay = 500;

        // Check which data key has been changed and if it should trigger a save
        for (const key in data) {
            if (data[key] !== prevData[key]) {

                if (['exportData', 'syncType'].includes(key)) {
                    return;
                }

                if ([
                    'workspaceDomain',
                    'email',
                    'clientID',
                    'clientSecret',
                    'customCss',
                    'nameTemplate',
                    'notificationEmail',
                    'emailReportRecipients',
                    'customSyncInterval',
                ].includes(key)) {
                    delay = 2000;
                }

                if (['customCss',].includes(key)) {
                    delay = 5000;
                }

                if (['enableStatistics',].includes(key)) {
                    shouldReload = true;
                }

                hasChanged = true;

            }
        }

        // If there's a change, call the debounce function
        if (hasChanged) {
            debounceSaveSettings(data, delay, shouldReload);
        }

        // Update the ref with the current data state for the next effect run
        prevDataRef.current = data;
    }, [data, debounceSaveSettings]);

    // Handle settings menu toggle in mobile
    const [menuActive, setMenuActive] = useState(!isMobile());

    return (

        <div className="igd-settings">

            {/* Header */}
            <div className="igd-settings-header">

                {isMobile() &&
                    <button
                        type={`button`}
                        className="menu-toggler"
                        onClick={() => setMenuActive(!menuActive)}
                        title={wp.i18n.__('Toggle Menu', 'integrate-google-drive')}
                        aria-label={wp.i18n.__('Toggle Menu', 'integrate-google-drive')}
                    >
                        <i className="dashicons dashicons-menu"></i>
                    </button>
                }

                <div className="igd-settings-header-title">
                    <img src={`${igd.pluginUrl}/assets/images/settings/settings-icon.svg`} alt="Settings"/>
                    <span>{wp.i18n.__('Settings', 'integrate-google-drive')}</span>
                </div>

                <div className="igd-settings-header-action">
                    <button type={`button`} className="igd-btn btn-primary" onClick={() => saveSettings(data)}>
                        {updating ? <div className="igd-spinner"></div> : <i className="dashicons dashicons-saved"></i>}
                        <span>{updating ? wp.i18n.__('Saving...', 'integrate-google-drive') : wp.i18n.__('Save Changes', 'integrate-google-drive')}</span>
                    </button>
                </div>

            </div>

            {/* Sidebar */}
            {menuActive &&
                <div className="igd-settings-menu">
                    {
                        tabs.map(item => {
                            const {key, title} = item;
                            const isActive = key === tab;

                            const src = `${igd.pluginUrl}/assets/images/settings/menu/${key}.svg`;

                            return (
                                <div
                                    key={key}
                                    className={`igd-settings-menu-item ${isActive ? 'active' : ''}`}
                                    onClick={() => setTab(key)}
                                >
                                    <img src={src} alt={title}/>
                                    <span>{title}</span>
                                </div>
                            )
                        })
                    }
                </div>
            }

            {'accounts' === tab && <Accounts data={data} setData={setData} saveSettings={saveSettings}/>}
            {'advanced' === tab && <Advanced data={data} setData={setData}/>}
            {'appearance' === tab && <Appearance data={data} setData={setData}/>}
            {'sync' === tab && <SyncSettings data={data} setData={setData} saveSettings={saveSettings}/>}
            {'privateFolders' === tab && <PrivateFoldersSettings data={data} setData={setData}/>}
            {'userAccess' === tab && <UserAccess data={data} setData={setData}/>}
            {'usageLimit' === tab && <UsageLimit data={data} setData={setData}/>}
            {'security' === tab && <Security data={data} setData={setData}/>}
            {'statistics' === tab && <StatisticsSettings data={data} setData={setData}/>}
            {'performance' === tab && <Performance data={data} setData={setData}/>}
            {'integrations' === tab && <Integrations data={data} setData={setData} />}
            {'tools' === tab && <Tools data={data} setData={setData} saveSettings={saveSettings}/>}

        </div>
    )
}