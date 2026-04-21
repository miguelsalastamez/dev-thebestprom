<?php
/**
 * Plugin Name: WooCommerce Manual Payments Tracking
 * Description: Permite registrar abonos manuales en pedidos de WooCommerce, controlando el saldo y actualizando el estado automáticamente.
 * Version: 1.8.22
 * Author: Antigravity
 * Text Domain: wc-manual-payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 0. Settings and Configuration
add_action('admin_menu', function() {
    add_submenu_page(
        'wcmp-reports',
        __( 'Ajustes Stripe', 'wc-manual-payments' ),
        __( 'Ajustes Stripe', 'wc-manual-payments' ),
        'manage_woocommerce',
        'wcmp-settings',
        'wcmp_render_settings_page'
    );
}, 100);

if ( ! function_exists( 'wcmp_render_settings_page' ) ) {
    function wcmp_render_settings_page() {
        if ( isset($_POST['wcmp_save_settings']) ) {
            check_admin_referer('wcmp_save_settings');
            
            $new_key = trim(sanitize_text_field($_POST['wcmp_stripe_secret_key'] ?? ''));
            
            // Seguridad v1.8.7: Jamás guardar una llave que parezca enmascarada (con asteriscos o prefijo mk_)
            if ( ! empty($new_key) ) {
                if ( strpos($new_key, '*') === false && strpos($new_key, 'mk_') === false && strpos($new_key, 'sk_') === 0 ) {
                    update_option('wcmp_stripe_secret_key', $new_key);
                    echo '<div class="notice notice-success is-dismissible"><p>✅ Llave de Stripe actualizada con éxito.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>❌ <strong>Error:</strong> La llave ingresada no es válida. Debe empezar con "sk_" y no contener asteriscos.</p></div>';
                }
            } elseif ( isset($_POST['wcmp_stripe_secret_key']) && empty($new_key) ) {
                // Si enviaron vacío intencionalmente, quizá quieren borrarla (no recomendado pero permitido)
                // update_option('wcmp_stripe_secret_key', ''); 
            }

            if ( isset($_GET['wcmp_clear_logs']) ) {
                $log_file = WP_CONTENT_DIR . '/wcmp-stripe-debug.log';
                if ( file_exists($log_file) ) { unlink($log_file); }
                echo '<div class="updated"><p>Registros borrados.</p></div>';
            }
        }

        if ( isset($_POST['wcmp_reset_data']) ) {
            check_admin_referer('wcmp_reset_all');
            
            $current_user = wp_get_current_user();
            $password = isset($_POST['wcmp_reset_password']) ? $_POST['wcmp_reset_password'] : '';

            if ( wp_check_password($password, $current_user->data->user_pass, $current_user->ID) ) {
                delete_option('wcmp_orphan_payments');
                delete_option('wcmp_last_webhook_signal');
                delete_option('wcmp_stripe_secret_key');
                
                global $wpdb;
                $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_wcmp_payments_history', '_wcmp_last_transaction_id')");
                
                echo '<div class="notice notice-error"><p><strong>TODOS LOS DATOS HAN SIDO BORRADOS DEFINITIVAMENTE.</strong></p></div>';
            } else {
                echo '<div class="error"><p>Error: La contraseña es incorrecta. No se ha borrado ningún dato.</p></div>';
            }
        }

        $key = get_option('wcmp_stripe_secret_key', '');
        $display_key = '';
        if ( ! empty($key) ) {
            $display_key = 'sk_...' . substr($key, -4) . ' (********)';
        }
        ?>
        <div class="wrap">
            <h1><?php _e( 'Ajustes de Seguridad - Stripe', 'wc-manual-payments' ); ?></h1>
            <form method="post">
                <?php wp_nonce_field('wcmp_save_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wcmp_stripe_secret_key"><?php _e( 'Stripe Secret Key', 'wc-manual-payments' ); ?></label></th>
                        <td>
                            <input name="wcmp_stripe_secret_key" type="password" id="wcmp_stripe_secret_key" 
                                   placeholder="<?php echo esc_attr($display_key ?: 'sk_test_...'); ?>" 
                                   class="regular-text" autocomplete="off">
                            <p class="description">
                                <?php if ( ! empty($key) ) : ?>
                                    <span style="color: green; font-weight: bold;">✅ <?php _e( 'Llave configurada y protegida.', 'wc-manual-payments' ); ?></span><br>
                                <?php endif; ?>
                                <?php _e( 'Ingresa una nueva llave solo si deseas cambiarla. Por seguridad, la actual está oculta.', 'wc-manual-payments' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="wcmp_save_settings" id="submit" class="button button-primary" value="<?php _e( 'Guardar Cambios', 'wc-manual-payments' ); ?>">
                </p>
            </form>
            
            <div style="margin-top: 30px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h2 style="margin-top:0;"><?php _e( 'Registro de Diagnóstico (Stripe)', 'wc-manual-payments' ); ?></h2>
                <p class="description"><?php _e( 'Últimos eventos de sincronización. Si ves "Invalid API Key", re-ingresa tu Secret Key.', 'wc-manual-payments' ); ?></p>
                <div style="background: #23282d; color: #32d74b; padding: 15px; font-family: monospace; font-size: 11px; max-height: 300px; overflow-y: auto; border-radius: 4px; border: 1px solid #000; line-height: 1.4;">
                    <?php
                    $log_file = WP_CONTENT_DIR . '/wcmp-stripe-debug.log';
                    if ( file_exists( $log_file ) ) {
                        $log_content = file_get_contents($log_file);
                        echo nl2br(esc_html($log_content));
                    } else {
                        echo '<span style="color: #888;">' . __( 'No hay registros aún.', 'wc-manual-payments' ) . '</span>';
                    }
                    ?>
                </div>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=wcmp-settings&wcmp_clear_logs=1&wcmp_save_settings=1'); ?>" 
                       class="button button-secondary" style="margin-top: 10px;"
                       onclick="return confirm('¿Borrar y reiniciar logs?');"><?php _e( 'Limpiar Historial de Diagnóstico', 'wc-manual-payments' ); ?></a>
                </p>
            </div>
            
            <div style="margin-top: 50px; padding: 20px; border: 1px solid #cc0000; background: #fff;">
                <h3 style="color: #cc0000;"><?php _e( 'Zona de Peligro: Borrar histórico', 'wc-manual-payments' ); ?></h3>
                <p><?php _e( 'Esta acción borrará todos los abonos registrados en los pedidos, pagos huérfanos y ajustes.', 'wc-manual-payments' ); ?></p>
                <form method="post" autocomplete="off" onsubmit="return confirm('¿ESTÁS ABSOLUTAMENTE SEGURO? Se perderán todos los datos de pagos registrados.');">
                    <?php wp_nonce_field('wcmp_reset_all'); ?>
                    <div style="margin-bottom: 15px;">
                        <label for="wcmp_reset_password" style="font-weight: bold; color: #cc0000;"><?php _e( 'Confirma tu contraseña de administrador:', 'wc-manual-payments' ); ?></label><br>
                        <input name="wcmp_reset_password" type="password" id="wcmp_reset_password" class="regular-text" required>
                    </div>
                    <input type="submit" name="wcmp_reset_data" class="button" style="background: #cc0000; color: #fff; border-color: #990000;" value="<?php _e( 'Borrar todos los datos definitivamente', 'wc-manual-payments' ); ?>">
                </form>
            </div>
        </div>
        <?php
    }
}

// Include required files
require_once plugin_dir_path( __FILE__ ) . 'includes/order-logic.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-order-metabox.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend-display.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/stripe-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-reports.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/virtual-raffle-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-raffle-metabox.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-order-reports.php';

/**
 * Register Custom Order Status: Parcialmente Pagado
 */
