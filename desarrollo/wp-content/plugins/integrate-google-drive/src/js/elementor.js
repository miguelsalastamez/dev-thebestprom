import {base64Encode} from "./includes/functions";

;(function ($) {
    const {ModuleBuilder} = window;

    const app = {
        init() {

            $(window).on('elementor/frontend/init', () => {

                // Init module shortcode selection
                elementorFrontend.hooks.addAction('frontend/element_ready/igd_shortcodes.default', app.handleModuleSelection);

                // Open module builder from editor panel
                elementor.channels.editor.on('igd:editor:edit_module', app.openModuleBuilder);
                elementor.channels.editor.on('igd:editor:add_module', app.openModuleBuilder);

                app.initPromotion();
            });

        },

        ready() {

            if (typeof elementor === 'undefined') return;

            // Handle module preview in Elementor editor
            elementor.hooks.addFilter(
                'elementor_pro/forms/content_template/field/google_drive_upload',
                (inputField, item) => {

                    const {module_id} = item;

                    let data = {
                        type: 'uploader',
                        isFormUploader: 'elementor',
                        isRequired: item.required,
                        width: '100%',
                        height: '',
                    }

                    if (module_id) {
                        const found = igd.shortcodes.find(item => item.id == module_id);
                        const shortcodeData = found?.config || found;

                        if (shortcodeData) {
                            data = {
                                ...data,
                                ...shortcodeData,
                            };
                        }
                    }

                    const {width, height} = data;

                    return `<div class="igd igd-shortcode-wrap igd-shortcode-uploader" style="width: ${width}; height: ${height};" data-shortcode-data='${base64Encode(JSON.stringify(data))}'></div>`;
                },
                10
            );

        },

        openModuleBuilder(view) {

            const $el = view?.$el;

            let moduleId = null;
            let isFormField = false;
            let formType = null;

            if ($el) {
                moduleId = window.parent.jQuery('[data-setting="module_id"]').val();

                isFormField = $el.hasClass('elementor-control-edit_field_form') || $el.hasClass('elementor-control-edit_field_metform');
                formType = $el.hasClass('elementor-control-edit_field_form') ? 'elementor' : 'metform';

                if (isFormField && formType !== 'metform') {
                    moduleId = window.parent.jQuery('.elementor-repeater-row-controls.editable [data-setting="module_id"]').val() || moduleId;
                }
            }


            Swal.fire({
                target: document.querySelector('.elementor-element-editable'), // Append to widget's DOM
                html: `<div id="igd-elementor-module-builder" class="igd-module-builder-modal-wrap"></div>`,
                showConfirmButton: false,
                customClass: {
                    container: 'igd-swal igd-module-builder-modal-container',
                },
                didOpen() {

                    ReactDOM.render(
                        <ModuleBuilder
                            isModuleBuilder={{
                                type: isFormField ? formType : 'post',
                                id: new URLSearchParams(window.location.search).get('elementor-preview') || '',
                            }}
                            isFormBuilder={isFormField && formType}
                            editId={moduleId}
                            addType={!$el ? 'init' : ''}
                            onUpdate={(id, shortcodeData) => {
                                app.updateModuleData(id, shortcodeData);

                                Swal.close();
                            }}
                            onClose={() => Swal.close()}
                        />,
                        document.getElementById('igd-elementor-module-builder')
                    );

                },
                willClose() {
                    ReactDOM.unmountComponentAtNode(document.getElementById('igd-elementor-module-builder'));
                }
            });

        },

        handleModuleSelection($scope) {
            const $select = $scope.find('.module_id');

            $select.select2({
                width: '100%',
                placeholder: wp.i18n.__('Select a module', 'integrate-google-drive'),
                allowClear: true,
                minimumResultsForSearch: 10,

                templateResult: function (data) {
                    if (!data.id) return data.text;

                    const image = $(data.element).data('image');
                    const label = data.text || '';

                    if (image) {
                        return $(
                            `<span class="igd-select2-option"><img src="${image}"> ${label}</span>`
                        );
                    }

                    return $('<span>').text(label);
                },

                templateSelection: function (data) {
                    const image = $(data.element).data('image');
                    const label = data.text || '';

                    if (image) {
                        return $(
                            `<span class="igd-select2-selection"><img src="${image}"> ${label}</span>`
                        );
                    }

                    return $('<span>').text(label);
                },
            });


            if (!$select.length) return;

            $select.on('change', function () {
                const selectedId = this.value;
                const $parentSelect = window.parent.jQuery('[data-setting="module_id"]');

                $parentSelect.val(selectedId)

                $parentSelect[0].dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));
            });
        },

        updateModuleData: (id, data) => {

            const $select = window.parent.jQuery('[data-setting="module_id"]');

            // Check if option already exists
            if (!$select.find(`option[value="${id}"]`).length) {
                const newOption = new Option(data.title, id);
                $select.append(newOption);
            }

            // Trigger change → Elementor will handle rerender internally
            $select.val(id).trigger('change');

        },

        initPromotion() {
            if (typeof parent.document === "undefined") return;

            parent.document.addEventListener("mousedown", (e) => {
                const promoWidgets = parent.document.querySelectorAll(".elementor-element--promotion");

                for (const widget of promoWidgets) {
                    if (widget.contains(e.target)) {
                        const dialog = parent.document.querySelector("#elementor-element--promotion__dialog");
                        const icon = widget.querySelector(".icon > i");

                        const isIGD = icon?.classList.toString().includes("igd");
                        const defaultBtn = dialog.querySelector(".dialog-buttons-action");
                        const igdBtn = dialog.querySelector(".igd-dialog-buttons-action");

                        if (isIGD) {
                            e.stopImmediatePropagation();
                            defaultBtn.style.display = "none";

                            if (!igdBtn) {
                                const button = document.createElement("a");
                                button.className = "dialog-button dialog-action dialog-buttons-action elementor-button elementor-button-success igd-dialog-buttons-action";
                                button.href = 'https://softlabbd.com/integrate-google-drive-pricing';
                                button.textContent = wp.i18n.__('GET PRO', 'integrate-google-drive');

                                defaultBtn.insertAdjacentElement("afterend", button);
                            } else {
                                igdBtn.style.display = "";
                            }
                        } else {
                            defaultBtn.style.display = "";
                            if (igdBtn) igdBtn.style.display = "none";
                        }
                        break;
                    }
                }
            });
        }
    };

    app.init();
    $(document).ready(app.ready);
})(jQuery);
