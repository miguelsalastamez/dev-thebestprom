<?php
/**
 * Admin Order Meta Box for Manual Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Meta Box to Order Edit Screen
 */
if ( ! function_exists( 'wcmp_add_payments_metabox' ) ) {
    function wcmp_add_payments_metabox() {
        add_meta_box(
            'wcmp_payments_history_box',
            __( 'Registro de Pagos Manuales', 'wc-manual-payments' ),
            'wcmp_payments_metabox_content',
            'shop_order',
            'normal',
            'high'
        );
    }
}
add_action( 'add_meta_boxes', 'wcmp_add_payments_metabox' );

/**
 * Meta Box Content
 */
if ( ! function_exists( 'wcmp_payments_metabox_content' ) ) {
    function wcmp_payments_metabox_content( $post ) {
        $order_id = $post->ID;
        $order = wc_get_order( $order_id );
        $payments = get_post_meta( $order_id, '_wcmp_payments_history', true );
        if ( ! is_array( $payments ) ) {
            $payments = array();
        }
        $total_order = (float) $order->get_total();
        $total_paid = wcmp_get_order_payments_total( $order_id );
        $balance = $total_order - $total_paid;
        $transaction_id = uniqid('wcmp_'); 

        wp_nonce_field( 'wcmp_save_payment', 'wcmp_nonce' );
        ?>
        <div class="wcmp-metabox-wrapper">
            <input type="hidden" name="wcmp_transaction_id" value="<?php echo esc_attr( $transaction_id ); ?>">
            <table class="wp-list-table widefat fixed striped" style="margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th><?php _e( 'Fecha', 'wc-manual-payments' ); ?></th>
                        <th><?php _e( 'Referencia/Nota', 'wc-manual-payments' ); ?></th>
                        <th style="width: 150px;"><?php _e( 'Monto', 'wc-manual-payments' ); ?></th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $payments ) ) : ?>
                        <tr><td colspan="4"><?php _e( 'No hay pagos registrados.', 'wc-manual-payments' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $payments as $index => $payment ) : ?>
                            <tr>
                                <td>
                                    <input type="date" name="wcmp_edit_payment[<?php echo $index; ?>][date]" value="<?php echo esc_attr( $payment['date'] ); ?>" style="width: 100%;">
                                </td>
                                <td>
                                    <input type="text" name="wcmp_edit_payment[<?php echo $index; ?>][note]" value="<?php echo esc_attr( $payment['note'] ); ?>" style="width: 100%;">
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <span>$</span>
                                        <input type="number" step="0.01" name="wcmp_edit_payment[<?php echo $index; ?>][amount]" value="<?php echo esc_attr( $payment['amount'] ); ?>" style="width: 100%;">
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <label style="cursor: pointer; color: #a00;" title="<?php _e( 'Eliminar', 'wc-manual-payments' ); ?>">
                                        <input type="checkbox" name="wcmp_delete_payment[]" value="<?php echo $index; ?>" style="display: none;">
                                        <span class="dashicons dashicons-trash" onclick="this.parentElement.parentElement.parentElement.style.opacity='0.5'; this.style.color='red';"></span>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" style="text-align: right;"><strong><?php _e( 'Total Abonado:', 'wc-manual-payments' ); ?></strong></th>
                        <th colspan="2"><strong><?php echo wc_price( $total_paid ); ?></strong></th>
                    </tr>
                    <tr>
                        <th colspan="2" style="text-align: right;"><strong><?php _e( 'Saldo Pendiente:', 'wc-manual-payments' ); ?></strong></th>
                        <th colspan="2" style="color: <?php echo $balance > 0 ? 'red' : 'green'; ?>;"><strong><?php echo wc_price( $balance ); ?></strong></th>
                    </tr>
                </tfoot>
            </table>

            <hr>
            <h4><?php _e( 'Registrar Nuevo Pago', 'wc-manual-payments' ); ?></h4>
            <div style="display: flex; gap: 10px; align-items: flex-end; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <div style="width: 150px;">
                    <label style="font-weight: bold;"><?php _e( 'Fecha', 'wc-manual-payments' ); ?></label><br>
                    <input type="date" name="wcmp_new_payment_date" value="<?php echo date('Y-m-d'); ?>" style="width: 100%;">
                </div>
                <div style="flex-grow: 1;">
                    <label style="font-weight: bold;"><?php _e( 'Nota / Referencia', 'wc-manual-payments' ); ?></label><br>
                    <input type="text" name="wcmp_new_payment_note" placeholder="Ej: DEPOSITO EN EFECTIVO/28484" style="width: 100%;">
                </div>
                <div style="width: 120px;">
                    <label style="font-weight: bold;"><?php _e( 'Monto ($)', 'wc-manual-payments' ); ?></label><br>
                    <input type="number" step="0.01" name="wcmp_new_payment_amount" placeholder="0.00" style="width: 100%;">
                </div>
                <div>
                    <button type="submit" class="button button-primary" style="height: 30px;"><?php _e( 'Agregar Pago', 'wc-manual-payments' ); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
}

