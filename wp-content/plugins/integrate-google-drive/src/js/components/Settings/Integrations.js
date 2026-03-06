import ReactSelect from "react-select";
import {showProModal} from "../../includes/ProModal";
import {Tooltip} from "react-tooltip";
import {useMounted} from "../../includes/functions";

const {FormToggle, CheckboxControl, Button} = wp.components;
const {useState, useEffect} = React;

const {isPro} = igd;

export default function Integrations({data, setData}) {

    const {
        integrations = ['classic-editor', 'gutenberg-editor', 'elementor'],
        channels = ['shareLink', 'embedCode', 'email',],

        // Media Library Settings
        mediaLibraryFolders = [],
        deleteMediaCloudFile = false,
        accessMediaLibraryUsers = ['administrator'],

        // WooCommerce Settings
        wooCommerceDownload = true,
        wooCommerceUpload = false,
        wooCommerceUploadLocations = ['checkout', 'order-received', 'my-account'],
        wooCommerceUploadOrderStatuses = ['wc-pending', 'wc-processing', 'wc-on-hold'],

        // Dokan Settings
        dokanDownload = true,
        dokanUpload = false,
        dokanMediaLibrary = false,

    } = data;

    let items = [
        {
            key: 'media-library',
            title: wp.i18n.__('Media Library', 'integrate-google-drive'),
            description: wp.i18n.__('Integrate Google Drive with WordPress Media Library and use the Google Drive files as attachments in the media library.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-enable-google-drive-integration-with-media-library/',
            isPro: true,
        },
        {
            key: 'classic-editor',
            title: wp.i18n.__('Classic Editor', 'integrate-google-drive'),
            description: wp.i18n.__('Add Google Drive modules to your pages using the Google Drive button on the classic editor.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-integrate-google-drive-with-classic-editor/',
        },
        {
            key: 'gutenberg-editor',
            title: wp.i18n.__('Gutenberg Editor', 'integrate-google-drive'),
            description: wp.i18n.__('Add the Google Drive Modules block to the Gutenberg editor for a fast and easy module management.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-integrate-google-drive-with-gutenberg-editor/',
        },

        {
            key: 'elementor',
            title: wp.i18n.__('Elementor', 'integrate-google-drive'),
            description: wp.i18n.__('Add Google Drive Modules widget to the Elementor editor for a fast and easy module management.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-integrate-google-drive-with-elementor-editor/',
        },

        {
            key: 'divi',
            title: wp.i18n.__('Divi', 'integrate-google-drive'),
            description: wp.i18n.__('Add Google Drive Modules module to the Divi editor for a fast and easy module management.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-integrate-google-drive-with-divi/',
        },

        {
            key: 'acf',
            title: wp.i18n.__('Advanced Custom Fields', 'integrate-google-drive'),
            description: wp.i18n.__('Allows you to select Google Drive files and folders using ACF field and display in theme template file.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-integrate-google-drive-with-advanced-custom-fields/',
            isPro: true,
        },

        {
            key: 'woocommerce',
            title: wp.i18n.__('WooCommerce', 'integrate-google-drive'),
            description: wp.i18n.__('Allows you to serve your Google Drive files as downloadable files and let customer upload files to Google Drive on checkout.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-allow-customers-to-upload-files-in-woocommerce-and-store-on-google-drive/',
            isPro: true,
        },

        {
            key: 'dokan',
            title: wp.i18n.__('Dokan', 'integrate-google-drive'),
            description: wp.i18n.__('Allows vendors to serve their Google Drive files as downloadable files and let customer upload files to Google Drive on checkout.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-allow-vendors-to-serve-sell-their-digital-download-files-directly-from-google-drive-in-dokan/',
            isPro: true,
        },
        {
            key: 'edd',
            title: wp.i18n.__('Easy Digital Downloads', 'integrate-google-drive'),
            description: wp.i18n.__('Allows you to serve your Easy Digital Downloads files directly from Google Drive.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-integrate-google-drive-with-easy-digital-downloads/',
            isPro: true,
        },
        {
            key: 'tutor',
            title: wp.i18n.__('Tutor LMS', 'integrate-google-drive'),
            description: wp.i18n.__('Allows Instructors to link their Google accounts for efficient and independent course material management.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-integrate-google-drive-with-the-tutor-lms-plugin/',
            isPro: true,
        },
        {
            key: 'cf7',
            title: wp.i18n.__('Contact Form 7', 'integrate-google-drive'),
            description: wp.i18n.__('Allows you to upload your files directly to Google Drive from your Contact Form 7 upload field.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-google-drive-uploader-field-with-contact-form-7/',
        },
        {
            key: 'wpforms',
            title: wp.i18n.__('WPForms', 'integrate-google-drive'),
            description: wp.i18n.__('Allows you to upload your files directly to Google Drive from your WPForms upload field.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-google-drive-uploader-field-with-wpforms/',
            isPro: true,
        },
        {
            key: 'gravityforms',
            title: wp.i18n.__('Gravity Forms', 'integrate-google-drive'),
            description: wp.i18n.__('Allows you to upload your files directly to Google Drive from your Gravity Forms upload field.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-google-drive-uploader-field-with-gravity-forms/',
            isPro: true,
        },
        {
            key: 'fluentforms',
            title: wp.i18n.__('Fluent Forms', 'integrate-google-drive'),
            description: wp.i18n.__('Allows you to upload your files directly to Google Drive from your Fluent Forms upload field.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-google-drive-uploader-field-with-fluent-forms/',
            isPro: true,
        },
        {
            key: 'formidableforms',
            title: wp.i18n.__('Formidable Forms', 'integrate-google-drive'),
            description: wp.i18n.__('Allows you to upload your files directly to Google Drive from your Formidable Forms upload field.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-google-drive-uploader-field-with-formidable-forms/',
            isPro: true,
        },
        {
            key: 'ninjaforms',
            title: wp.i18n.__('Ninja Forms', 'integrate-google-drive'),
            description: wp.i18n.__('Allows effortless file uploads from Ninja Forms to Google Drive for quick and efficient storage.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/how-to-use-google-drive-uploader-field-with-ninja-forms/',
            isPro: true,
        },
        {
            key: 'elementor-form',
            title: wp.i18n.__('Elementor Form', 'integrate-google-drive'),
            description: wp.i18n.__('Allows effortless file uploads from Elementor PRO Form widget to Google Drive for quick and efficient storage.', 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/upload-files-from-elementor-form-to-google-drive',
            isPro: true,
        },
        {
            key: 'metform',
            title: wp.i18n.__('MetForm', 'integrate-google-drive'),
            description: wp.i18n.__("Allows effortless file uploads from MetForm to Google Drive for quick and efficient storage.", 'integrate-google-drive'),
            docLink: 'https://softlabbd.com/docs/upload-files-from-metform-to-google-drive/',
            isPro: true,
        },
    ];

    // Sort items by isPro key
    if (!igd.isPro) {
        items.sort((a, b) => {
            if (a.isPro === b.isPro) {
                return 0;
            }
            return a.isPro ? 1 : -1;
        });
    }

    const channelOptions = [
        {
            label: wp.i18n.__('Share Link', 'integrate-google-drive'),
            value: 'shareLink',
            icon: 'admin-links',
        },
        {
            label: wp.i18n.__('Embed Code', 'integrate-google-drive'),
            value: 'embedCode',
            icon: 'editor-code',
        },
        {
            label: wp.i18n.__('Email', 'integrate-google-drive'),
            value: 'email',
            icon: 'email',
        },
        {
            label: wp.i18n.__('Facebook', 'integrate-google-drive'),
            value: 'facebook',
            icon: 'facebook',
        },
        {
            label: wp.i18n.__('Twitter', 'integrate-google-drive'),
            value: 'twitter',
            icon: 'twitter',
        },
        {
            label: wp.i18n.__('WhatsApp', 'integrate-google-drive'),
            value: 'whatsapp',
            icon: 'whatsapp',
        }
    ];

    // Media Library
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

    const clearAttachments = () => {

        if (!isPro) {
            showProModal(wp.i18n.__('Upgrade to PRO to enable Media Library integration.', 'integrate-google-drive'));

            return;
        }

        Swal.fire({
            title: wp.i18n.__('Are you sure?', 'integrate-google-drive'),
            text: wp.i18n.__('Clear all the Google Drive attachments!', 'integrate-google-drive'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: wp.i18n.__('Clear attachments', 'integrate-google-drive'),
            cancelButtonText: wp.i18n.__('Cancel', 'integrate-google-drive'),
            reverseButtons: true,
            showLoaderOnConfirm: true,
            customClass: {container: 'igd-swal igd-swal-reverse'},
            preConfirm: () => {

                return new Promise((resolve, reject) => {
                    wp.ajax.post('igd_media_clear_attachments', {nonce: igd.nonce})
                        .done(() => resolve())
                        .fail((error) => reject(error));
                });

            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: wp.i18n.__('Attachments Cleared!', 'integrate-google-drive'),
                    text: wp.i18n.__('Google Drive attachments are deleted from the media library.', 'integrate-google-drive'),
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                    toast: true,
                });
            }
        });
    }

    // WooCommerce
    const uploadLocations = [
        {
            label: wp.i18n.__('Product Page', 'integrate-google-drive'),
            value: 'product',
        },
        {
            label: wp.i18n.__('Cart Page', 'integrate-google-drive'),
            value: 'cart',
        },
        {
            label: wp.i18n.__('Checkout Page', 'integrate-google-drive'),
            value: 'checkout',
        },
        {
            label: wp.i18n.__('Order Received Page', 'integrate-google-drive'),
            value: 'order-received',
        },
        {
            label: wp.i18n.__('My Account Page', 'integrate-google-drive'),
            value: 'my-account',
        },
    ];

    const orderStatuses = {
        'wc-pending': wp.i18n._x('Pending payment', 'Order status', 'woocommerce'),
        'wc-processing': wp.i18n._x('Processing', 'Order status', 'woocommerce'),
        'wc-on-hold': wp.i18n._x('On hold', 'Order status', 'woocommerce'),
        'wc-completed': wp.i18n._x('Completed', 'Order status', 'woocommerce'),
        'wc-cancelled': wp.i18n._x('Cancelled', 'Order status', 'woocommerce'),
        'wc-refunded': wp.i18n._x('Refunded', 'Order status', 'woocommerce'),
        'wc-failed': wp.i18n._x('Failed', 'Order status', 'woocommerce'),
    }

    const isMounted = useMounted();
    useEffect(() => {
        if (!isMounted) return;

        if ((wooCommerceDownload || wooCommerceUpload)) {
            setData(prevState => ({...prevState, integrations: [...integrations, 'woocommerce']}));
        }

    }, [wooCommerceDownload, wooCommerceUpload]);

    // Dokan
    useEffect(() => {
        if (!isMounted) return;

        if (dokanDownload || dokanUpload) {
            setData(prevState => ({...prevState, integrations: [...integrations, 'woocommerce', 'dokan']}));
        }

    }, [dokanDownload, dokanUpload]);

    return (
        <div className="igd-settings-body">

            {/* Integrations */}
            <div className="integrations-wrapper settings-field">

                <div className="integrations-header">

                    <div className="integrations-header-title">
                        <h3 className="igd-settings-body-title">{wp.i18n.__('Plugins Integrations', 'integrate-google-drive')}</h3>
                        <p className="description">{wp.i18n.__("Select the plugins you want to integrate with Integrate Google Drive.", "integrate-google-drive")}</p>
                    </div>

                    <div className="integrations-actions">
                        <button className="igd-btn btn-outline-primary" onClick={() => {
                            setData({
                                ...data, integrations: items.filter(item => {
                                    return item.isPro ? !!igd.isPro : true;
                                }).map(item => item.key)
                            });
                        }}>{wp.i18n.__('Enable All', 'integrate-google-drive')}</button>

                        <button className="igd-btn btn-outline-warning" onClick={() => {
                            setData({...data, integrations: []});
                        }}>{wp.i18n.__('Disable All', 'integrate-google-drive')}</button>
                    </div>

                </div>

                <div className="integrations">
                    {
                        items.map(item => {
                            const {key, title, description, isPro, docLink} = item;
                            const isActive = isPro ? igd.isPro && integrations.includes(key) : integrations.includes(key);

                            const isDisabled = isPro && !igd.isPro;

                            return (
                                <div
                                    key={key}
                                    className={`integration-item ${isDisabled ? 'pro-item' : ''} ${isActive ? 'active' : ''}`}
                                    onClick={() => {
                                        if (isPro && !igd.isPro) {
                                            showProModal(`Upgrade to PRO to enable ${title} integration`);
                                            return;
                                        }

                                        let newIntegrations = isActive ? integrations.filter(i => i !== key) : [...integrations, key];

                                        if (newIntegrations.includes('dokan') && !integrations.includes('woocommerce')) {
                                            newIntegrations.push('woocommerce');
                                        } else if (!newIntegrations.includes('woocommerce') && newIntegrations.includes('dokan')) {
                                            newIntegrations = newIntegrations.filter(i => i !== 'dokan');
                                        }

                                        setData({...data, integrations: newIntegrations});
                                    }}
                                >

                                    <img className='integration-item-img'
                                         src={`${igd.pluginUrl}/assets/images/settings/${key}.png`} alt={title}/>

                                    <span className="integration-item-title">{title}</span>

                                    <i className={`info-${key} dashicons dashicons-info`}></i>
                                    <Tooltip
                                        anchorSelect={`.info-${key}`}
                                        className={"igd-tooltip integration-tooltip"}
                                        place="top"
                                        variant="dark"
                                        content={description}
                                    />

                                    <Button
                                        href={docLink}
                                        target="_blank"
                                        className="integration-item-doc-link"
                                        size={"small"}
                                        variant={"secondary"}
                                        icon={'media-text'}
                                        label={wp.i18n.__('Documentation', 'integrate-google-drive')}
                                    />

                                    {isDisabled &&
                                        <>
                                            <div className="pro-badge"
                                                 data-tooltip-content={wp.i18n.__('Available in PRO', 'integrate-google-drive')}
                                                 data-tooltip-id={`integration-pro-tooltip-${key}`}
                                            >
                                                <i className="dashicons dashicons-lock"></i>
                                                <span
                                                    className="pro-label">{wp.i18n.__('PRO', 'integrate-google-drive')}</span>
                                            </div>

                                            <Tooltip
                                                className={"igd-tooltip"}
                                                place="top"
                                                variant="warning"
                                                effect="solid"
                                                id={`integration-pro-tooltip-${key}`}
                                            />
                                        </>
                                    }

                                    <div className="integration-actions">
                                        {!!isActive && ['media-library', 'woocommerce', 'dokan'].includes(key) &&

                                            <Button
                                                className="integration-item-settings"
                                                size={"small"}
                                                variant={"secondary"}
                                                icon={'admin-generic'}
                                                label={wp.i18n.__('Settings', 'integrate-google-drive')}
                                                onClick={(e) => {
                                                    e.stopPropagation();

                                                    //smooth scroll to the settings section
                                                    const settingsSection = document.getElementById(`${key}-settings`);
                                                    if (settingsSection) {
                                                        settingsSection.classList.add('active');
                                                        settingsSection.querySelector('.accordion-content').style.display = 'block';

                                                        settingsSection.scrollIntoView({
                                                            behavior: 'smooth',
                                                            block: 'start'
                                                        });
                                                    }

                                                }}
                                            />
                                        }

                                        <FormToggle
                                            className="integration-item-toggle"
                                            checked={isActive}
                                        />
                                    </div>

                                </div>
                            )
                        })
                    }
                </div>
            </div>

            {/* Media Library Settings */}
            {integrations.includes('media-library') && isPro &&
                <div id={`media-library-settings`} className="settings-field-accordion">
                    <div className="accordion-header"
                         onClick={(e) => {
                             const $accordion = jQuery(e.currentTarget).closest('.settings-field-accordion');
                             $accordion.find('.accordion-content').slideToggle(300, () => {
                                 $accordion.toggleClass('active');
                             });

                         }}
                    >
                        <img className={`header-img`}
                             src={`${igd.pluginUrl}/assets/images/settings/media-library.png`}
                             alt={wp.i18n.__('Media Library', 'integrate-google-drive')}/>

                        <div className="header-content">
                            <h3 className="igd-settings-body-title">{wp.i18n.__('Media Library Settings', 'integrate-google-drive')}</h3>
                            <p className="description">{wp.i18n.__("Manage how Google Drive integrates with the WordPress Media Library — choose which Drive folders are available and who can access them.", "integrate-google-drive")}</p>
                        </div>

                        <Button
                            className="accordion-toggle"
                            size={"small"}
                            variant={"secondary"}
                            icon={'arrow-down-alt2'}
                            label={wp.i18n.__('Toggle', 'integrate-google-drive')}
                        />
                    </div>

                    <div className="accordion-content">

                        {/* Media Library Folders */}
                        <div className="settings-field field-media-library-folders">
                            <h4 className="settings-field-label">{wp.i18n.__("Media Library Folders", "integrate-google-drive")}</h4>

                            <div className={`settings-field-content ${!isPro ? 'disabled' : ''}`}>

                                <div className={`media-folders-wrap`}>

                                    <div className="media-folders">
                                        {mediaLibraryFolders.map((item, index) => {
                                            const {name, iconLink} = item;

                                            return (
                                                <div className="folder-item">
                                                    <span className={`folder-index`}>{index + 1}.</span>
                                                    <img src={iconLink} alt={name}/>
                                                    <span className="folder-name">{name}</span>

                                                    {/* remove button */}
                                                    <Button
                                                        className="remove-folder-btn"
                                                        size={"small"}
                                                        variant={"danger"}
                                                        icon={'no-alt'}
                                                        onClick={() => {
                                                            const updatedFolders = mediaLibraryFolders.filter((_, i) => i !== index);
                                                            setData({
                                                                ...data,
                                                                mediaLibraryFolders: updatedFolders
                                                            });
                                                        }}
                                                    />


                                                </div>
                                            )
                                        })}
                                    </div>

                                    <button
                                        data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                        data-tooltip-id={'media-library-folders'}
                                        className={`igd-btn btn-info ${!isPro ? 'disabled' : ''} `}
                                        onClick={() => {

                                            if (!isPro) {
                                                showProModal(wp.i18n.__('Upgrade to PRO to enable Media Library integration.', 'integrate-google-drive'));

                                                return;
                                            }

                                            Swal.fire({
                                                html: `<div id="igd-select-files" class="igd-module-builder-modal-wrap select-folders"></div>`,
                                                showConfirmButton: false,
                                                customClass: {
                                                    container: 'igd-module-builder-modal-container'
                                                },
                                                didOpen() {
                                                    const element = document.getElementById('igd-select-files');

                                                    ReactDOM.render(
                                                        <ModuleBuilderModal
                                                            initData={{folders: mediaLibraryFolders}}
                                                            onUpdate={data => {
                                                                const {folders = []} = data;
                                                                setData(data => ({
                                                                    ...data,
                                                                    mediaLibraryFolders: folders.map(folder => ({
                                                                        id: folder.id,
                                                                        name: folder.name,
                                                                        iconLink: folder.iconLink,
                                                                        accountId: folder.accountId,
                                                                    }))
                                                                }));

                                                                Swal.close();
                                                            }}
                                                            onClose={() => Swal.close()}
                                                            isSelectFiles
                                                            selectionType="folders"
                                                        />, element);
                                                },

                                                willClose() {
                                                    const element = document.getElementById('igd-select-files');
                                                    ReactDOM.unmountComponentAtNode(element);
                                                }
                                            });

                                        }}
                                    >
                                        <i className="dashicons dashicons-open-folder"></i>
                                        <span>{!!mediaLibraryFolders.length ? wp.i18n.__('Update Folders', 'integrate-google-drive') : wp.i18n.__('Select Folders', 'integrate-google-drive')}</span>
                                    </button>

                                    {!isPro &&
                                        <Tooltip
                                            id={'media-library-folders'}
                                            effect="solid"
                                            place="right"
                                            variant={'warning'}
                                            className={"igd-tooltip"}
                                        />
                                    }

                                </div>

                                <p className="description">{wp.i18n.__("Choose which Google Drive folders should appear in the Media Library. These folders will be available when adding or selecting media in WordPress.", "integrate-google-drive")}</p>
                            </div>
                        </div>

                        {/* User Access */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Media Library Access", "integrate-google-drive")}</h4>

                            <div className={`settings-field-content ${!isPro ? 'disabled' : ''} `}>

                                <div
                                    className="settings-content-wrap"
                                    data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                >
                                    <ReactSelect
                                        isMulti
                                        placeholder={wp.i18n.__("Select users & roles", "integrate-google-drive")}
                                        options={usersOptions}
                                        value={!!userData ? usersOptions.filter(item => accessMediaLibraryUsers.includes(item.value)) : []}
                                        onChange={selected => {
                                            if (!isPro) {
                                                showProModal(wp.i18n.__('Upgrade to PRO to enable Media Library integration.', 'integrate-google-drive'));

                                                return;
                                            }

                                            setData({
                                                ...data,
                                                accessMediaLibraryUsers: [...selected.map(item => item.value)]
                                            })
                                        }}
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
                                                return state.data.value === 'administrator' ? {
                                                    ...base,
                                                    display: "none"
                                                } : base;
                                            }
                                        }}
                                    />
                                </div>

                                <p className="description">{wp.i18n.__("Select the users and roles who may view, attach, or manage Google Drive files from the WordPress Media Library.", "integrate-google-drive")}</p>
                            </div>
                        </div>

                        {/* Clear Attachments */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Remove Google Drive Attachments", "integrate-google-drive")}</h4>

                            <div className={`settings-field-content ${!isPro ? 'disabled' : ''}`}>

                                <button
                                    data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                    data-tooltip-id={'media-library-folders'}
                                    className={`igd-btn btn-info ${!isPro ? 'disabled' : ''} `}
                                    onClick={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to PRO to enable Media Library integration.', 'integrate-google-drive'));

                                            return;
                                        }

                                        Swal.fire({
                                            html: `<div id="igd-select-files" class="igd-module-builder-modal-wrap select-folders"></div>`,
                                            showConfirmButton: false,
                                            customClass: {
                                                container: 'igd-module-builder-modal-container'
                                            },
                                            didOpen() {
                                                const element = document.getElementById('igd-select-files');

                                                ReactDOM.render(
                                                    <ModuleBuilderModal
                                                        initData={{folders: mediaLibraryFolders}}
                                                        onUpdate={data => {
                                                            const {folders = []} = data;
                                                            setData(data => ({
                                                                ...data,
                                                                mediaLibraryFolders: folders.map(folder => ({
                                                                    id: folder.id,
                                                                    name: folder.name,
                                                                    iconLink: folder.iconLink,
                                                                    accountId: folder.accountId,
                                                                }))
                                                            }));

                                                            Swal.close();
                                                        }}
                                                        onClose={() => Swal.close()}
                                                        isSelectFiles
                                                        selectionType="folders"
                                                    />, element);
                                            },

                                            willClose() {
                                                const element = document.getElementById('igd-select-files');
                                                ReactDOM.unmountComponentAtNode(element);
                                            }
                                        });

                                    }}
                                >
                                    <i className="dashicons dashicons-open-folder"></i>
                                    <span>{!!mediaLibraryFolders.length ? wp.i18n.__('Update Folders', 'integrate-google-drive') : wp.i18n.__('Select Folders', 'integrate-google-drive')}</span>
                                </button>

                                {!isPro &&
                                    <Tooltip
                                        id={'media-library-folders'}
                                        effect="solid"
                                        place="right"
                                        variant={'warning'}
                                        className={"igd-tooltip"}
                                    />
                                }

                                <p className="description">{wp.i18n.__("Remove Drive-based attachment records from the WordPress Media Library. This will unlink attachments but will not delete the original files in your Google Drive.", "integrate-google-drive")}</p>

                            </div>
                        </div>

                        {/* Delete Cloud Files */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Delete Drive Files When Attachment Removed", "integrate-google-drive")}</h4>

                            <div className={`settings-field-content ${!isPro ? 'disabled' : ''}`}>
                                <FormToggle
                                    data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                    checked={deleteMediaCloudFile}
                                    onChange={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to PRO to enable Media Library integration.', 'integrate-google-drive'));

                                            return;
                                        }

                                        setData({...data, deleteMediaCloudFile: !deleteMediaCloudFile})
                                    }}
                                />

                                <p className="description">{wp.i18n.__("When enabled, deleting a Google Drive attachment in the WordPress Media Library will also permanently delete the corresponding file from your Google Drive.", "integrate-google-drive")}</p>

                                <div className="igd-notice igd-notice-warning">
                                    <p className="igd-notice-content">
                                        <strong>{wp.i18n.__("⚠️ Warning:", "integrate-google-drive")}</strong> {wp.i18n.__("This will permanently delete matching files from the connected Google Drive account. This action is irreversible.", "integrate-google-drive")}
                                    </p>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>
            }

            {/* WooCommerce Settings */}
            {integrations.includes('woocommerce') && isPro &&
                <div id={`woocommerce-settings`} className="settings-field-accordion">
                    <div className="accordion-header"
                         onClick={(e) => {
                             const $accordion = jQuery(e.currentTarget).closest('.settings-field-accordion');
                             $accordion.find('.accordion-content').slideToggle(300, () => {
                                 $accordion.toggleClass('active');
                             });

                         }}
                    >

                        <img className={`header-img`} src={`${igd.pluginUrl}/assets/images/settings/woocommerce.png`}
                             alt={wp.i18n.__('WooCommerce', 'integrate-google-drive')}/>

                        <div className="header-content">
                            <h3 className="igd-settings-body-title">{wp.i18n.__('WooCommerce Settings', 'integrate-google-drive')}</h3>
                            <p className="description">{wp.i18n.__("Configure the settings for Google Drive integration with WooCommerce.", "integrate-google-drive")}</p>
                        </div>

                        <Button
                            className="accordion-toggle"
                            size={"small"}
                            variant={"secondary"}
                            icon={'arrow-down-alt2'}
                            label={wp.i18n.__('Toggle', 'integrate-google-drive')}
                        />
                    </div>

                    <div className="accordion-content">

                        {/* Enable WooCommerce Downloads */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Enable Downloads", "integrate-google-drive")}</h4>
                            <div className="settings-field-content">
                                <FormToggle
                                    data-tooltip-content={wp.i18n.__(`Pro Feature`, 'integrate-google-drive')}
                                    data-tooltip-id={`igd-pro-tooltip`}
                                    checked={isPro && wooCommerceDownload}
                                    className={!isPro ? 'disabled' : ''}
                                    onChange={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to the Pro version to enable WooCommerce downloads.', 'integrate-google-drive'));
                                            return;
                                        }

                                        setData({...data, wooCommerceDownload: !wooCommerceDownload})
                                    }}
                                />

                                {!isPro &&
                                    <Tooltip
                                        id={'igd-pro-tooltip'}
                                        effect="solid"
                                        place="right"
                                        variant={`warning`}
                                        className={"igd-tooltip"}
                                    />
                                }

                                <p className="description">
                                    {wp.i18n.__("Enable to add Google Drive files to WooCommerce downloadable products.", "integrate-google-drive")}

                                    <a href="https://softlabbd.com/docs/how-to-use-integrate-google-drive-with-woocommerce/"
                                       target="_blank">
                                        <i className="dashicons dashicons-editor-help"></i>
                                        <span>{wp.i18n.__('Documentation', 'integrate-google-drive')}</span>
                                    </a>

                                </p>
                            </div>
                        </div>

                        {/* Enable WooCommerce Uploads */}
                        <div className="settings-field field-woocommerce-upload">

                            <h4 className="settings-field-label">{wp.i18n.__("Enable Uploads", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">
                                <FormToggle
                                    data-tooltip-content={wp.i18n.__(`Pro Feature`, 'integrate-google-drive')}
                                    data-tooltip-id={`igd-pro-tooltip`}
                                    checked={isPro && wooCommerceUpload}
                                    className={!isPro ? 'disabled' : ''}
                                    onChange={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__(`Upgrade to the Pro version to enable WooCommerce uploads.`, 'integrate-google-drive'));
                                            return;
                                        }

                                        setData({...data, wooCommerceUpload: !wooCommerceUpload})
                                    }}
                                />

                                <p className="description">
                                    {wp.i18n.__("Enable/ disable customer file uploads for WooCommerce products.", "integrate-google-drive")}

                                    <a href="https://softlabbd.com/docs/how-to-allow-customers-to-upload-files-in-woocommerce-and-store-on-google-drive/"
                                       target="_blank">
                                        <i className="dashicons dashicons-editor-help"></i>
                                        <span>{wp.i18n.__('Documentation', 'integrate-google-drive')}</span>
                                    </a>
                                </p>

                                {wooCommerceUpload &&
                                    <div className={`settings-field-sub`}>

                                        {/* Upload Order Status */}
                                        <div className={`settings-field`}>
                                            {(wooCommerceUploadLocations.includes('order-received') || wooCommerceUploadLocations.includes('my-account')) &&
                                                <>
                                                    <h4 className="settings-field-label">{wp.i18n.__("Show Upload Box When Order Status is", "integrate-google-drive")}</h4>

                                                    <div className="settings-field-content">
                                                        <div className="upload-box-order-status">
                                                            {
                                                                Object.keys(orderStatuses).map(key => (
                                                                    <CheckboxControl
                                                                        key={key}
                                                                        label={orderStatuses[key]}
                                                                        checked={wooCommerceUploadOrderStatuses.includes(key)}
                                                                        onChange={() => {

                                                                            if (wooCommerceUploadOrderStatuses.includes(key)) {
                                                                                setData({
                                                                                    ...data,
                                                                                    wooCommerceUploadOrderStatuses: wooCommerceUploadOrderStatuses.filter(item => item !== key)
                                                                                });
                                                                            } else {
                                                                                setData({
                                                                                    ...data,
                                                                                    wooCommerceUploadOrderStatuses: [...wooCommerceUploadOrderStatuses, key]
                                                                                });
                                                                            }
                                                                        }}
                                                                    />
                                                                ))
                                                            }
                                                        </div>

                                                        <p className={`description`}>
                                                            {wp.i18n.__("Choose the order statuses during which the upload box should be visible.", "integrate-google-drive")}
                                                        </p>

                                                        <div className="igd-notice igd-notice-info">
                                                            <p className="igd-notice-content">
                                                                <strong>Note: </strong> {wp.i18n.__('This option is only supported on the Order Received and My Account pages.', 'integrate-google-drive')}
                                                            </p>
                                                        </div>

                                                    </div>
                                                </>
                                            }
                                        </div>

                                        {/* Upload Box Locations */}
                                        <div className={`settings-field`}>
                                            <h4 className="settings-field-label">{wp.i18n.__("Upload Box Locations", "integrate-google-drive")}</h4>

                                            <div className="settings-field-content">
                                                <div className="upload-box-locations">
                                                    {
                                                        uploadLocations.map(location => (
                                                            <CheckboxControl
                                                                key={location.value}
                                                                label={location.label}
                                                                checked={wooCommerceUploadLocations.includes(location.value)}
                                                                onChange={() => {

                                                                    if (wooCommerceUploadLocations.includes(location.value)) {
                                                                        setData({
                                                                            ...data,
                                                                            wooCommerceUploadLocations: wooCommerceUploadLocations.filter(item => item !== location.value)
                                                                        });
                                                                    } else {
                                                                        setData({
                                                                            ...data,
                                                                            wooCommerceUploadLocations: [...wooCommerceUploadLocations, location.value]
                                                                        });
                                                                    }
                                                                }}
                                                            />
                                                        ))
                                                    }
                                                </div>
                                                <p className={`description`}>{wp.i18n.__("Select the locations where you want to show the upload box in woocommerce products.", "integrate-google-drive")}</p>
                                            </div>
                                        </div>


                                    </div>
                                }

                            </div>

                        </div>
                    </div>
                </div>
            }

            {/* Dokan Settings */}
            {integrations.includes('dokan') && isPro &&
                <div id={`dokan-settings`} className="settings-field-accordion">
                    <div className="accordion-header"
                         onClick={(e) => {
                             const $accordion = jQuery(e.currentTarget).closest('.settings-field-accordion');
                             $accordion.find('.accordion-content').slideToggle(300, () => {
                                 $accordion.toggleClass('active');
                             });

                         }}
                    >
                        <img className={`header-img`} src={`${igd.pluginUrl}/assets/images/settings/dokan.png`}
                             alt={wp.i18n.__('Dokan', 'integrate-google-drive')}/>

                        <div className="header-content">
                            <h3 className="igd-settings-body-title">{wp.i18n.__('Dokan Settings', 'integrate-google-drive')}</h3>
                            <p className="description">{wp.i18n.__("Configure the settings for Google Drive integration with Dokan.", "integrate-google-drive")}</p>
                        </div>

                        <Button
                            className="accordion-toggle"
                            size={"small"}
                            variant={"secondary"}
                            icon={'arrow-down-alt2'}
                            label={wp.i18n.__('Toggle', 'integrate-google-drive')}
                        />
                    </div>

                    <div className="accordion-content">
                        {/* Enable Downloads */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Enable Downloads", "integrate-google-drive")}</h4>
                            <div className="settings-field-content">
                                <FormToggle
                                    data-tooltip-content={wp.i18n.__(`Pro Feature`, 'integrate-google-drive')}
                                    data-tooltip-id={`igd-tooltip-dokan-download`}
                                    checked={isPro && dokanDownload}
                                    className={!isPro ? 'disabled' : ''}
                                    onChange={() => {

                                        if (!isPro) {
                                            showProModal(wp.i18n.__('Upgrade to the Pro version to enable Dokan downloads.', 'integrate-google-drive'));
                                            return;
                                        }

                                        setData({...data, dokanDownload: !dokanDownload})
                                    }}
                                />

                                {!isPro &&
                                    <Tooltip
                                        id={`igd-tooltip-dokan-download`}
                                        className="igd-tooltip"
                                        effect="solid"
                                        place="right"
                                        variant={'warning'}
                                    />
                                }

                                <p className="description">
                                    {wp.i18n.__("Enable to allow vendors to use their Google Drive files as downloadable products.", "integrate-google-drive")}

                                    <a href="https://softlabbd.com/docs/how-to-allow-vendors-to-serve-sell-their-digital-download-files-directly-from-google-drive-in-dokan/"
                                       target="_blank">
                                        <i className="dashicons dashicons-editor-help"></i>
                                        <span>{wp.i18n.__('Documentation', 'integrate-google-drive')}</span>
                                    </a>
                                </p>
                            </div>
                        </div>

                        {/* Enable Uploads */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Enable Uploads", "integrate-google-drive")}</h4>
                            <div className="settings-field-content">
                                <FormToggle
                                    data-tooltip-content={wp.i18n.__(`Pro Feature`, 'integrate-google-drive')}
                                    data-tooltip-id={`igd-tooltip-dokan-upload`}
                                    checked={isPro && dokanUpload}
                                    className={!isPro ? 'disabled' : ''}
                                    onChange={() => {
                                        if (!isPro) {
                                            showProModal(wp.i18n.__(`Upgrade to the Pro version to enable Dokan uploads.`, 'integrate-google-drive'));
                                            return;
                                        }

                                        setData({...data, dokanUpload: !dokanUpload})
                                    }}
                                />

                                {!isPro &&
                                    <Tooltip
                                        id={`igd-tooltip-dokan-upload`}
                                        className="igd-tooltip"
                                        effect="solid"
                                        place="right"
                                        variant={'warning'}
                                    />
                                }

                                <p className="description">
                                    {wp.i18n.__("Enable to allow vendors to let customers upload files to their Google Drive.", "integrate-google-drive")}

                                    <a href="https://softlabbd.com/docs/how-to-allow-vendors-in-dokan-to-enable-customer-file-uploads-and-store-in-google-drive/"
                                       target="_blank">
                                        <i className="dashicons dashicons-editor-help"></i>
                                        <span>{wp.i18n.__('Documentation', 'integrate-google-drive')}</span>
                                    </a>
                                </p>

                            </div>

                        </div>

                        {/* Enable Media Library Integration */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Enable Media Library Integration", "integrate-google-drive")}</h4>
                            <div className="settings-field-content">
                                <FormToggle
                                    data-tooltip-content={wp.i18n.__(`Pro Feature`, 'integrate-google-drive')}
                                    data-tooltip-id={`igd-tooltip-dokan-media-library`}
                                    checked={isPro && dokanMediaLibrary}
                                    className={!isPro ? 'disabled' : ''}
                                    onChange={() => {
                                        if (!isPro) {
                                            showProModal(wp.i18n.__(`Upgrade to the Pro version to enable Dokan Media Library Integration.`, 'integrate-google-drive'));
                                            return;
                                        }

                                        setData({...data, dokanMediaLibrary: !dokanMediaLibrary})
                                    }}
                                />

                                {!isPro &&
                                    <Tooltip
                                        id={`igd-tooltip-dokan-media-library`}
                                        className="igd-tooltip"
                                        effect="solid"
                                        place="right"
                                        variant={'warning'}
                                    />
                                }

                                <p className="description">
                                    {wp.i18n.__("Allow vendors to connect their Google Drive to the WordPress media library, enabling them to use Google Drive files like images and downloads directly in their products.", "integrate-google-drive")}
                                </p>

                            </div>

                        </div>
                    </div>
                </div>
            }

            {/* Sharing Channels */}
            <div className="sharing-channels-wrap">
                <h3 className="igd-settings-body-title">{wp.i18n.__('File sharing channels', 'integrate-google-drive')}</h3>

                <p className="description">{wp.i18n.__("Select the sharing channels you want to show for the users in the file sharing modal.", "integrate-google-drive")}</p>

                <div className="sharing-channels">
                    {
                        channelOptions.map(tool => (
                            <CheckboxControl
                                key={tool.value}
                                label={<div className={`tool-option`}><i
                                    className={`dashicons dashicons-` + tool.icon}></i> {tool.label} </div>}
                                checked={channels.includes(tool.value)}
                                onChange={() => {

                                    if (channels.includes(tool.value)) {
                                        setData({
                                            ...data,
                                            channels: channels.filter(item => item !== tool.value)
                                        });
                                    } else {
                                        setData({...data, channels: [...channels, tool.value]});
                                    }
                                }}
                            />
                        ))
                    }
                </div>

            </div>

        </div>
    )
}
