import {Tooltip} from "react-tooltip";
import {showProModal} from "../../includes/ProModal";

const {useEffect} = React;
const {
    FormToggle,
    SelectControl,
    ButtonGroup,
    Button,
} = wp.components;

export default function Security({data, setData}) {

    const {isPro} = igd;

    const {
        crossDomainVerification = false,
        nonceVerification = true,
        loginType = 'form',
        loginUrl,
        loginMessage =
            `<h3>${wp.i18n.__('Login Required!', 'integrate-google-drive')}</h3>
<p>${wp.i18n.__('Please log in to access this module.', 'integrate-google-drive')}</p>`,

        accessDeniedMessage =
            `<h3>${wp.i18n.__('Access Denied', 'integrate-google-drive')}</h3>
<p>${wp.i18n.__('We\'re sorry, but your account does not currently have access to this content. To gain access, please contact the site administrator who can assist in linking your account to the appropriate content. Thank you.', 'integrate-google-drive')}</p>`,

        passwordProtectedMessage =
            `<h3>${wp.i18n.__('This module is password protected!', 'integrate-google-drive')}</h3>
<p>${wp.i18n.__('To view it please enter your password below:', 'integrate-google-drive')}</p>`,

        emailRequiredMessage =
            `<h3>${wp.i18n.__('Email Required!', 'integrate-google-drive')}</h3>
<p>${wp.i18n.__('Please enter your email address below to proceed:', 'integrate-google-drive')}</p>`,

        secureVideoPlayback,
        workspaceDomain,
        manageSharing = true,
        restoreManageSharing,
        restoreManageSharingInterval = '1',
        customRestoreManageSharingInterval = '1',
    } = data;

    // Render editor
    const editorIds = {
        'login-message-input': 'loginMessage',
        'password-protected-message-input': 'passwordProtectedMessage',
        'access-denied-message-input': 'accessDeniedMessage',
    };

    const editorConfig = {
        wpautop: true,
        toolbar1: 'formatselect,bold,italic,strikethrough,forecolor,backcolor,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,media,spellchecker,fullscreen,wp_adv',
        toolbar2: 'underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
        plugins: 'lists,fullscreen,paste,wpautoresize,wpdialogs,wpeditimage,wpgallery,wplink,wptextpattern,wpview,wordpress,wpemoji,media,textcolor,hr',
        menubar: false,
        branding: false,
        height: 150,
        wp_adv_height: 48,
        setup: editor => {
            editor.on('change', () => {
                const content = editor.getContent();
                const fieldId = editor.id;
                const fieldKey = editorIds[fieldId];

                if (fieldKey) {
                    setData(prev => ({
                        ...prev,
                        [fieldKey]: content
                    }));
                }
            });
        }
    };

    const quicktagsConfig = {
        buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close',
    }

    const config = {
        tinymce: editorConfig,
        quicktags: quicktagsConfig,
        mediaButtons: true,
    };

    useEffect(() => {

        setTimeout(() => {
            Object.keys(editorIds).forEach(editorId => {
                wp.editor.remove(editorId);
                wp.domReady(() => wp.editor?.initialize(editorId, config));
            });

        }, 100)

        return () => {
            Object.keys(editorIds).forEach(editorId => {
                wp.editor.remove(editorId);
            });
        }

    }, []);

    return (
        <div className="igd-settings-body">

            <h3 className="igd-settings-body-title">{wp.i18n.__('Security Settings', 'integrate-google-drive')}</h3>

            {/* Google Workspace Domain */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Google Workspace Domain", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <input type="text" value={workspaceDomain}
                           onChange={e => setData({...data, workspaceDomain: e.target.value})}
                           placeholder={wp.i18n.__("Google Workspace Domain", "integrate-google-drive")}
                    />
                    <p className="description">{wp.i18n.__("If you are using Google Workspace  and you want to share your documents ONLY with users having an account in your Google Workspace Domain, please insert your domain.", "integrate-google-drive")}</p>

                    <div className="igd-notice igd-notice-info">
                        <p className={`igd-notice-content`}>{wp.i18n.__("To make your documents accessible to the public, please leave this field blank.", "integrate-google-drive")}</p>
                    </div>

                </div>
            </div>

            {/* Manage Sharing Permissions */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Manage Sharing Permissions", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <FormToggle
                        checked={manageSharing}
                        onChange={() => setData({...data, manageSharing: !manageSharing})}
                    />
                    <p className="description">{wp.i18n.__("By default, the plugin will manage the sharing permissions of the documents. If you want to manage the sharing permissions manually, please disable this option.", "integrate-google-drive")}</p>
                </div>
            </div>


            {/* Restore Sharing Permissions */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Restore Sharing Permissions", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <FormToggle
                        checked={restoreManageSharing}
                        onChange={() => setData({...data, restoreManageSharing: !restoreManageSharing})}
                    />

                    <p className="description">{wp.i18n.__("For files that require public access to be previewed through the plugin, public sharing permissions will be automatically removed after the selected time interval.", "integrate-google-drive")}</p>

                    {restoreManageSharing && (
                        <div className="settings-field-sub">

                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("Restore Sharing Permissions Interval", "integrate-google-drive")}</h4>

                                <div className="settings-field-content">
                                    <SelectControl
                                        value={restoreManageSharingInterval}
                                        options={[
                                            {value: '1', label: wp.i18n.__("1 Hour", "integrate-google-drive")},
                                            {value: '2', label: wp.i18n.__("2 Hours", "integrate-google-drive")},
                                            {value: '6', label: wp.i18n.__("6 Hours", "integrate-google-drive")},
                                            {value: '12', label: wp.i18n.__("12 Hours", "integrate-google-drive")},
                                            {value: '24', label: wp.i18n.__("24 Hours", "integrate-google-drive")},
                                            {value: '48', label: wp.i18n.__("48 Hours", "integrate-google-drive")},
                                            {value: '72', label: wp.i18n.__("72 Hours", "integrate-google-drive")},
                                            {value: 'custom', label: wp.i18n.__("Custom", "integrate-google-drive")},
                                        ]}

                                        onChange={(value) => setData({...data, restoreManageSharingInterval: value})}
                                    />

                                    <p className="description">{wp.i18n.__("Select the time interval after which the sharing permissions will be restored.", "integrate-google-drive")}</p>
                                </div>
                            </div>

                            {/* Custom Restore Sharing Interval */}
                            {'custom' === restoreManageSharingInterval &&
                                <div className="settings-field">
                                    <h4 className="settings-field-label">{wp.i18n.__("Custom Restore Sharing Interval", "integrate-google-drive")}</h4>
                                    <div className="settings-field-content">
                                        <input
                                            type="number"
                                            value={customRestoreManageSharingInterval}
                                            onChange={e => setData({...data, customRestoreManageSharingInterval: e.target.value})}
                                            min={0}

                                        />

                                        <p className="description">{wp.i18n.__("Enter the custom time interval in hours after which the sharing permissions will be restored.", "integrate-google-drive")}</p>
                                    </div>
                                </div>
                            }

                        </div>
                    )
                    }

                </div>
            </div>


            {/* Cross-Domain Verification */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Cross-Domain Verification", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <FormToggle
                        checked={crossDomainVerification}
                        onChange={() => setData({...data, crossDomainVerification: !crossDomainVerification})}
                    />

                    <p className="description">{wp.i18n.__('Enable to verify that AJAX requests originate from the same domain. This helps prevent unauthorized access. Disable if you\'re using multiple domains for the same WordPress site (e.g., language-specific domains).', 'integrate-google-drive')}</p>
                </div>
            </div>

            {/* Nonce Verification */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Nonce Verification", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <FormToggle
                        checked={nonceVerification}
                        onChange={() => setData({...data, nonceVerification: !nonceVerification})}
                    />

                    <p className="description">{wp.i18n.__('The plugin uses WordPress nonces and other methods to protect against attacks like CSRF. Disable this only if it\'s causing conflicts with other plugins that modify nonce handling.', 'integrate-google-drive')}</p>
                </div>
            </div>

            {/* Secure Video Playback */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Secure Video Playback", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <FormToggle
                        data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                        data-tooltip-id={"igd-pro-tooltip"}
                        className={`igd-form-toggle ${!isPro ? 'disabled' : ''}`}
                        checked={secureVideoPlayback}
                        onChange={() => {

                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to secure video playback.', 'integrate-google-drive'));
                                return;
                            }

                            setData({...data, secureVideoPlayback: !secureVideoPlayback});
                        }}
                    />

                    <p className="description">{wp.i18n.__("Enable this to secure the video playback. This will prevent direct access to the Google Drive videos.", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Login Screen */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Login Screen", "integrate-google-drive")}</h4>

                <div className="settings-field-content">
                    <div className="settings-field-sub">

                        {/* Loading Type */}
                        <div className="settings-field">

                            <h4 className="settings-field-label">{wp.i18n.__("Login Type", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">

                                <ButtonGroup>

                                    <Button
                                        variant={'form' === loginType ? 'primary' : 'secondary'}
                                        onClick={() => setData({...data, loginType: 'form'})}
                                        text={wp.i18n.__("Form", "integrate-google-drive")}
                                        size={'default'}
                                        icon={'editor-table'}
                                    />

                                    <Button
                                        variant={'redirect' === loginType ? 'primary' : 'secondary'}
                                        onClick={() => setData({...data, loginType: 'redirect'})}
                                        text={wp.i18n.__("Redirect URL", "integrate-google-drive")}
                                        size={'default'}
                                        icon={'admin-links'}
                                    />

                                </ButtonGroup>

                                <p className="description">{wp.i18n.__("Choose the login method in the module login screen.", "integrate-google-drive")}</p>

                                <div className="igd-notice igd-notice-info loading-method-info">
                                    <div className="igd-notice-content">
                                        <p>
                                            <code>Form</code> → {wp.i18n.__("Display a login form for users to enter their credentials.", "integrate-google-drive")}
                                        </p>
                                        <p>
                                            <code>Redirect
                                                URL</code> → {wp.i18n.__("Redirect users to a specified URL for login.", "integrate-google-drive")}
                                        </p>
                                    </div>
                                </div>

                            </div>

                        </div>

                        {/* Login URL */}
                        {loginType === 'redirect' && (
                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("Login URL", "integrate-google-drive")}</h4>

                                <div className="settings-field-content">
                                    <input
                                        type="text"
                                        value={loginUrl}
                                        onChange={(e) => setData({loginUrl: e.target.value})}
                                        placeholder={igd.loginUrl || window.location.origin + `/wp-login.php`}
                                    />

                                    <p className="description">{wp.i18n.__("Enter the login URL to be used in the module loing screen.", "integrate-google-drive")}</p>
                                </div>
                            </div>
                        )}

                        {/* Login Message */}
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Login Screen Message", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">
                                <textarea
                                    value={loginMessage}
                                    onChange={(e) => {
                                        setData(data => ({
                                            ...data,
                                            loginMessage: e.target.value
                                        }))
                                    }}
                                    rows={4}
                                    id={"login-message-input"}
                                />

                                <p className="description">{wp.i18n.__("Enter the message to display when a user is prompted to log in to access this module.", "integrate-google-drive")}</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {/* Password Protected Message */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Password Protected Message", "integrate-google-drive")}</h4>

                <div className="settings-field-content">
                    <textarea
                        value={passwordProtectedMessage}
                        onChange={(e) => {
                            setData(data => ({
                                ...data,
                                passwordProtectedMessage: e.target.value
                            }))
                        }}
                        rows={4}
                        id={"password-protected-message-input"}
                    />

                    <p className="description">{wp.i18n.__("Enter the message to displayed when a password-protected module prompts the user to enter a password.", "integrate-google-drive")}</p>
                </div>
            </div>

            {/* Access Denied Message */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Access Denied Message", "integrate-google-drive")}</h4>

                <div className="settings-field-content">
                    <textarea
                        value={accessDeniedMessage}
                        onChange={(e) => {
                            setData(data => ({
                                ...data,
                                accessDeniedMessage: e.target.value
                            }))
                        }}
                        rows={4}
                        id={"access-denied-message-input"}
                    />

                    <p className={"description"}>{wp.i18n.__("Enter the message you want to show to users who don't have access to the module.", "integrate-google-drive")}</p>

                </div>
            </div>


        </div>
    )
}