<?php
/**
 * Plugin Name: ExpoScan QR
 * Description: Sistema de registro para exposiciones con generación de QR para WhatsApp
 * Version: 1.1.0
 * Author: Miguel Tolentino
 * Text Domain: exposcan-qr
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('EXPOSCAN_QR_VERSION', '1.0.0');
define('EXPOSCAN_QR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EXPOSCAN_QR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EXPOSCAN_QR_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/exposcan-qr');
define('EXPOSCAN_QR_UPLOAD_URL', wp_upload_dir()['baseurl'] . '/exposcan-qr');

// Incluir archivos necesarios
require_once EXPOSCAN_QR_PLUGIN_DIR . 'includes/class-exposcan-database.php';
require_once EXPOSCAN_QR_PLUGIN_DIR . 'includes/class-exposcan-admin.php';
require_once EXPOSCAN_QR_PLUGIN_DIR . 'includes/class-exposcan-form.php';
require_once EXPOSCAN_QR_PLUGIN_DIR . 'includes/class-exposcan-qr-generator.php';
require_once EXPOSCAN_QR_PLUGIN_DIR . 'includes/class-exposcan-sheets.php';
require_once EXPOSCAN_QR_PLUGIN_DIR . 'vendor/autoload.php'; // Para phpqrcode y Google API

/**
 * Clase principal del plugin
 */
class ExpoScan_QR {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Activación y desactivación del plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Inicializar componentes
        add_action('plugins_loaded', array($this, 'init'));
        
        // Registrar assets
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        // Crear tabla en la base de datos
        $database = new ExpoScan_Database();
        $database->create_tables();
        
        // Crear directorios para QRs
        if (!file_exists(EXPOSCAN_QR_UPLOAD_DIR)) {
            wp_mkdir_p(EXPOSCAN_QR_UPLOAD_DIR);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Inicializar componentes
     */
    public function init() {
        // Inicializar componentes
        new ExpoScan_Admin();
        new ExpoScan_Form();
        
        // Cargar traducciones
        load_plugin_textdomain('exposcan-qr', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Registrar assets para el frontend
     */
    public function register_frontend_assets() {
        // CSS
        wp_enqueue_style(
            'exposcan-qr-style', 
            EXPOSCAN_QR_PLUGIN_URL . 'assets/css/frontend.css', 
            array(), 
            EXPOSCAN_QR_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'exposcan-qr-script',
            EXPOSCAN_QR_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            EXPOSCAN_QR_VERSION,
            true
        );
        
        // Localizar script
        wp_localize_script(
            'exposcan-qr-script',
            'exposcanQR',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('exposcan_qr_nonce'),
                'messages' => array(
                    'success' => __('Registro exitoso. Tu QR está listo.', 'exposcan-qr'),
                    'error' => __('Hubo un error en el registro. Intenta nuevamente.', 'exposcan-qr')
                )
            )
        );
    }
    
    /**
     * Registrar assets para el admin
     */
    public function register_admin_assets($hook) {
        // Solo cargar en páginas del plugin
        if (strpos($hook, 'exposcan-qr') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'exposcan-qr-admin-style',
            EXPOSCAN_QR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EXPOSCAN_QR_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'exposcan-qr-admin-script',
            EXPOSCAN_QR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            EXPOSCAN_QR_VERSION,
            true
        );
    }
}

// Usar un método singleton para inicializar el plugin
function exposcan_qr_init() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new ExpoScan_QR();
    }
    
    return $instance;
}

// Inicializar el plugin
exposcan_qr_init();