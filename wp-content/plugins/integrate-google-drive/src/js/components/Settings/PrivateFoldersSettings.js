import {Tooltip} from "react-tooltip";
import ReactSelect from "react-select";
import {showProModal} from "../../includes/ProModal";

import {getRootFolders} from "../../includes/functions";
import NamingTemplate from "../ShortcodeBuilder/Form/Fields/NamingTemplate";

const {useEffect, useState} = React;
const {FormToggle} = wp.components;

const {ModuleBuilderModal} = window;

export default function PrivateFoldersSettings({data, setData}) {

    const {isPro, accounts} = igd;

    const {
        autoPrivateFolders,
        privateFolderRoles = ['editor', 'author', 'contributor'],

        deleteAutoPrivateFolders,
        nameTemplate = '%user_login% (%user_email%)',
        parentFolder = getRootFolders('root'),
        templateFolder,
        mergeFolders = true,
        privateFoldersInAdminDashboard,
        sharePrivateFolder,
    } = data;

    // Users data
    const [userData, setUserData] = useState(null);

    const usersOptions = userData && [
        {label: wp.i18n.__('All', "integrate-google-drive"), value: 'all'},

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

    useEffect(() => {
        wp.ajax.post('igd_get_users_data', {
            nonce: igd.nonce,
        }).then((data) => setUserData(data));
    }, []);

    return (
        <div className="igd-settings-body">

            <h3 className="igd-settings-body-title">
                {wp.i18n.__('Private Folders', 'integrate-google-drive')}

                <a href="https://softlabbd.com/docs/how-to-use-and-enable-private-folders-automatically-link-manually/"
                   target="_blank" className="igd-btn btn-outline-info">
                    <i className="dashicons dashicons-editor-help"></i>
                    {wp.i18n.__('Documentation', 'integrate-google-drive')}
                </a>

            </h3>

            {/* Creates Private Folders */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Create Private Folders on Registration", "integrate-google-drive")} </h4>

                <div className="settings-field-content">
                    <FormToggle
                        data-tooltip-content="PRO Feature"
                        data-tooltip-id="create-folder"
                        checked={isPro && autoPrivateFolders}
                        className={!isPro ? 'disabled' : ''}
                        onChange={() => {
                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to enable auto private folders creation on user registration.', 'integrate-google-drive'));

                                return;
                            }
                            setData({...data, autoPrivateFolders: !autoPrivateFolders})
                        }}
                    />

                    {!isPro &&
                        <Tooltip
                            id={`create-folder`}
                            effect="solid"
                            place="right"
                            variant={"warning"}
                            className={"igd-tooltip"}
                        />
                    }

                    <p className="description">
                        {wp.i18n.__("Enable/ disable automatic private folders.", "integrate-google-drive")}
                        <br/>
                        {wp.i18n.__("If enabled, a new private folder will be created for the new registered users.", "integrate-google-drive")}
                    </p>
                </div>
            </div>

            {(isPro && autoPrivateFolders) &&
                <>
                    {/* Private Folder User Roles */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Filter User Roles", 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">
                            <ReactSelect
                                isMulti
                                placeholder={"Select user roles"}
                                options={usersOptions}
                                value={!!userData ? usersOptions.filter(item => privateFolderRoles.includes(item.value)) : []}
                                onChange={selected => setData({
                                    ...data,
                                    privateFolderRoles: [...selected.map(item => item.value)]
                                })}
                                className="igd-select"
                                classNamePrefix="igd-select"
                                isClearable={true}
                            />

                            <p className="description">{wp.i18n.__("Select the user roles for which the private folders will be created.", "integrate-google-drive")}</p>
                        </div>

                    </div>

                    {/* Name Template */}
                    <NamingTemplate
                        value={nameTemplate}
                        onUpdate={(nameTemplate) => setData(data => ({...data, nameTemplate}))}
                        type={'folder'}
                    />

                    {/* Parent Folder */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Parent Folder", "integrate-google-drive")} </h4>

                        <div className="settings-field-content">

                            <div className="template-folder-wrap">
                                {!!parentFolder &&
                                    <div className="template-folder">
                                        <div className="template-folder-account">
                                            {accounts[parentFolder.accountId]?.email}
                                        </div>

                                        <div className="template-folder-item">
                                            {!!parentFolder.iconLink ?
                                                <img src={parentFolder.iconLink}/>
                                                :
                                                <i className="dashicons dashicons-category"></i>
                                            }
                                            <span
                                                className="template-folder-name">{!!parentFolder.name ? parentFolder.name : getRootFolders(parentFolder)}</span>
                                        </div>

                                        <div className="dashicons dashicons-no-alt"
                                             onClick={() => setData({...data, parentFolder: null})}>
                                            <span
                                                className="screen-reader-text">{wp.i18n.__('Remove', 'integrate-google-drive')}</span>
                                        </div>
                                    </div>
                                }

                                <button
                                    className="igd-btn btn-info"
                                    onClick={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to PRO to enable auto private folders creation on user registration.', 'integrate-google-drive'));

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
                                                        initData={{folders: !!parentFolder ? [parentFolder] : []}}
                                                        onUpdate={data => {
                                                            const {folders = []} = data;
                                                            setData(data => ({...data, parentFolder: folders[0]}));
                                                            Swal.close();
                                                        }}
                                                        onClose={() => Swal.close()}
                                                        isSelectFiles
                                                        selectionType="parent"
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
                                    <span>{!!parentFolder ? wp.i18n.__('Change Parent Folder', 'integrate-google-drive') : wp.i18n.__('Select Parent Folder', 'integrate-google-drive')}</span>
                                </button>

                            </div>
                            <p className="description">{wp.i18n.__("Select the parent folder for the newly created private folders. All the private folders will be created within the selected parent folder.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                    {/* Template Folder */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Template Folder", "integrate-google-drive")} </h4>
                        <div className="settings-field-content">
                            <div className="template-folder-wrap">
                                {!!templateFolder &&
                                    <div className="template-folder">
                                        <div className="template-folder-account">
                                            {accounts[templateFolder.accountId]?.email}
                                        </div>

                                        <div className="template-folder-item">
                                            {!!templateFolder.iconLink ?
                                                <img src={templateFolder.iconLink}/>
                                                :
                                                <i className="dashicons dashicons-category"></i>
                                            }
                                            <span
                                                className="template-folder-name">{!!templateFolder.name ? templateFolder.name : getRootFolders(templateFolder)}</span>
                                        </div>

                                        <div className="dashicons dashicons-no-alt"
                                             onClick={() => setData({...data, templateFolder: null})}>
                                        <span
                                            className="screen-reader-text">{wp.i18n.__('Remove', 'integrate-google-drive')}</span>
                                        </div>
                                    </div>
                                }

                                <button
                                    className="igd-btn btn-info"
                                    onClick={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to PRO to enable auto private folders creation on user registration.', 'integrate-google-drive'));

                                            return;
                                        }

                                        Swal.fire({
                                            html: `<div id="igd-select-files" class="igd-module-builder-modal-wrap single"></div>`,
                                            showConfirmButton: false,
                                            customClass: {
                                                container: 'igd-module-builder-modal-container'
                                            },
                                            didOpen(popup) {
                                                const element = document.getElementById('igd-select-files');

                                                ReactDOM.render(
                                                    <ModuleBuilderModal
                                                        initData={{folders: !!templateFolder ? [templateFolder] : []}}
                                                        onUpdate={data => {
                                                            const {folders = []} = data;
                                                            setData(data => ({...data, templateFolder: folders[0]}));
                                                            Swal.close();
                                                        }}
                                                        onClose={() => Swal.close()}
                                                        isSelectFiles
                                                        selectionType="template"
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
                                    <span>{!!templateFolder ? wp.i18n.__('Change Template Folder', 'integrate-google-drive') : wp.i18n.__('Select Template Folder', 'integrate-google-drive')}</span>
                                </button>

                            </div>

                            {!!parentFolder && !!templateFolder && parentFolder.accountId !== templateFolder.accountId &&
                                <div className="template-folder-error">
                                    <i className="dashicons dashicons-warning"></i>
                                    <span>{wp.i18n.__('Template folder and parent folder must be from the same account.', 'integrate-google-drive')}</span>
                                </div>
                            }

                            {!!parentFolder && !!templateFolder && parentFolder.id === templateFolder.id &&
                                <div className="template-folder-error">
                                    <i className="dashicons dashicons-warning"></i>
                                    <span>{wp.i18n.__(`Template folder and parent folder can't be the same.`, 'integrate-google-drive')}</span>
                                </div>
                            }

                            <p className="description">{wp.i18n.__("Select the template folder for the newly created private folders. All the files and folders within the template folder will be copied to the newly created private folders for the users.", "integrate-google-drive")}</p>

                        </div>

                    </div>

                </>
            }

            {/* Share Folder with User */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Share Folder with User", "integrate-google-drive")} </h4>
                <div className="settings-field-content">
                    <FormToggle
                        data-tooltip-content="PRO Feature"
                        checked={sharePrivateFolder}
                        onChange={() => {
                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to enable auto private folders creation on user registration.', 'integrate-google-drive'));

                                return;
                            }

                            setData({...data, sharePrivateFolder: !sharePrivateFolder})
                        }}
                    />

                    <p className="description">{wp.i18n.__("Grant the user access to the cloud folder by adding their email to its sharing permissions. They’ll be able to access it directly through the cloud.", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Merge Folders */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Merge Folders", "integrate-google-drive")} </h4>

                <div className="settings-field-content">
                    <FormToggle
                        data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                        checked={mergeFolders}
                        onChange={() => {

                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to enable auto private folders creation on user registration.', 'integrate-google-drive'));
                                return;
                            }

                            setData({...data, mergeFolders: !mergeFolders})
                        }}
                    />

                    <p className="description">{wp.i18n.__("Enable the merging of folders when a folder with a same name already exists, rather than creating a new folder.", "integrate-google-drive")}</p>

                </div>
            </div>

            {/* Private Folders in Admin Dashboard */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Private Folders in Admin Dashboard", "integrate-google-drive")} </h4>

                <div className="settings-field-content">
                    <FormToggle
                        data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                        data-tooltip-id={"private-folders-dashboard"}
                        checked={privateFoldersInAdminDashboard}
                        className={!isPro ? 'disabled' : ''}
                        onChange={() => {

                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to allow private folders in admin dashboard.', 'integrate-google-drive'));
                                return;
                            }

                            setData({...data, privateFoldersInAdminDashboard: !privateFoldersInAdminDashboard})
                        }}
                    />

                    {!isPro &&
                        <Tooltip
                            id={`private-folders-dashboard`}
                            effect="solid"
                            place="right"
                            className={"igd-tooltip"}
                            variant={"warning"}
                        />
                    }

                    <p className="description">{wp.i18n.__("Allow users to access the linked private folders in admin dashboard file browser.", "integrate-google-drive")}</p>

                </div>
            </div>

            {/* Delete Private Folders */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Delete Private Folders on WP User Delete", "integrate-google-drive")} </h4>
                <div className="settings-field-content">
                    <FormToggle
                        data-tooltip-content="PRO Feature"
                        checked={deleteAutoPrivateFolders}
                        onChange={() => {
                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to enable auto private folders creation on user registration.', 'integrate-google-drive'));

                                return;
                            }
                            setData({...data, deleteAutoPrivateFolders: !deleteAutoPrivateFolders})
                        }}
                    />

                    <p className="description">{wp.i18n.__("If ON, Delete the linked private folders on account delete.", "integrate-google-drive")}</p>
                </div>
            </div>

        </div>
    )
}