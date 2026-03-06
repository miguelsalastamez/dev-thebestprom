import ReactSelect from "react-select";


export default function FilterUsers({
                                        usersOptions,
                                        values,
                                        onChange,
                                        description = wp.i18n.__("Select the users and roles who can access.", "integrate-google-drive")
                                    }) {
    return (
        <div className="filter-users">

            <h4 className={`filter-users-title`}>{wp.i18n.__("Filter Users & Roles:", "integrate-google-drive")}</h4>

            <div className="filter-users-section-wrap">
                <ReactSelect
                    isClearable={false}
                    isMulti
                    placeholder={"Select users & roles"}
                    options={usersOptions}
                    value={usersOptions.filter(item => values.includes(item.value))}
                    onChange={selected => onChange(selected)}
                    className="igd-select filter-users-select"
                    classNamePrefix="igd-select"
                />

                <p className="description">{description}</p>
            </div>
        </div>
    )
}