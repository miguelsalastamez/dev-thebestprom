import {timeAgo, getTypeIcon, exportStatistics} from "../../includes/functions";


const {useState} = React;

export default function TopUploads({data, startDate, endDate}) {

    const {uploads, uploadUsers} = data;

    const [showData, setShowData] = useState(localStorage.getItem('igd_show_uploads_data') ? localStorage.getItem('igd_show_uploads_data') === 'true' : true);
    const [showUsers, setShowUsers] = useState(localStorage.getItem('igd_show_uploads_users') ? localStorage.getItem('igd_show_uploads_users') === 'true' : true);

    return (
        <div className="statistics-boxes top-upload">

            {/* Top Uploads */}
            <div className="statistics-box">

                <div className="box-title">
                    <span>{wp.i18n.__('Top Uploads', 'integrate-google-drive')}</span>

                    <i className={`dashicons dashicons-download`}
                       onClick={() => exportStatistics(startDate, endDate, 'uploads')}
                    ></i>

                    <i className={`dashicons dashicons-arrow-${showData ? 'up' : 'down'}-alt2`}
                       onClick={() => {
                           localStorage.setItem('igd_show_uploads_data', !showData);
                           setShowData(!showData);
                       }}
                       data-tooltip-content={wp.i18n.__(showData ? 'Hide Data' : 'Show Data')}
                    ></i>
                </div>

                <div className={`table-wrapper ${showData ? '' : 'igd-hidden'}`}>
                    {uploads.length > 0 ?
                        <table className="widefat top-items striped">
                            <thead>
                            <tr>
                                <th></th>
                                <th>{wp.i18n.__('File', 'integrate-google-drive')}</th>
                                <th>{wp.i18n.__('Date', 'integrate-google-drive')}</th>
                            </tr>
                            </thead>

                            <tbody>
                            {
                                uploads.map((item, index) => (
                                    <tr key={index}>
                                        <td>
                                            <div className="sl"><span className="sl-no">{index + 1}.</span>
                                                <img width={24} src={getTypeIcon(item.file_type)}/>
                                            </div>
                                        </td>
                                        <td width="55%">
                                            <a href={`https://drive.google.com/file/d/${item.file_id}/view?usp=drivesdk`}
                                               target="_blank">{item.file_name}</a>
                                        </td>
                                        <td className="col-date">{timeAgo(item.created_at)}</td>
                                    </tr>
                                ))
                            }
                            </tbody>
                        </table>
                        : <span className="no-data">{wp.i18n.__('No data found!', 'integrate-google-drive')}</span>
                    }
                </div>

            </div>

            {/* Top Users */}
            <div className="statistics-box">
                <span className="box-title">

                    <span>{wp.i18n.__('Top Users with most Uploads', 'integrate-google-drive')}</span>

                    <i className={`dashicons dashicons-download`}
                       onClick={() => exportStatistics(startDate, endDate, 'upload_users')}
                    ></i>

                    <i className={`dashicons dashicons-arrow-${showUsers ? 'up' : 'down'}-alt2`}
                       onClick={() => {
                           localStorage.setItem('igd_show_uploads_users', !showUsers);
                           setShowUsers(!showUsers);
                       }}
                       data-tooltip-content={wp.i18n.__(showUsers ? 'Hide Data' : 'Show Data')}
                    ></i>

                </span>
                <div className={`table-wrapper ${showUsers ? '' : 'igd-hidden'}`}>
                    {uploadUsers.length > 0 ?
                        <table className="widefat top-users striped">
                            <thead>
                            <tr>
                                <th></th>
                                <th>{wp.i18n.__('User', 'integrate-google-drive')}</th>
                                <th>{wp.i18n.__('Uploads', 'integrate-google-drive')}</th>
                            </tr>
                            </thead>

                            <tbody>
                            {
                                uploadUsers.map((item, index) => (
                                    <tr key={index}>
                                        <td>
                                            <div className="sl"><span className="sl-no">{index + 1}.</span>
                                                <span dangerouslySetInnerHTML={{__html: item.avatar}}></span>
                                            </div>
                                        </td>
                                        <td>
                                            {!!parseInt(item.user_id) ?
                                                <a href={`${igd.adminUrl}/user-edit.php?user_id=${item.user_id}`}
                                                   target="_blank">{item.name}</a>
                                                : item.name
                                            }
                                        </td>
                                        <td>{item.count}</td>
                                    </tr>
                                ))
                            }
                            </tbody>
                        </table>
                        : <span className="no-data">{wp.i18n.__('No data found!', 'integrate-google-drive')}</span>
                    }
                </div>

            </div>
        </div>
    )
}