const app = {

    init(elementorTinymce = false) {

        const $doc = elementorTinymce ? window.parent.jQuery(window.parent.document) : jQuery(document);

        if (elementorTinymce) {
            window.tinymce = elementorTinymce;
        }

        $doc.on('click', '#igd-media-button', (e) => {
            e.preventDefault();

            app.openModuleBuilder();
        });

        let isToolbarActive = false;

        const isIgdPlaceholder = (node, editor) => editor.dom.hasClass(node, 'igd_module_shortcode');

        const addToolbar = (node, editor) => {
            if (!node || node.nodeName !== 'IMG' || !isIgdPlaceholder(node, editor)) return;

            const dom = editor.dom;
            removeToolbar(editor);
            dom.setAttrib(node, 'data-wp-igd-select', 1);

            const shortcodeId = node.getAttribute('data-id');

            const toolbarHtml = `
                <div class="dashicons dashicons-no-alt remove" title="${wp.i18n.__('Remove', 'integrate-google-drive')} (#${shortcodeId})" data-mce-bogus="1"></div>
                <div class="dashicons dashicons-edit edit" title="${wp.i18n.__('Edit', 'integrate-google-drive')} (#${shortcodeId})" data-mce-bogus="1"></div>
               
            `;

            const toolbar = dom.create('div', {
                id: 'wp-igd-toolbar',
                'data-mce-bogus': '1',
                contenteditable: false
            }, toolbarHtml);

            node.parentNode.insertBefore(toolbar, node);
            isToolbarActive = true;
        };

        const removeToolbar = (editor) => {
            const dom = editor.dom;
            const toolbar = dom.get('wp-igd-toolbar');
            if (toolbar) dom.remove(toolbar);
            dom.setAttrib(dom.select('img[data-wp-igd-select]'), 'data-wp-igd-select', null);
            isToolbarActive = false;
        };

        const removeIgdImage = (node, editor) => {
            const wrapper = editor.dom.getParent(node, '.igd_module_shortcode_wrap');
            if (wrapper) editor.dom.remove(wrapper);
            else editor.dom.remove(node);
            removeToolbar(editor);
        };

        const doShortcode = (content) => {
            return content.replace(/\[integrate_google_drive\s+id=\"([^\"]+)\"\]/g, (_, id) => {

                return `<img 
                    src="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='5' height='5'></svg>"
                    class="igd_module_shortcode mceItem"
                    data-mce-placeholder="1"
                    data-id="${id}"
                />`;
            });
        };

        const getShortcode = (content) => {
            const getAttr = (s, n) => {
                const match = new RegExp(n + '="([^"]+)"', 'g').exec(s);
                return match ? match[1] : '';
            };

            return content.replace(/<img[^>]*class=["'][^"']*igd_module_shortcode[^"']*["'][^>]*>/g, (img) => {
                const id = getAttr(img, 'data-id');
                return `[integrate_google_drive id="${id}"]`;
            });
        };

        tinymce.PluginManager.add('igd_tinymce_js', (editor) => {
            editor.on('BeforeSetcontent', (e) => {
                e.content = doShortcode(e.content);
            });

            editor.on('PostProcess', (e) => {
                if (e.get) {
                    e.content = getShortcode(e.content);
                }
            });

            editor.on('mouseup', (e) => {
                const {target, button} = e;
                if (button && button > 1) return;

                const dom = editor.dom;
                const isToolbarClick = dom.getParent(target, '#wp-igd-toolbar');
                let image;

                if (target.nodeName === 'DIV' && isToolbarClick) {
                    image = dom.select('img[data-wp-igd-select]')[0];

                    if (image) {
                        editor.selection.select(image);

                        if (dom.hasClass(target, 'remove')) {
                            removeToolbar(editor);
                            removeIgdImage(image, editor);
                        } else if (dom.hasClass(target, 'edit')) {
                            const id = image.getAttribute('data-id');
                            app.openModuleBuilder(id);
                            removeToolbar(editor);
                        }
                    }
                } else if (target.nodeName === 'IMG' && !dom.getAttrib(target, 'data-wp-igd-select') && isIgdPlaceholder(target, editor)) {
                    addToolbar(target, editor);
                } else if (target.nodeName !== 'IMG') {
                    removeToolbar(editor);
                }
            });

            editor.on('mousedown cut keydown', (e) => {
                if (!editor.dom.getParent(e.target, '#wp-igd-toolbar')) {
                    removeToolbar(editor);
                }
            });
        });
    },

    openModuleBuilder(id = null) {

        const {ModuleBuilder} = window;

        Swal.fire({
            html: `<div id="igd-tinymce" class="igd-module-builder-modal-wrap"></div>`,
            showConfirmButton: false,
            allowOutsideClick: false,
            customClass: {
                container: 'igd-swal igd-module-builder-modal-container'
            },
            didOpen() {

                ReactDOM.render(
                    <ModuleBuilder
                        isModuleBuilder={{
                            type: 'post',
                            id: document.querySelector('#post_ID')?.value || '',
                        }}
                        editId={id}
                        onUpdate={(id) => {
                            app.insertContentInEditor(`[integrate_google_drive id="${id}"]`);

                            Swal.close();
                        }}
                    />,
                    document.getElementById('igd-tinymce')
                );

            },
            willClose() {
                ReactDOM.unmountComponentAtNode(document.getElementById('igd-tinymce'));
            }
        });
    },

    insertContentInEditor: (content) => {
        let isParentEditor = typeof tinyMCE === 'undefined';

        if (isParentEditor) {
            window.tinyMCE = window.parent.tinyMCE;
        }

        let activeEditor = tinyMCE.activeEditor;

        if (activeEditor) {
            let editorId = activeEditor.id;

            if (['upload-confirmation-message', 'access-denied-message'].includes(editorId)) {
                editorId = 'content';
            }

            const textEditor = isParentEditor ? window.parent.document.getElementById(editorId) : document.getElementById(editorId);
            const visualEditor = tinyMCE.get(editorId);

            if (visualEditor && !visualEditor.isHidden()) {
                // Visual mode (TinyMCE editor)
                visualEditor.execCommand('mceInsertContent', false, content);
            } else {
                // Text mode (textarea)
                const cursorPosition = textEditor.selectionStart;
                const textBefore = textEditor.value.substring(0, cursorPosition);
                const textAfter = textEditor.value.substring(textEditor.selectionEnd, textEditor.value.length);

                textEditor.value = textBefore + content + textAfter;

                // Update the cursor position after inserting content
                textEditor.selectionStart = cursorPosition + content.length;
                textEditor.selectionEnd = cursorPosition + content.length;
            }
        } else {
            console.warn('No active TinyMCE editor found.');
        }
    },

};

export default app;
