export default function ({setOpenTypeModal}) {

    return (
        <div className="no-item-placeholder">
            <img src={`${igd.pluginUrl}/assets/images/shortcode-builder/placeholder.png`} alt="No Shortcode"/>

            <h3>{wp.i18n.__("You didn't create any module yet!", 'integrate-google-drive')}</h3>

            <button type={`button`} className={`igd-btn btn-primary`} onClick={() => setOpenTypeModal('new')}>
                <i className="dashicons dashicons-plus"></i>
                <span>{wp.i18n.__("Create new module", 'integrate-google-drive')}</span>
            </button>

        </div>
    )
}