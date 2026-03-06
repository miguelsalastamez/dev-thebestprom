
export default function StarredPlaceholder() {

    return (
        <div className="igd-root-placeholder">
            <img src={`${igd.pluginUrl}/assets/images/file-browser/starred-placeholder.svg`} alt="Starred Files"/>

            <span
                className="igd-root-placeholder-title">{wp.i18n.__('Nothing is starred', 'integrate-google-drive')}</span>
            <span
                className="igd-root-placeholder-text">{wp.i18n.__('Adds star to files and folders that you want to find easily later.', 'integrate-google-drive')}</span>
        </div>
    )
}