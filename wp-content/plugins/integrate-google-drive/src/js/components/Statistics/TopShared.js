import  {exportStatistics, getTypeIcon} from "../../includes/functions";
import {Tooltip} from "react-tooltip";

const {useState} = React;

export default function TopShared({data, startDate, endDate}) {
    const [showData, setShowData] = useState(localStorage.getItem('igd_show_shared_data') ? localStorage.getItem('igd_show_shared_data') === 'true' : true);
    const [showUsers, setShowUsers] = useState(localStorage.getItem('igd_show_shared_users') ? localStorage.getItem('igd_show_shared_users') === 'true' : true);

    const {shared, sharedUsers} = data;

    return (
        <div className="statistics-boxes top-shared">

            {/* Top Shared */}
            <div className="statistics-box">
                <span className="box-title">

                    <span>{wp.i18n.__('Top Shared', 'integrate-google-drive')}</span>

                    <i className={`dashicons dashicons-download`}
                       onClick={() => exportStatistics(startDate, endDate, 'shared')}
                    ></i>

                    <Tooltip
                        anchorSelect={'.dashicons-download'}
                        place="top"
                        content={'Export'}
                    />

                    <i className={`dashicons dashicons-arrow-${showData ? 'up' : 'down'}-alt2`}
                       onClick={() => {
                           localStorage.setItem('igd_show_shared_data', !showData);
                           setShowData(!showData);
                       }}
                       data-tooltip-content={wp.i18n.__(showData ? 'Hide Data' : 'Show Data')}
                    ></i>

                    <Tooltip
                        anchorSelect={'.dashicons-arrow-up-alt2, .dashicons-arrow-down-alt2'}
                        place="top"
                    />

                </span>

                <div className={`table-wrapper ${showData ? '' : 'igd-hidden'}`}>
                    {shared.length > 0 ?
                        <table className="widefat top-items striped">
                            <thead>
                            <tr>
                                <th></th>
                                <th>{wp.i18n.__('File', 'integrate-google-drive')}</th>
                                <th>{wp.i18n.__('Total', 'integrate-google-drive')}</th>
                            </tr>
                            </thead>

                            <tbody>
                            {
                                shared.map((item, index) => (
                                    <tr key={index}>
                                        <td>
                                            <div className="sl"><span className="sl-no">{index + 1}.</span>
                                                <img width={24} src={getTypeIcon(item.file_type)}/>
                                            </div>
                                        </td>
                                        <td><a
                                            href={`https://drive.google.com/file/d/${item.file_id}/view?usp=drivesdk`}
                                            target="_blank">{item.file_name}</a></td>
                                        <td>{item.total}</td>
                                    </tr>
                                ))
                            }
                            </tbody>
                        </table>
                        : <span className="no-data">{wp.i18n.__('No data found', 'integrate-google-drive')}</span>
                    }
                </div>

            </div>

            {/* Top Users */}
            <div className="statistics-box">
                <span className="box-title">

                    <span>{wp.i18n.__('Top Users with most Shared', 'integrate-google-drive')}</span>

                    <i className={`dashicons dashicons-download`}
                       onClick={() => exportStatistics(startDate, endDate, 'download_users')}
                    ></i>

                    <i className={`dashicons dashicons-arrow-${showUsers ? 'up' : 'down'}-alt2`}
                       onClick={() => {
                           localStorage.setItem('igd_show_shared_users', !showUsers);
                           setShowUsers(!showUsers);
                       }}
                       data-tooltip-content={wp.i18n.__(showUsers ? 'Hide Data' : 'Show Data')}
                    ></i>
                </span>

                <div className={`table-wrapper ${showUsers ? '' : 'igd-hidden'}`}>
                    {sharedUsers.length ?
                        <table className="widefat top-users striped">
                            <thead>
                            <tr>
                                <th></th>
                                <th>{wp.i18n.__('User', 'integrate-google-drive')}</th>
                                <th>{wp.i18n.__('Total', 'integrate-google-drive')}</th>
                            </tr>
                            </thead>

                            <tbody>
                            {
                                sharedUsers.map((item, index) => (
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
                        : <span className="no-data">{wp.i18n.__('No data found', 'integrate-google-drive')}</span>
                    }
                </div>

            </div>

        </div>
    )
}