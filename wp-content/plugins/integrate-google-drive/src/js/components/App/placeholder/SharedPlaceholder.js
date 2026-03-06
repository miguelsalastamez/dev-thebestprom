

export default function SharedPlaceholder() {
    return (
        <div className="igd-root-placeholder">
            <img src={`${igd.pluginUrl}/assets/images/file-browser/shared-placeholder.svg`} alt="Shared Files"/>

            <span className="igd-root-placeholder-title">{wp.i18n.__('Shared With Me', 'integrate-google-drive')}</span>
            <span
                className="igd-root-placeholder-text">{wp.i18n.__('Files and folders others have shared with you.', 'integrate-google-drive')}</span>
        </div>

    )
}