import {openAddAccountWindow} from "../../../includes/functions";

export default function AddAccountPlaceholder() {

    return (
        <div className="no-account-placeholder">
            <img src={`${igd.pluginUrl}/assets/images/file-browser/no-account-placeholder.svg`} alt="No Accounts"/>
            <span
                className="placeholder-heading">{wp.i18n.__("You didn't link any account yet.", 'integrate-google-drive')}</span>
            <span
                className="placeholder-description">{wp.i18n.__("Please link to a Google Drive account to continue.", 'integrate-google-drive')}</span>

            <button
                className="igd-btn add-account-btn"
                onClick={() => {
                    if (!!igd.authUrl) {
                        openAddAccountWindow();
                    } else {
                        window.location = igd.adminUrl + '/admin.php?page=integrate-google-drive-settings&tab=accounts'
                    }
                }}
            >
                <img src={`${igd.pluginUrl}/assets/images/google-icon.png`}/>
                <span>{wp.i18n.__("Sign in with Google", 'integrate-google-drive')}</span>
            </button>
        </div>
    )
}