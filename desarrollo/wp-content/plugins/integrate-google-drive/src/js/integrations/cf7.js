const $ = jQuery;

const app = {

    init: () => {
        $(document).on('click', '.igd-form-uploader-trigger-cf7', app.openModal);
    },


    openModal: function () {

        const {ModuleBuilder} = window;

        const dataElement = document.querySelector('#tag-generator-panel-google_drive-data');

        const id = dataElement.value;

        Swal.fire({
            html: `<div id="igd-form-uploader-config" class="igd-module-builder-modal-wrap"></div>`,
            showConfirmButton: false,
            customClass: {container: 'igd-swal igd-module-builder-modal-container'},

            didOpen(popup) {
                ReactDOM.render(
                    <ModuleBuilder
                        isModuleBuilder={{
                            type: 'cf7',
                            id: document.querySelector('#post_ID')?.value || '',
                        }}
                        isFormBuilder={'cf7'}
                        editId={id}
                        onUpdate={(newId, shortcodeData) => {
                            app.updateData(newId, shortcodeData, id, dataElement);
                            Swal.close();
                        }}
                        onClose={() => Swal.close()}
                    />, document.getElementById('igd-form-uploader-config'));
            },

            willClose(popup) {
                ReactDOM.unmountComponentAtNode(document.getElementById('igd-form-uploader-config'));
            },
            target: document.querySelector('dialog#tag-generator-panel-google_drive') || 'body',
        });
    },

    updateData: (newId, shortcodeData, id, dataElement) => {

        if (newId == id) {
            return;
        }

        if (dataElement) {

            // Check if option with newId exists
            if (!dataElement.querySelector(`option[value="${newId}"]`)) {
                const newOption = new Option(shortcodeData.title, newId);
                dataElement.appendChild(newOption);
            }

            dataElement.value = newId;
            dataElement.dispatchEvent(new Event('change', {bubbles: true}));
        }

    },

}

export default app;