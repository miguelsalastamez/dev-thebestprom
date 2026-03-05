import {Tooltip} from "react-tooltip";
import {showProModal} from "../../includes/ProModal";

const {FormToggle, SelectControl} = wp.components;

export default function Performance({data, setData}) {

    const {isPro} = igd;

    const {
        serverThrottle,
        loadScriptsOnAllPages,
    } = data;

    return (
        <div className="igd-settings-body">

            <h3 className="igd-settings-body-title">{wp.i18n.__('Performance Settings', 'integrate-google-drive')}</h3>

            {/* Load Scripts on All Pages */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Load Scripts on All Pages", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <FormToggle
                        checked={loadScriptsOnAllPages}
                        onChange={() => setData({...data, loadScriptsOnAllPages: !loadScriptsOnAllPages})}
                    />

                    <p className="description">{wp.i18n.__('The plugin loads scripts only when a shortcode is detected on the page. Enable this if you\'re loading content via AJAX and the plugin doesn\'t display correctly.', 'integrate-google-drive')}</p>
                </div>
            </div>

            {/* Server Throttle */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__("Server Throttle", "integrate-google-drive")}</h4>
                <div className="settings-field-content">
                    <SelectControl
                        data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                        data-tooltip-id={"igd-pro-tooltip"}
                        className={!isPro ? 'disabled' : ''}
                        value={serverThrottle}
                        options={[
                            {label: wp.i18n.__("Disabled", "integrate-google-drive"), value: "off"},
                            {label: wp.i18n.__("Low", "integrate-google-drive"), value: "low"},
                            {label: wp.i18n.__("Medium", "integrate-google-drive"), value: "medium"},
                            {label: wp.i18n.__("High", "integrate-google-drive"), value: "high"},
                        ]}
                        onChange={(value) => {
                            if (!isPro) {
                                showProModal(wp.i18n.__('Upgrade to PRO to enable server throttle.', 'integrate-google-drive'));
                                return;
                            }

                            setData({...data, serverThrottle: value});
                        }}
                    />

                    {!isPro &&
                        <Tooltip
                            id={"igd-pro-tooltip"}
                            effect={"solid"}
                            place={"right"}
                            variant={"warning"}
                            className={"igd-tooltip"}
                        />
                    }

                    <p className="description">{wp.i18n.__("Enable server throttle to avoid resource issues on budget hosts. Increasing this value will slow down the downloads and media streaming.", "integrate-google-drive")}</p>
                </div>
            </div>

        </div>
    )
}