/**
 * Save Payment Data
 */
if ( ! function_exists( 'wcmp_save_payments_metabox_data' ) ) {
    function wcmp_save_payments_metabox_data( $post_id ) {
        if ( ! isset( $_POST['wcmp_nonce'] ) || ! wp_verify_nonce( $_POST['wcmp_nonce'], 'wcmp_save_payment' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $payments = get_post_meta( $post_id, '_wcmp_payments_history', true );
        if ( ! is_array( $payments ) ) {
            $payments = array();
        }
        $updated_payments = array();
        $something_changed = false;

        // 1. Process Edits and Deletions
        if ( isset( $_POST['wcmp_edit_payment'] ) ) {
            $delete_indices = isset( $_POST['wcmp_delete_payment'] ) ? $_POST['wcmp_delete_payment'] : array();
            
            foreach ( $_POST['wcmp_edit_payment'] as $index => $data ) {
                if ( in_array( $index, $delete_indices ) ) {
                    $something_changed = true;
                    continue;
                }

                $amount = (float) $data['amount'];
                if ( $amount > 0 ) {
                    $updated_payments[] = array(
                        'date'   => sanitize_text_field( $data['date'] ),
                        'note'   => sanitize_text_field( $data['note'] ),
                        'amount' => $amount
                    );
                }
            }
            
            if ( serialize( $updated_payments ) !== serialize( $payments ) ) {
                $something_changed = true;
                update_post_meta( $post_id, '_wcmp_payments_history', $updated_payments );
            }
        }

        // 2. Process New Payment
        $new_amount = isset( $_POST['wcmp_new_payment_amount'] ) ? (float) $_POST['wcmp_new_payment_amount'] : 0;
        $transaction_id = isset( $_POST['wcmp_transaction_id'] ) ? sanitize_text_field( $_POST['wcmp_transaction_id'] ) : '';
        $last_transaction = get_post_meta( $post_id, '_wcmp_last_transaction_id', true );

        if ( $new_amount > 0 && $transaction_id && $transaction_id !== $last_transaction ) {
            $date = sanitize_text_field( $_POST['wcmp_new_payment_date'] );
            $note = sanitize_text_field( $_POST['wcmp_new_payment_note'] );

            if ( wcmp_add_order_payment( $post_id, $new_amount, $note, $date, $transaction_id ) ) {
                update_post_meta( $post_id, '_wcmp_last_transaction_id', $transaction_id );
                $something_changed = true;
            }
        }

        if ( $something_changed ) {
            clean_post_cache( $post_id );
            wcmp_update_order_status_by_balance( $post_id );
        }
    }
}
add_action( 'woocommerce_process_shop_order_meta', 'wcmp_save_payments_metabox_data', 50 );
