import ReactSelect from "react-select";
import {animation, Item, Menu, Submenu, useContextMenu} from "react-contexify";
import {Tooltip} from "react-tooltip";
import ShortcodeBuilderContext from "../../contexts/ShortcodeBuilderContext";
import {copyShortcode, copyViewUrl, moduleTypes, removeIdParam} from "../../includes/functions";

const {useContext, useEffect} = React;
const {Button, ButtonGroup} = wp.components;

const MENU_ID = "embed-context-menu";

export default function Header() {

    const context = useContext(ShortcodeBuilderContext);

    const {
        isModuleBuilder,
        edit,
        setEdit,
        editData,
        setEditData,
        updateShortcode,
        updating,
        isDirty,
        setOpenTypeModal,
        onUpdate,
    } = context;

    const {
        id,
        title,
        type,
    } = editData || {};

    const {show} = useContextMenu({id: MENU_ID});

    useEffect(() => {
        if (edit) {
            document.getElementById("shortcode-title")?.focus();
        }
    }, [edit]);

    // Handle title change
    useEffect(() => {
        const input = document.getElementById("shortcode-title");

        if (input && input?.innerText !== title) {
            input.innerText = title;
        }

    }, [title]);

    const handleBack = () => {
        removeIdParam();
        setEdit(null);
    };

    const handleSave = () => {
        const url = new URL(window.location.href);
        url.searchParams.delete("id");
        window.history.pushState({}, "", url);
        updateShortcode(editData);
    };

    const selectPage = async () => {
        try {
            const pages = await wp.ajax.post('igd_get_pages', {nonce: igd.nonce});

            const options = pages.map((page) => ({
                value: page.id,
                label: page.title,
            }));

            let selectedPageId = null;

            Swal.fire({
                title: wp.i18n.__("Select a Page", "integrate-google-drive"),
                html: `
                        <p>${wp.i18n.__("Select a page to embed the shortcode.", "integrate-google-drive")}</p>
                        <div id="react-select-container"></div>
                      `,
                didOpen: () => {
                    const container = document.getElementById('react-select-container');

                    if (container) {
                        const root = wp.element.createRoot(container);
                        root.render(
                            wp.element.createElement(ReactSelect, {
                                options,
                                onChange: (selected) => {
                                    selectedPageId = selected?.value;
                                },
                                placeholder: wp.i18n.__('Search and select a page...', 'integrate-google-drive'),
                                isSearchable: true,
                                className: 'igd-select',
                                menuPortalTarget: document.body,
                                styles: {
                                    container: (base) => ({
                                        ...base,
                                        width: '100%',
                                        zIndex: 10000,
                                    }),
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 999999,
                                    }),
                                },
                            })
                        );
                    }
                },
                preConfirm: () => {
                    if (!selectedPageId) {
                        Swal.showValidationMessage(wp.i18n.__("Please select a page.", "integrate-google-drive"));
                        return false;
                    }

                    window.location.href = `${igd.adminUrl}/post.php?post=${selectedPageId}&action=edit`;
                },
                confirmButtonText: wp.i18n.__("Let's Go", "integrate-google-drive"),
                showCloseButton: true,
                customClass: {
                    container: "igd-swal no-icon",
                },
            });
        } catch (err) {
            Swal.showValidationMessage(wp.i18n.__("Please select a page.", "integrate-google-drive"));
        }
    }

    const createPage = () => {
        Swal.fire({
            title: wp.i18n.__("Create New Page", "integrate-google-drive"),
            text: wp.i18n.__("Enter the title for the new page.", "integrate-google-drive"),
            input: 'text',
            inputPlaceholder: wp.i18n.__("Enter page title", "integrate-google-drive"),
            confirmButtonText: wp.i18n.__("Let's Go", "integrate-google-drive"),
            showCloseButton: true,
            customClass: {
                container: "igd-swal no-icon",
            },
            preConfirm: (title) => {

                const data = {
                    title,
                    id,
                    nonce: igd.nonce,
                };

                return wp.ajax.post('igd_create_page', data).then((response) => {

                    window.location.href = `${igd.adminUrl}/post.php?post=${response.id}&action=edit`;

                }).catch((err) => {
                    console.log(err);

                    Swal.showValidationMessage(wp.i18n.__("Failed to create page.", "integrate-google-drive"));
                });


            }
        });
    }

    return (
        <div className={`igd-shortcode-builder-header ${edit ? "is-edit" : ""}`}>

            {!!edit && (
                <button type="button" className="btn-back" onClick={handleBack}>
                    <i className="dashicons dashicons-arrow-left-alt2"></i>
                    <span>{wp.i18n.__("Back", "integrate-google-drive")}</span>
                </button>
            )}

            <div className="header-title">

                <img
                    src={`${igd.pluginUrl}/assets/images/shortcode-builder/shortcode-icon.svg`}
                    alt="Module Builder"
                    className={`header-icon ${edit ? "edit" : ""}`}
                />

                {!edit ? (
                    <span>{wp.i18n.__("Module Builder", "integrate-google-drive")}</span>
                ) : (
                    <>
                        <label htmlFor="shortcode-title">{wp.i18n.__("Edit Module", "integrate-google-drive")}</label>

                        <div className="input-wrap">

                            {!!type && (
                                <img
                                    className={`module-type-icon type-${type}`}
                                    src={`${igd.pluginUrl}/assets/images/shortcode-builder/types/${type}.svg`}
                                    alt={moduleTypes()[type]?.title}
                                    onClick={() => setOpenTypeModal('update')}
                                />
                            )}

                            <Tooltip
                                anchorSelect=".inxput-wrap img"
                                place="left"
                                className="igd-tooltip"
                                content={moduleTypes()[type]?.title}
                            />

                            <span
                                id="shortcode-title"
                                className="shortcode-title"
                                contentEditable
                                suppressContentEditableWarning={true} // suppress React warning
                                spellCheck={false}
                                onInput={(e) => {

                                    setEditData((prev) => ({
                                        ...prev,
                                        title: e.target?.innerText?.trim() || '',
                                    }))

                                }}
                            />
                        </div>
                    </>
                )}
            </div>

            <div className="header-actions">

                {/*--- Cancel Button ---*/}
                {isModuleBuilder && (
                    <button
                        type="button"
                        className="igd-btn btn-danger btn-cancel"
                        onClick={() => {
                            setEdit(false);
                            Swal.close()
                        }}
                    >
                        <i className="dashicons dashicons-no-alt"></i>
                        <span>{wp.i18n.__("Cancel", "integrate-google-drive")}</span>
                    </button>
                )}

                {edit ? (
                    <>
                        {/*--- Usage ---*/}
                        <>
                            <ButtonGroup
                                className="igd-btn-group"
                                aria-label={wp.i18n.__("Module Actions", "integrate-google-drive")}
                            >
                                <Button
                                    variant="primary"
                                    size={'default'}
                                    icon={`visibility`}
                                    iconSize={20}
                                    className="view-module-btn"
                                    text={wp.i18n.__("View", "integrate-google-drive")}
                                    label={wp.i18n.__("View Module", "integrate-google-drive")}
                                    onClick={() => {
                                        window.open(`${igd.homeUrl}/igd-modules/${id}`, `module-${id}`);
                                    }}
                                />

                                {!isModuleBuilder &&
                                    <Button
                                        variant="primary"
                                        size={'default'}
                                        className="embed-btn"
                                        icon={`embed-post`}
                                        iconSize={20}
                                        text={wp.i18n.__("Embed", "integrate-google-drive")}
                                        label={wp.i18n.__("Embed Module", "integrate-google-drive")}
                                        onClick={e => show(e)}
                                    />
                                }

                            </ButtonGroup>

                            {/*--- Embed Context Menu ---*/}
                            <Menu
                                id={MENU_ID}
                                animation={animation.fade}
                                className={"igd-context-menu embed-context-menu"}
                            >
                                <Item
                                    onClick={() => copyViewUrl(id)}
                                    className={`context-menu-item`}
                                >
                                    <i className="dashicons dashicons-admin-links"></i>
                                    {wp.i18n.__("Copy View URL", "integrate-google-drive")}
                                </Item>

                                <Item
                                    onClick={() => copyShortcode(id)}
                                    className={`context-menu-item`}
                                >
                                    <i className="dashicons dashicons-shortcode"></i>
                                    {wp.i18n.__("Copy Shortcode", "integrate-google-drive")}
                                </Item>

                                <Item className={`context-menu-item`}>
                                    <Submenu
                                        label={
                                            <>
                                                <i className="dashicons dashicons-welcome-add-page"></i>
                                                {wp.i18n.__("Embed in Page", "integrate-google-drive")}
                                            </>
                                        }
                                        className="context-submenu"
                                        arrow={<i className={`dashicons dashicons-arrow-right`}></i>}
                                    >

                                        <Item
                                            onClick={selectPage}
                                            className="context-menu-item"
                                        >
                                            <i className="dashicons dashicons-edit-page"></i>
                                            {wp.i18n.__("Select Existing Page", "integrate-google-drive")}
                                        </Item>


                                        <Item
                                            onClick={createPage}
                                            className="context-menu-item"
                                        >
                                            <i className="dashicons dashicons-plus-alt2"></i>
                                            {wp.i18n.__("Create New Page", "integrate-google-drive")}
                                        </Item>


                                    </Submenu>
                                </Item>
                            </Menu>
                        </>

                        {/*--- Save ---*/}
                        <button
                            type="button"
                            className={`igd-btn btn-save  ${!isModuleBuilder && !isDirty ? "btn-secondary" : "btn-primary"}`}
                            onClick={() => {

                                if (isDirty) {
                                    return handleSave();
                                }

                                if (isModuleBuilder) {
                                    onUpdate(id, editData);
                                    setEdit(false);
                                    Swal.close();
                                } else {
                                    handleBack();
                                }
                            }}
                        >
                            {updating ? (
                                <div className="igd-spinner"></div>
                            ) : (
                                (isModuleBuilder || isDirty) ?
                                    <i className="dashicons dashicons-saved"></i>
                                    :
                                    <i className="dashicons dashicons-arrow-left-alt2"></i>
                            )}

                            <span>
                                {isDirty
                                    ? wp.i18n.__("Save", "integrate-google-drive")
                                    : isModuleBuilder
                                        ? wp.i18n.__("Done", "integrate-google-drive")
                                        : wp.i18n.__("Back", "integrate-google-drive")}
                            </span>
                        </button>
                    </>
                ) : (
                    <button
                        type="button"
                        className="igd-btn btn-primary add-new-btn"
                        onClick={() => setOpenTypeModal('new')}
                    >
                        {updating ? (
                            <div className="igd-spinner"></div>
                        ) : (
                            <i className="dashicons dashicons-plus"></i>
                        )}
                        <span>{wp.i18n.__("Add New Module", "integrate-google-drive")}</span>
                    </button>
                )
                }
            </div>
        </div>
    );
}
