import {exportStatistics} from "../../includes/functions";

const {useState} = React;

export default function TopSearches({data, startDate, endDate}) {
    const storedData = localStorage.getItem('igd_show_searches_data');
    const storedUsers = localStorage.getItem('igd_show_searches_users');

    const [showData, setShowData] = useState(storedData ? storedData === 'true' : true);
    const [showUsers, setShowUsers] = useState(storedUsers ? storedUsers === 'true' : true);

    const {searches = [], searchUsers} = data;

    return (
        <div className="statistics-boxes top-search">

            {/* Top Searches */}
            <div className="statistics-box">
                <div className="box-title">
                    <span>{wp.i18n.__('Top Searches', 'integrate-google-drive')}</span>

                    <i className={`dashicons dashicons-download`}
                       onClick={() => exportStatistics(startDate, endDate, 'searches')}
                    ></i>

                    <i className={`dashicons dashicons-arrow-${showData ? 'up' : 'down'}-alt2`}
                       onClick={() => {
                           localStorage.setItem('igd_show_searches_data', !showData);
                           setShowData(!showData);
                       }}
                       data-tooltip-content={wp.i18n.__(showData ? 'Hide Data' : 'Show Data')}
                    ></i>
                </div>

                <div className={`table-wrapper ${showData ? '' : 'igd-hidden'}`}>
                    {searches.length > 0 ?
                        <table className="widefat top-items striped">
                            <thead>
                            <tr>
                                <th></th>
                                <th>{wp.i18n.__('Query', 'integrate-google-drive')}</th>
                                <th>{wp.i18n.__('Total', 'integrate-google-drive')}</th>
                            </tr>
                            </thead>

                            <tbody>
                            {
                                searches.map((item, index) => {
                                    const {file_id, total} = item;

                                    return (
                                        <tr key={index}>
                                            <td>
                                                <div className="sl">
                                                    <span className="sl-no">{index + 1}.</span>
                                                </div>
                                            </td>
                                            <td>{file_id}</td>
                                            <td>{total}</td>
                                        </tr>
                                    )
                                })
                            }
                            </tbody>
                        </table>
                        : <span className="no-data">{wp.i18n.__('No data found', 'integrate-google-drive')}</span>
                    }
                </div>

            </div>

            {/* Top Users */}
            <div className="statistics-box">
                <div className="box-title">
                    <span>{wp.i18n.__('Top Users with most Searches', 'integrate-google-drive')}</span>

                    <i className={`dashicons dashicons-download`}
                       onClick={() => exportStatistics(startDate, endDate, 'search_users')}
                    ></i>

                    <i className={`dashicons dashicons-arrow-${showUsers ? 'up' : 'down'}-alt2`}
                       onClick={() => {
                           localStorage.setItem('igd_show_searches_users', !showUsers);
                           setShowUsers(!showUsers);
                       }}
                       data-tooltip-content={wp.i18n.__(showData ? 'Hide Data' : 'Show Data')}
                    ></i>
                </div>

                <div className={`table-wrapper ${showUsers ? '' : 'igd-hidden'}`}>
                    {searchUsers.length ?
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
                                searchUsers.map((item, index) => {
                                    const {user_id, name, avatar, count} = item;
                                    return (<tr key={index}>
                                            <td>
                                                <div className="sl"><span className="sl-no">{index + 1}.</span>
                                                    <span dangerouslySetInnerHTML={{__html: avatar}}></span>
                                                </div>
                                            </td>
                                            <td>
                                                {!!parseInt(user_id) ?
                                                    <a href={`${igd.adminUrl}/user-edit.php?user_id=${user_id}`}
                                                       target="_blank">{name}</a>
                                                    : name
                                                }
                                            </td>
                                            <td>{count}</td>
                                        </tr>
                                    )
                                })
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