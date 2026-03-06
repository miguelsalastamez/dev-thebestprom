import Pagination from "../../includes/Pagination/Pagination";
import ProModal, {showProModal} from "../../includes/ProModal";

import {useMounted} from "../../includes/functions";

const {useState, useEffect} = React;
const {ModuleBuilderModal} = window;

export default function Table() {

    const {roles, total: initTotal, users: initUsers} = igd.userData;

    const userCount = {all: initTotal, ...roles}

    const [total, setTotal] = useState(initTotal);
    const [users, setUsers] = useState(initUsers);

    const [page, setPage] = useState(1);
    const [role, setRole] = useState('all');
    const [search, setSearch] = useState('');

    const [isLoading, setIsLoading] = useState(false);

    const onSearch = e => {
        e.preventDefault();
        setRole('');
        setPage(1);
        getUserData();
    }

    /**
     * Update the user data
     *
     * @param data
     */
    const updateUser = data => {

        const index = users.findIndex(item => item['id'] === data['id']);
        users[index] = data;

        setUsers([...users]);
    }

    const getUserData = () => {
        setIsLoading(true);

        wp.ajax.post('igd_get_users_data', {
            search,
            role,
            page,
            number: 10,
            nonce: igd.nonce,
        }).done(({total, users}) => {
            setUsers(users);
            setTotal(total);
        }).always(() => {
            setIsLoading(false);
        });
    }

    const isMounted = useMounted();

    useEffect(() => {
        if (!isMounted) return;

        getUserData();

    }, [role, page]);

    return (
        <div className="private-folders-list">

            {/* Table Header */}
            <div className="private-folders-list-header">

                <ul className="nav-items">
                    {
                        Object.keys(userCount).map((key) => {

                            const count = userCount[key];

                            if (!count) return;

                            return (
                                <li
                                    key={key}
                                    className={`${key == role ? 'active' : ''}`}
                                    onClick={() => {
                                        setSearch('');
                                        setPage(1);
                                        setRole(key);
                                    }}
                                >
                                    <span>{key} </span>
                                    <span> ({count})</span>
                                </li>
                            )
                        })
                    }
                </ul>

                <form onSubmit={onSearch} className="users-search">
                    <input type="search" id="user-search-input" value={search}
                           placeholder={wp.i18n.__('Search Users', 'integrate-google-drive')}
                           onChange={e => setSearch(e.target.value)}/>
                    <button type="submit" id="search-submit"
                            className="igd-btn btn-primary">{wp.i18n.__('Search', 'integrate-google-drive')}</button>
                </form>

            </div>

            {isLoading && <div className="igd-spinner spinner-large"></div>}

            {/* Users List */}
            {
                !isLoading &&
                <div className="private-folders-table-wrap">
                    <table className="private-folders-table widefat striped table-view-list">
                        <thead>
                        <tr>
                            <th id="username" className="manage-column column-username column-primary sortable desc">
                                <span>Username</span></th>
                            <th id="name"
                                className="manage-column column-name">{wp.i18n.__('Name', 'integrate-google-drive')}</th>
                            <th id="email"
                                className="manage-column column-email">{wp.i18n.__('Email', 'integrate-google-drive')}</th>
                            <th id="role"
                                className="manage-column column-role">{wp.i18n.__('Role', 'integrate-google-drive')}</th>
                            <th id="folders"
                                className="manage-column column-private-folders">{wp.i18n.__('Private Files', 'integrate-google-drive')}</th>
                            <th id="actions"
                                className="manage-column column-actions num">{wp.i18n.__('Actions', 'integrate-google-drive')}</th>
                        </tr>
                        </thead>

                        <tbody id="the-list">

                        {users.length > 0 ?
                            users.map(user => {

                                const {avatar, username, name, role, folders = [], email, id,} = user;

                                return (
                                    <tr>
                                        <td className="username column-username has-row-actions column-primary"
                                            data-colname="Username">
                                            <span dangerouslySetInnerHTML={{__html: avatar}}></span>

                                            {username}
                                        </td>

                                        <td className="name column-name" data-colname="Name">{name}</td>
                                        <td className="email column-email" data-colname="Email">{email}</td>
                                        <td className="role column-role" data-colname="Role">{role}</td>
                                        <td className="role column-folders" data-colname="Private Folders">
                                            {!!folders && folders.map(item => {
                                                    const {name, iconLink} = item;
                                                    return (
                                                        <div className="folder-item">
                                                            {!!iconLink ?
                                                                <img src={iconLink}/>
                                                                :
                                                                <i className="dashicons dashicons-category"></i>
                                                            }

                                                            <span>{!!name ? item.name : item}</span>

                                                            <i className={`dashicons dashicons-no-alt`}
                                                               onClick={() => {
                                                                   const index = folders.findIndex(folder => folder?.id === item.id);
                                                                   folders.splice(index, 1);
                                                                   updateUser({...user, folders,});
                                                                   wp.ajax.post('igd_update_user_folders', {
                                                                       id, folders,
                                                                       nonce: igd.nonce,
                                                                   });
                                                               }}
                                                            ></i>

                                                        </div>
                                                    )
                                                }
                                            )}
                                        </td>
                                        <td className="posts column-actions num" data-colname="Actions">
                                            <button className="igd-btn btn-info"
                                                    onClick={() => {

                                                        if (!igd.isPro) {
                                                            showProModal(wp.i18n.__('With Private Folders, securely share Google Drive documents with users/clients for viewing, downloading, and managing in their private folders. Upgrade to PRO for user-specific private folders.', 'integrate-google-drive'))
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

                                                                            updateUser({...user, folders,});

                                                                            wp.ajax.post('igd_update_user_folders', {
                                                                                id,
                                                                                folders,
                                                                                nonce: igd.nonce,
                                                                            });

                                                                            Swal.fire({
                                                                                text: wp.i18n.__('User files have been updated successfully.', 'integrate-google-drive'),
                                                                                icon: 'success',
                                                                                showConfirmButton: false,
                                                                                timer: 2000,
                                                                                timerProgressBar: true,
                                                                                toast: true,
                                                                                position: 'top-end',
                                                                                customClass: {
                                                                                    container: 'igd-swal igd-swal-toast',
                                                                                }
                                                                            });
                                                                        }}
                                                                        onClose={() => Swal.close()}
                                                                        isSelectFiles
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
                                                <span>{wp.i18n.__('Select Files', 'integrate-google-drive')}</span>
                                            </button>
                                        </td>
                                    </tr>
                                )
                            })

                            :
                            <tr>
                                <td colSpan={6} style={{textAlign: 'center', padding: '20px 0'}}>
                                    {wp.i18n.__('No users found.', 'integrate-google-drive')}
                                </td>
                            </tr>
                        }

                        </tbody>

                    </table>
                </div>
            }

            {/* Pagination */}
            {
                !isLoading &&
                <div className="private-folders-list-footer">
                    <Pagination
                        className={"igd-pagination"}
                        pageCount={Math.ceil(total / 10)}
                        currentPage={page}
                        onPageChange={page => {
                            setPage(page);
                        }}
                    />
                </div>
            }

            {!igd.isPro && <ProModal
                text={wp.i18n.__('Upgrade to PRO to use the private folders for users.', 'integrate-google-drive')}
                isDismissable={false}
            />}

        </div>
    )
}