<?php
/**
 * Plugin Name: The Best Prom - Egresos
 * Plugin URI: https://dev.thebestprom.com
 * Description: Sistema avanzado de gestión de egresos. Incluye automatización de Órdenes de Compra, control de saldos y seguridad financiera en pagos.
 * Version: 5.4.1
 * Author: Antigravity Team
 * Text Domain: tbp-egresos
 */

/**
 * Handle Manual Recompute from Admin Button
 */
add_action( 'admin_init', 'tbp_egresos_handle_manual_recompute' );
function tbp_egresos_handle_manual_recompute() {
    if ( isset( $_GET['tbp_recompute_po'] ) && isset( $_GET['post'] ) ) {
        if ( current_user_can( 'edit_post', $_GET['post'] ) ) {
            $po_id = intval( $_GET['post'] );
            
            // Recompute from po-engine.php
            if ( function_exists( 'tbp_egresos_recompute_po_totals' ) ) {
                tbp_egresos_recompute_po_totals( $po_id );
            }
            
            // Redirect back without the trigger arg to avoid loops
            $redirect = remove_query_arg( 'tbp_recompute_po', wp_get_referer() );
            wp_redirect( add_query_arg( 'tbp_updated', '1', $redirect ) );
            exit;
        }
    }
}

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'TBP_EGRESOS_VERSION', '1.0.0' );
define( 'TBP_EGRESOS_PATH', plugin_dir_path( __FILE__ ) );
define( 'TBP_EGRESOS_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once TBP_EGRESOS_PATH . 'includes/cpt-registrations.php';
require_once TBP_EGRESOS_PATH . 'includes/cpt-meta-proveedores.php';
require_once TBP_EGRESOS_PATH . 'includes/product-integration.php';
require_once TBP_EGRESOS_PATH . 'includes/po-engine.php';
require_once TBP_EGRESOS_PATH . 'includes/po-admin-ui.php';
require_once TBP_EGRESOS_PATH . 'includes/payment-handler.php';
require_once TBP_EGRESOS_PATH . 'includes/admin-reports.php';
require_once TBP_EGRESOS_PATH . 'includes/admin-advanced-reports.php';
require_once TBP_EGRESOS_PATH . 'includes/cron-subscriptions.php';

/**
 * Register Main Menu
 */
function tbp_egresos_register_menu() {
    add_menu_page(
        __( 'EGRESOS', 'tbp-egresos' ),
        __( 'EGRESOS', 'tbp-egresos' ),
        'manage_woocommerce',
        'tbp-egresos',
        'tbp_egresos_dashboard_page',
        'dashicons-money-alt',
        26
    );

    add_submenu_page(
        'tbp-egresos',
        __( 'Proveedores', 'tbp-egresos' ),
        __( 'Proveedores', 'tbp-egresos' ),
        'manage_woocommerce',
        'edit.php?post_type=tbp_proveedor'
    );

    add_submenu_page(
        'tbp-egresos',
        __( 'Órdenes de Compra', 'tbp-egresos' ),
        __( 'Órdenes de Compra', 'tbp-egresos' ),
        'manage_woocommerce',
        'edit.php?post_type=tbp_orden_compra'
    );

    add_submenu_page(
        'tbp-egresos',
        __( 'Pagos', 'tbp-egresos' ),
        __( 'Pagos', 'tbp-egresos' ),
        'manage_woocommerce',
        'edit.php?post_type=tbp_pago'
    );

    add_submenu_page(
        'tbp-egresos',
        __( 'Reporte Maestro (O.C)', 'tbp-egresos' ),
        __( 'Reporte Maestro (O.C)', 'tbp-egresos' ),
        'manage_woocommerce',
        'tbp-egresos-reportes',
        'tbp_egresos_reportes_page_content'
    );

    add_submenu_page(
        'tbp-egresos',
        __( 'Pagos Efectuados', 'tbp-egresos' ),
        __( 'Pagos Efectuados', 'tbp-egresos' ),
        'manage_woocommerce',
        'tbp-egresos-pagos-historial',
        'tbp_egresos_pagos_history_page_content'
    );

    add_submenu_page(
        'tbp-egresos',
        __( 'Ingresos vs Egresos', 'tbp-egresos' ),
        __( 'Ingresos vs Egresos', 'tbp-egresos' ),
        'manage_woocommerce',
        'tbp-egresos-balance-global',
        'tbp_egresos_balance_global_page_content'
    );
}
add_action( 'admin_menu', 'tbp_egresos_register_menu' );

function tbp_egresos_dashboard_page() {
    echo '<div class="wrap"><h1>' . __( 'Dashboard Egresos - The Best Prom', 'tbp-egresos' ) . '</h1>';
    echo '<p>' . __( 'Gestión financiera de obligaciones con proveedores.', 'tbp-egresos' ) . '</p></div>';
}

function tbp_egresos_reportes_page() {
    echo '<div class="wrap"><h1>' . __( 'Reportes de Egresos', 'tbp-egresos' ) . '</h1>';
    echo '<p>' . __( 'Módulo disponible en la Fase 5.', 'tbp-egresos' ) . '</p></div>';
}

/**
 * Activation Hook
 */
register_activation_hook( __FILE__, 'tbp_egresos_activate' );
function tbp_egresos_activate() {
    // Flush rewrite rules for CPTs
    tbp_egresos_register_cpts();
    flush_rewrite_rules();
}
