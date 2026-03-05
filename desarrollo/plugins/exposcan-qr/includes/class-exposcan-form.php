<?php
/**
 * Clase para gestionar el formulario de registro
 */
class ExpoScan_Form {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Registrar shortcode para el formulario
        add_shortcode('exposcan_formulario', array($this, 'render_form'));
        
        // Manejar envío del formulario
        add_action('wp_ajax_exposcan_submit_form', array($this, 'process_form'));
        add_action('wp_ajax_nopriv_exposcan_submit_form', array($this, 'process_form'));
		// Añadir endpoint para generar nuevo token
add_action('wp_ajax_exposcan_generate_token', array($this, 'generate_new_token'));
add_action('wp_ajax_nopriv_exposcan_generate_token', array($this, 'generate_new_token'));
    }
    
    /**
     * Renderizar el formulario
     */
public function render_form($atts) {
    // Generar token único para este formulario
    $form_token = wp_create_nonce('exposcan_form_token_' . time());
    
    // Guardar el token en opción transitoria (expira en 12 horas)
    set_transient('exposcan_form_token_' . $form_token, '1', 12 * HOUR_IN_SECONDS);
    
    // Iniciar buffer de salida
    ob_start();
    
    // Incluir template del formulario - PASAR VARIABLES AL TEMPLATE
    include EXPOSCAN_QR_PLUGIN_DIR . 'templates/form.php';
    
    // Devolver contenido
    return ob_get_clean();
}
    
    /**
     * Procesar el formulario
     */
    public function process_form() {
        // Verificar nonce
    if (!check_ajax_referer('exposcan_qr_nonce', 'nonce', false)) {
        wp_send_json_error('Acceso no autorizado.');
        wp_die();
    }
    
    // Verificar token único del formulario
    $form_token = isset($_POST['exposcan_form_token']) ? sanitize_text_field($_POST['exposcan_form_token']) : '';
    if (empty($form_token) || get_transient('exposcan_form_token_' . $form_token) !== '1') {
        wp_send_json_error('Formulario ya procesado o inválido.');
        wp_die();
    }
    
    // Borrar el token para evitar reenvíos
    delete_transient('exposcan_form_token_' . $form_token);
        
       // Validar campos requeridos
			$required_fields = array('nombre', 'apellido', 'telefono', 'email');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error('Por favor completa todos los campos requeridos.');
                wp_die();
            }
        }
        
        // Validar email
        if (!is_email($_POST['email'])) {
            wp_send_json_error('Por favor ingresa un email válido.');
            wp_die();
        }
        
        // Validar teléfono (solo números)
        if (!preg_match('/^[0-9+\s()-]+$/', $_POST['telefono'])) {
            wp_send_json_error('Por favor ingresa un número de teléfono válido.');
            wp_die();
        }
        
        // Datos validados
        $data = array(
            'empresa' => sanitize_text_field($_POST['empresa']),
            'nombre' => sanitize_text_field($_POST['nombre']),
            'apellido' => sanitize_text_field($_POST['apellido']),
            'telefono' => sanitize_text_field($_POST['telefono']),
            'email' => sanitize_email($_POST['email']),
'web' => isset($_POST['web']) && !empty($_POST['web']) 
    ? esc_url_raw((strpos($_POST['web'], 'http') === 0 ? '' : 'https://') . $_POST['web']) 
    : '',			
            'requerimientos' => isset($_POST['requerimientos']) ? sanitize_textarea_field($_POST['requerimientos']) : ''
        );
		
		// Verificar si ya existe un registro similar
		$database = new ExpoScan_Database();
		$existing = $database->check_duplicate($data['email'], $data['telefono']);
		if ($existing) {
			wp_send_json_error('Ya existe un registro con este email o teléfono.');
			wp_die();
		}
        
        // Generar QR para WhatsApp
