import ShortcodeBuilderContext from "../../../contexts/ShortcodeBuilderContext";

const {FormToggle, CheckboxControl} = wp.components;
const {useContext} = React;

export default function () {
    const context = useContext(ShortcodeBuilderContext);

    const {
        editData,
        setEditData,
        isFormBuilder
    } = context;

    const {
        type = igd.isPro ? 'browser' : 'gallery',
        allowExtensions,
        allowAllExtensions,
        allowExceptExtensions,
        allowNames,
        allowAllNames,
        allowExceptNames,
        nameFilterOptions = ['files'],
        showFiles = true,
        showFolders = true,

        maxFileSize,
        maxFiles,
        minFiles,
        minFileSize,
        fileNumbers,
    } = editData;

    const isBrowser = 'browser' === type;
    const isReview = 'review' === type;
    const isUploader = 'uploader' === type;
    const isGallery = 'gallery' === type;
    const isEmbed = 'embed' === type;
    const isSearch = 'search' === type;
    const isAudioVideo = 'media' === type;
    const isSlider = 'slider' === type;

    return (
        <div className="shortcode-module-body">

            {/*----- Show Files -----*/}
            {(isBrowser || isReview || isGallery || isSearch) &&
                <div className="settings-field">

                    <h4 className="settings-field-label">{wp.i18n.__("Show Files", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={showFiles}
                            onChange={() => setEditData({...editData, showFiles: !showFiles})}
                        />

                        <p className="description">{wp.i18n.__("If turned off, files won't show.", "integrate-google-drive")}</p>
                    </div>
                </div>
            }

            {/*----- Show Folders -----*/}
            {(isBrowser || isReview || isGallery || isSearch) &&
                <div className="settings-field">

                    <h4 className="settings-field-label">{wp.i18n.__("Show Folders", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <FormToggle
                            checked={showFolders}
                            onChange={() => setEditData({...editData, showFolders: !showFolders})}
                        />

                        <p className="description">{wp.i18n.__("If turned off, folders won't show.", "integrate-google-drive")}</p>
                    </div>
                </div>
            }

            {/*----- Allowed extensions -----*/}
            <div className="settings-field filter-field">

                <h4 className="settings-field-label">{wp.i18n.__("Allowed Extensions", "integrate-google-drive")}</h4>

                <div className="settings-field-content">

                    <div className="filter-field-input">
                        <input type="text"
                               disabled={allowAllExtensions}
                               value={allowExtensions}
                               onChange={(e) => setEditData({...editData, allowExtensions: e.target.value})}
                        />

                        <p className="description">{wp.i18n.sprintf(wp.i18n.__('Enter comma ( , ) seperated file extensions to allow, such as "jpg, png, gif". Leave empty to %s all extension files.', 'integrate-google-drive'), isUploader ? wp.i18n.__('upload', 'integrate-google-drive') : wp.i18n.__('show', 'integrate-google-drive'))}</p>
                    </div>

                    <div className="filter-field-all">

                        <div>
                            <h4 className="filter-field-all-label">{wp.i18n.__("Allow all : ", "integrate-google-drive")} </h4>

                            <FormToggle
                                checked={allowAllExtensions}
                                onChange={() => setEditData({...editData, allowAllExtensions: !allowAllExtensions})}
                            />

                        </div>

                        <div>
                            <h4 className="filter-field-all-label">{wp.i18n.__("Except : ", "integrate-google-drive")}</h4>
                            <input type="text"
                                   disabled={!allowAllExtensions}
                                   value={allowExceptExtensions}
                                   onChange={(e) => setEditData({...editData, allowExceptExtensions: e.target.value})}
                            />
                        </div>

                        <p className="description">{wp.i18n.sprintf(wp.i18n.__('When "Allow all" is enabled, exceptions will not be %s.', 'integrate-google-drive'), isUploader ? wp.i18n.__('uploaded', 'integrate-google-drive') : wp.i18n.__('displayed', 'integrate-google-drive'))}</p>

                    </div>
                </div>
            </div>

            {/*----- Filter names -----*/}
            {
                (isBrowser || isReview || isGallery || isEmbed || isSearch || isAudioVideo || isSlider) &&
                <div className="settings-field filter-field">

                    <h4 className="settings-field-label">{wp.i18n.__("Allowed Names", "integrate-google-drive")}</h4>
                    <div className="settings-field-content">

                        <div className="filter-field-input">
                            <input type="text"
                                   disabled={allowAllNames}
                                   value={allowNames}
                                   onChange={(e) => setEditData({...editData, allowNames: e.target.value})}
                            />

                            <p className="description">{wp.i18n.sprintf(wp.i18n.__('Enter file and folder names, separated by commas, to display. Leave blank to display all files and folders.', 'integrate-google-drive'))}</p>
                        </div>


                        <div className="filter-field-all">
                            <div>
                                <h4 className="filter-field-all-label">{wp.i18n.__("Allow all : ", "integrate-google-drive")} </h4>
                                <FormToggle
                                    checked={allowAllNames}
                                    onChange={() => setEditData({...editData, allowAllNames: !allowAllNames})}
                                />
                            </div>

                            <div>
                                <h4 className="filter-field-all-label">{wp.i18n.__("Except : ", "integrate-google-drive")}</h4>
                                <input type="text"
                                       disabled={!allowAllNames}
                                       value={allowExceptNames}
                                       onChange={(e) => setEditData({...editData, allowExceptNames: e.target.value})}
                                />
                            </div>

                            <p className="description">{wp.i18n.__('When "Allow all" is enabled, exceptions will not be displayed.', "integrate-google-drive")}</p>

                            <div className="igd-notice igd-notice-info">
                                <div className="igd-notice-content">

                                    <h5>{wp.i18n.__("You can use the * and ? wildcards to match multiple or single characters, respectively.", "integrate-google-drive")}</h5>

                                    <ul>
                                        <li><code>*</code> {wp.i18n.__("matches any number of characters.", "integrate-google-drive")}</li>
                                        <li><code>?</code> {wp.i18n.__("matches exactly one character.", "integrate-google-drive")}</li>
                                    </ul>

                                    <h5>{wp.i18n.__("For example:", "integrate-google-drive")}</h5>

                                    <ul>
                                        <li>
                                            <code>report*</code> , <code>*.txt</code>  →  {wp.i18n.__("will match all files that start with 'report' and have the .txt extension.", "integrate-google-drive")}
                                        </li>
                                        <li>
                                            <code>file?</code> , <code>image_*</code>  →  {wp.i18n.__("will match files like 'file1', 'file2', and all files that start with 'image_'.", "integrate-google-drive")}
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div className="name-filter-option">
                                <h4 className="name-filter-option-label">{wp.i18n.__("Apply to : ", "integrate-google-drive")} </h4>

                                {
                                    ['files', 'folders'].map(item => (
                                        <div key={item} className="name-filter-option-item">
                                            <CheckboxControl
                                                label={item}
                                                checked={nameFilterOptions.includes(item)}
                                                onChange={() => {
                                                    if (nameFilterOptions.includes(item)) {
                                                        setEditData({
                                                            ...editData,
                                                            nameFilterOptions: nameFilterOptions.filter(i => i !== item)
                                                        })
                                                    } else {
                                                        setEditData({
                                                            ...editData,
                                                            nameFilterOptions: [...nameFilterOptions, item]
                                                        })
                                                    }
                                                }}
                                            />
                                        </div>
                                    ))
                                }

                                <p className={"description"}>{wp.i18n.__("Select the type of files to apply the name filters.", "integrate-google-drive")}</p>

                            </div>

                        </div>

                    </div>

                </div>
            }

            {/*----- File Numbers -----*/}
            {(isBrowser || isReview || isGallery || isSearch || isAudioVideo || isSlider) &&
                <div className="settings-field">

                    <h4 className="settings-field-label">{wp.i18n.__("Maximum File Numbers", "integrate-google-drive")}</h4>

                    <div className="settings-field-content">
                        <input type="number"
                               min={1}
                               max={1000}
                               value={fileNumbers}
                               onChange={(e) => setEditData({...editData, fileNumbers: e.target.value})}
                        />
                        <p className="description">{wp.i18n.__("Enter the maximum number of how many files you want to show. Leave empty to show all files.", "integrate-google-drive")}</p>
                    </div>
                </div>
            }

            {isUploader &&
                <>
                    {/*----- Max Files -----*/}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Max File Uploads", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <input type="number"
                                   min={1}
                                   value={maxFiles}
                                   onChange={(e) => {
                                       let value = e.target.value;
                                       //remove any non-numeric characters
                                       value = value.replace(/[^0-9]/g, '');
                                       setEditData({...editData, maxFiles: value})
                                   }}
                            />

                            <p className="description">{wp.i18n.__('Enter the max number of files to upload at once. Leave empty for no limit.', 'integrate-google-drive')}</p>
                        </div>
                    </div>

                    {/*----- Min Files -----*/}
                    {(isFormBuilder && 'metform' !== isFormBuilder) &&
                        <div className="settings-field">
                            <h4 className="settings-field-label">{wp.i18n.__("Min File Uploads", "integrate-google-drive")}</h4>

                            <div className="settings-field-content">

                                <input type="number"
                                       min={1}
                                       value={minFiles}
                                       onChange={(e) => {
                                           let value = e.target.value;
                                           //remove any non-numeric characters
                                           value = value.replace(/[^0-9]/g, '');
                                           setEditData({...editData, minFiles: value})
                                       }}
                                />

                                <p className="description">{wp.i18n.__('Enter the minimum number of files to upload. Leave empty for no limit.', 'integrate-google-drive')}</p>
                            </div>
                        </div>
                    }

                    {/*----- Max File Size -----*/}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Max File Size (MB)", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <input type="number"
                                   min={0}
                                   value={maxFileSize}
                                   onChange={(e) => {
                                       let value = e.target.value;
                                       //remove any non-numeric characters
                                       value = value.replace(/[^0-9]/g, '');
                                       setEditData({...editData, maxFileSize: value})
                                   }}
                            />

                            <p className="description">{wp.i18n.__('Enter the maximum upload file size (MB).', 'integrate-google-drive')}</p>
                        </div>
                    </div>

                    {/*----- Min File Size -----*/}
                    <div className="settings-field">
                        <h4 className="settings-field-label">{wp.i18n.__("Min File Size (MB)", "integrate-google-drive")}</h4>

                        <div className="settings-field-content">

                            <input type="number"
                                   min={0}
                                   value={minFileSize}
                                   onChange={(e) => {
                                       let value = e.target.value;
                                       //remove any non-numeric characters
                                       value = value.replace(/[^0-9]/g, '');
                                       setEditData({...editData, minFileSize: value})
                                   }}
                            />

                            <p className="description">{wp.i18n.__('Enter the minimum upload file size (MB).', 'integrate-google-drive')}</p>
                        </div>
                    </div>
                </>
            }

        </div>
    )
}