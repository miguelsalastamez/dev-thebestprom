<?php
/**
 * Clase para la administración del plugin
 */
class ExpoScan_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Agregar menú
        add_action('admin_menu', array($this, 'add_menu'));
        
        // Registrar ajustes
        add_action('admin_init', array($this, 'register_settings'));
        
        // Agregar acción para impresión por lotes
        add_action('admin_post_exposcan_print_batch', array($this, 'print_batch'));
        
        // Agregar enlaces de acción
        add_filter('plugin_action_links_exposcan-qr/exposcan-qr.php', array($this, 'add_action_links'));
    }
    
    /**
     * Agregar menú admin
     */
    public function add_menu() {
        // Menú principal
        add_menu_page(
            __('ExpoScan QR', 'exposcan-qr'),
            __('ExpoScan QR', 'exposcan-qr'),
            'manage_options',
            'exposcan-qr',
            array($this, 'registros_page'),
            'dashicons-format-gallery',
            30
        );
        
        // Submenú de registros
        add_submenu_page(
            'exposcan-qr',
            __('Registros', 'exposcan-qr'),
            __('Registros', 'exposcan-qr'),
            'manage_options',
            'exposcan-qr',
            array($this, 'registros_page')
        );
        
        // Submenú de ajustes
        add_submenu_page(
            'exposcan-qr',
            __('Ajustes', 'exposcan-qr'),
            __('Ajustes', 'exposcan-qr'),
            'manage_options',
            'exposcan-qr-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Registrar ajustes
     */
    public function register_settings() {
        // Grupo de opciones
        register_setting('exposcan_options', 'exposcan_spreadsheet_id');
        register_setting('exposcan_options', 'exposcan_sheet_name');
        register_setting('exposcan_options', 'exposcan_credentials_path');
        
        // Sección de Google Sheets
        add_settings_section(
            'exposcan_sheets_section',
            __('Configuración de Google Sheets', 'exposcan-qr'),
            array($this, 'sheets_section_callback'),
            'exposcan-qr-settings'
        );
        
        // Campo: ID de hoja de cálculo
        add_settings_field(
            'exposcan_spreadsheet_id',
            __('ID de la hoja de cálculo', 'exposcan-qr'),
            array($this, 'spreadsheet_id_callback'),
            'exposcan-qr-settings',
            'exposcan_sheets_section'
        );
        
        // Campo: Nombre de la hoja
        add_settings_field(
            'exposcan_sheet_name',
            __('Nombre de la hoja', 'exposcan-qr'),
            array($this, 'sheet_name_callback'),
            'exposcan-qr-settings',
            'exposcan_sheets_section'
        );
        
        // Campo: Ruta de credenciales
        add_settings_field(
            'exposcan_credentials_path',
            __('Ruta al archivo de credenciales', 'exposcan-qr'),
            array($this, 'credentials_path_callback'),
            'exposcan-qr-settings',
            'exposcan_sheets_section'
        );
    }
    
    /**
     * Callback para la sección de Google Sheets
     */
    public function sheets_section_callback() {
        echo '<p>' . __('Configura la integración con Google Sheets para sincronizar los registros.', 'exposcan-qr') . '</p>';
    }
    
    /**
     * Callback para el campo ID de la hoja de cálculo
     */
    public function spreadsheet_id_callback() {
        $value = get_option('exposcan_spreadsheet_id', '');
        echo '<input type="text" id="exposcan_spreadsheet_id" name="exposcan_spreadsheet_id" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('El ID de la hoja de cálculo se encuentra en la URL: https://docs.google.com/spreadsheets/d/[ID]/edit', 'exposcan-qr') . '</p>';
    }
    
    /**
     * Callback para el campo nombre de la hoja
     */
    public function sheet_name_callback() {
        $value = get_option('exposcan_sheet_name', 'Registros');
        echo '<input type="text" id="exposcan_sheet_name" name="exposcan_sheet_name" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('El nombre de la hoja dentro del documento (por defecto: Registros).', 'exposcan-qr') . '</p>';
    }
    
    /**
     * Callback para el campo ruta de credenciales
     */
    public function credentials_path_callback() {
        $value = get_option('exposcan_credentials_path', '');
        echo '<input type="text" id="exposcan_credentials_path" name="exposcan_credentials_path" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Ruta absoluta al archivo JSON de credenciales de la cuenta de servicio.', 'exposcan-qr') . '</p>';
        
        // Botón para probar conexión
        echo '<button type="button" id="exposcan_test_connection" class="button">' . __('Probar conexión', 'exposcan-qr') . '</button>';
        echo '<span id="exposcan_connection_result" style="margin-left: 10px;"></span>';
        
        // Script para manejar la prueba de conexión
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#exposcan_test_connection').on('click', function() {
                    var data = {
                        'action': 'exposcan_test_sheets_connection',
                        'nonce': '<?php echo wp_create_nonce('exposcan_test_connection'); ?>',
                        'spreadsheet_id': $('#exposcan_spreadsheet_id').val(),
                        'sheet_name': $('#exposcan_sheet_name').val(),
                        'credentials_path': $('#exposcan_credentials_path').val()
                    };
                    
                    $('#exposcan_connection_result').html('<?php _e('Probando conexión...', 'exposcan-qr'); ?>');
                    
                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            $('#exposcan_connection_result').html('<span style="color: green;"><?php _e('Conexión exitosa', 'exposcan-qr'); ?></span>');
                        } else {
                            $('#exposcan_connection_result').html('<span style="color: red;"><?php _e('Error de conexión', 'exposcan-qr'); ?></span>');
                        }
                    });
                });
            });
        </script>
        <?php
    }
    
    /**
     * Página de registros
     */
    public function registros_page() {
        // Incluir lista de registros
        require_once EXPOSCAN_QR_PLUGIN_DIR . 'includes/class-exposcan-list-table.php';
        
        // Crear instancia de la tabla
        $registros_table = new ExpoScan_List_Table();
        $registros_table->prepare_items();
        
        // Incluir template
        include EXPOSCAN_QR_PLUGIN_DIR . 'templates/admin-registros.php';
    }
    
    /**
     * Página de ajustes
     */
    public function settings_page() {
        // Incluir template
        include EXPOSCAN_QR_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * Imprimir QRs por lotes
     */
    public function print_batch() {
        // Verificar nonce
        check_admin_referer('exposcan_print_batch', 'exposcan_nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'exposcan-qr'));
        }
        
        // Obtener IDs seleccionados
        $ids = isset($_POST['registro_ids']) ? $_POST['registro_ids'] : array();
        
        if (empty($ids)) {
            wp_redirect(admin_url('admin.php?page=exposcan-qr&error=no-selection'));
            exit;
        }
        
        // Sanitizar IDs
        $ids = array_map('intval', $ids);
        
        // Obtener registros
        $database = new ExpoScan_Database();
        $registros = array();
        
        foreach ($ids as $id) {
            $registro = $database->get_registro($id);
            if ($registro) {
                $registros[] = $registro;
            }
        }
        
        // Si no hay registros, redirigir
        if (empty($registros)) {
            wp_redirect(admin_url('admin.php?page=exposcan-qr&error=no-records'));
            exit;
        }
        
        // Cargar template de impresión por lotes
        include EXPOSCAN_QR_PLUGIN_DIR . 'templates/print-batch.php';
        exit;
    }
    
    /**
     * Agregar enlaces de acción
     */
    public function add_action_links($links) {
        $custom_links = array(
            '<a href="' . admin_url('admin.php?page=exposcan-qr') . '">' . __('Registros', 'exposcan-qr') . '</a>',
            '<a href="' . admin_url('admin.php?page=exposcan-qr-settings') . '">' . __('Ajustes', 'exposcan-qr') . '</a>'
        );
        
        return array_merge($custom_links, $links);
    }
}

