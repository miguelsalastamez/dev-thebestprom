<?php
/**
 * Plugin Name: WC Disable Payment Methods per Product
 * Description: Permite activar o desactivar métodos de pago específicos para cada producto individualmente.
 * Version: 1.0.0
 * Author: Antigravity
 * Text Domain: wc-disable-payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase principal del plugin
 */
class WC_Disable_Payments_Per_Product {

    public function __construct() {
        // Admin: Añadir campos al producto (Cambiado a General para asegurar visibilidad)
        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_payment_methods_fields' ) );
        
        // Admin: Guardar campos del producto
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_payment_methods_fields' ) );

        // Frontend: Filtrar pasarelas de pago
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_available_gateways' ), 10, 1 );
    }

    /**
     * Añade los checkboxes de métodos de pago en la pestaña General del producto.
     */
    public function add_payment_methods_fields() {
        echo '<div class="options_group show_if_simple show_if_virtual" style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px;">';
        echo '<h3 style="margin-left: 10px; color: #2271b1;">' . __( '💳 Desactivar Métodos de Pago', 'wc-disable-payments' ) . '</h3>';
        echo '<p class="form-field" style="margin-left: 10px; font-style: italic;">' . __( 'Selecciona los métodos que NO quieres que aparezcan cuando este producto esté en el carrito.', 'wc-disable-payments' ) . '</p>';

        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        $product_id = get_the_ID();
        $disabled_methods = get_post_meta( $product_id, '_disabled_payment_methods', true );
        
        if ( ! is_array( $disabled_methods ) ) {
            $disabled_methods = array();
        }

        if ( empty( $gateways ) ) {
            echo '<p style="margin-left: 10px;">' . __( 'No hay métodos de pago activos en WooCommerce.', 'wc-disable-payments' ) . '</p>';
        }

        foreach ( $gateways as $gateway ) {
            $is_checked = in_array( $gateway->id, $disabled_methods ) ? 'checked="checked"' : '';
            
            echo '<p class="form-field">';
            echo '<label for="disable_payment_' . esc_attr( $gateway->id ) . '">' . esc_html( $gateway->get_title() ) . '</label>';
            echo '<input type="checkbox" name="disabled_payment_methods[]" id="disable_payment_' . esc_attr( $gateway->id ) . '" value="' . esc_attr( $gateway->id ) . '" ' . $is_checked . ' style="margin-left: 10px;">';
            echo '<span class="description" style="margin-left: 10px;">' . sprintf( esc_html__( 'Desactivar %s para este producto.', 'wc-disable-payments' ), $gateway->get_title() ) . '</span>';
            echo '</p>';
        }

        echo '</div>';
    }

    /**
     * Guarda la configuración de métodos desactivados.
     */
    public function save_payment_methods_fields( $post_id ) {
        // Si no se envía nada, guardamos un array vacío para limpiar los anteriores
        $disabled_methods = isset( $_POST['disabled_payment_methods'] ) ? array_map( 'sanitize_text_field', (array) $_POST['disabled_payment_methods'] ) : array();
        update_post_meta( $post_id, '_disabled_payment_methods', $disabled_methods );
    }

    /**
     * Filtra las pasarelas de pago disponibles en el checkout según los productos del carrito.
     */
    public function filter_available_gateways( $available_gateways ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return $available_gateways;
        }

        if ( ! WC()->cart ) {
            return $available_gateways;
        }

        $all_disabled_methods = array();

        // Recorrer productos del carrito
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            $disabled_for_product = get_post_meta( $product_id, '_disabled_payment_methods', true );

            if ( is_array( $disabled_for_product ) && ! empty( $disabled_for_product ) ) {
                $all_disabled_methods = array_unique( array_merge( $all_disabled_methods, $disabled_for_product ) );
            }
        }

        // Si hay métodos deshabilitados, eliminarlos de la lista de disponibles
        if ( ! empty( $all_disabled_methods ) ) {
            foreach ( $all_disabled_methods as $gateway_id ) {
                if ( isset( $available_gateways[ $gateway_id ] ) ) {
                    unset( $available_gateways[ $gateway_id ] );
                }
            }
        }

        return $available_gateways;
    }
}

// Inicializar el plugin
new WC_Disable_Payments_Per_Product();
