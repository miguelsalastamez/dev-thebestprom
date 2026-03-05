import {useState, useEffect, useRef} from "react";
import {getThumb, humanFileSize} from "../../includes/functions";

export default function ImportModal({files}) {
    const filesRef = useRef([...files]);

    const [inProgress, setInProgress] = useState(null);
    const [completed, setCompleted] = useState(new Set());

    const importFile = (file) => {
        if (!file) return;

        setInProgress(file.id);

        wp.ajax.post("igd_import_media", {
            file,
            nonce: igd.nonce,
        }).done(response => {
            setCompleted((prev) => new Set([...prev, file.id]));
        }).fail(response => {
            console.error(response);
        }).always(() => {
            filesRef.current = filesRef.current.filter((f) => f.id !== file.id);
            setInProgress(null);

            if (filesRef.current.length > 0) {
                importFile(filesRef.current[0]);
            }
        });

    }

    // Initialize the import process
    useEffect(() => {
        if (filesRef.current.length > 0) {
            importFile(filesRef.current[0]);
        }
    }, []);

    useEffect(() => {
        if (filesRef.current.length === 0 && completed.size > 0) {
            Swal.fire({
                title: wp.i18n.__("Import Successful!", "integrate-google-drive"),
                text: wp.i18n.sprintf(
                    wp.i18n._n(
                        "The file has been added to your media library.",
                        "%d files have been added to your media library.",
                        completed.size,
                        "integrate-google-drive"
                    ),
                    completed.size
                ),
                icon: "success",
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                toast: true,
                customClass: {container: "igd-swal"},
            });
        }
    }, [completed]);

    const cancelImport = () => {
        if (!inProgress) return;

        const file = files.find((f) => f.id === inProgress);

        setInProgress(null);

        wp.ajax.post("igd_cancel_import", {
            file,
            nonce: igd.nonce,
        }).fail(response => {
            console.error(response)
        });
    }

    // listen cancel import
    useEffect(() => {
        document.addEventListener('igd_import_cancel', cancelImport);

        return () => {
            document.removeEventListener('igd_import_cancel', cancelImport);
        }

    }, [inProgress]);

    return (
        <div className="import-files">
            {files.map((file) => (
                <div key={file.id} className="import-file">
                    <div className="import-file-thumb">
                        <img src={getThumb(file)} alt={file.name}/>
                    </div>
                    <div className="import-file-info">
                        <div className="import-file-name">{file.name}</div>
                        <div className="import-file-size">{humanFileSize(file.size)}</div>
                    </div>
                    <div className="import-file-actions">

                        {inProgress === file.id && <div className="igd-spinner"></div>}

                        {completed.has(file.id) && <i className="dashicons dashicons-saved"></i>}

                        {!completed.has(file.id) && (
                            <button
                                type="button"
                                className="button button-link-delete"
                                onClick={(e) => {

                                    // add hidden class to the .import-file
                                    e.target.closest('.import-file').classList.add('igd-hidden');

                                    filesRef.current = filesRef.current.filter((f) => f.id !== file.id);

                                    if (inProgress === file.id) {
                                        cancelImport();

                                        if (filesRef.current.length > 0) {
                                            importFile(filesRef.current[0]);
                                        }
                                    }

                                }}
                            >
                                <i className="dashicons dashicons-no"></i>
                            </button>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}

export function handleImport(files) {

    Swal.fire({
        title: wp.i18n.sprintf(wp.i18n._n('Importing %d file', 'Importing %d files', files.length, 'integrate-google-drive'), files.length),
        text: wp.i18n.__('Please wait...', 'integrate-google-drive'),
        html: '<div id="igd-import"></div>',
        showConfirmButton: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        allowOutsideClick: false,
        customClass: {container: 'igd-swal igd-import-swal'},
        showCloseButton: true,
        didOpen: () => {
            const element = document.getElementById('igd-import');

            if (element) {
                ReactDOM.render(<ImportModal files={files}/>, element);
            }
        },
        willClose: () => {
            // trigger import_cancel custom js event
            const event = new CustomEvent('igd_import_cancel');
            document.dispatchEvent(event);
        }
    });
}
