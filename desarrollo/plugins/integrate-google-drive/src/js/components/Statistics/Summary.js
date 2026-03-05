export default function Summary({data}) {
    const totalDownloads = data['downloads'].reduce((acc, curr) => acc + parseInt(curr.total), 0);
    const totalUploads = data['uploads'].reduce((acc, curr) => acc + parseInt(curr.total), 0);
    const totalPreviews = data['previews'].reduce((acc, curr) => acc + parseInt(curr.total), 0);
    const totalStreams = data['streams'].reduce((acc, curr) => acc + parseInt(curr.total), 0);
    const totalShared = data['shared'].reduce((acc, curr) => acc + parseInt(curr.total), 0);

    const items = [
        {
            title: wp.i18n.__('Total Downloads', 'inegrate-google-drive'),
            value: totalDownloads,
            key: 'download',
            'color': '#4CAF50',
        },
        {
            title: wp.i18n.__('Total Uploads', 'inegrate-google-drive'),
            value: totalUploads,
            key: 'upload',
            'color': '#2196F3',
        },
        {
            title: wp.i18n.__('Total Previews', 'inegrate-google-drive'),
            value: totalPreviews,
            key: 'preview',
            'color': '#0DAFFD',
        },
        {
            title: wp.i18n.__('Total Streams', 'inegrate-google-drive'),
            value: totalStreams,
            key: 'stream',
            'color': '#7DA2F6',
        },
        {
            title: wp.i18n.__('Total Shared', 'inegrate-google-drive'),
            value: totalShared,
            key: 'shared',
            'color': '#BE6EFD',
        }
    ];

    return (
        <div className="statistics-summary">

            {items.map(item => {
                const {title, value, key} = item;
                return (
                    <div
                        key={key}
                        className={`statistics-summary-item summary-${key}`}
                        style={{'--item-color': item.color}}
                    >
                        <img src={`${igd.pluginUrl}/assets/images/statistics/${key}.svg`} alt={title}
                             className={`statistics-summary-item-icon`}/>
                        <div className="summary-info">
                            <span className="statistics-summary-item-count">{value}</span>
                            <span className="statistics-summary-item-title">{title}</span>
                        </div>
                    </div>
                )
            })}
        </div>
    )
}