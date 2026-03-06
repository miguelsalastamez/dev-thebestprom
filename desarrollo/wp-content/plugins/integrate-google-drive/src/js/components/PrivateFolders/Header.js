

export default function Header() {
    return (
        <>
            <div className="igd-private-folders-header">

                <div className="header-title">
                    <img src={igd.pluginUrl + '/assets/images/private-folders-icon.svg'} alt="Private Folders"/>

                    <div className="header-title-text">
                        <h3>{wp.i18n.__('User Private Files', 'integrate-google-drive')}</h3>
                        <span>{wp.i18n.__('Link specific files & folders to the users', 'integrate-google-drive')}</span>
                    </div>

                </div>

                <a target="_blank" href="https://softlabbd.com/docs/how-to-use-and-enable-private-folders-automatically-link-manually/" className={`igd-btn btn-outline-info`}>
                    <i className="dashicons dashicons-welcome-learn-more"></i>
                    <span>{wp.i18n.__('Documentation', 'integrate-google-drive')}</span>
                </a>

                {!igd.isPro &&
                    <a href={`https://softlabbd.com/integrate-google-drive-pricing`} className={`igd-btn btn-outline-primary`} target={"_blank"}>
                        <i className="dashicons dashicons-unlock"></i>
                        <span>{wp.i18n.__('Unlock PRO', 'integrate-google-drive')}</span>
                    </a>
                }
            </div>
        </>
    )
}