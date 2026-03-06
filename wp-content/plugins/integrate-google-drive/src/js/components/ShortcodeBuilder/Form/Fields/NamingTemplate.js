const {useState, useMemo, useRef} = React;

const {Button} = wp.components;

let tagCategories = {
    'File Tags': [
        '%file_name%', '%file_extension%', '%queue_index%'
    ],

    'User Tags': [
        '%user_login%', '%user_email%', '%first_name%', '%last_name%', '%display_name%', '%user_id%', '%user_role%', '%user_meta_{key}%'
    ],

    'Post Tags': [
        '%post_id%', '%post_title%', '%post_slug%', '%post_author%', '%post_date%', '%post_modified%', '%post_type%', '%post_status%'
    ],

    'WooCommerce Tags': [
        '%wc_product_name%', '%wc_product_id%', '%wc_product_sku%', '%wc_product_price%', '%wc_product_sale_price%', '%wc_product_regular_price%', '%wc_product_tags%', '%wc_product_type%', '%wc_product_status%'
    ],

    'Form Tags': [
        '%entry_id%',
        '%form_id%',
        '%form_title%',
    ],

    'System Tags': [
        '%date%', '%time%', '%unique_id%'
    ],

};

const tagDescriptions = {
    // Post Tags
    '%post_id%': 'ID of the current post',
    '%post_title%': 'Title of the current post',
    '%post_slug%': 'Slug (URL-friendly title) of the post',
    '%post_author%': 'Author of the post',
    '%post_date%': 'Date when the post was published',
    '%post_modified%': 'Date when the post was last modified',
    '%post_type%': 'Post type (e.g., post, page)',
    '%post_status%': 'Status of the post (e.g., publish, draft)',

    // WooCommerce Tags
    '%wc_product_name%': 'Product name from WooCommerce',
    '%wc_product_id%': 'WooCommerce product ID',
    '%wc_product_sku%': 'Product SKU (stock-keeping unit)',
    '%wc_product_price%': 'Current price of the product',
    '%wc_product_sale_price%': 'Sale price of the product',
    '%wc_product_regular_price%': 'Regular price before discount',
    '%wc_product_tags%': 'Tags assigned to the product',
    '%wc_product_type%': 'Type of WooCommerce product (e.g., simple, variable)',
    '%wc_product_status%': 'Status of the product (e.g., publish, draft)',

    // User Tags
    '%user_login%': 'Username of the logged-in user',
    '%user_email%': 'Email address of the logged-in user',
    '%first_name%': 'First name of the user',
    '%last_name%': 'Last name of the user',
    '%display_name%': 'Display name of the user',
    '%user_id%': 'User ID of the logged-in user',
    '%user_role%': 'Role of the user (e.g., administrator, subscriber)',
    '%user_meta_{key}%': 'Custom user meta field (replace {key} with meta key)',

    // File Tags
    '%file_name%': 'Original uploaded file name (without extension)',
    '%file_extension%': 'File extension (e.g., .jpg, .pdf)',
    '%queue_index%': 'Index of the file in the upload queue',

    // Form Tags
    '%entry_id%': 'Unique ID of the submitted form entry',
    '%form_id%': 'ID of the current form',
    '%form_title%': 'Title of the form being submitted',

    // System Tags
    '%date%': 'Current date (YYYY-MM-DD)',
    '%time%': 'Current time (HH-MM)',
    '%unique_id%': 'Random unique identifier for the file',

    // Email Tags
    '%admin_email%': 'Email address of the site administrator',
    '%linked_user_email%': 'Email address of the user linked to the folder',
};

const previewTagValueMap = {
    '%post_id%': '123',
    '%post_title%': 'My Post',
    '%post_slug%': 'my-post',
    '%post_author%': 'admin',
    '%post_date%': '2025-06-09',
    '%post_modified%': '2025-06-08',
    '%post_type%': 'post',
    '%post_status%': 'publish',

    '%wc_product_name%': 'Sample Product',
    '%wc_product_id%': '987',
    '%wc_order_id%': '999',
    '%wc_product_sku%': 'SKU-001',
    '%wc_product_price%': '$25.00',
    '%wc_product_sale_price%': '$20.00',
    '%wc_product_regular_price%': '$30.00',
    '%wc_product_tags%': 'tag1, tag2',
    '%wc_product_type%': 'simple',
    '%wc_product_status%': 'published',

    '%user_login%': 'johndoe',
    '%user_email%': 'user@example.com',
    '%first_name%': 'John',
    '%last_name%': 'Doe',
    '%display_name%': 'John Doe',
    '%user_id%': '42',
    '%user_role%': 'editor',

    '%file_name%': 'myfile',
    '%file_extension%': '.pdf',
    '%queue_index%': '001',

    '%entry_id%': '55901',
    '%form_id%': '77',
    '%form_title%': 'Contact Us Form',

    '%date%': '2025-06-09',
    '%time%': '12-00',
    '%unique_id%': 'abc123',

    '%admin_email%': '',
    '%linked_user_email%': '',
};

