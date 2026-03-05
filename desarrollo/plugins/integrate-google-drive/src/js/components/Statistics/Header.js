import {Tooltip} from "react-tooltip";

import {DateRangePicker} from "react-date-range";
import "react-date-range/dist/styles.css"; // Main style file
import "react-date-range/dist/theme/default.css"; // Theme file

import {exportStatistics, formatDateForSQL} from "../../includes/functions";

const {useEffect, useState} = React;

export default function Header({handleDateChange, setData, dateRange}) {

    const {startDate, endDate} = dateRange[0];

    // Handle filter tooltip
    const [isTooltipOpen, setTooltipOpen] = useState(false);

    return (
        <div className="statistics-header">

            <div className="statistics-header-title">
                <img src={igd.pluginUrl + '/assets/images/statistics/statistics-icon.svg'} alt="Statistics"/>
                <span>{wp.i18n.__('Statistics', 'integrate-google-drive')}</span>
            </div>

            <div className="statistics-range">

                <button type="button" className="igd-btn btn-primary filter-btn"
                        onClick={() => setTooltipOpen(!isTooltipOpen)}
                >
                    <i className="dashicons dashicons-filter"></i>

                    <span>{wp.i18n.__('Filter', 'integrate-google-drive')}</span>

                    <i className="dashicons dashicons-arrow-down-alt2"></i>

                </button>

                <Tooltip
                    anchorSelect={'.filter-btn'}
                    place={'bottom'}
                    isOpen={isTooltipOpen}
                    setIsOpen={setTooltipOpen}
                    openEvents={['click']}
                    variant="light"
                    clickable={true}
                    border={`1px solid #ddd`}
                    resizeHide={false}
                    className="statistics-tooltip-wrap igd-tooltip"
                >
                    <div className="statistics-filter">
                        <div className="filter-options">

                            <DateRangePicker
                                editableDateInputs={true}
                                onChange={handleDateChange}
                                showSelectionPreview={true}
                                moveRangeOnFirstSelection={false}
                                ranges={dateRange}
                                showMonthAndYearPickers={true}
                                maxDate={new Date()}
                                months={2}
                                direction="horizontal"
                                rangeColors={["#3d91ff"]} // Custom color
                            />

                        </div>
                    </div>
                </Tooltip>


                <i data-tooltip-id="clear-statistics"
                   className="clear-statistics dashicons dashicons-ellipsis"></i>

                <Tooltip
                    id="clear-statistics"
                    place="left"
                    variant="light"
                    openOnClick="click"
                    globalEventOff="click"
                    className="igd-tooltip clear-statistics-tooltip"
                    clickable={true}
                    border={`1px solid #ddd`}
                >
                    <button
                        className="igd-btn btn-info"
                        onClick={() => exportStatistics(formatDateForSQL(startDate), formatDateForSQL(endDate))}
                    >
                        <i className="dashicons dashicons-download"></i>
                        {wp.i18n.__('Export Statistics', 'integrate-google-drive')}
                    </button>

                    <button type="button" className="igd-btn btn-danger" onClick={() => {
                        Swal.fire({
                            title: wp.i18n.__('Deleted!', 'integrate-google-drive'),
                            text: wp.i18n.__('All statistics data has been deleted.', 'integrate-google-drive'),
                            icon: 'success',
                            toast: true,
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                        });

                        setData({
                            downloads: [],
                            downloadUsers: [],
                            uploads: [],
                            uploadUsers: [],
                            streams: [],
                            streamUsers: [],
                            previews: [],
                            previewUsers: [],
                            searches: [],
                            searchUsers: [],
                            events: [],
                        });

                        wp.ajax.post('igd_clear_statistics', {nonce: igd.nonce});

                    }}>
                        <i className="dashicons dashicons-trash"></i>
                        {wp.i18n.__('Clear Statistics', 'integrate-google-drive')}
                    </button>
                </Tooltip>
            </div>

        </div>
    )
}