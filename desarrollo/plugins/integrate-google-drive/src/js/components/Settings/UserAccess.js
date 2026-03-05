import {Tooltip} from "react-tooltip";
import {showProModal} from "../../includes/ProModal";
import ReactSelect from "react-select";

const {useState, useEffect} = React;

export default function UserAccess({data, setData}) {

    const {isPro} = igd;

    const {
        accessFileBrowserUsers = ['administrator'],
        userFolders = {},
        accessSettingsUsers = ['administrator'],
        accessShortcodeBuilderUsers = ['administrator'],
        accessStatisticsUsers = ['administrator'],
        accessPrivateFilesUsers = ['administrator'],
        accessGettingStartedUsers = ['administrator'],
    } = data;

    const [userData, setUserData] = useState({roles: {administrator: 1}, users: []});

    useEffect(() => {
        wp.ajax.post('igd_get_users_data', {
            nonce: igd.nonce,
        })
            .done((data) => setUserData(data))
            .fail((error) => console.log(error));
    }, []);

    const usersOptions = userData && [
        ...Object.keys(userData.roles).map(key => {

            return {
                label: `${key} (role)`,
                value: key
            }
        }),

        ...userData.users.map(({username, email, id}) => {

            return {
                label: `${username} (${email})`,
                value: parseInt(id)
            }
        }),
    ];

    const fileBrowserUsers = userData ? usersOptions.filter(item => accessFileBrowserUsers.includes(item.value)) : [];

    return (
        <div className="igd-settings-body">

            <h3 className="igd-settings-body-title">
                {wp.i18n.__('User Access Settings', 'integrate-google-drive')}

                <a href="https://softlabbd.com/docs/allow-other-users-to-use-integrate-google-drive-in-admin-dashboard/"
                   target="_blank" className="igd-btn btn-outline-info">
                    <i className="dashicons dashicons-editor-help"></i>

                    {wp.i18n.__('Documentation', 'integrate-google-drive')}
                </a>
            </h3>

            {/* File Browser Page */}
            <div className="settings-field field-access-browser">
                <h4 className="settings-field-label">{wp.i18n.__("File Browser Page", 'integrate-google-drive')}</h4>

                <div className="settings-field-content">
                    <ReactSelect
                        isMulti
                        placeholder={"Select users & roles"}
                        options={usersOptions}
                        value={fileBrowserUsers}
                        onChange={selected => setData({
                            ...data,
                            accessFileBrowserUsers: [...selected.map(item => item.value)]
                        })}
                        className="igd-select"
                        classNamePrefix="igd-select"
                        isClearable={false}
                        styles={{
                            multiValue: (base, state) => {
                                return state.data.value === 'administrator' ? {
                                    ...base,
                                    backgroundColor: "gray"
                                } : base;
                            },
                            multiValueLabel: (base, state) => {
                                return state.data.value === 'administrator'
                                    ? {...base, fontWeight: "bold", color: "white", paddingRight: 6}
                                    : base;
                            },
                            multiValueRemove: (base, state) => {
                                return state.data.value === 'administrator' ? {...base, display: "none"} : base;
                            }
                        }}
                    />

                    <p className="description">{wp.i18n.__("Select which roles & users can access the admin dashboard file browser.", 'integrate-google-drive')}</p>

                    {fileBrowserUsers.length > 1 && (
                        <div className="settings-field-sub">
                            {
                                fileBrowserUsers.map(item => {
                                    const {label, value} = item;

                                    if ('administrator' === value) return;

                                    const folders = userFolders[value] || [];

                                    return (
                                        <div className="settings-field">
                                            <h4 className="settings-field-label">{wp.i18n.__("Select folders for ", 'integrate-google-drive')} - {label}</h4>

                                            <div className="template-folder-wrap">

                                                {folders.length ?
                                                    folders.map(folder => {
                                                        let {id, name, iconLink} = folder;

                                                        iconLink = iconLink || `${igd.pluginUrl}/assets/images/icons/folder.png`;

                                                        return (

                                                            <div className="template-folder">
                                                                <div className="template-folder-item">

                                                                    <img src={iconLink}/>

                                                                    <span className="template-folder-name">{name}</span>

                                                                    <div className="dashicons dashicons-no-alt"
                                                                         onClick={() => {
                                                                             const newFolders = userFolders[value].filter(folder => folder.id !== id);
                                                                             setData({
                                                                                 ...data,
                                                                                 userFolders: {
                                                                                     ...userFolders,
                                                                                     [value]: newFolders
                                                                                 }
                                                                             });
                                                                         }}
                                                                    ></div>
                                                                </div>
                                                            </div>
                                                        )
                                                    })
                                                    :

                                                    <div className="template-folder">
                                                        <div className="template-folder-item">
                                                            <img src={`${igd.pluginUrl}/assets/images/icons/folder.png`}/>
                                                            <span
                                                                className="template-folder-name">{wp.i18n.__('All Folders', 'integrate-google-drive')}</span>
                                                        </div>
                                                    </div>
                                                }

                                                <button
                                                    data-tooltip-content="PRO Feature"
                                                    data-tooltip-id={'specific-folders'}
                                                    className="igd-btn btn-success"
                                                    onClick={() => {

                                                        if (!igd.isPro) {
                                                            showProModal(wp.i18n.__('Upgrade to select specific folders for users.', 'integrate-google-drive'));

                                                            return;
                                                        }

                                                        Swal.fire({
                                                            html: `<div id="igd-select-files" class="igd-module-builder-modal-wrap"></div>`,
                                                            showConfirmButton: false,
                                                            customClass: {
                                                                container: 'igd-module-builder-modal-container'
                                                            },
                                                            didOpen(popup) {
                                                                const element = document.getElementById('igd-select-files');

                                                                ReactDOM.render(
                                                                    <ModuleBuilderModal
                                                                        initData={{folders}}
                                                                        onUpdate={data => {
                                                                            const {folders = []} = data;

                                                                            setData(data => ({
                                                                                ...data,
                                                                                userFolders: {
                                                                                    ...userFolders,
                                                                                    [value]: folders.map(folder => ({
                                                                                        id: folder.id,
                                                                                        name: folder.name,
                                                                                        iconLink: folder.iconLink,
                                                                                        accountId: folder.accountId,
                                                                                        type: folder.type,
                                                                                        shortcutDetails: folder.shortcutDetails,
                                                                                    }))
                                                                                }
                                                                            }));

                                                                            Swal.close();
                                                                        }}
                                                                        onClose={() => Swal.close()}
                                                                        isSelectFiles
                                                                        selectionType="folders"
                                                                    />, element);
                                                            },

                                                            willClose(popup) {
                                                                const element = document.getElementById('igd-select-files');
                                                                ReactDOM.unmountComponentAtNode(element);
                                                            }
                                                        });

                                                    }}
                                                >
                                                    <i className="dashicons dashicons-open-folder"></i>
                                                    <span>{!!folders.length ? wp.i18n.__('Update Folders', 'integrate-google-drive') : wp.i18n.__('Select Folders', 'integrate-google-drive')}</span>
                                                </button>

                                                {!igd.isPro &&
                                                    <Tooltip
                                                        id={`specific-folders`}
                                                        effect="solid"
                                                        place="right"
                                                        variant={'warning'}
                                                        className={"igd-tooltip"}
                                                    />
                                                }


                                            </div>

                                            <p className="description">{wp.i18n.__("Select specific folders that will be accessible to ", 'integrate-google-drive')} {label}.</p>
                                        </div>
                                    )
                                })
                            }
                        </div>
                    )}

                </div>
            </div>

            {/* Module Builder Page */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Module Builder Page", 'integrate-google-drive')}</h4>

                <div className="settings-field-content">
                    <ReactSelect
                        isMulti
                        placeholder={"Select users & roles"}
                        options={usersOptions}
                        value={!!userData ? usersOptions.filter(item => accessShortcodeBuilderUsers.includes(item.value)) : []}
                        onChange={selected => setData({
                            ...data,
                            accessShortcodeBuilderUsers: [...selected.map(item => item.value)]
                        })}
                        className="igd-select"
                        classNamePrefix="igd-select"
                        isClearable={false}
                        styles={{
                            multiValue: (base, state) => {
                                return state.data.value === 'administrator' ? {
                                    ...base,
                                    backgroundColor: "gray"
                                } : base;
                            },
                            multiValueLabel: (base, state) => {
                                return state.data.value === 'administrator'
                                    ? {...base, fontWeight: "bold", color: "white", paddingRight: 6}
                                    : base;
                            },
                            multiValueRemove: (base, state) => {
                                return state.data.value === 'administrator' ? {...base, display: "none"} : base;
                            }
                        }}
                    />

                    <p className="description">{wp.i18n.__("Select which roles & users can create & edit shortcode modules.", 'integrate-google-drive')}</p>
                    <p className="description">
                        <strong>{wp.i18n.__("Note: ", 'integrate-google-drive')}</strong> {wp.i18n.__("Roles & Users can only access the folders that they have assigned in the ", 'integrate-google-drive')}
                        <strong>{wp.i18n.__(" Access Backend File Browser ", 'integrate-google-drive')}</strong> {wp.i18n.__(" settings above.", 'integrate-google-drive')}
                    </p>

                </div>
            </div>

            {/* Users Private Files Page */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("User Private Files Page", 'integrate-google-drive')}</h4>

                <div className="settings-field-content"
                     data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                     data-tooltip-id={"igd-pro-tooltip"}
                >
                    <ReactSelect
                        isMulti
                        placeholder={"Select users & roles"}
                        options={usersOptions}
                        value={!!userData ? usersOptions.filter(item => accessPrivateFilesUsers.includes(item.value)) : []}
                        onChange={selected => setData({
                            ...data,
                            accessPrivateFilesUsers: [...selected.map(item => item.value)]
                        })}
                        className="igd-select"
                        classNamePrefix="igd-select"
                        isClearable={false}
                        isDisabled={!isPro}
                        styles={{
                            multiValue: (base, state) => {
                                return state.data.value === 'administrator' ? {
                                    ...base,
                                    backgroundColor: "gray"
                                } : base;
                            },
                            multiValueLabel: (base, state) => {
                                return state.data.value === 'administrator'
                                    ? {...base, fontWeight: "bold", color: "white", paddingRight: 6}
                                    : base;
                            },
                            multiValueRemove: (base, state) => {
                                return state.data.value === 'administrator' ? {...base, display: "none"} : base;
                            }
                        }}
                    />

                    {!isPro &&
                        <Tooltip
                            id={"igd-pro-tooltip"}
                            effect={"solid"}
                            place={"left"}
                            variant={"warning"}
                            className={"igd-tooltip"}
                        />
                    }

                    <p className="description">{wp.i18n.__("Select which roles & users can manually link private files & folders to users.", 'integrate-google-drive')}</p>
                    <p className="description">
                        <strong>{wp.i18n.__("Note: ", 'integrate-google-drive')}</strong> {wp.i18n.__("Roles & Users can only access the folders that they have assigned in the ", 'integrate-google-drive')}
                        <strong>{wp.i18n.__(" Access Backend File Browser ", 'integrate-google-drive')}</strong> {wp.i18n.__(" settings above.", 'integrate-google-drive')}
                    </p>
                </div>


            </div>

            {/* Settings Page */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Settings Page", 'integrate-google-drive')}</h4>

                <div className="settings-field-content">
                    <ReactSelect
                        isMulti
                        placeholder={"Select users & roles"}
                        options={usersOptions}
                        value={!!userData ? usersOptions.filter(item => accessSettingsUsers.includes(item.value)) : []}
                        onChange={selected => setData({
                            ...data,
                            accessSettingsUsers: [...selected.map(item => item.value)]
                        })}
                        className="igd-select"
                        classNamePrefix="igd-select"
                        isClearable={false}
                        styles={{
                            multiValue: (base, state) => {
                                return state.data.value === 'administrator' ? {
                                    ...base,
                                    backgroundColor: "gray"
                                } : base;
                            },
                            multiValueLabel: (base, state) => {
                                return state.data.value === 'administrator'
                                    ? {...base, fontWeight: "bold", color: "white", paddingRight: 6}
                                    : base;
                            },
                            multiValueRemove: (base, state) => {
                                return state.data.value === 'administrator' ? {...base, display: "none"} : base;
                            }
                        }}
                    />

                    <p className="description">{wp.i18n.__("Select which roles & users can change the plugin settings.", 'integrate-google-drive')}</p>
                </div>

            </div>

            {/* Statistics Page */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Statistics Page", 'integrate-google-drive')}</h4>

                <div className="settings-field-content"
                     data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                     data-tooltip-id={"igd-pro-tooltip"}
                >
                    <ReactSelect
                        isMulti
                        placeholder={"Select users & roles"}
                        options={usersOptions}
                        value={!!userData ? usersOptions.filter(item => accessStatisticsUsers.includes(item.value)) : []}
                        onChange={selected => setData({
                            ...data,
                            accessStatisticsUsers: [...selected.map(item => item.value)]
                        })}
                        className="igd-select"
                        classNamePrefix="igd-select"
                        isClearable={false}
                        isDisabled={!isPro}
                        styles={{
                            multiValue: (base, state) => {
                                return state.data.value === 'administrator' ? {
                                    ...base,
                                    backgroundColor: "gray"
                                } : base;
                            },
                            multiValueLabel: (base, state) => {
                                return state.data.value === 'administrator'
                                    ? {...base, fontWeight: "bold", color: "white", paddingRight: 6}
                                    : base;
                            },
                            multiValueRemove: (base, state) => {
                                return state.data.value === 'administrator' ? {...base, display: "none"} : base;
                            }
                        }}
                    />

                    <p className="description">{wp.i18n.__("Select the users & roles to allow access to the statistics page.", 'integrate-google-drive')}</p>
                </div>
            </div>

            {/* Getting Started Page */}
            <div className="settings-field">
                <span
                    className="settings-field-label">{wp.i18n.__("Getting Started Page", 'integrate-google-drive')}</span>

                <div className="settings-field-content">
                    <ReactSelect
                        isMulti
                        placeholder={"Select users & roles"}
                        options={usersOptions}
                        value={!!userData ? usersOptions.filter(item => accessGettingStartedUsers.includes(item.value)) : []}
                        onChange={selected => setData({
                            ...data,
                            accessGettingStartedUsers: [...selected.map(item => item.value)]
                        })}
                        className="igd-select"
                        classNamePrefix="igd-select"
                        isClearable={false}
                        styles={{
                            multiValue: (base, state) => {
                                return state.data.value === 'administrator' ? {
                                    ...base,
                                    backgroundColor: "gray"
                                } : base;
                            },
                            multiValueLabel: (base, state) => {
                                return state.data.value === 'administrator'
                                    ? {...base, fontWeight: "bold", color: "white", paddingRight: 6}
                                    : base;
                            },
                            multiValueRemove: (base, state) => {
                                return state.data.value === 'administrator' ? {...base, display: "none"} : base;
                            }
                        }}
                    />

                    <p className="description">{wp.i18n.__("Select the users & roles to allow viewing the getting started page.", 'integrate-google-drive')}</p>
                </div>
            </div>


        </div>
    )
}