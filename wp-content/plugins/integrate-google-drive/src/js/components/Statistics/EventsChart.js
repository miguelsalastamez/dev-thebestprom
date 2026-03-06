import {formatDate} from "../../includes/functions";


const {useState, useEffect, useRef} = React;

export default function EventsChart({data}) {

    const [showData, setShowData] = useState(localStorage.getItem('igd_show_chart_data') ? localStorage.getItem('igd_show_chart_data') == 'true' : true);

    const containerRef = useRef(null);

    const events = data.events.map(event => ({
        ...event,
        created_at: formatDate(event.created_at)
    }));

    const dates = [...new Set(events.map(event => event.created_at))].reverse();

    const downloadEvents = _.groupBy(events.filter(event => event.type === "download"), event => event.created_at);
    const uploadEvents = _.groupBy(events.filter(event => event.type === "upload"), event => event.created_at);
    const previewEvents = _.groupBy(events.filter(event => event.type === "preview"), event => event.created_at);
    const streamEvents = _.groupBy(events.filter(event => event.type === "stream"), event => event.created_at);

    const downloads = dates.map(date => downloadEvents[date] ? downloadEvents[date].length : 0);
    const uploads = dates.map(date => uploadEvents[date] ? uploadEvents[date].length : 0);
    const previews = dates.map(date => previewEvents[date] ? previewEvents[date].length : 0);
    const streams = dates.map(date => streamEvents[date] ? streamEvents[date].length : 0);

    useEffect(() => {

        const canvasElement = document.createElement("canvas");
        if (!showData) {
            canvasElement.style.display = "none";
        }

        canvasElement.height = 400;
        containerRef.current.appendChild(canvasElement);

        const myChart = new Chart(canvasElement, {
            type: 'line',
            data: {
                labels: dates.map(date => formatDate(date)),
                datasets: [
                    {
                        label: wp.i18n.__('Downloads', 'integrate-google-drive'),
                        data: downloads,
                        backgroundColor: 'rgba(47, 180, 75, 0.1)',
                        borderColor: '#2FB44B',
                        borderWidth: 2
                    },
                    {
                        label: wp.i18n.__('Uploads', 'integrate-google-drive'),
                        data: uploads,
                        backgroundColor: 'rgba(112, 197, 255, 0.1)',
                        borderColor: '#70C5FF',
                        borderWidth: 2
                    },
                    {
                        label: wp.i18n.__('Previews', 'integrate-google-drive'),
                        data: previews,
                        backgroundColor: 'rgba(252, 163, 29, 0.1)',
                        borderColor: '#18B3FD',
                        borderWidth: 2
                    },
                    {
                        label: wp.i18n.__('Streams', 'integrate-google-drive'),
                        data: streams,
                        backgroundColor: 'rgba(125, 162, 246, 0.1)',
                        borderColor: '#7DA2F6',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: false,
            }
        });

        return () => {
            canvasElement.remove();
            myChart.destroy();
        }
    }, [events]);

    return (
        <div className="statistics-box event-chart" ref={containerRef}>
            <div className="box-title">
                <span>{wp.i18n.__('Events Chart / Day', 'integrate-google-drive')}</span>

                <i className={`dashicons dashicons-arrow-${showData ? 'up' : 'down'}-alt2`}
                   onClick={() => {
                       localStorage.setItem('igd_show_chart_data', !showData);
                       setShowData(!showData);
                   }}
                   data-tooltip-content={wp.i18n.__(showData ? 'Hide Data' : 'Show Data')}
                ></i>
            </div>
        </div>
    )
}