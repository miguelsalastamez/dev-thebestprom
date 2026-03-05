/**
 * ExpoScan QR - JavaScript para el admin
 */
(function($) {
    'use strict';
    
    // Función para inicializar tabs
    function initTabs() {
        $('.exposcan-admin-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Obtener el target
            const target = $(this).attr('href');
            
            // Activar tab
            $('.exposcan-admin-tabs .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Mostrar contenido
            $('.tab-pane').removeClass('active');
            $(target).addClass('active');
            
            // Guardar estado en localStorage
            localStorage.setItem('exposcan_active_tab', target);
        });
        
        // Restaurar tab activo desde localStorage
        const activeTab = localStorage.getItem('exposcan_active_tab');
        if (activeTab) {
            $(`.exposcan-admin-tabs .nav-tab[href="${activeTab}"]`).click();
        }
    }
    
    // Función para manejar prueba de conexión con Google Sheets
    function initTestConnection() {
        $('#exposcan_test_connection').on('click', function() {
            const button = $(this);
            const resultSpan = $('#exposcan_connection_result');
            
            // Datos para la prueba
            const data = {
                'action': 'exposcan_test_sheets_connection',
                'nonce': button.data('nonce'),
                'spreadsheet_id': $('#exposcan_spreadsheet_id').val(),
                'sheet_name': $('#exposcan_sheet_name').val(),
                'credentials_path': $('#exposcan_credentials_path').val()
            };
            
            // Cambiar estado del botón
            button.prop('disabled', true).text('Probando...');
            resultSpan.html('<span style="color: blue;">Probando conexión...</span>');
            
            // Enviar AJAX
            $.post(ajaxurl, data, function(response) {
                // Restaurar botón
                button.prop('disabled', false).text('Probar conexión');
                
                if (response.success) {
                    resultSpan.html('<span style="color: green;">✓ Conexión exitosa</span>');
                } else {
                    resultSpan.html(`<span style="color: red;">✗ Error: ${response.data || 'No se pudo conectar'}</span>`);
                }
            }).fail(function() {
                // Restaurar botón
                button.prop('disabled', false).text('Probar conexión');
                resultSpan.html('<span style="color: red;">✗ Error de conexión</span>');
            });
        });
    }
    
    // Función para inicializar confirmaciones de eliminación
    function initDeleteConfirmations() {
        // Confirmación para eliminar registros individuales
        $('.exposcan-delete').on('click', function(e) {
            if (!confirm('¿Estás seguro de querer eliminar este registro? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
        
        // Confirmación para acción masiva de eliminación
        $('#doaction, #doaction2').on('click', function(e) {
            const selectedAction = $(this).prev('select').val();
            
            if (selectedAction === 'delete') {
                if (!confirm('¿Estás seguro de querer eliminar los registros seleccionados? Esta acción no se puede deshacer.')) {
                    e.preventDefault();
                }
            }
        });
    }
    
    // Función para inicializar la selección de registros
    function initBulkSelection() {
        // Seleccionar/deseleccionar todos
        $('.check-column input[type="checkbox"]').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('tbody .check-column input[type="checkbox"]').prop('checked', isChecked);
        });
        
        // Actualizar botón de acciones masivas
        $('tbody .check-column input[type="checkbox"]').on('change', function() {
            const checkedCount = $('tbody .check-column input[type="checkbox"]:checked').length;
            
            // Actualizar contador
            if (checkedCount > 0) {
                $('.tablenav .displaying-num').after(`<span class="selected-count"> ${checkedCount} elementos seleccionados</span>`);
            } else {
                $('.tablenav .selected-count').remove();
            }
        });
    }
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        // Inicializar tabs si están presentes
        if ($('.exposcan-admin-tabs').length) {
            initTabs();
        }
        
        // Inicializar prueba de conexión si está presente
        if ($('#exposcan_test_connection').length) {
            initTestConnection();
        }
        
        // Inicializar confirmaciones de eliminación si están presentes
        if ($('.exposcan-delete').length || $('#doaction').length) {
            initDeleteConfirmations();
        }
        
        // Inicializar selección masiva si está presente
        if ($('.wp-list-table').length) {
            initBulkSelection();
        }
    });
    
})(jQuery);