<?php
/**
 * Plugin Name: The Best Prom - Soporte y Tickets
 * Plugin URI: https://dev.thebestprom.com
 * Description: Sistema de gestión de tickets de soporte técnico y servicio al cliente integrado con WooCommerce y notificaciones por WhatsApp.
 * Version: 1.3.0
 * Author: Antigravity Team
 * Text Domain: tbp-soporte
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( defined( 'TBP_SOPORTE_LOADED' ) ) {
    return;
}
define( 'TBP_SOPORTE_LOADED', true );

// Define constants
define( 'TBP_SOPORTE_VERSION', '1.3.0' );
define( 'TBP_SOPORTE_PATH', plugin_dir_path( __FILE__ ) );
define( 'TBP_SOPORTE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
add_action( 'plugins_loaded', 'tbp_soporte_check_woocommerce' );
function tbp_soporte_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'tbp_soporte_woocommerce_missing_notice' );
        return;
    }
    
    // Include core files
    require_once TBP_SOPORTE_PATH . 'includes/cpt-registrations.php';
    require_once TBP_SOPORTE_PATH . 'includes/whatsapp-sender.php';
    require_once TBP_SOPORTE_PATH . 'includes/email-sender.php';
    require_once TBP_SOPORTE_PATH . 'includes/wc-integration.php';
    require_once TBP_SOPORTE_PATH . 'includes/ajax-handlers.php';
    require_once TBP_SOPORTE_PATH . 'includes/frontend-kanban-shortcode.php';
    require_once TBP_SOPORTE_PATH . 'includes/frontend-staff-shortcode.php';
    require_once TBP_SOPORTE_PATH . 'includes/frontend-order-lookup.php';
    require_once TBP_SOPORTE_PATH . 'includes/admin-dashboard.php';
}

function tbp_soporte_woocommerce_missing_notice() {
    echo '<div class="error"><p>' . esc_html__( 'El plugin "The Best Prom - Soporte y Tickets" requiere que WooCommerce esté instalado y activo.', 'tbp-soporte' ) . '</p></div>';
}

/**
 * Enqueue Frontend scripts and styles
 */
add_action( 'wp_enqueue_scripts', 'tbp_soporte_enqueue_scripts' );
function tbp_soporte_enqueue_scripts() {
    // Registrar scripts globalmente para que los shortcodes puedan usarlos
    wp_register_style( 'tbp-soporte-frontend-css', TBP_SOPORTE_URL . 'assets/css/style.css', array(), TBP_SOPORTE_VERSION );
    wp_register_script( 'tbp-soporte-frontend-js', TBP_SOPORTE_URL . 'assets/js/frontend.js', array( 'jquery' ), TBP_SOPORTE_VERSION, true );
    
    // Variables AJAX globales para el frontend normal
    wp_localize_script( 'tbp-soporte-frontend-js', 'tbp_soporte_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'tbp_soporte_nonce' ),
    ) );

    // Encolar automáticamente solo en la cuenta de WooCommerce
    if ( is_account_page() ) {
        wp_enqueue_style( 'tbp-soporte-frontend-css' );
        wp_enqueue_script( 'tbp-soporte-frontend-js' );
    }
}

/**
 * Enqueue Admin scripts and styles
 */
add_action( 'admin_enqueue_scripts', 'tbp_soporte_enqueue_admin_scripts' );
function tbp_soporte_enqueue_admin_scripts( $hook ) {
    // Enqueue only on our custom admin dashboard
    if ( strpos( $hook, 'tbp-soporte' ) !== false || strpos( $hook, 'tbp_ticket' ) !== false ) {
        wp_enqueue_style( 'tbp-soporte-admin-css', TBP_SOPORTE_URL . 'assets/css/admin.css', array(), TBP_SOPORTE_VERSION );
        wp_enqueue_script( 'tbp-soporte-admin-js', TBP_SOPORTE_URL . 'assets/js/admin.js', array( 'jquery' ), TBP_SOPORTE_VERSION, true );
        
        wp_localize_script( 'tbp-soporte-admin-js', 'tbp_soporte_admin_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'tbp_soporte_admin_nonce' ),
        ) );
    }
}

/**
 * Activation hook
 */
register_activation_hook( __FILE__, 'tbp_soporte_activate' );
function tbp_soporte_activate() {
    // Register CPT and taxonomy to register rewrite rules on activation
    require_once TBP_SOPORTE_PATH . 'includes/cpt-registrations.php';
    tbp_soporte_register_cpt();
    tbp_soporte_register_taxonomy();
    tbp_soporte_create_default_categories();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, 'tbp_soporte_deactivate' );
function tbp_soporte_deactivate() {
    flush_rewrite_rules();
}
