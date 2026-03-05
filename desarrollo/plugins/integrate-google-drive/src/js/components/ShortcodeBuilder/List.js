import {Tooltip} from "react-tooltip";
import ShortcodeBuilderContext from "../../contexts/ShortcodeBuilderContext";
import Pagination from "../../includes/Pagination/Pagination";
import {copyShortcode, timeAgo, moduleTypes, setIdParam, useMounted, copyViewUrl} from "../../includes/functions";

const {
    FormToggle,
    CheckboxControl,
    SelectControl,
    Button,
} = wp.components;

const {useContext, useState, useEffect} = React;

export default function List() {

    const context = useContext(ShortcodeBuilderContext);

    const {
        isModuleBuilder,
        onUpdate,

        shortcodes,
        deleteShortcode,
        setEdit,
        updateShortcode,
        duplicateShortcode,
        page,
        setPage,
        perPage,
        setPerPage,
        sortBy,
        setSortBy,
        sortOrder,
        setSortOrder,

    } = context;

    const types = moduleTypes();

    const [filteredShortcodes, setFilteredShortcodes] = useState([]);
    const [filteredTotal, setFilteredTotal] = useState(0);

    const [searchTerm, setSearchTerm] = useState('');

    const [selectedShortcode, setSelectedShortcode] = useState([]);
    const [lastCheckedIndex, setLastCheckedIndex] = useState(null);

    const savedColumns = localStorage.getItem('igd_shortcode_list_columns');

    let initColumns = savedColumns ? JSON.parse(savedColumns) : ['ID', 'type', 'status', 'shortcode', 'created'];

    if (isModuleBuilder) {
        initColumns = initColumns.filter(column => column !== 'shortcode' && column !== 'locations');
    }

    let [columns, setColumns] = useState(initColumns);

    const isMounted = useMounted();

    useEffect(() => {
        if (!isMounted) return;

        localStorage.setItem('igd_shortcode_list_columns', JSON.stringify(columns));
    }, [columns]);

    useEffect(() => {

        let data = [...shortcodes];

        // 1. Search
        const term = searchTerm.trim().toLowerCase();
        if (term.length > 0) {
            data = data.filter(({title, config}) => {
                const t = title?.toLowerCase() || '';
                const typeTitle = types[config?.type] ? types[config?.type]['title'].toLowerCase() : '';
                return t.includes(term) || typeTitle.includes(term) || String(config?.id).includes(term);
            });
        }


        // 2. Sorting
        if (sortBy) {
            data.sort((a, b) => {
                const aVal = a[sortBy];
                const bVal = b[sortBy];

                const isNumeric = sortBy === 'id';

                if (isNumeric) {
                    return sortOrder === 'asc'
                        ? Number(aVal) - Number(bVal)
                        : Number(bVal) - Number(aVal);
                }

                return sortOrder === 'asc'
                    ? String(aVal).localeCompare(String(bVal))
                    : String(bVal).localeCompare(String(aVal));
            });
        }

        const totalItems = data.length;

        // 3. Pagination
        const start = (page - 1) * perPage;
        const end = start + perPage;
        const paginated = data.slice(start, end);

        setFilteredShortcodes(paginated);
        setFilteredTotal(totalItems);

    }, [shortcodes, searchTerm, sortBy, sortOrder, page, perPage]);

    return (
        <div className="igd-shortcode-list-wrap">

            {/*----- List Header -----*/}
            <div className="igd-shortcode-list-header">

                <h3 className="igd-shortcode-list-title">
                    {wp.i18n.__('All Modules', 'integrate-google-drive')}

                    <span
                        className="shortcode-list-count">({filteredTotal === 0 ? 0 : (page - 1) * perPage + 1}–{Math.min(page * perPage, filteredTotal)} / {filteredTotal})</span>
                </h3>


                {/*--- Items Per Page ---*/}
                <div className="settings-per-page">
                    <h4>{wp.i18n.__('Items Per Page', 'integrate-google-drive')}</h4>

                    <SelectControl
                        value={perPage}
                        options={[
                            {label: '10', value: 10},
                            {label: '20', value: 20},
                            {label: '50', value: 50},
                            {label: '100', value: 100},
                        ]}
                        onChange={value => {
                            setPerPage(value);
                            setPage(1);
                            localStorage.setItem('igd_shortcode_list_per_page', value);
                        }}
                    />
                </div>

                {/*--- Search ---*/}
                <div className="search-wrap">

                    {searchTerm &&
                        <div className="search-dismiss"
                             onClick={() => {
                                 setSearchTerm('');
                             }}
                        >
                            <svg width="14" height="14" viewBox="0 0 12 12" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.5 1.5L10.5 10.5M1.5 10.5L10.5 1.5L1.5 10.5Z" stroke="#BABABA"
                                      strokeWidth="2"
                                      strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </div>
                    }

                    <input
                        type="text"
                        value={searchTerm}
                        className="search-input"
                        placeholder={wp.i18n.__("Search by title, type, ID", 'integrate-google-drive')}
                        onChange={(e) => {
                            const value = e.target.value;

                            setSearchTerm(value);
                        }}
                    />

                    <div className="search-submit">
                        <svg width="20" height="20" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="9.7659" cy="9.76639" r="8.98856" stroke="#3D3D3D" strokeWidth="1.5"
                                    strokeLinecap="round" strokeLinejoin="round"/>
                            <path d="M16.0176 16.4849L19.5416 19.9997" stroke="#3D3D3D" strokeWidth="1.5"
                                  strokeLinecap="round"
                                  strokeLinejoin="round"/>
                        </svg>
                    </div>
                </div>

                {/*--- Column Settings ---*/}
                <div className={`igd-list-column-settings`}>

                    <i className="column-settings-icon dashicons dashicons-admin-generic"></i>
                    <Tooltip
                        anchorSelect={`.column-settings-icon`}
                        place="left"
                        variant="light"
                        clickable={true}
                        border={`1px solid #ddd`}
                        scrollHide={false}
                        className="igd-tooltip list-settings-tooltip"
                    >
                        <div className="settings-wrap settings-columns">
                            <h4>{wp.i18n.__('Columns', 'integrate-google-drive')}</h4>

                            <div className="column-options">
                                {
                                    ['ID', 'type', 'status', 'shortcode', 'locations', 'created'].map((column, index) => {
                                        return (
                                            <div className="igd-column-setting" key={index}>
                                                <CheckboxControl
                                                    label={column}
                                                    checked={columns.includes(column)}
                                                    onChange={() => {
                                                        const newColumns = columns.includes(column) ? columns.filter(item => item !== column) : [...columns, column];

                                                        setColumns(newColumns);
                                                    }}
                                                />
                                            </div>
                                        );
                                    })
                                }
                            </div>
                        </div>
                    </Tooltip>
                </div>

            </div>

            {/*----- Bulk Actions -----*/}
            {!!selectedShortcode.length &&
                <div className="selection-actions-wrap">
                    <div className="selection-count">
                        {selectedShortcode.length} {wp.i18n.__('Item(s) selected', 'integrate-google-drive')}
                    </div>

                    <button
                        className="igd-btn btn-warning"
                        onClick={() => setSelectedShortcode([])}>
                        <i className={'dashicons dashicons-no-alt'}></i>
                        <span>{wp.i18n.__('Clear Selection', 'integrate-google-drive')}</span>
                    </button>

                    {/* Duplicate */}
                    <button
                        className="igd-btn btn-info"
                        onClick={() => {
                            duplicateShortcode(selectedShortcode);

                            setSelectedShortcode([]);
                        }}>
                        <i className={'dashicons dashicons-admin-page'}></i>
                        <span>{wp.i18n.__('Duplicate', 'integrate-google-drive')}</span>
                    </button>

                    {/* Delete Selection */}
                    <button
                        className="igd-btn btn-danger"
                        onClick={() => {

                            Swal.fire({
                                title: wp.i18n.__('Are you sure?', 'integrate-google-drive'),
                                text: wp.i18n.__('You will not be able to recover this shortcode!', 'integrate-google-drive'),
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: wp.i18n.__('Yes, delete it!', 'integrate-google-drive'),
                                reverseButtons: true,
                                customClass: {container: 'igd-swal igd-swal-reverse'},
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    selectedShortcode.forEach(id => deleteShortcode(id));
                                    setSelectedShortcode([]);
                                }
                            });
                        }}>
                        <i className={'dashicons dashicons-trash'}></i>
                        <span>{wp.i18n.__('Delete', 'integrate-google-drive')}</span>
                    </button>
                </div>
            }

            {/*----- List -----*/}
            <table className="igd-shortcode-list">
                <thead>
                <tr>
                    <th className={`col-selection`}>
                        <CheckboxControl
                            checked={selectedShortcode.length === filteredShortcodes.length}
                            onChange={() => {
                                if (selectedShortcode.length === filteredShortcodes.length) {
                                    setSelectedShortcode([]);
                                } else {
                                    setSelectedShortcode(filteredShortcodes.map(item => item.id));
                                }
                            }}
                        />
                    </th>

                    {columns.includes('ID') &&
                        <th className={`col-id`}>
                            <div
                                className={`sortable ${sortBy === 'id' && sortOrder} ${sortBy === 'id' ? 'active' : ''}`}
                                onClick={() => {
                                    setSortBy('id');
                                    setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
                                }}
                            >
                                <span>{wp.i18n.__("ID", 'integrate-google-drive')}</span>
                                <i className={`dashicons dashicons-arrow-up`}></i>
                            </div>
                        </th>
                    }

                    <th className={`col-title`}>
                        <div
                            className={`sortable ${sortBy === 'title' && sortOrder} ${sortBy === 'title' ? 'active' : ''}`}
                            onClick={() => {
                                setSortBy('title');
                                setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
                            }}
                        >
                            <span>{wp.i18n.__("Title", 'integrate-google-drive')}</span>
                            <i className={`dashicons dashicons-arrow-up`}></i>
                        </div>
                    </th>

                    {columns.includes('type') &&
                        <th className={`col-shortcode-type`}>
                            <div
                                className={`sortable ${sortBy === 'type' && sortOrder} ${sortBy === 'type' ? 'active' : ''}`}
                                onClick={() => {
                                    setSortBy('type');
                                    setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
                                }}
                            >
                                <span>{wp.i18n.__("Type", 'integrate-google-drive')}</span>
                                <i className={`dashicons dashicons-arrow-up`}></i>
                            </div>
                        </th>
                    }

                    {columns.includes('status') &&
                        <th>
                            {wp.i18n.__("Status", 'integrate-google-drive')}

                            <i className={`dashicons dashicons-info col-status-info`}></i>

                            <Tooltip
                                anchorSelect={`.col-status-info`}
                                content={wp.i18n.__("Module active/inactive status", 'integrate-google-drive')}
                                place="top"
                                className={"igd-tooltip list-th-tooltip"}
                            />
                        </th>
                    }

                    {columns.includes('shortcode') &&
                        <th>{wp.i18n.__("Shortcode", 'integrate-google-drive')}</th>
                    }

                    {columns.includes('locations') &&
                        <th>
                            {wp.i18n.__("Locations", 'integrate-google-drive')}

                            <i className={`dashicons dashicons-info col-locations-info`}></i>

                            <Tooltip
                                anchorSelect={`.col-locations-info`}
                                content={wp.i18n.__("The locations where the shortcode is used", 'integrate-google-drive')}
                                place="top"
                                className={"igd-tooltip list-th-tooltip"}
                            />
                        </th>
                    }

                    {columns.includes('created') &&
                        <th>
                            <div className="sortable">
                                <span>{wp.i18n.__("Created", 'integrate-google-drive')}</span>

                                <i className={`dashicons dashicons-arrow-up  ${sortOrder}`}
                                   onClick={() => {
                                       setSortBy('id');
                                       setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
                                   }}></i>
                            </div>
                        </th>
                    }

                    <th>{wp.i18n.__("Actions", 'integrate-google-drive')}</th>
                </tr>
                </thead>

                <tbody>

                {
                    filteredShortcodes.map(item => {

                        const {id, title, type, status, created_at, config, locations} = item;

                        const typeTitle = types[config?.type] ? types[config?.type]['title'] : '';

                        return (
                            <tr
                                key={id}
                                className="igd-shortcode-list-item">

                                <td className={`col-selection`}>
                                    <CheckboxControl
                                        checked={selectedShortcode.includes(id)}
                                        onChange={(isChecked) => {
                                            const currentIndex = shortcodes.findIndex(s => s.id === id);

                                            // Shift key pressed and a previous index exists
                                            if (window.event.shiftKey && lastCheckedIndex !== null) {
                                                const [start, end] = [lastCheckedIndex, currentIndex].sort((a, b) => a - b);
                                                const rangeIds = shortcodes.slice(start, end + 1).map(s => s.id);

                                                const newSelected = isChecked
                                                    ? [...new Set([...selectedShortcode, ...rangeIds])]
                                                    : selectedShortcode.filter(id => !rangeIds.includes(id));

                                                setSelectedShortcode(newSelected);
                                            } else {
                                                // Normal single toggle
                                                setSelectedShortcode(prev => {
                                                    return isChecked
                                                        ? [...prev, id]
                                                        : prev.filter(item => item !== id);
                                                });
                                            }

                                            setLastCheckedIndex(currentIndex);
                                        }}
                                    />

                                </td>

                                {/* ID */}
                                {columns.includes('ID') &&
                                    <td className={`col-id`}>{id}</td>
                                }

                                {/* Title */}
                                <td className="col-title"
                                    onClick={() => {
                                        setIdParam(id);
                                        setEdit(id);
                                    }}
                                >{title}</td>

                                {/* Type */}
                                {columns.includes('type') &&
                                    <td className="col-shortcode-type">
                                        <div>
                                            <img
                                                src={`${igd.pluginUrl}/assets/images/shortcode-builder/types/${config.type}.svg`}
                                                alt={title}/>
                                            <span>{typeTitle}</span>
                                        </div>
                                    </td>
                                }

                                {/*--- Status ---*/}
                                {columns.includes('status') &&
                                    <td className="col-shortcode-status">

                                        <FormToggle
                                            checked={'on' === status}
                                            onChange={() => {
                                                updateShortcode({
                                                    ...config,
                                                    id,
                                                    status: 'on' === status ? 'off' : 'on'
                                                });
                                            }}
                                        />

                                    </td>
                                }

                                {/* Shortcode */}
                                {columns.includes('shortcode') &&
                                    <td className="col-code">
                                        <div
                                            onClick={() => copyShortcode(id)}
                                        >
                                            <i data-tooltip-content={wp.i18n.__("Copy shortcode", 'integrate-google-drive')}
                                               data-tooltip-id="copyShortcode"
                                               className="dashicons dashicons-admin-page"
                                            ></i>

                                            <Tooltip
                                                id="copyShortcode"
                                                effect="solid"
                                                place="top"
                                                className={"igd-tooltip"}
                                            />

                                            <code>{`[integrate_google_drive id="${id}"]`}</code>
                                        </div>
                                    </td>
                                }

                                {/* Locations */}
                                {columns.includes('locations') &&
                                    <td className="col-locations">
                                        {!!locations && locations.length > 0 ?
                                            <div className="shortcode-locations">
                                                <span
                                                    className={`location-count location-count-${id}`}>{locations.length}</span>

                                                <Tooltip
                                                    anchorSelect={`.location-count-${id}`}
                                                    place="top"
                                                    variant="light"
                                                    clickable={true}
                                                    border={`1px solid #ddd`}
                                                    scrollHide={false}
                                                    className="igd-tooltip locations-tooltip"
                                                    globalEventOff="click"
                                                >
                                                    <h3>{wp.i18n.__('Module Locations', 'integrate-google-drive')}</h3>

                                                    {
                                                        locations.map((location, index) => {
                                                            return (
                                                                <div
                                                                    key={index}
                                                                    className="location-item">
                                                                    <a href={location.url} target="_blank">
                                                                        <span
                                                                            className={`location-index`}>{index + 1}. </span>
                                                                        <span
                                                                            className={`location-title`}>{location.title}</span>
                                                                        <i className="dashicons dashicons-external"></i>
                                                                    </a>
                                                                </div>
                                                            )
                                                        })
                                                    }

                                                </Tooltip>

                                            </div>
                                            :
                                            <div className="shortcode-locations">
                                                <span
                                                    className="location-count">{wp.i18n.__('0', 'integrate-google-drive')}</span>
                                            </div>
                                        }
                                    </td>
                                }

                                {/*--- Created at ---*/}
                                {columns.includes('created') &&
                                    <td className="col-created">{timeAgo(created_at)}</td>
                                }

                                {/*----- Actions -----*/}
                                <td className="col-actions">

                                    {/*--- View Selections ---*/}
                                    {!isModuleBuilder && (type === 'review' || ('gallery' === type && config.photoProof)) &&
                                        <Button
                                            className="igd-btn btn-primary btn-view-selections"
                                            label={wp.i18n.__('View Selections', 'integrate-google-drive')}
                                            href={`${igd.adminUrl}admin.php?page=integrate-google-drive-proof-selections&module_id=${id}`}
                                        >
                                            <img
                                                src={`${igd.pluginUrl}/assets/images/shortcode-builder/types/review-alt.svg`}/>
                                        </Button>
                                    }

                                    {/*--- Edit shortcode ---*/}
                                    <Button
                                        className="igd-btn btn-primary"
                                        onClick={() => {
                                            setIdParam(id);
                                            setEdit(id);
                                        }}
                                        icon="edit"
                                        label={wp.i18n.__('Edit Module', 'integrate-google-drive')}
                                        text={wp.i18n.__('Edit', 'integrate-google-drive')}
                                    />

                                    {/*--- Insert Module ---*/}
                                    {isModuleBuilder &&
                                        <button
                                            className="igd-btn btn-primary btn-insert"
                                            onClick={() => onUpdate(id)}
                                        >
                                            <img
                                                src={`${igd.pluginUrl}/assets/images/shortcode-builder/insert-module.svg`}/>
                                            <span>{wp.i18n.__('Insert Module', 'integrate-google-drive')}</span>
                                        </button>
                                    }

                                    {/*--- Tools ---*/}
                                    <Button
                                        className={`igd-btn btn-tools btn-${id}`}
                                        icon={"ellipsis"}
                                        label={wp.i18n.__('Actions', 'integrate-google-drive')}
                                    />

                                    <Tooltip
                                        anchorSelect={`.btn-${id}`}
                                        place="left"
                                        variant="light"
                                        clickable={true}
                                        border={`1px solid #ddd`}
                                        className="options-tooltip igd-tooltip"
                                        offset={-5}
                                    >
                                        <div className="action-tools">

                                            {/*--- Edit shortcode ---*/}
                                            <button
                                                className="igd-btn btn-preview"
                                                onClick={() => {
                                                    setIdParam(id);
                                                    setEdit(id);
                                                }}
                                            >
                                                <i className="dashicons dashicons-edit"></i>
                                                <span>{wp.i18n.__("Edit", 'integrate-google-drive')}</span>
                                            </button>

                                            {/*--- Preview shortcode ---*/}
                                            <button
                                                className="igd-btn btn-preview"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    e.preventDefault();

                                                    window.open(`${igd.homeUrl}/igd-modules/${id}`, '_blank');
                                                }}>

                                                <i className="dashicons dashicons-visibility"></i>
                                                <span>{wp.i18n.__("View", 'integrate-google-drive')}</span>
                                            </button>

                                            {/*--- Copy View URL ---*/}
                                            <button
                                                className="igd-btn"
                                                onClick={() => copyViewUrl(id)}>
                                                <i className="dashicons dashicons-admin-links"></i>
                                                <span>{wp.i18n.__("Copy View URL", 'integrate-google-drive')}</span>
                                            </button>

                                            {/*--- Copy shortcode ---*/}
                                            <button
                                                className="igd-btn btn-copy"
                                                onClick={() => copyShortcode(id)}>
                                                <i className="dashicons dashicons-shortcode"></i>
                                                <span>{wp.i18n.__("Copy Shortcode", 'integrate-google-drive')}</span>
                                            </button>

                                            {/*--- Duplicate shortcode ---*/}
                                            <button
                                                className="igd-btn btn-duplicate"
                                                onClick={() => {
                                                    duplicateShortcode([id])
                                                }}>
                                                <i className="dashicons dashicons-admin-page"></i>
                                                <span>{wp.i18n.__("Duplicate", 'integrate-google-drive')}</span>
                                            </button>

                                            {/*--- Delete shortcode ---*/}
                                            <button
                                                className="igd-btn btn-delete"
                                                onClick={() => {
                                                    Swal.fire({
                                                        title: wp.i18n.__("Are you sure?", "integrate-google-drive"),
                                                        text: wp.i18n.__("You won't be able to revert this!", "integrate-google-drive"),
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonText: wp.i18n.__("Yes, delete it!", "integrate-google-drive"),
                                                        cancelButtonText: wp.i18n.__("No, cancel!", "integrate-google-drive"),
                                                        reverseButtons: true,
                                                        focusCancel: true,
                                                        showLoaderOnConfirm: true,
                                                        customClass: {container: 'igd-swal igd-swal-reverse'},
                                                        preConfirm: () => {
                                                            return deleteShortcode(id);
                                                        }
                                                    });
                                                }}>
                                                <i className="text-red-500 dashicons dashicons-trash"></i>
                                                <span>{wp.i18n.__("Delete", 'integrate-google-drive')}</span>
                                            </button>


                                        </div>
                                    </Tooltip>
                                </td>
                            </tr>
                        )
                    })
                }

                </tbody>
            </table>

            {/*--- No results ---*/}
            {filteredTotal === 0 && (
                <div className="igd-no-results">
                    <i className="dashicons dashicons-warning"></i>
                    <span>{wp.i18n.__('No shortcodes found matching your search.', 'integrate-google-drive')}</span>
                </div>
            )}

            {/* Pagination */}
            <div className="igd-shortcode-list-footer">
                <Pagination
                    className={"igd-pagination"}
                    pageCount={Math.ceil(filteredTotal / perPage)}
                    currentPage={page}
                    onPageChange={page => setPage(page)}
                />
            </div>

        </div>
    )
}