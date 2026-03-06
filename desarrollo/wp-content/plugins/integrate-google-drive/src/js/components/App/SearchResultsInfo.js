import AppContext from "../../contexts/AppContext";

const {useContext} = React;

export default function SearchResultsInfo() {
    const {files, searchKeywordRef} = useContext(AppContext);

    const searchInfo = files.length ? files.length : wp.i18n.__('No', 'integrate-google-drive');

    const infoMessage = wp.i18n.sprintf(
        wp.i18n.__('Search results for "%s": %s items found.', 'integrate-google-drive'),
        `<strong>${searchKeywordRef.current}</strong>`,
        searchInfo
    );

    return (
        <div className="search-result-info">

            <span dangerouslySetInnerHTML={{__html: infoMessage}}/>

            <button
                onClick={(e) => {
                    let parent = e.target.closest('.igd-file-browser');
                    let searchDismiss = parent.querySelector('.search-dismiss');

                    if (searchDismiss) {
                        searchDismiss.click();
                    }
                }}
                className="clear-button igd-btn btn-warning">
                {wp.i18n.__('Clear', 'integrate-google-drive')}
            </button>
        </div>
    );
}