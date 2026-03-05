import Summary from "./Summary";
import EventsChart from "./EventsChart";
import TopDownloads from "./TopDownloads";
import TopUploads from "./TopUploads";
import TopStreams from "./TopStreams";
import EventLogs from "./EventLogs";
import Header from "./Header";
import TopPreviews from "./TopPreviews";
import TopSearches from "./TopSearches";

import {formatDate, formatDateForSQL} from "../../includes/functions";
import TopShared from "./TopShared";

const {useState, useEffect} = React;

export default function Statistics() {

    const [loading, setLoading] = useState(false);

    const lastMonthDate = new Date();
    lastMonthDate.setMonth(lastMonthDate.getMonth() - 1);

    const [startDate, setStartDate] = useState(formatDate(lastMonthDate));
    const [endDate, setEndDate] = useState(formatDate(new Date()));

    const [dateRange, setDateRange] = useState([
        {
            startDate: lastMonthDate,
            endDate: new Date(),
            key: "selection",
        },
    ]);

    const handleDateChange = (ranges) => {
        setDateRange([ranges.selection]);

        setStartDate(formatDate(ranges.selection.startDate));
        setEndDate(formatDate(ranges.selection.endDate));
    }

    const [data, setData] = useState(null);

    useEffect(() => {
        setLoading(true);
        setData(null);

        wp.ajax.send('igd_get_logs', {
            data: {
                start_date: formatDateForSQL(startDate),
                end_date: formatDateForSQL(endDate),
                nonce: igd.nonce,
            },
            success: (response) => {
                setData(response);
            },
            error: (error) => {
                console.log(error);
            },
            complete: () => {
                setLoading(false);
            }
        });

    }, [startDate, endDate]);

    useEffect(() => {
        if (!igd.isPro) {
            Swal.fire({
                title: wp.i18n.__('Upgrade to Pro', 'integrate-google-drive'),
                text: wp.i18n.__('Upgrade to Pro to access the statistics.', 'integrate-google-drive'),
                icon: 'warning',
                confirmButtonText: wp.i18n.__('GET PRO', 'integrate-google-drive'),
                target: document.querySelector('.private-folders-table-wrap'),
                heightAuto: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                customClass: {container: 'igd-swal'},
            });
        }
    }, []);

    return (
        <div className="igd-statistics-wrapper">

            {/* Header */}
            <Header
                dateRange={dateRange}
                setDateRange={setDateRange}
                handleDateChange={handleDateChange}
            />

            {loading && <div className="igd-spinner spinner-large"></div>}

            {/* Summary */}
            {!!data &&
                <Summary data={data}/>
            }

            {/* Top Boxes */}
            {!!data &&
                <div className="statistics-boxes-wrapper">
                    {/* Events Chart */}
                    <EventsChart data={data}/>

                    <TopDownloads data={data} startDate={startDate} endDate={endDate}/>

                    <TopUploads data={data} startDate={startDate} endDate={endDate}/>

                    <TopPreviews data={data} startDate={startDate} endDate={endDate}/>

                    <TopStreams data={data} startDate={startDate} endDate={endDate}/>

                    <TopSearches data={data} startDate={startDate} endDate={endDate}/>

                    <TopShared data={data} startDate={startDate} endDate={endDate}/>

                    {/* Event Logs */}
                    <EventLogs
                        data={data}
                        startDate={startDate}
                        endDate={endDate}
                    />
                </div>
            }
        </div>
    )
}