export default function NamingTemplate({value, onUpdate, type = 'file',}) {

    const {isPro} = igd;

    const [openTags, setOpenTags] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [cursorPosition, setCursorPosition] = useState(null);
    const inputRef = useRef(null);

    // Remove file tags if is folder type
    if (['folder', 'wc_folder'].includes(type)) {
        delete tagCategories['File Tags'];
    }

    if (['wc_file', 'wc_folder'].includes(type)) {
        delete tagCategories['Form Tags'];
    }

    if (['notifications'].includes(type)) {
        tagCategories = {
            'Email Tags': [
                '%admin_email%',
                '%user_email%',
                '%linked_user_email%',
            ]
        }

    }

    const filteredTags = useMemo(() => {
        if (!searchTerm) return tagCategories;
        const lower = searchTerm.toLowerCase();

        const result = {};

        for (const category in tagCategories) {
            const filtered = tagCategories[category].filter(tag => tag.toLowerCase().includes(lower));
            if (filtered.length) result[category] = filtered;
        }

        return result;
    }, [searchTerm]);

    const preview = useMemo(() => {

        return value.replace(/%[^%]+%/g, (match) => {
            // Handle custom field tags: %field_some-field%
            const fieldMatch = match.match(/^%field_(.+?)%$/);
            if (fieldMatch) {
                return `[${fieldMatch[1]}-value]`;
            }

            // Handle user meta: %user_meta_key%
            const userMetaMatch = match.match(/^%user_meta_(.+?)%$/);
            if (userMetaMatch) {
                return `[meta-${userMetaMatch[1]}]`;
            }

            return previewTagValueMap[match] ?? match;
        });

    }, [value]);

    const insertTag = (tag) => {
        let updatedFileName;

        if (cursorPosition !== null) {
            updatedFileName = value.slice(0, cursorPosition) + tag + ' ' + value.slice(cursorPosition);
        } else {
            updatedFileName = value + ' ' + tag;
        }

        updatedFileName = updatedFileName.trim();

        onUpdate(updatedFileName);

        const newCursorPosition = cursorPosition + tag.length + 1;
        requestAnimationFrame(() => {
            inputRef.current.setSelectionRange(newCursorPosition, newCursorPosition);
        });

    };

    let placeholder = '';
    if (['file', 'wc_file'].includes(type)) {
        placeholder = wp.i18n.__("Enter file name template", "integrate-google-drive");
    } else if (['folder', 'wc_folder'].includes(type)) {
        placeholder = wp.i18n.__("Enter folder name template", "integrate-google-drive");
    } else if ('search' === type) {
        placeholder = wp.i18n.__("Enter initial search term", "integrate-google-drive");
    } else if ('notifications' === type) {
        placeholder = wp.i18n.__("Enter the notifications recipients", "integrate-google-drive");
    }

    return (
        <div className={`settings-field file-rename-config type-${type}`}>

            <h4 className={`settings-field-label`}>
                {['file', 'wc_file'].includes(type) && wp.i18n.__("File Name Template", "integrate-google-drive")}
                {['folder', 'wc_folder'].includes(type) && wp.i18n.__("Folder Name Template", "integrate-google-drive")}
                {'search' === type && wp.i18n.__("Initial Search Term", "integrate-google-drive")}
                {'notifications' === type && wp.i18n.__("Notifications Recipients", "integrate-google-drive")}
            </h4>

            <div className="settings-field-content">
                <input
                    type="text"
                    value={value}
                    onSelect={e => setCursorPosition(e.target.selectionStart)}
                    ref={inputRef}
                    onChange={(e) => onUpdate(e.target.value)}
                    placeholder={placeholder}
                    className="igd-tag-input"
                    disabled={!isPro}
                />

                {(!!value && !['notifications'].includes(type)) &&
                    <div className="preview">
                        <i className="dashicons dashicons-visibility"></i>
                        <strong>{wp.i18n.__('Preview:', 'integrate-google-drive')}</strong> {preview}
                    </div>
                }


                <p className="description">
                    {['wc_file'].includes(type) &&
                        wp.i18n.__("Rename the uploaded files by adding suffix or prefix.", "integrate-google-drive")
                    }

                    {'folder' === type &&
                        wp.i18n.__("Set a template for the folder name.", "integrate-google-drive")
                    }

                    {'wc_folder' === type &&
                        wp.i18n.__("Set the naming template for the upload folder. When a customer uploads a file, a new folder will be created using this name.", "integrate-google-drive")
                    }

                    {'search' === type &&
                        wp.i18n.__("Set initial search terms to trigger a search when the shortcode first loads.", "integrate-google-drive")
                    }

                    {'notifications' === type &&
                        wp.i18n.__("Enter the email address to receive notifications. To send to multiple recipients, separate each address with a comma (,).", "integrate-google-drive")
                    }
                </p>

                <h5 className="tags-heading" onClick={() => setOpenTags(!openTags)}>
                    <i className={`dashicons dashicons-arrow-${openTags ? 'up' : 'down'}-alt2`}></i>
                    {wp.i18n.__('You can also use the available dynamic placeholder tags:', 'integrate-google-drive')}
                </h5>

                {openTags &&
                    <>
                        {!['notifications'].includes(type) &&
                            <input
                                type="text"
                                placeholder="🔍 Search available tags..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className={`tag-search-input`}
                            />
                        }

                        {Object.entries(filteredTags).map(([category, tags]) => (
                            <fieldset key={category}>
                                <legend>{category}</legend>
                                <div className="tags-grid">
                                    {tags.map(tag => (
                                        <Button
                                            size={"medium"}
                                            variant="tertiary"
                                            key={tag}
                                            label={tagDescriptions[tag] || tag}
                                            text={tag}
                                            onClick={() => insertTag(tag)}
                                        />
                                    ))}
                                </div>
                            </fieldset>
                        ))}

                        {!['wc_file', 'wc_folder', 'notifications'].includes(type) &&
                            (
                                <details className="igd-field-hint">
                                    <summary>
                                        <span className="toggle-icon" aria-hidden="true"></span>
                                        <span className="dashicons dashicons-info-outline"></span>
                                        <span
                                            className="igd-summary-title">{wp.i18n.__('How to use form field values in file names', 'integrate-google-drive')}</span>

                                        <a
                                            href="https://softlabbd.com/docs/how-to-rename-uploaded-files-based-on-form-field-values/"
                                            className="igd-learn-more"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {wp.i18n.__('Learn More', 'integrate-google-drive')}
                                        </a>
                                    </summary>

                                    <div className="igd-hint-content">

                                        <section>
                                            <h4 className="igd-form-title">

                                                <img src={igd.pluginUrl + '/assets/images/settings/cf7.png'}
                                                     alt="Contact Form 7"/>
                                                <span>{wp.i18n.__('Contact Form 7', 'integrate-google-drive')}</span>

                                                <img src={igd.pluginUrl + '/assets/images/settings/fluentforms.png'}
                                                     alt="Fluent Forms"/>
                                                <span>{wp.i18n.__('Fluent Forms', 'integrate-google-drive')}</span>

                                                <img src={igd.pluginUrl + '/assets/images/settings/metform.png'}
                                                     alt="MetForm"/>
                                                <span>{wp.i18n.__('MetForm', 'integrate-google-drive')}</span>
                                            </h4>
                                            <div className="igd-hint-example">
                                                <p>{wp.i18n.__('Use the field NAME to customize the file names using form field values.', 'integrate-google-drive')}</p>

                                                <div className="igd-code-group">
                                    <span
                                        className="igd-label">{wp.i18n.__('Examples:', 'integrate-google-drive')}</span>
                                                    <code>%field_your-name%</code>
                                                    <code>%field_your-email%</code>
                                                </div>
                                            </div>
                                        </section>

                                        <section>
                                            <h4 className="igd-form-title">
                                                <img src={igd.pluginUrl + '/assets/images/settings/ninjaforms.png'}
                                                     alt="Ninja Forms"/>
                                                <span>{wp.i18n.__('Ninja Forms', 'integrate-google-drive')}</span>
                                            </h4>
                                            <div className="igd-hint-example">
                                                <p>{wp.i18n.__('Use the field KEY to customize the file names using form field values.', 'integrate-google-drive')}</p>
                                                <div className="igd-code-group">
                                    <span
                                        className="igd-label">{wp.i18n.__('Examples:', 'integrate-google-drive')}</span>
                                                    <code>%field_your-name%</code>
                                                    <code>%field_your-email%</code>
                                                </div>
                                            </div>
                                        </section>

                                        <section>
                                            <h4 className="igd-form-title">

                                                <img src={igd.pluginUrl + '/assets/images/settings/wpforms.png'}
                                                     alt="WPForms"/>
                                                <span>{wp.i18n.__('WPForms', 'integrate-google-drive')}</span>

                                                <img src={igd.pluginUrl + '/assets/images/settings/gravityforms.png'}
                                                     alt="Gravity Forms"/>
                                                <span>{wp.i18n.__('Gravity Forms', 'integrate-google-drive')}</span>

                                                <img src={igd.pluginUrl + '/assets/images/settings/elementor.png'}
                                                     alt="Elementor Forms"/>
                                                <span>{wp.i18n.__('Elementor Forms', 'integrate-google-drive')}</span>

                                                <img src={igd.pluginUrl + '/assets/images/settings/formidableforms.png'}
                                                     alt="Formidable Forms"/>
                                                <span>{wp.i18n.__('Formidable Forms', 'integrate-google-drive')}</span>
                                            </h4>

                                            <div className="igd-hint-example">
                                                <p>{wp.i18n.__('Use the field ID to customize the file names using form field values.', 'integrate-google-drive')}</p>
                                                <div className="igd-code-group">
                                    <span
                                        className="igd-label">{wp.i18n.__('Examples:', 'integrate-google-drive')}</span>
                                                    <code>%field_id_1%</code>
                                                    <code>%field_id_2%</code>
                                                </div>
                                            </div>
                                        </section>

                                    </div>


                                </details>
                            )
                        }
                    </>
                }

            </div>
        </div>

    );
}