// AJAX para probar conexión con Google Sheets
add_action('wp_ajax_exposcan_test_sheets_connection', 'exposcan_test_sheets_connection');

/**
 * Callback para probar conexión con Google Sheets
 */
function exposcan_test_sheets_connection() {
    // Verificar nonce
    if (!check_ajax_referer('exposcan_test_connection', 'nonce', false)) {
        wp_send_json_error('Acceso no autorizado.');
        wp_die();
    }
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos para realizar esta acción.');
        wp_die();
    }
    
    // Obtener datos
    $spreadsheet_id = isset($_POST['spreadsheet_id']) ? sanitize_text_field($_POST['spreadsheet_id']) : '';
    $sheet_name = isset($_POST['sheet_name']) ? sanitize_text_field($_POST['sheet_name']) : '';
    $credentials_path = isset($_POST['credentials_path']) ? sanitize_text_field($_POST['credentials_path']) : '';
    
    // Verificar datos
    if (empty($spreadsheet_id) || empty($sheet_name) || empty($credentials_path)) {
        wp_send_json_error('Faltan datos obligatorios.');
        wp_die();
    }
    
    // Verificar credenciales
    if (!file_exists($credentials_path)) {
        wp_send_json_error('El archivo de credenciales no existe.');
        wp_die();
    }
    
    // Inicializar Sheets
    $sheets = new ExpoScan_Sheets();
    $sheets->setup($spreadsheet_id, $sheet_name, $credentials_path);
    
    // Probar conexión
    if ($sheets->test_connection()) {
        // Inicializar hoja
        $sheets->initialize_sheet();
        wp_send_json_success('Conexión exitosa.');
    } else {
        wp_send_json_error('No se pudo conectar con Google Sheets.');
    }
    
    wp_die();
}