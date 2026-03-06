

export default function ComputersPlaceholder() {
    return (

        <div className="igd-root-placeholder">
            <img src={`${igd.pluginUrl}/assets/images/file-browser/computers-placeholder.svg`} alt="Computers"/>

            <span
                className="igd-root-placeholder-title">{wp.i18n.__('No Computers Syncing', 'integrate-google-drive')}</span>
            <span
                className="igd-root-placeholder-text">{wp.i18n.__('The files synced with computers will display here.', 'integrate-google-drive')}</span>
        </div>
    )
}