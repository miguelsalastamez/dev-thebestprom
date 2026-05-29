<?php
/**
 * Frontend Order Lookup Shortcode
 * Generates the form for staff to lookup WooCommerce orders from the frontend.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register the shortcode [tbp_soporte_order_lookup]
 */
function tbp_soporte_register_order_lookup_shortcode() {
    add_shortcode( 'tbp_soporte_order_lookup', 'tbp_soporte_order_lookup_html' );
}
add_action( 'init', 'tbp_soporte_register_order_lookup_shortcode' );

/**
 * Render the shortcode HTML
 */
function tbp_soporte_order_lookup_html( $atts ) {
    // Security Check: Only logged-in users who are NOT customers/subscribers
    $user = wp_get_current_user();
    if ( ! is_user_logged_in() || in_array( 'customer', (array) $user->roles, true ) || in_array( 'subscriber', (array) $user->roles, true ) ) {
        return '<div class="woocommerce-error">' . esc_html__( 'No tienes permisos suficientes para ver el buscador de pedidos.', 'tbp-soporte' ) . '</div>';
    }

    // Enqueue Frontend Scripts & Styles
    wp_enqueue_style( 'tbp-soporte-frontend-css' );
    wp_enqueue_script( 'tbp-soporte-frontend-js' );

    wp_localize_script( 'tbp-soporte-frontend-js', 'tbp_soporte_admin_ajax', array(
        'ajax_url'    => admin_url( 'admin-ajax.php' ),
        'nonce'       => wp_create_nonce( 'tbp_soporte_admin_nonce' ),
        'staff_nonce' => wp_create_nonce( 'tbp_soporte_staff_nonce' ),
    ) );

    ob_start();
    ?>
    <div class="tbp-order-lookup-wrapper">
        <div class="tbp-lookup-header">
            <h3>🔍 <?php esc_html_e( 'Buscador de Pedidos (Staff)', 'tbp-soporte' ); ?></h3>
            <p><?php esc_html_e( 'Busca cualquier pedido por ID, Nombre o Correo electrónico para ver su información completa.', 'tbp-soporte' ); ?></p>
        </div>

        <div class="tbp-lookup-search-box">
            <input type="text" id="tbp-order-lookup-input" placeholder="<?php esc_attr_e( 'Ej. 36100, Juan Perez, juan@email.com...', 'tbp-soporte' ); ?>" autocomplete="off" />
            <span class="tbp-lookup-spinner" style="display: none;"></span>
            <div id="tbp-lookup-results-container" class="tbp-lookup-results-container" style="display: none;"></div>
        </div>

        <!-- The Modal -->
        <div id="tbp-order-modal" class="tbp-order-modal" style="display: none;">
            <div class="tbp-order-modal-backdrop"></div>
            <div class="tbp-order-modal-content">
                <div class="tbp-order-modal-header">
                    <h3 id="tbp-modal-title"></h3>
                    <button type="button" class="tbp-order-modal-close">&times;</button>
                </div>
                <div class="tbp-order-modal-body" id="tbp-modal-body">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
