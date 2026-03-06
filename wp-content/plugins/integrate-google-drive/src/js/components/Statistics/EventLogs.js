import {exportStatistics, formatDate} from "../../includes/functions";


const {useState, useEffect, useRef} = React;

export default function EventLogs({data, startDate, endDate}) {
    const [events, setEvents] = useState(data.events);

    const storedData = localStorage.getItem('igd_show_logs_data');
    const [showData, setShowData] = useState(storedData ? storedData === 'true' : true);

    const [page, setPage] = useState(events.length >= 30 ? 2 : 0); //initial data loaded in Statistics.js
    const [isLoading, setIsLoading] = useState(false);

    const getLogs = () => {
        setIsLoading(true);

        wp.ajax.send('igd_get_events', {
            data: {
                page: page,
                start_date: startDate,
                end_date: endDate,
                nonce: igd.nonce,
            },
            success: (response) => {
                setPage(prePage => response.complete ? 0 : prePage + 1);

                setEvents(prevEvents => [...prevEvents, ...response.events]);
            },
            error: (error) => {
                console.log(error);
                setPage(0);
            },
            complete: () => {
                setIsLoading(false);
            }
        })
    }

    const wrapperRef = useRef();

    useEffect(() => {
        if (page < 1 || isLoading) return;

        const handleScroll = () => {
            const wrapper = wrapperRef.current;

            if (wrapper.scrollTop + wrapper.clientHeight >= wrapper.scrollHeight - 1) {
                getLogs();
            }
        }

        const wrapper = wrapperRef.current;
        if (wrapper) {
            wrapper.addEventListener('scroll', handleScroll);
        }

        return () => {
            if (wrapper) {
                wrapper.removeEventListener('scroll', handleScroll);
            }
        }

    }, [page, isLoading]);

    return (
        <div
            className="statistics-box event-logs">

            <div className="box-title">
                <span>{wp.i18n.__('Event Logs', 'integrate-google-drive')}</span>

                <i className={`dashicons dashicons-download`}
                   onClick={() => exportStatistics(startDate, endDate, 'events')}
                ></i>

                <i className={`dashicons dashicons-arrow-${showData ? 'up' : 'down'}-alt2`}
                   onClick={() => {
                       localStorage.setItem('igd_show_logs_data', !showData);
                       setShowData(!showData);
                   }}
                   data-tooltip-content={wp.i18n.__(showData ? 'Hide Data' : 'Show Data')}
                ></i>
            </div>

            <div
                ref={wrapperRef}

                className={`table-wrapper ${showData ? '' : 'igd-hidden'}`}>
                {events.length > 0 ?
                    <>
                        <table className="widefat striped">
                            <thead>
                            <tr>
                                <th></th>
                                <th>
                                    <div>{wp.i18n.__('Description', 'integrate-google-drive')}</div>
                                </th>
                                <th>
                                    <div>{wp.i18n.__('Date', 'integrate-google-drive')}</div>
                                </th>
                                <th>
                                    <div>{wp.i18n.__('Module', 'integrate-google-drive')}</div>
                                </th>
                                <th>
                                    <div>{wp.i18n.__('Page', 'integrate-google-drive')}</div>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            {
                                events.map((event, index) => {
                                    const {
                                        file_id,
                                        file_name,
                                        user_id,
                                        username,
                                        type,
                                        created_at,
                                        shortcode_id,
                                        page,
                                    } = event;

                                    const icon = <img src={`${igd.pluginUrl}/assets/images/statistics/${type}.svg`}
                                                      alt={type}/>

                                    const userText = !!parseInt(user_id) ?
                                        <a href={`${igd.adminUrl}/user-edit.php?user_id=${user_id}`}
                                           target="_blank">{username}</a>
                                        : wp.i18n.__('A visitor', 'integrate-google-drive');

                                    let fileName = <a
                                        href={`https://drive.google.com/file/d/${file_id}/view?usp=drivesdk`}
                                        target="_blank">{file_name}</a>

                                    if ('search' === type) {
                                        fileName = file_id;
                                    }else if('shared' === type) {}

                                    const actionTextMap = {
                                        'download': wp.i18n.__('downloaded the file', 'integrate-google-drive'),
                                        'upload': wp.i18n.__('uploaded the file', 'integrate-google-drive'),
                                        'stream': wp.i18n.__('streamed the file', 'integrate-google-drive'),
                                        'delete': wp.i18n.__('deleted the file', 'integrate-google-drive'),
                                        'copy': wp.i18n.__('copied the file', 'integrate-google-drive'),
                                        'move': wp.i18n.__('moved the file', 'integrate-google-drive'),
                                        'rename': wp.i18n.__('renamed the file', 'integrate-google-drive'),
                                        'create': wp.i18n.__('created the file', 'integrate-google-drive'),
                                        'share': wp.i18n.__('shared the file', 'integrate-google-drive'),
                                        'description': wp.i18n.__('updated description of the file', 'integrate-google-drive'),
                                        'search': wp.i18n.__('searched for', 'integrate-google-drive'),
                                        'folder': wp.i18n.__('created a new folder', 'integrate-google-drive'),
                                        'shared': wp.i18n.__('changed sharing permissions to public for the file ', 'integrate-google-drive'),
                                    };

                                    const actionText = actionTextMap[type] || wp.i18n.__('previewed the file', 'integrate-google-drive');

                                    let text = <span>{userText} {actionText} <strong>{fileName}</strong></span>;

                                    return (
                                        <tr key={index}>
                                            <td>
                                                <div className="sl"><span className="sl-no">{index + 1}.</span> {icon}
                                                </div>
                                            </td>
                                            <td>{text}</td>
                                            <td className="col-date">{formatDate(created_at, true)}</td>
                                            <td className={`col-shortcode`}>
                                                {!!parseInt(shortcode_id) &&
                                                    <a target={`_blank`}
                                                       href={`${igd.adminUrl}/admin.php?page=integrate-google-drive-shortcode-builder&id=${shortcode_id}`}>
                                                        <i className={`dashicons dashicons-external`}></i>
                                                        {shortcode_id}
                                                    </a>
                                                }
                                            </td>
                                            <td className={`col-page`}>
                                                {page &&
                                                    <a href={page} target="_blank">
                                                        <i className={`dashicons dashicons-external`}></i>
                                                        {page.replace(igd.siteUrl, '')}
                                                    </a>
                                                }
                                            </td>
                                        </tr>
                                    )
                                })
                            }

                            </tbody>
                        </table>

                        {isLoading && <div className="igd-spinner spinner-large"></div>}

                    </>
                    : <div className="no-data">{wp.i18n.__('No data found!', 'integrate-google-drive')}</div>
                }
            </div>


        </div>
    )
}