if ( ! function_exists( 'wcmp_register_partially_paid_status' ) ) {
    function wcmp_register_partially_paid_status() {
        register_post_status( 'wc-p-pagado', array(
            'label'                     => _x( 'Pagado Parcialmente', 'Order status', 'wc-manual-payments' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Pagado Parcialmente <span class="count">(%s)</span>', 'Pagado Parcialmente <span class="count">(%s)</span>', 'wc-manual-payments' )
        ) );
    }
}
add_action( 'init', 'wcmp_register_partially_paid_status' );

/**
 * Add custom status to WooCommerce
 */
if ( ! function_exists( 'wcmp_add_partially_paid_to_order_statuses' ) ) {
    function wcmp_add_partially_paid_to_order_statuses( $order_statuses ) {
        $new_order_statuses = array();

        foreach ( $order_statuses as $key => $status ) {
            $new_order_statuses[ $key ] = $status;
            if ( 'wc-on-hold' === $key ) {
                $new_order_statuses['wc-p-pagado'] = _x( 'Pagado Parcialmente', 'Order status', 'wc-manual-payments' );
            }
        }

        return $new_order_statuses;
    }
}
add_filter( 'wc_order_statuses', 'wcmp_add_partially_paid_to_order_statuses' );

/**
 * Rename "Processing" status label to "[Pagado con Tarjeta]"
 */
add_filter( 'woocommerce_get_status_label', 'wcmp_customize_processing_label', 20, 2 );
function wcmp_customize_processing_label( $label, $order ) {
    if ( $label === 'Procesando' || $label === 'Processing' ) {
        return '[Pagado con Tarjeta]';
    }
    return $label;
}

add_filter( 'wc_order_statuses', 'wcmp_rename_processing_in_statuses', 20 );
function wcmp_rename_processing_in_statuses( $order_statuses ) {
    if ( isset( $order_statuses['wc-processing'] ) ) {
        $order_statuses['wc-processing'] = '[Pagado con Tarjeta]';
    }
    return $order_statuses;
}

/**
 * Test Route to verify REST API registration
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'wcmp/v1', '/test', array(
        'methods' => 'GET',
        'callback' => function() { return array('status' => 'OK', 'version' => '1.5.9'); },
        'permission_callback' => '__return_true',
    ) );
} );
