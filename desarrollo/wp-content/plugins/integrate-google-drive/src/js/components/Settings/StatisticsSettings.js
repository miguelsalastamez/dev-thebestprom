import {showProModal} from "../../includes/ProModal";
import {Tooltip} from "react-tooltip";

const {
    FormToggle,
    SelectControl,
    TextControl,
} = wp.components;

export default function StatisticsSettings({data, setData}) {
    const isPro = igd.isPro;
    const {
        enableStatistics = false,
        emailReport = false,
        emailReportFrequency = "weekly",
        emailReportRecipients,
    } = data;

    return (
        <div className="igd-settings-body">

            <span className="igd-settings-body-title">
                {wp.i18n.__('Statistics Settings', 'integrate-google-drive')}

                <a href="https://softlabbd.com/docs/understanding-the-integrate-google-drive-statistics-properly/"
                   target="_blank" className="igd-btn btn-outline-info">
                    <i className="dashicons dashicons-editor-help"></i>

                    {wp.i18n.__('Documentation', 'integrate-google-drive')}
                </a>
            </span>

            {/* Enable Statistics */}
            <div className="settings-field">
                <span
                    className="settings-field-label">{wp.i18n.__("Enable Statistics", "integrate-google-drive")}</span>
                <div className="settings-field-content">
                    <FormToggle
                        data-tooltip-content={wp.i18n.__('PRO Feature', 'integrate-google-drive')}
                        data-tooltip-id={`enable-statistics-tooltip`}
                        checked={isPro && enableStatistics}
                        className={!isPro ? 'disabled' : ''}
                        onChange={() => {
                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to enable the statistics.', 'integrate-google-drive'));
                                return;
                            }

                            setData({...data, enableStatistics: !enableStatistics})
                        }}
                    />

                    {!isPro &&
                        <Tooltip
                            id={`enable-statistics-tooltip`}
                            effect="solid"
                            place="right"
                            variant={"warning"}
                            className={"igd-tooltip"}
                        />
                    }

                    <span
                        className="description">{wp.i18n.__("Enable/ disable the statistics logs.", "integrate-google-drive")}</span>
                </div>
            </div>

            {/* Email Reporting */}
            {(enableStatistics || !isPro) &&
                <div className="settings-field">
                    <h4 className="settings-field-label">{wp.i18n.__("Email Reporting", "integrate-google-drive")}</h4>
                    <div className="settings-field-content">
                        <FormToggle
                            data-tooltip-content={wp.i18n.__('PRO Feature', 'integrate-google-drive')}
                            data-tooltip-id={`email-report-tooltip`}
                            checked={isPro && emailReport}
                            className={!isPro ? 'disabled' : ''}
                            onChange={() => {
                                if (!isPro) {
                                    showProModal(wp.i18n.__('Upgrade to PRO to enable the statistics.', 'integrate-google-drive'));
                                    return;
                                }

                                setData({...data, emailReport: !emailReport})
                            }}
                        />

                        {!isPro &&
                            <Tooltip
                                id={`email-report-tooltip`}
                                effect="solid"
                                place="right"
                                variant={"warning"}
                                className={"igd-tooltip"}
                            />
                        }

                        <p className="description">{wp.i18n.__("Enable / disable the statistics email reporting.", "integrate-google-drive")}</p>
                    </div>
                </div>
            }

            {(enableStatistics && emailReport && isPro) &&
                <>
                    {/* Email Report Frequency */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Email Report Frequency", "integrate-google-drive")}</h4>
                        <div className="settings-field-content">
                            <SelectControl
                                value={emailReportFrequency}
                                options={[
                                    {label: wp.i18n.__("Daily", "integrate-google-drive"), value: "daily"},
                                    {label: wp.i18n.__("Weekly", "integrate-google-drive"), value: "weekly"},
                                    {label: wp.i18n.__("Monthly", "integrate-google-drive"), value: "monthly"},
                                ]}
                                onChange={(value) => {
                                    if (!isPro) {
                                        showProModal(wp.i18n.__('Upgrade to PRO to enable the statistics.', 'integrate-google-drive'));
                                        return;
                                    }

                                    setData({...data, emailReportFrequency: value})
                                }}
                            />


                            <p className="description">{wp.i18n.__("Select the email report frequency.", "integrate-google-drive")}</p>

                        </div>
                    </div>

                    {/* Email Report Recipients */}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Email Report Recipients", "integrate-google-drive")}</h4>
                        <div className="settings-field-content">
                            <TextControl
                                value={emailReportRecipients}
                                onChange={(value) => {
                                    if (!isPro) {
                                        showProModal(wp.i18n.__('Upgrade to PRO to enable the statistics.', 'integrate-google-drive'));
                                        return;
                                    }

                                    setData({...data, emailReportRecipients: value})
                                }
                                }
                            />

                            <p className="description">{wp.i18n.__("Enter the recipients email addresses separated by comma.", "integrate-google-drive")}</p>

                        </div>
                    </div>
                </>
            }

        </div>
    )
}