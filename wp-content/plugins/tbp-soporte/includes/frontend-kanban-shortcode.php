<?php
/**
 * Frontend Kanban Shortcode
 * Renders the full Kanban board for authorized staff in the frontend.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register the shortcode [tbp_soporte_frontend_kanban]
 */
function tbp_soporte_register_frontend_kanban_shortcode() {
    add_shortcode( 'tbp_soporte_frontend_kanban', 'tbp_soporte_frontend_kanban_html' );
}
add_action( 'init', 'tbp_soporte_register_frontend_kanban_shortcode' );

/**
 * Render the shortcode HTML
 */
function tbp_soporte_frontend_kanban_html( $atts ) {
    // Check if user is logged in and not a regular customer/subscriber
    $user = wp_get_current_user();
    if ( ! is_user_logged_in() || in_array( 'customer', (array) $user->roles, true ) || in_array( 'subscriber', (array) $user->roles, true ) ) {
        return '<div class="woocommerce-error">' . esc_html__( 'No tienes permisos suficientes para ver el tablero de soporte.', 'tbp-soporte' ) . '</div>';
    }

    // Include the admin-dashboard functions if they haven't been loaded
    if ( ! function_exists( 'tbp_soporte_render_kanban_board' ) ) {
        require_once TBP_SOPORTE_PATH . 'includes/admin-dashboard.php';
    }

    // Force enqueue the admin styles and scripts in the frontend
    wp_enqueue_style( 'tbp-soporte-admin-css', TBP_SOPORTE_URL . 'assets/css/admin.css', array(), TBP_SOPORTE_VERSION );
    
    // We need jQuery UI Draggable/Droppable if they are not already loaded in the theme
    // But our JS handles drag and drop with native HTML5 events, so we just need our admin.js
    wp_enqueue_script( 'tbp-soporte-admin-js', TBP_SOPORTE_URL . 'assets/js/admin.js', array( 'jquery' ), TBP_SOPORTE_VERSION, true );
    
    wp_localize_script( 'tbp-soporte-admin-js', 'tbp_soporte_admin_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'tbp_soporte_admin_nonce' ),
    ) );

    ob_start();
    
    // Wrapper for frontend styling specificity
    echo '<div class="tbp-frontend-kanban-wrapper" style="margin-top: 30px; margin-bottom: 50px;">';
    
    // Call the original render function, passing 'frontend' context
    tbp_soporte_render_kanban_board( 'frontend' );
    
    echo '</div>';
    
    // Small inline CSS patch to ensure the Kanban fits nicely in a frontend container
    echo '<style>
        .tbp-frontend-kanban-wrapper .tbp-soporte-admin-wrap { margin: 0; padding: 0; background: transparent; }
        .tbp-frontend-kanban-wrapper h1.wp-heading-inline { font-size: 24px; color: #111b3d; margin-bottom: 20px; font-weight: bold; }
        .tbp-frontend-kanban-wrapper hr.wp-header-end { display: none; }
        .tbp-frontend-kanban-wrapper .tbp-soporte-kanban-board { min-height: 70vh; }
    </style>';

    return ob_get_clean();
}
