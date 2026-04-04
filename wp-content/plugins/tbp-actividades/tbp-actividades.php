<?php
/**
 * Plugin Name: The Best Prom - Actividades
 * Plugin URI: https://dev.thebestprom.com
 * Description: Hub de actividades para eventos de graduación (Rifas, Asientos, Fotografía).
 * Version: 6.5.8
 * Author: Antigravity Team
 * Text Domain: tbp-actividades
 *
 * Changelog:
 * 6.5.5 - Feature: Soporte para 'event_id' manual en shortcode y URL para forzar visualización.
 * 6.5.4 - Debug: Volcado de llaves meta de ítems para análisis de pedidos conflictivos.
 * 6.5.3 - UI: Cuadro de información de shortcode e integración en el editor de Premiaciones.
 * 6.4.2 - Fix: El selector de imágenes de categorías ahora actualiza la categoría correcta (corrección de scope JS).
 * 6.4.1 - Fix: Mejora en la detección de campos de asistente usando la API de Tribe y búsquedas profundas.
 * 6.4.0 - UX Mejorada: Selección dinámica del campo de grupo basado en los tickets del evento.
 * 6.3.0 - Optimización de Premiaciones: Post único por evento, detección automática de grupos.
 *         Normalización de nombres de grupos (ej: 065 = 65).
 * 6.2.0 - Versión inicial de la funcionalidad de PREMIACIONES.
 * 6.1.1 - Corregido error de sintaxis en JavaScript.
 * 6.1.0 - Agregado control de tómbola y recepción de boletos vendidos.
 *         Reparación de funciones de mensajería (Email/WhatsApp) en reportes.
 *         Nueva columna "En tómbola" en el reporte administrativo.
 * 6.0.1 - Versión inicial estable.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'TBP_ACTIVIDADES_VERSION', '6.5.8' );
define( 'TBP_ACTIVIDADES_PATH', plugin_dir_path( __FILE__ ) );
define( 'TBP_ACTIVIDADES_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once TBP_ACTIVIDADES_PATH . 'includes/database.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/cpt-rifas.php';
// [SAFEGUARD V6.5.7] Módulo de Premiaciones Desactivado Temporalmente
// require_once TBP_ACTIVIDADES_PATH . 'includes/cpt-premiaciones.php';
// require_once TBP_ACTIVIDADES_PATH . 'includes/premiaciones-functions.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/eventbrite-webhook.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/physical-management.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/local-raffle-handler.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/order-integration.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/frontend-integration.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/messaging.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/template-manager.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/admin-reports.php';

/**
 * Register Main Menu
 */
function tbp_actividades_register_menu() {
    add_menu_page(
        __( 'ACTIVIDADES', 'tbp-actividades' ),
        __( 'ACTIVIDADES', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades',
        'tbp_actividades_main_page',
        'dashicons-tickets-alt',
        25
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Rifas', 'tbp-actividades' ),
        __( 'Rifas', 'tbp-actividades' ),
        'manage_woocommerce',
        'edit.php?post_type=tbp_rifas'
    );

    /* [SAFEGUARD]
    add_submenu_page(
        'tbp-actividades',
        __( 'Premiaciones', 'tbp-actividades' ),
        __( 'Premiaciones', 'tbp-actividades' ),
        'manage_woocommerce',
        'edit.php?post_type=tbp_premiaciones'
    );
    */

    add_submenu_page(
        'tbp-actividades',
        __( 'Reportes de Entrega', 'tbp-actividades' ),
        __( 'Reporte Entregas', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-reportes',
        'tbp_actividades_reportes_page'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Asignación de Asientos', 'tbp-actividades' ),
        __( 'Asientos (Fase 2)', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-asientos',
        'tbp_actividades_placeholder_page'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Toma de Fotografía', 'tbp-actividades' ),
        __( 'Fotografía (Fase 2)', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-fotografia',
        'tbp_actividades_placeholder_page'
    );
    add_submenu_page(
        'tbp-actividades',
        __( 'Configuración', 'tbp-actividades' ),
        __( 'Configuración', 'tbp-actividades' ),
        'manage_options', // Keep settings as admin only
        'tbp-actividades-settings',
        'tbp_actividades_settings_page'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Plantillas MKT', 'tbp-actividades' ),
        __( 'Plantillas MKT', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-plantillas',
        'tbp_actividades_plantillas_page'
    );

    /* [SAFEGUARD]
    add_submenu_page(
        'tbp-actividades',
        __( 'Reporte de Premiaciones', 'tbp-actividades' ),
        __( 'Reporte Premiaciones', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-reporte-premiaciones',
        'tbp_actividades_premiaciones_report_page'
    );
    */
}
add_action( 'admin_menu', 'tbp_actividades_register_menu' );
add_action( 'admin_init', 'tbp_actividades_register_settings' );

function tbp_actividades_register_settings() {
    register_setting( 'tbp_actividades_options', 'tbp_eventbrite_webhook_secret' );
    register_setting( 'tbp_actividades_options', 'tbp_eventbrite_api_token' );
    register_setting( 'tbp_actividades_options', 'tbp_stripe_secret_key' );
}

function tbp_actividades_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'Configuración de Actividades', 'tbp-actividades' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'tbp_actividades_options' );
            do_settings_sections( 'tbp_actividades_options' );
            ?>
            <table class="form-table">
                <tr>
                    <th><label for="tbp_eventbrite_webhook_secret"><?php _e( 'Eventbrite Webhook Secret', 'tbp-actividades' ); ?></label></th>
                    <td><input type="text" id="tbp_eventbrite_webhook_secret" name="tbp_eventbrite_webhook_secret" value="<?php echo esc_attr( get_option( 'tbp_eventbrite_webhook_secret' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="tbp_eventbrite_api_token"><?php _e( 'Eventbrite API Token', 'tbp-actividades' ); ?></label></th>
                    <td><input type="password" id="tbp_eventbrite_api_token" name="tbp_eventbrite_api_token" value="<?php echo esc_attr( get_option( 'tbp_eventbrite_api_token' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="tbp_stripe_secret_key"><?php _e( 'Stripe Secret Key', 'tbp-actividades' ); ?></label></th>
                    <td><input type="password" id="tbp_stripe_secret_key" name="tbp_stripe_secret_key" value="<?php echo esc_attr( get_option( 'tbp_stripe_secret_key' ) ); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong><?php _e( 'Webhook URL para Eventbrite:', 'tbp-actividades' ); ?></strong> <code><?php echo esc_url( get_rest_url( null, 'tbp-actividades/v1/eventbrite' ) ); ?></code></p>
    </div>
    <?php
}

function tbp_actividades_main_page() {
    echo '<div class="wrap"><h1>' . __( 'Hub de Actividades - The Best Prom', 'tbp-actividades' ) . '</h1>';
    echo '<p>' . __( 'Bienvenido al centro de gestión de dinámicas para eventos.', 'tbp-actividades' ) . '</p></div>';
}

function tbp_actividades_placeholder_page() {
    echo '<div class="wrap"><h1>' . __( 'Módulo en Desarrollo', 'tbp-actividades' ) . '</h1>';
    echo '<p>' . __( 'Este módulo estará disponible en la Fase 2 del proyecto.', 'tbp-actividades' ) . '</p></div>';
}

/**
 * Activation Hook
 */
register_activation_hook( __FILE__, 'tbp_actividades_activate' );
function tbp_actividades_activate() {
    tbp_actividades_create_db_tables();
    flush_rewrite_rules();
}
