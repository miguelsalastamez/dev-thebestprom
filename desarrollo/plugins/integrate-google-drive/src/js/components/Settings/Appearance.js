import preloaders from "../../includes/preloaders";

import {Tooltip} from "react-tooltip";
import {showProModal} from "../../includes/ProModal";
import {colorBrightness, hex2Rgba} from "../../includes/functions";

const {
    ColorPalette,
    ButtonGroup,
    Button,
    RangeControl,
    FormToggle,
} = wp.components;

const {useEffect, useRef} = React;

export default function Appearance({data, setData}) {

    const {isPro} = igd;

    const {
        preloader = 'default',
        customPreloader,
        primaryColor = '#3C82F6',
        customCss,
        adminLazyLoad = true,
        adminLazyLoadNumber = 100,
        adminLazyLoadType = 'pagination',
    } = data;

    useEffect(() => {
        const cssEditor = document.querySelector('.igd-custom-css');

        if (!cssEditor) return;

        const instance = wp.codeEditor?.initialize(cssEditor, {
            ...wp.codeEditor.defaultSettings,
            mode: 'css',
            wordWrap: true,
            tabSize: 2,
            autoComplete: {
                enable: true,
                showDescriptions: true,
                caseSensitive: true,
                autoTrigger: true,
                delay: 0,
                maxItems: 10,
                sortBy: 'score',
                maxLength: 0,
                maxResults: 10,
                highlightMatches: true,
                maxHighlightLength: 0,
                style: 'popup',
            },
            showGutter: true,
            showPrintMargin: true,
            highlightActiveLine: true,
            showLineNumbers: true,
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true,
            enableCodeFolding: true,
            enableCodeFormatting: true,
        });

        instance?.codemirror.on('change', (codemirror) => {
            const value = codemirror.getValue();
            setData({...data, customCss: value});
        });

    }, []);

    const frameRef = useRef();

    const uploadPreloader = () => {

        if (frameRef.current) {
            frameRef.current.off('select');

            frameRef.current.on('select', () => {
                const attachment = frameRef.current.state().get('selection').first().toJSON();

                setData({...data, customPreloader: attachment.url});

            }).open();
            return;
        }

        frameRef.current = wp.media({
            title: wp.i18n.__('Select Preloader', 'integrate-google-drive'),
            button: {text: wp.i18n.__('Select', 'integrate-google-drive')},
            multiple: false,
            library: {type: 'image'},
        });

        frameRef.current.on('select', () => {
            const attachment = frameRef.current.state().get('selection').first().toJSON();
            setData({...data, customPreloader: attachment.url});
        }).open();
    }

    // Handle css variables
    useEffect(() => {
        document.documentElement.style.setProperty('--color-primary', primaryColor);
        document.documentElement.style.setProperty('--color-primary-dark', colorBrightness(primaryColor, -30));
        document.documentElement.style.setProperty('--color-primary-light', hex2Rgba(primaryColor, .5));
        document.documentElement.style.setProperty('--color-primary-light-alt', colorBrightness(primaryColor, 30));
        document.documentElement.style.setProperty('--color-primary-lighter', hex2Rgba(primaryColor, .1));
        document.documentElement.style.setProperty('--color-primary-lighter-alt', colorBrightness(primaryColor, 50));

    }, [primaryColor]);

    return (
        <div className="igd-settings-body">

            <h3 className="igd-settings-body-title">{wp.i18n.__('Appearance Settings', 'integrate-google-drive')}</h3>

            {/* Lazy Load */}
            <div className="settings-field">

                <h4 className="settings-field-label">{wp.i18n.__("Enable Files Lazy load", "integrate-google-drive")}</h4>

                <div className="settings-field-content">
                    <FormToggle
                        checked={adminLazyLoad}
                        onChange={() => setData({...data, adminLazyLoad: !adminLazyLoad})}
                    />

                    {adminLazyLoad &&
                        <div className="settings-field-sub">

                            {/* Loading Type */}
                            <div className="settings-field">

                                <h4 className="settings-field-label">{wp.i18n.__("Files Loading Type", "integrate-google-drive")}</h4>

                                <div className="settings-field-content">

                                    <ButtonGroup>

                                        {/* Scroll, Pagination, Button */}

                                        <Button isPrimary={'pagination' === adminLazyLoadType}
                                                isSecondary={'pagination' !== adminLazyLoadType}
                                                onClick={() => setData({...data, adminLazyLoadType: 'pagination'})}
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M2.53317 11.9998C3.33317 12.7998 3.99984 13.4665 4.79984 14.2665C5.19984 14.6665 5.73317 15.1998 6.13317 15.5998C6.39984 15.8665 6.39984 16.1331 6.2665 16.5331C6.13317 16.7998 5.8665 17.0665 5.59984 17.0665C5.33317 17.0665 5.0665 16.9331 4.93317 16.7998C4.2665 16.1331 3.4665 15.3331 2.79984 14.6665C2.13317 13.8665 1.59984 13.3331 1.0665 12.7998C0.533171 12.2665 0.533171 11.5998 1.0665 11.0665C2.2665 9.73313 3.59984 8.53313 4.79984 7.19979C5.0665 7.06646 5.33317 6.93313 5.73317 7.06646C5.99984 7.19979 6.2665 7.33313 6.39984 7.73313C6.53317 8.13313 6.39984 8.39979 6.13317 8.53313C4.93317 9.59979 3.8665 10.6665 2.93317 11.7331C2.79984 11.7331 2.6665 11.8665 2.53317 11.9998Z"
                                                    fill="#2FB44B"/>
                                                <path
                                                    d="M21.4665 12C20.2665 10.8 19.1998 9.73332 17.9998 8.53332C17.5998 8.13332 17.5998 7.73332 17.8665 7.46665C18.1332 7.06665 18.7998 6.93332 19.1998 7.33332C19.9998 8.13332 20.9332 9.06665 21.7332 9.86665C22.1332 10.2666 22.5332 10.6666 22.9332 11.0666C23.4665 11.6 23.4665 12.1333 22.9332 12.6666C21.5998 14 20.3998 15.3333 19.0665 16.5333C18.7998 16.8 18.2665 16.8 17.9998 16.5333C17.5998 16.2666 17.5998 15.7333 17.8665 15.4667C17.8665 15.3333 17.9998 15.3333 18.1332 15.2C19.1998 14.1333 20.2665 13.0666 21.3332 12C21.3332 12.1333 21.3332 12.1333 21.4665 12Z"
                                                    fill="#2FB44B"/>
                                                <path
                                                    d="M11.0664 12C11.0664 11.4666 11.5997 10.9333 12.1331 11.0666C12.6664 11.0666 13.1997 11.6 13.0664 12.1333C13.0664 12.6666 12.5331 13.2 11.9997 13.0666C11.4664 12.9333 11.0664 12.5333 11.0664 12Z"
                                                    fill="#2FB44B"/>
                                                <path
                                                    d="M7.19971 11.9998C7.19971 11.4664 7.59971 10.9331 8.13304 10.9331C8.66637 10.9331 9.19971 11.3331 9.19971 11.9998C9.19971 12.5331 8.79971 13.0664 8.13304 13.0664C7.59971 13.0664 7.19971 12.5331 7.19971 11.9998Z"
                                                    fill="#2FB44B"/>
                                                <path
                                                    d="M15.0664 11.9998C15.0664 11.4664 15.4664 10.9331 16.1331 10.9331C16.6664 10.9331 17.1997 11.4664 17.1997 11.9998C17.1997 12.5331 16.6664 13.0664 16.1331 13.0664C15.4664 13.0664 15.0664 12.5331 15.0664 11.9998Z"
                                                    fill="#2FB44B"/>
                                            </svg>
                                            <span>{wp.i18n.__("Pagination", "integrate-google-drive")}</span>
                                        </Button>

                                        <Button isPrimary={'button' === adminLazyLoadType}
                                                isSecondary={'button' !== adminLazyLoadType}
                                                onClick={() => setData({...data, adminLazyLoadType: 'button'})}
                                        >
                                            <i className="dashicons dashicons-plus"></i>
                                            <span>{wp.i18n.__("Load More Button", "integrate-google-drive")}</span>
                                        </Button>

                                        <Button isPrimary={'scroll' === adminLazyLoadType}
                                                isSecondary={'scroll' !== adminLazyLoadType}
                                                onClick={() => setData({...data, adminLazyLoadType: 'scroll'})}
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                 viewBox="0 0 18 18" fill="none">
                                                <path
                                                    d="M13.7532 13.1894C14.3346 12.608 14.8831 12.0594 15.427 11.5108C15.6427 11.2951 15.8865 11.1967 16.1913 11.2764C16.7211 11.417 16.9274 12.0359 16.5851 12.4673C16.5476 12.5189 16.5007 12.5658 16.4539 12.608C15.5067 13.5551 14.5549 14.5069 13.6078 15.454C13.2093 15.8526 12.792 15.8526 12.3981 15.4587C11.4275 14.4881 10.457 13.5223 9.4911 12.5517C9.24729 12.3079 9.18634 11.9891 9.31762 11.6984C9.44422 11.4123 9.73492 11.2342 10.0491 11.2529C10.2601 11.267 10.4242 11.3655 10.5695 11.5108C11.0759 12.0219 11.587 12.5283 12.098 13.0393C12.1402 13.0815 12.1777 13.1237 12.2481 13.2034C12.2481 13.0956 12.2481 13.0346 12.2481 12.9737C12.2481 9.66814 12.2481 6.36259 12.2481 3.05704C12.2481 2.60224 12.5388 2.26934 12.9514 2.24589C13.3734 2.22245 13.711 2.52722 13.7485 2.96327C13.7532 3.02891 13.7532 3.08986 13.7532 3.15551C13.7532 6.41886 13.7532 9.68221 13.7532 12.9456C13.7532 13.0159 13.7532 13.0768 13.7532 13.1894Z"
                                                    fill="white"/>
                                                <path
                                                    d="M4.24675 4.80575C3.66066 5.39184 3.11677 5.94042 2.57288 6.489C2.3572 6.70468 2.10869 6.80314 1.80862 6.71874C1.2741 6.57339 1.07249 5.93573 1.42883 5.51374C1.51323 5.41059 1.61638 5.32151 1.71015 5.22304C2.6057 4.33219 3.50124 3.43664 4.39679 2.54109C4.79533 2.14255 5.20794 2.14255 5.60648 2.54109C6.57704 3.51166 7.54761 4.48222 8.51349 5.4481C8.72448 5.65909 8.7995 5.91697 8.71979 6.20299C8.64008 6.49369 8.43378 6.67186 8.14308 6.73281C7.87113 6.78908 7.6367 6.6953 7.44446 6.49837C6.93339 5.98262 6.41763 5.47154 5.90656 4.95578C5.86436 4.91359 5.83623 4.86201 5.80341 4.81981C5.78934 4.82919 5.77059 4.83388 5.75652 4.84326C5.75652 4.90421 5.75652 4.96516 5.75652 5.02612C5.75652 8.33166 5.75652 11.6372 5.75652 14.9428C5.75652 15.3929 5.47519 15.7211 5.06728 15.7492C4.6406 15.782 4.28895 15.4679 4.25613 15.0318C4.25144 14.9662 4.25144 14.9053 4.25144 14.8396C4.25144 11.5809 4.25144 8.32229 4.25144 5.06363C4.24675 4.99329 4.24675 4.92765 4.24675 4.80575Z"
                                                    fill="white"/>
                                            </svg>
                                            <span>{wp.i18n.__("Scroll", "integrate-google-drive")}</span>
                                        </Button>

                                    </ButtonGroup>

                                    <p className="description">{wp.i18n.__("Select the lazy load files loading type in the admin file browser.", "integrate-google-drive")}</p>

                                    <div className="igd-notice igd-notice-info loading-method-info">
                                        <div className="igd-notice-content">
                                            <p>
                                                <code>Scroll </code> → {wp.i18n.__("Loads more files as the user scrolls to the bottom.", "integrate-google-drive")}
                                            </p>
                                            <p>
                                                <code>Pagination </code> → {wp.i18n.__("Navigate through files using page numbers.", "integrate-google-drive")}
                                            </p>
                                            <p><code>Load More
                                                Button </code> → {wp.i18n.__("Load files with a button click.", "integrate-google-drive")}
                                            </p>
                                        </div>
                                    </div>

                                </div>

                            </div>

                            {/* Lazy load number */}
                            <div className="settings-field">
                                <h4 className="settings-field-label">{wp.i18n.__("Number of Files to Load", "integrate-google-drive")}</h4>

                                <div className="settings-field-content">

                                    <RangeControl
                                        value={adminLazyLoadNumber}
                                        onChange={(adminLazyLoadNumber) => setData({...data, adminLazyLoadNumber})}
                                        allowReset={true}
                                        resetFallbackValue={100}
                                        min={0}
                                        max={500}
                                        marks={[
                                            {value: 0, label: '0'},
                                            {value: 500, label: '500'},
                                        ]}
                                        step={10}
                                    />

                                    <p className="description">{wp.i18n.__("Set the number of files to load on each lazy load.", "integrate-google-drive")}</p>
                                </div>
                            </div>

                        </div>
                    }

                </div>
            </div>

            {/* Preloader */}
            <div className="settings-field field-preloader">
                <h4 className="settings-field-label">{wp.i18n.__('Preloader', 'integrate-google-drive')}</h4>
                <p className="description">{wp.i18n.__('Choose the preloader style for the file browser. Preloader will be displayed in the file browser while the files are loading.', 'integrate-google-drive')}</p>

                <div className="settings-field-content">

                    <div className="preloaders">

                        <div
                            className={`preloader preloader-none ${preloader === 'none' && !customPreloader ? 'active' : ''}`}
                            onClick={() => setData({...data, preloader: 'none', customPreloader: ''})}>

                            <span className={`preloader-title`}>{wp.i18n.__('None', 'integrate-google-drive')}</span>

                            <span className="preloader-name">{wp.i18n.__('None', 'integrate-google-drive')}</span>
                        </div>

                        {Object.keys(preloaders).map(key => {

                            const item = preloaders[key];

                            const isActive = (isPro || ['default', 'ring'].includes(key)) && preloader === key && !customPreloader;

                            return (
                                <div
                                    className={`preloader ${isActive ? 'active' : ''} ${!isPro && !['default', 'ring'] ? 'disabled' : ''}`}
                                    key={key}
                                    onClick={() => {
                                        if (!isPro && !['default', 'ring'].includes(key)) {
                                            showProModal(wp.i18n.__(`Upgrade to PRO to use this preloader.`, 'integrate-google-drive'));
                                            return;
                                        }

                                        setData({...data, preloader: key, customPreloader: ''});
                                    }}>

                                    <span dangerouslySetInnerHTML={{__html: item.svg}}/>

                                    {(!isPro && !['default', 'ring'].includes(key)) ?
                                        <div className="preloader-name">
                                            <i className="dashicons dashicons-lock"></i>
                                            <span>{wp.i18n.__('PRO', 'integrate-google-drive')}</span>
                                        </div>
                                        :
                                        <div className="preloader-name">
                                            <span>{item.title}</span>
                                        </div>
                                    }
                                </div>
                            )
                        })}
                    </div>

                    <div className="preloader-upload">
                        <h4>{wp.i18n.__('Custom Preloader Image', 'integrate-google-drive')}</h4>

                        <div className="preloader-upload-actions">

                            <button
                                data-tooltip-content={wp.i18n.__("PRO Feature", "integrate-google-drive")}
                                data-tooltip-id={`igd-pro-tooltip`}
                                type={`button`}
                                className={`igd-btn btn-info upload-btn ${!isPro ? 'disabled' : ''}`}
                                onClick={() => {
                                    if (!isPro) {
                                        showProModal(wp.i18n.__(`Upgrade to PRO to use custom preloader.`, 'integrate-google-drive'));
                                        return;
                                    }

                                    uploadPreloader();
                                }}
                            >
                                <i className="dashicons dashicons-upload"></i>
                                <span>{wp.i18n.__('Upload Image', 'integrate-google-drive')}</span>
                            </button>

                            {!isPro &&
                                <Tooltip
                                    id={"igd-pro-tooltip"}
                                    effect={"solid"}
                                    place={"right"}
                                    variant={"warning"}
                                    className={"igd-tooltip"}
                                />
                            }

                            {!!customPreloader &&
                                <button type={`button`} className={`igd-btn btn-danger`}
                                        onClick={() => setData({...data, customPreloader: ''})}
                                >
                                    <i className="dashicons dashicons-trash"></i>
                                    <span>{wp.i18n.__('Remove', 'integrate-google-drive')}</span>
                                </button>
                            }
                        </div>

                        {!!customPreloader &&
                            <div className="preloader-preview">
                                <img src={customPreloader}/>
                            </div>
                        }
                    </div>

                </div>
            </div>

            {/* Primary Color */}
            <div className="settings-field">
                <h4 className="settings-field-label">{wp.i18n.__('Primary Color', 'integrate-google-drive')}</h4>

                <div className="settings-field-content">

                    <div className="igd-color-palette-wrap"
                         data-tooltip-content={wp.i18n.__("PRO Feature", 'integrate-google-drive')}
                         data-tooltip-id={`primary-color-tooltip`}
                         onClick={() => {
                             if (!isPro) {
                                 showProModal(wp.i18n.__(`Upgrade to PRO to change the file browser primary color.`, 'integrate-google-drive'));
                             }
                         }}
                    >
                        <ColorPalette
                            colors={[
                                {name: 'Default', color: '#3C82F6'},
                                {name: 'Green', color: '#2FB44B'},
                                {"color": "#2463EB", "name": "Azure"},
                                {"color": "#0073aa", "name": "Sapphire Blue"},
                                {"color": "#2265C0", "name": "Lapis Blue"},
                                {"color": "#3F51B5", "name": "Indigo"},
                                {"color": "#6E61C2", "name": "Amethyst"}
                            ]}
                            value={primaryColor}
                            onChange={primaryColor => {
                                if (!isPro) {
                                    showProModal(wp.i18n.__(`Upgrade to PRO to change the file browser primary color.`, 'integrate-google-drive'));
                                    return;
                                }

                                setData({...data, primaryColor})
                            }}
                            className={`igd-color-palette ${isPro ? '' : 'disabled'}`}
                            enableAlpha={true}
                            asButtons={true}
                        />
                    </div>

                    <p className="description">{wp.i18n.__('Choose the primary color for the file browser. The color will affects both on admin and frontend file browser module.', 'integrate-google-drive')}</p>
                </div>
            </div>

            {/* Custom CSS */}
            <div className="settings-field field-custom-css">
                <h4 className="settings-field-label">{wp.i18n.__('Custom CSS', 'integrate-google-drive')}</h4>
                <p className="description">{wp.i18n.__('To customize the plugin\'s styles, insert your custom CSS here.', 'integrate-google-drive')}</p>

                <div className="settings-field-content">

                    <textarea
                        value={customCss}
                        onChange={customCss => setData({...data, customCss})}
                        rows={20}
                        placeholder={wp.i18n.__("Enter your custom CSS here...", 'integrate-google-drive')}
                        id="igd-custom-css"
                        className={`igd-textarea igd-custom-css`}
                    />

                </div>
            </div>

        </div>
    )
}