$qr_generator = new ExpoScan_QR_Generator();
$phone_number = preg_replace('/[^0-9+]/', '', $data['telefono']);
$empresa_texto = !empty($data['empresa']) ? " de {$data['empresa']}" : "";
$web_texto = !empty($data['web']) ? "\nWeb: {$data['web']}" : "";
$requerimientos_texto = !empty($data['requerimientos']) ? "\nRequerimientos: {$data['requerimientos']}" : "";
$whatsapp_text = "Contacto: {$data['nombre']} {$data['apellido']}{$empresa_texto}\nEmail: {$data['email']}{$web_texto}{$requerimientos_texto}";
$whatsapp_url = "https://wa.me/{$phone_number}?text=" . urlencode($whatsapp_text);
        
        // Generar y guardar QR
        $current_date = date('Y/m');
        $upload_dir = EXPOSCAN_QR_UPLOAD_DIR . '/' . $current_date;
        
        // Crear directorio si no existe
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }
        
        // Nombre único para el archivo (timestamp + datos aleatorios)
        $qr_filename = 'qr_' . time() . '_' . substr(md5(rand()), 0, 6);
        $qr_path = $current_date . '/' . $qr_filename . '.png';
        $full_path = EXPOSCAN_QR_UPLOAD_DIR . '/' . $qr_path;
        
        // Generar QR
        $qr_generator->generate($whatsapp_url, $full_path, $data['nombre'] . ' ' . $data['apellido']);
        
        // Actualizar datos con la ruta del QR
        $data['qr_path'] = $qr_path;
        
        // Guardar en la base de datos
        $database = new ExpoScan_Database();
        $registro_id = $database->insert_registro($data);
        
        if (!$registro_id) {
            wp_send_json_error('Error al guardar el registro.');
            wp_die();
        }
        
        // Añadir a Google Sheets en segundo plano
        $this->schedule_sheets_sync($registro_id);
        
        // Preparar respuesta
        $response = array(
            'message' => 'Registro exitoso',
            'qr_url' => EXPOSCAN_QR_UPLOAD_URL . '/' . $qr_path,
            'print_url' => add_query_arg(
                array(
                    'action' => 'exposcan_print_qr',
                    'id' => $registro_id,
					        'auto_print' => '1', // Añadir este parámetro
                    'nonce' => wp_create_nonce('exposcan_print_qr_' . $registro_id)
                ),
                admin_url('admin-ajax.php')
            )
        );
        
        wp_send_json_success($response);
        wp_die();
    }
	
	/**
 * Generar nuevo token para el formulario
 */
public function generate_new_token() {
    // Verificar nonce
    if (!check_ajax_referer('exposcan_qr_nonce', 'nonce', false)) {
        wp_send_json_error('Acceso no autorizado.');
        wp_die();
    }
    
    // Generar nuevo token
    $form_token = wp_create_nonce('exposcan_form_token_' . time());
    
    // Guardar el token en opción transitoria
    set_transient('exposcan_form_token_' . $form_token, '1', 12 * HOUR_IN_SECONDS);
    
    // Devolver nuevo token
    wp_send_json_success(array('token' => $form_token));
    wp_die();
}
    
  /**
 * Sincronizar directamente con Google Sheets
 */
private function schedule_sheets_sync($registro_id) {
    // Obtener registro
    $database = new ExpoScan_Database();
    $registro = $database->get_registro($registro_id);
    
    if ($registro) {
        // Sincronizar con Google Sheets directamente
        $sheets = new ExpoScan_Sheets();
        $sheets->add_row($registro);
    }
}
}

// Manejar la impresión del QR
add_action('wp_ajax_exposcan_print_qr', 'exposcan_print_qr_callback');
add_action('wp_ajax_nopriv_exposcan_print_qr', 'exposcan_print_qr_callback');

/**
 * Callback para imprimir QR
 */
function exposcan_print_qr_callback() {
    // Verificar parámetros
    if (empty($_GET['id']) || empty($_GET['nonce'])) {
        wp_die('Solicitud no válida');
    }
    
    $registro_id = intval($_GET['id']);
    
    // Verificar nonce
    if (!wp_verify_nonce($_GET['nonce'], 'exposcan_print_qr_' . $registro_id)) {
        wp_die('Acceso no autorizado');
    }
    
    // Obtener registro
    $database = new ExpoScan_Database();
    $registro = $database->get_registro($registro_id);
    
    if (!$registro) {
        wp_die('Registro no encontrado');
    }
    
    // Cargar template de impresión
    include EXPOSCAN_QR_PLUGIN_DIR . 'templates/print-qr.php';
    exit;
}