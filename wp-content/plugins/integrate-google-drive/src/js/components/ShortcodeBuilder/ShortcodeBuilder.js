import {ShortcodeBuilderProvider} from "../../contexts/ShortcodeBuilderContext";
import Header from './Header'
import List from "./List";
import Placeholder from "./Placeholder";
import Form from "./Form";
import Types from "./Form/Types";
import Modal from "../../includes/Modal";

import {base64Encode, getIdParam, removeIdParam, useMounted} from "../../includes/functions";

const {useState, useEffect, useRef} = React;

export default function ShortcodeBuilder({isModuleBuilder, isFormBuilder, isWooCommerce, editId, onUpdate, addType}) {

    const [edit, setEdit] = useState(editId);

    let initShortcodes = igd.shortcodes || [];

    if (isFormBuilder || isWooCommerce) {
        initShortcodes = initShortcodes.filter(item => ['browser', 'uploader'].includes(item.type));
    }

    if( isModuleBuilder?.userId) {
        initShortcodes = initShortcodes.filter(item => item.user_id === isModuleBuilder.userId);
    }

    const [shortcodes, setShortcodes] = useState(initShortcodes);

    const [updating, setUpdating] = useState(false);
    const [loading, setLoading] = useState(false);

    const [perPage, setPerPage] = useState(localStorage.getItem('igd_shortcode_list_per_page') || 20);
    const [sortBy, setSortBy] = useState(localStorage.getItem('igd_shortcode_list_sort_by') || 'id');
    const [sortOrder, setSortOrder] = useState(localStorage.getItem('igd_shortcode_list_sort_order') || 'desc');

    const [page, setPage] = useState(1);

    const prevDataRef = useRef(null);

    const [editData, setEditData] = useState(null);
    const [isDirty, setIsDirty] = useState(false);
    const [openTypeModal, setOpenTypeModal] = useState(addType);

    const getShortcodes = () => {
        setLoading(true);

        return wp.ajax.post('igd_get_shortcodes', {
            nonce: igd.nonce,
        }).done((response) => {
            let shortcodes = response.shortcodes || [];

            if (isFormBuilder || isWooCommerce) {
                shortcodes = shortcodes.filter(item => ['browser', 'uploader'].includes(item.type));
            }

            setShortcodes(shortcodes);
        }).fail((error) => {
            console.log(error);
        }).always(() => {
            setLoading(false);
        });
    }

    // Load initializes shortcodes
    useEffect(() => {
        if (!isModuleBuilder && getIdParam()) {
            setEdit(getIdParam());
        }
    }, []);

    // Update editData
    useEffect(() => {

        if (!edit || !shortcodes.length) {
            setEditData(null);
            return;
        }

        const found = shortcodes.find(item => item.id == edit);

        if (!found) {
            setEdit(false);
            return;
        }

        const editData = {
            ...found['config'],
            id: parseInt(edit)
        }

        setEditData(editData);

        prevDataRef.current = editData;

    }, [shortcodes, edit]);

    /**
     * Send only the config data as param
     *
     * @param shortcodeData
     */
    const updateShortcode = (shortcodeData) => {

        setUpdating(true);

        // If not isNew
        if (shortcodeData.id) {

            // Update shortcode list
            const index = shortcodes.findIndex(item => item.id == shortcodeData.id);
            shortcodes[index]['config'] = shortcodeData;
            shortcodes[index]['status'] = shortcodeData?.status || 'on';
            shortcodes[index]['title'] = shortcodeData.title;

            setShortcodes(shortcodes);

            setEditData({...shortcodeData});
            prevDataRef.current = {...shortcodeData};
        }

        const encodedData = base64Encode(JSON.stringify(shortcodeData));

        return wp.ajax.post('igd_update_shortcode', {
            data: encodedData,
            isModuleBuilder,
            nonce: igd.nonce,
        }).done((response) => {

            // If is new
            if (!shortcodeData.id) {
                const {id, config} = response;

                const newShortcodes = [response, ...shortcodes];
                setShortcodes(newShortcodes);

                setOpenTypeModal(false);
                setEdit(id);
                setEditData(config);
                prevDataRef.current = config;

                return;
            } else if (typeof onUpdate === 'function') {
                onUpdate(shortcodeData.id, response);
            }

            if (!isModuleBuilder) {
                Swal.fire({
                    text: wp.i18n.__("Module has been updated.", "integrate-google-drive"),
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    customClass: {container: 'igd-swal save-settings-toast'},
                });
            }

        }).fail((error) => {
            console.error('Module update failed:', error);

            Swal.fire({
                text: wp.i18n.__("Something went wrong.", "integrate-google-drive"),
                icon: 'error',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            });
        }).always(() => {
            setUpdating(false);
        });
    }

    const duplicateShortcode = ids => {
        setUpdating(true);

        return wp.ajax.post('igd_duplicate_shortcode', {
            ids,
            nonce: igd.nonce,
        }).done((data) => {
            setShortcodes([...shortcodes, ...data]);

            Swal.fire({
                title: wp.i18n.__("Duplicated!", "integrate-google-drive"),
                text: wp.i18n.__("Module has been duplicated.", "integrate-google-drive"),
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            });
        }).fail(error => {
            console.log(error);

            Swal.fire({
                title: wp.i18n.__("Error!", "integrate-google-drive"),
                text: wp.i18n.__("Something went wrong.", "integrate-google-drive"),
                icon: 'error',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            });

        }).always(() => {
            setUpdating(false);
        });
    }

    const deleteShortcode = (id, isEdit = false) => {
        return wp.ajax.post('igd_delete_shortcode', {
            id,
            nonce: igd.nonce,
        }).done(() => {

            setShortcodes(shortcodes => shortcodes.filter(item => item.id != id));

            if (isEdit) {
                removeIdParam();
                setEdit(false);
            }

            Swal.fire({
                title: wp.i18n.__("Module has been deleted", "integrate-google-drive"),
                icon: 'success',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
                toast: true,
            });

        });
    }

    const isMounted = useMounted();

    // Update page title on edit
    useEffect(() => {

        if (isModuleBuilder) return;

        if (editData?.id) {
            document.title = wp.i18n.__('Edit Module', 'integrate-google-drive') + ' - ' + editData.title;
        } else {
            document.title = wp.i18n.__('All Modules - Integrate Google Drive', 'integrate-google-drive');
        }

    }, [edit, editData]);

    // Handle enter key to update shortcode
    useEffect(() => {

        const handleEnter = (e) => {
            // Prevent unintended triggers
            if (e.defaultPrevented) return;

            // Trigger only on Enter key, and if no text input is focused
            const isInputFocused = ['TEXTAREA', 'INPUT'].includes(e.target.tagName);
            if (e.key === 'Enter' && !isInputFocused) {
                e.preventDefault();
                updateShortcode(editData);
            }
        };

        // Add keydown listener
        document.addEventListener('keydown', handleEnter);

        // Clean up on unmount or dependency change
        return () => {
            document.removeEventListener('keydown', handleEnter);
        }

    }, [editData]);

    // Check if is changed
    useEffect(() => {
        if (!isMounted) return;

        setIsDirty(JSON.stringify(editData) !== JSON.stringify(prevDataRef.current));
    }, [editData, prevDataRef.current]);

    return (
        <ShortcodeBuilderProvider
            value={{
                isModuleBuilder,
                isFormBuilder,
                isWooCommerce,
                editId,
                onUpdate,

                shortcodes,
                setShortcodes,
                edit,
                setEdit,
                editData,
                setEditData,
                updating,
                isDirty,
                perPage,
                setPerPage,
                page,
                setPage,
                updateShortcode,
                deleteShortcode,
                duplicateShortcode,
                getShortcodes,
                sortBy,
                setSortBy,
                sortOrder,
                setSortOrder,

                openTypeModal,
                setOpenTypeModal,
            }}
        >
            <div className={`igd-shortcode-builder ${isModuleBuilder ? 'module-builder' : ''}`}>

                <Modal
                    isOpen={openTypeModal}
                    onClose={() => {

                        if ('new' === openTypeModal && !edit) {
                            Swal.close();
                        }

                        setOpenTypeModal(false);
                    }}
                    className={`module-types-modal ${openTypeModal === 'init' ? 'modal-init' : (openTypeModal === 'new' ? 'modal-new' : (editId ? 'modal-update' : ''))} ${isFormBuilder || isModuleBuilder?.type === 'woocommerce' ? 'form-builder-modal' : ''}`}
                    target={document.querySelector('.igd-shortcode-builder')?.parentElement || document.body}
                >
                    <Types/>
                </Modal>

                {/*--- Header ---*/}
                <Header/>

                {loading && <div className="igd-spinner spinner-large"></div>}

                {/* List */}
                {!loading && !edit && !!shortcodes.length && <List/>}

                {/* Module Placeholder */}
                {!loading && !edit && !shortcodes.length && <Placeholder setOpenTypeModal={setOpenTypeModal}/>}

                {/* Module Form */}
                {!loading && !!edit && !!editData && <Form/>}

            </div>
        </ShortcodeBuilderProvider>
    )
}