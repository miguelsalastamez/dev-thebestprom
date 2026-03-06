

export default function SharedDrivesPlaceholder() {
    return (
        <div className="igd-root-placeholder">
            <img src={`${igd.pluginUrl}/assets/images/file-browser/shared-drives-placeholder.svg`} alt="Shared Drives"/>

            <span className="igd-root-placeholder-title">{wp.i18n.__('No Shared Drives', 'integrate-google-drive')}</span>
            <span
                className="igd-root-placeholder-text">{wp.i18n.__('The Drives others have shared with you.', 'integrate-google-drive')}</span>
        </div>

    )
}