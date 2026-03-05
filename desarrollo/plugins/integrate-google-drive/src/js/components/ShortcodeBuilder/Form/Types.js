import ShortcodeBuilderContext from "../../../contexts/ShortcodeBuilderContext";
import {moduleTypes} from "../../../includes/functions";
import {showProModal} from "../../../includes/ProModal";

const {useContext} = React;

export default function Types() {

    const context = useContext(ShortcodeBuilderContext);

    const {
        updating,
        editData,
        edit,
        setEditData,
        openTypeModal,
        setOpenTypeModal,
        updateShortcode,
        isFormBuilder,
        isModuleBuilder,
        isWooCommerce,
    } = context;

    let types = moduleTypes();

    if (isFormBuilder || isWooCommerce) {
        // Filter out types that are not applicable for form builder
        types = Object.fromEntries(Object.entries(types).filter(([key]) => ['browser', 'uploader'].includes(key)));
    }

    const {type} = editData || {};

    return (
        <div className="igd-module-types-wrap">

            <div className="module-types-header">
                <h3>{wp.i18n.__('Select Module Type', 'integrate-google-drive')}</h3>

                {edit ?
                    <p>{wp.i18n.__('Select your preferred module type.', 'integrate-google-drive')}</p>
                    :
                    <p>{wp.i18n.__('Select a module type to get started.', 'integrate-google-drive')}</p>
                }

                <i className="module-types-close dashicons dashicons-no-alt"
                   onClick={() => {

                       if ('init' === openTypeModal && !edit) {
                           Swal.close();
                       }

                       setOpenTypeModal(false);
                   }}
                ></i>
            </div>

            <div className="igd-module-types">

                {Object.keys(types).map(key => {

                    const isActive = key === type;

                    const {title, isPro, description} = types[key];

                    const isProModule = isPro && !igd.isPro && ('cf7' !== isModuleBuilder?.type || 'uploader' !== key);

                    return (
                        <div
                            key={key}
                            className={`module-type ${isProModule ? 'pro-feature' : ''} ${isActive ? 'active' : ''} ${updating ? 'loading' : ''}`}
                            onClick={() => {

                                if (isProModule) {
                                    showProModal(wp.i18n.__('Upgrade to PRO to use this module.', 'integrate-google-drive'));
                                    return;
                                }

                                if (isActive) return;

                                if (edit) {
                                    setEditData({...editData, type: key});
                                    setOpenTypeModal(false);
                                } else {
                                    updateShortcode({type: key});
                                }

                            }}
                        >
                            {isProModule &&
                                <div className="pro-badge">
                                    <i className="dashicons dashicons-lock"></i>
                                    <span>{wp.i18n.__('PRO', 'integrate-google-drive')}</span>
                                </div>
                            }

                            <div className={`icon-wrap icon-${key}`}>
                                <img className={`type-${key}`}
                                     src={`${igd.pluginUrl}/assets/images/shortcode-builder/types/${key}.svg`}
                                     alt={title}/>
                            </div>

                            <div className="type-meta">
                                <h4 className={`type-title`}>{title}</h4>
                                <p className="description">{description}</p>
                            </div>

                        </div>
                    )
                })}
            </div>

        </div>

    )
}