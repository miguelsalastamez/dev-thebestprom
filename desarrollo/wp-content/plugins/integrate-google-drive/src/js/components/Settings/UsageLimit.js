import {Tooltip} from "react-tooltip";
import ReactSelect from "react-select";
import NamingTemplate from "../ShortcodeBuilder/Form/Fields/NamingTemplate";
import {showProModal} from "../../includes/ProModal";

const {useState, useEffect} = React;

const {
    FormToggle,
    ButtonGroup,
    Button,
} = wp.components;

export default function UsageLimit({data, setData}) {

    const {isPro} = igd;

    const restrictionPeriods = [
        {
            key: 'day',
            text: wp.i18n.__('Per Day', "integrate-google-drive"),
            label: wp.i18n.__('Restrict Downloads on a Daily Basis', "integrate-google-drive"),
            icon: 'schedule',
        },
        {
            key: 'week',
            text: wp.i18n.__('Per Week', "integrate-google-drive"),
            label: wp.i18n.__('Restrict Downloads on a Weekly Basis', "integrate-google-drive"),
            icon: 'schedule',
        },
        {
            key: 'month',
            text: wp.i18n.__('Per Month', "integrate-google-drive"),
            label: wp.i18n.__('Restrict Downloads on a Monthly Basis', "integrate-google-drive"),
            icon: 'schedule',
        },
    ];

    const {
        restrictionPeriod = 'day',
        enableDownloadLimits,
        downloadLimits,
        downloadsPerFile,
        zipDownloadLimits,
        bandwidthLimits,
        limitExcludedUsers = ['administrator'],
        limitExcludeAllUsers = [],
        limitExcludedExceptUsers = [],
        blockUntraceableUsers,
        limitsEmailNotification,
        limitsNotificationEmail = '%admin_email%',
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

    const [isLoading, setIsLoading] = useState(false);

    return (
        <div className="igd-settings-body">

            <h3 className="igd-settings-body-title">
                {wp.i18n.__('Usage Limit Settings', 'integrate-google-drive')}

                <a href="https://softlabbd.com/docs/how-to-configure-usage-limit-settings"
                   target="_blank" className="igd-btn btn-outline-info">
                    <i className="dashicons dashicons-editor-help"></i>

                    {wp.i18n.__('Documentation', 'integrate-google-drive')}
                </a>
            </h3>

            {/* Enable Usage Limits */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Enable Usage Limits", 'integrate-google-drive')}</h4>

                <div className="settings-field-content">
                    <FormToggle
                        data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                        data-tooltip-id="igd-pro-tooltip"
                        checked={isPro && enableDownloadLimits}
                        className={!isPro ? 'disabled' : ''}
                        onChange={() => {
                            if (!isPro) {
                                showProModal(wp.i18n.__(`Upgrade to Pro to enable the usage limits.`, 'integrate-google-drive'));
                                return;
                            }

                            setData({...data, enableDownloadLimits: !enableDownloadLimits});
                        }}
                    />

                    {!isPro &&
                        <Tooltip
                            id="igd-pro-tooltip"
                            effect="solid"
                            place="right"
                            type="light"
                            className="igd-tooltip"
                            variant={'warning'}
                        />
                    }

                    <p className="description">{wp.i18n.__("Enable usage limits for users to control the download access globally.", 'integrate-google-drive')}</p>

                </div>
            </div>

            {(!!enableDownloadLimits || !isPro) && (
                <>

                    {/* Restrictions Period */}
                    <div className="settings-field">

                        <h4 className="settings-field-label">{wp.i18n.__("Restrictions Period", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <ButtonGroup
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                data-tooltip-id={'igd-pro-tooltip'}
                            >
                                {restrictionPeriods.map(({key, text, label, icon}) => (
                                    <Button
                                        key={key}
                                        variant={key === restrictionPeriod ? 'primary' : 'secondary'}
                                        onClick={() => {
                                            if (!isPro) {
                                                showProModal(wp.i18n.__('Upgrade to PRO to enable the usage limits.', 'integrate-google-drive'));
                                                return;
                                            }
                                            setData({...data, restrictionPeriod: key});
                                        }}
                                        text={text}
                                        label={label}
                                    />
                                ))}
                            </ButtonGroup>

                            <p className="description">{wp.i18n.__("Select the period for which the download limits will be applied. This will restrict the number of downloads, bandwidth, and other limits based on the selected period.", "integrate-google-drive")}</p>

                        </div>
                    </div>

                    {/* Download Limits */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Download Limits", 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">
                            <input
                                data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                data-tooltip-id="igd-pro-tooltip"
                                type="number" className="regular-text" value={downloadLimits}
                                onChange={(e) => setData({...data, downloadLimits: e.target.value})}
                                disabled={!isPro}
                            />

                            <p className="description">{wp.i18n.__("Set the maximum number of files a user can download within the selected period. Keep blank for unlimited.", 'integrate-google-drive')}</p>
                        </div>
                    </div>

                    {/* Download Limits/per File */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Download Limits per File", 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">
                            <input
                                data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                data-tooltip-id="igd-pro-tooltip"
                                type="number"
                                className="regular-text"
                                value={downloadsPerFile}
                                onChange={(e) => setData({...data, downloadsPerFile: e.target.value})}
                                disabled={!isPro}
                            />

                            <p className="description">{wp.i18n.__("Set the maximum number of times the same file can be downloaded by a user within the selected period. Keep blank for unlimited.", 'integrate-google-drive')}</p>
                        </div>
                    </div>

                    {/* ZIP Download Limits */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("ZIP Download Limits", 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">
                            <input
                                data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                data-tooltip-id="igd-pro-tooltip"
                                type="number"
                                className="regular-text"
                                value={zipDownloadLimits}
                                onChange={(e) => setData({...data, zipDownloadLimits: e.target.value})}
                                disabled={!isPro}
                            />

                            <p className="description">{wp.i18n.__("Set the number of ZIP files a user can download within the selected period. Leave blank for unlimited.", 'integrate-google-drive')}</p>
                        </div>
                    </div>

                    {/* Bandwidth Limit/Day */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Bandwidth Limits (in MB)", 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">
                            <input
                                data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                data-tooltip-id="igd-pro-tooltip"
                                type="number"
                                className="regular-text"
                                value={bandwidthLimits}
                                onChange={(e) => setData({...data, bandwidthLimits: e.target.value})}
                                disabled={!isPro}
                            />

                            <p className="description">{wp.i18n.__("Set the maximum bandwidth (in MB) allowed per user during the selected period. Leave blank for unlimited.", 'integrate-google-drive')}</p>
                        </div>
                    </div>

                    {/* Block untraceable users */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Block Untraceable Users", 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                data-tooltip-id="igd-pro-tooltip"
                                checked={isPro && blockUntraceableUsers}
                                className={!isPro ? 'disabled' : ''}
                                onChange={() => {

                                    if (!isPro) {
                                        showProModal(wp.i18n.__(`Upgrade to Pro to enable the usage limits.`, 'integrate-google-drive'));
                                        return;
                                    }

                                    setData({...data, blockUntraceableUsers: !blockUntraceableUsers})
                                }}
                            />

                            <p className="description">{wp.i18n.__("Block users who are not traceable by the system. This will prevent users from downloading files without being logged in or without enabling cookies.", 'integrate-google-drive')}</p>

                        </div>
                    </div>

                    {/* Filter Roles & Users */}
                    <div className="settings-field filter-users-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Exclude Roles & Users", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">
                            {!!userData ?
                                <div className="filter-users">

                                    <div className="filter-users-section-wrap">
                                        <ReactSelect
                                            isDisabled={!isPro || limitExcludeAllUsers}
                                            isMulti
                                            placeholder={"Select users & roles"}
                                            options={usersOptions}
                                            value={usersOptions.filter(item => limitExcludedUsers.includes(item.value))}
                                            onChange={selected => {

                                                if (!isPro) {
                                                    showProModal(wp.i18n.__(`Upgrade to Pro to enable the usage limits.`, 'integrate-google-drive'));
                                                    return;
                                                }

                                                setData({
                                                    ...data,
                                                    limitExcludedUsers: [...selected.map(item => item.value)]
                                                })
                                            }}
                                            className="igd-select"
                                            classNamePrefix="igd-select"
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

                                        <p className="description">{wp.i18n.__("Select the roles and users who will be excluded from the download restrictions.", "integrate-google-drive")}</p>
                                    </div>

                                    <div className="filter-users-section-wrap">
                                        <div className="filter-users-section">
                                            <span
                                                className="filter-users-section-label">{wp.i18n.__("Exclude All :", "integrate-google-drive")} </span>
                                            <FormToggle
                                                checked={isPro && limitExcludeAllUsers}
                                                disabled={!isPro}
                                                onChange={() => {

                                                    if (!isPro) {
                                                        showProModal(wp.i18n.__(`Upgrade to Pro to enable the usage limits.`, 'integrate-google-drive'));
                                                        return;
                                                    }

                                                    setData({
                                                        ...data,
                                                        limitExcludeAllUsers: !limitExcludeAllUsers
                                                    })
                                                }}
                                            />
                                        </div>

                                        <div className="filter-users-section">
                                            <span
                                                className="filter-users-section-label">{wp.i18n.__("Except : ", "integrate-google-drive")}</span>
                                            <ReactSelect
                                                isDisabled={!isPro || !limitExcludeAllUsers}
                                                isMulti
                                                placeholder={"Select users & roles"}
                                                options={usersOptions.filter(item => item.value !== 'everyone')}
                                                value={usersOptions.filter(item => limitExcludedExceptUsers.includes(item.value))}
                                                onChange={selected => {

                                                    if (!isPro) {
                                                        showProModal(wp.i18n.__(`Upgrade to Pro to enable the usage limits.`, 'integrate-google-drive'));
                                                        return;
                                                    }

                                                    setData({
                                                        ...data,
                                                        limitExcludedExceptUsers: [...selected.map(item => item.value)]
                                                    })
                                                }}
                                                className="igd-select"
                                                classNamePrefix="igd-select"
                                            />
                                        </div>

                                        <p className="description">{wp.i18n.__("When activated, the download restrictions will be only applied to the selected roles and users.", "integrate-google-drive")}</p>
                                    </div>

                                </div>
                                :
                                <div className="igd-spinner spinner-large"></div>
                            }
                        </div>
                    </div>

                    {/* Email Notification */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Email Notification", 'integrate-google-drive')}</h4>

                        <div className="settings-field-content">
                            <FormToggle
                                data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                data-tooltip-id="igd-pro-tooltip"
                                checked={isPro && limitsEmailNotification}
                                className={!isPro ? 'disabled' : ''}
                                onChange={() => {

                                    if (!isPro) {
                                        showProModal(wp.i18n.__(`Upgrade to Pro to enable the usage limits.`, 'integrate-google-drive'));
                                        return;
                                    }

                                    setData({...data, limitsEmailNotification: !limitsEmailNotification})
                                }}
                            />

                            <p className="description">{wp.i18n.__("Receive email notifications when users reach their download limits.", 'integrate-google-drive')}</p>

                            {/* Notification Recipients */}
                            {(!isPro || !!limitsEmailNotification) &&
                                <div className="settings-field-sub">

                                    {/*--- Notification Email Recipients ---*/}
                                    <NamingTemplate
                                        value={limitsNotificationEmail}
                                        onUpdate={(limitsNotificationEmail) => setData(data => ({
                                            ...data,
                                            limitsNotificationEmail
                                        }))}
                                        type={'notifications'}
                                    />

                                </div>
                            }

                        </div>
                    </div>

                    {/*  Reset Usage Limits */}
                    <div className="settings-field">
                        <h4 className={`settings-field-label`}>{wp.i18n.__("Reset Usage Limits", "integrate-google-drive")}</h4>
                        <div className="settings-field-content">
                            <button
                                data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                                data-tooltip-id="igd-pro-tooltip"
                                type={`button`} className={`igd-btn btn-danger`} onClick={() => {

                                if (!isPro) {
                                    showProModal(wp.i18n.__(`Upgrade to Pro to enable the usage limits.`, 'integrate-google-drive'));
                                    return;
                                }

                                setIsLoading(true);

                                wp.ajax.post('igd_reset_usage_limits')
                                    .done(() => {
                                        Swal.fire({
                                            title: false,
                                            text: wp.i18n.__('Usage limits have been reset successfully.', 'integrate-google-drive'),
                                            icon: 'success',
                                            toast: true,
                                            timer: 3000,
                                            timerProgressBar: true,
                                            showConfirmButton: false,
                                            customClass: {container: 'igd-swal save-settings-toast'},
                                        });
                                    })
                                    .fail((error) => console.log(error))
                                    .always(() => setIsLoading(false));
                            }}>
                                {!!isLoading ? <div className="igd-spinner"></div> :
                                    <i className={`dashicons dashicons-update-alt`}></i>}
                                <span>{wp.i18n.__("Reset", "integrate-google-drive")}</span>
                            </button>

                            <p className="description">{wp.i18n.__("Reset the current usage limits for all users.", "integrate-google-drive")}</p>
                        </div>
                    </div>

                </>
            )}

        </div>
    )
}