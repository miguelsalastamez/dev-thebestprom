<?php
/**
 * Frontend Display for Manual Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display balance and payments in "My Account > View Order"
 */
if ( ! function_exists( 'wcmp_display_order_balance_frontend' ) ) {
    function wcmp_display_order_balance_frontend( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $payments = get_post_meta( $order_id, '_wcmp_payments_history', true );
        if ( ! is_array( $payments ) ) {
            $payments = array();
        }
        $total_order = (float) $order->get_total();
        $total_paid = wcmp_get_order_payments_total( $order_id );
        $balance = $total_order - $total_paid;

        // Zen Design Styling
        echo '<style>
            .wcmp-zen-card { background: #fff; border: 1px solid #e1e4e8; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-top: 3em; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .wcmp-zen-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f3f5; padding-bottom: 15px; }
            .wcmp-zen-header h2 { margin: 0; font-size: 1.25em; color: #2c3e50; font-weight: 600; }
            .wcmp-zen-summary { display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 10px; }
            .wcmp-summary-item { display: flex; flex-direction: column; }
            .wcmp-summary-label { font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 5px; }
            .wcmp-summary-value { font-size: 24px; font-weight: 700; color: #1e293b; }
            .wcmp-balance-pending { color: #e74c3c; }
            .wcmp-balance-paid { color: #27ae60; }
            .wcmp-zen-table { width: 100%; border-collapse: collapse; font-size: 14px; }
            .wcmp-zen-table th { text-align: left; padding: 12px 10px; color: #94a3b8; font-weight: 600; border-bottom: 1px solid #f1f3f5; }
            .wcmp-zen-table td { padding: 15px 10px; border-bottom: 1px solid #f8fafc; color: #334155; }
            .wcmp-zen-table tr:hover { background: #fcfdfe; }
            .wcmp-badge { background: #e2e8f0; color: #475569; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
            .wcmp-button-receipt { background: #6772e5; color: #fff !important; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none !important; transition: all 0.2s; display: inline-block; }
            .wcmp-button-receipt:hover { background: #5469d4; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(103, 114, 229, 0.2); }

            /* Mobile Card Design for Payments History */
            @media (max-width: 767px) {
                .wcmp-zen-summary {
                    display: grid !important;
                    grid-template-columns: 1fr !important;
                    gap: 16px !important;
                    padding: 16px !important;
                }
                .wcmp-summary-value {
                    font-size: 20px !important;
                }
                .wcmp-zen-table, 
                .wcmp-zen-table thead, 
                .wcmp-zen-table tbody, 
                .wcmp-zen-table th, 
                .wcmp-zen-table td, 
                .wcmp-zen-table tr {
                    display: block !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .wcmp-zen-table thead {
                    display: none !important;
                }
                .wcmp-zen-table tr {
                    background: #ffffff !important;
                    border: 1px solid #e2e8f0 !important;
                    border-radius: 12px !important;
                    padding: 16px !important;
                    margin-bottom: 16px !important;
                    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.02) !important;
                }
                .wcmp-zen-table td {
                    padding: 10px 0 !important;
                    border-bottom: none !important;
                    display: flex !important;
                    justify-content: space-between !important;
                    align-items: flex-start !important;
                    font-size: 13px !important;
                    gap: 12px !important;
                }
                .wcmp-zen-table td > * {
                    text-align: right !important;
                    max-width: 60% !important;
                    word-wrap: break-word !important;
                }
                .wcmp-zen-table td::before {
                    font-weight: 700 !important;
                    color: #64748b !important;
                    text-transform: uppercase !important;
                    font-size: 11px !important;
                    letter-spacing: 0.05em !important;
                }
                .wcmp-zen-table td:nth-child(1)::before {
                    content: "Fecha" !important;
                }
                .wcmp-zen-table td:nth-child(2)::before {
                    content: "Referencia" !important;
                }
                .wcmp-zen-table td:nth-child(3)::before {
                    content: "Recibo" !important;
                }
                .wcmp-zen-table td:nth-child(4)::before {
                    content: "Monto" !important;
                }
                .wcmp-zen-table td:nth-child(4) {
                    border-top: 1px dashed #f1f5f9 !important;
                    margin-top: 8px !important;
                    padding-top: 12px !important;
                }
            }
        </style>';

        echo '<section class="wcmp-zen-card">';
        echo '<div class="wcmp-zen-header"><h2>📊 ' . __( 'Estado de Cuenta y Pagos', 'wc-manual-payments' ) . '</h2></div>';
        
        echo '<div class="wcmp-zen-summary">';
        echo '<div class="wcmp-summary-item"><span class="wcmp-summary-label">' . __('Total Compra', 'wc-manual-payments') . '</span><span class="wcmp-summary-value">' . wc_price($total_order) . '</span></div>';
        echo '<div class="wcmp-summary-item"><span class="wcmp-summary-label">' . __('Total Pagado', 'wc-manual-payments') . '</span><span class="wcmp-summary-value">' . wc_price($total_paid) . '</span></div>';
        $balance_class = $balance > 0 ? 'wcmp-balance-pending' : 'wcmp-balance-paid';
        $balance_label = $balance > 0 ? __('Saldo Pendiente', 'wc-manual-payments') : __('Saldo', 'wc-manual-payments');
        echo '<div class="wcmp-summary-item"><span class="wcmp-summary-label">' . $balance_label . '</span><span class="wcmp-summary-value ' . $balance_class . '">' . ($balance <= 0 ? __('$0.00 (Liquidado)', 'wc-manual-payments') : wc_price($balance)) . '</span></div>';
        echo '</div>';

        if ( ! empty( $payments ) ) {
            echo '<table class="wcmp-zen-table">';
            echo '<thead><tr><th>' . __('Fecha', 'wc-manual-payments') . '</th><th>' . __('Referencia / Nota', 'wc-manual-payments') . '</th><th>' . __('Recibo', 'wc-manual-payments') . '</th><th style="text-align:right;">' . __('Monto', 'wc-manual-payments') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ( $payments as $payment ) {
                $receipt_html = '';
                if ( ! empty($payment['transaction_id']) && (strpos($payment['transaction_id'], 'pi_') === 0 || strpos($payment['transaction_id'], 'ch_') === 0) ) {
                    $redirect_url = get_rest_url( null, 'wcmp/v1/receipt/' . $payment['transaction_id'] );
                    $receipt_html = '<a href="' . esc_url($redirect_url) . '" target="_blank" class="wcmp-button-receipt">' . __('Ver Recibo', 'wc-manual-payments') . '</a>';
                } elseif ( ! empty($payment['receipt_url']) ) {
                    $receipt_html = '<a href="' . esc_url($payment['receipt_url']) . '" target="_blank" class="wcmp-button-receipt">' . __('Ver Recibo', 'wc-manual-payments') . '</a>';
                }

                echo '<tr>';
                echo '<td>' . esc_html( $payment['date'] ) . '</td>';
                echo '<td>' . esc_html( $payment['note'] ) . '</td>';
                echo '<td>' . $receipt_html . '</td>';
                echo '<td style="text-align:right; font-weight:600; color:#27ae60;">' . wc_price( $payment['amount'] ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            // RETRO-COMPATIBILIDAD v1.8.21: Mostrar fila informativa si está pagado por pasarela
            if ( in_array( $order->get_status(), array( 'processing', 'completed' ) ) ) {
                echo '<table class="wcmp-zen-table">';
                echo '<thead><tr><th>' . __('Fecha', 'wc-manual-payments') . '</th><th>' . __('Referencia / Nota', 'wc-manual-payments') . '</th><th>' . __('Recibo', 'wc-manual-payments') . '</th><th style="text-align:right;">' . __('Monto', 'wc-manual-payments') . '</th></tr></thead>';
                echo '<tbody>';
                echo '<tr>';
                echo '<td>' . ( $order->get_date_paid() ? $order->get_date_paid()->date_i18n('d/m/Y') : $order->get_date_created()->date_i18n('d/m/Y') ) . '</td>';
                echo '<td>' . sprintf( __('Pago acreditado vía %s (Pasarela)', 'wc-manual-payments'), $order->get_payment_method_title() ) . '</td>';
                echo '<td><span class="wcmp-badge">' . __('Automático', 'wc-manual-payments') . '</span></td>';
                echo '<td style="text-align:right; font-weight:600; color:#27ae60;">' . wc_price( $order->get_total() ) . '</td>';
                echo '</tr>';
                echo '</tbody></table>';
            } else {
                echo '<p style="color: #94a3b8; font-style: italic; font-size: 13px;">' . __('No hay abonos manuales registrados aún.', 'wc-manual-payments') . '</p>';
            }
        }
        
        echo '</section>';
    }
}
add_action( 'woocommerce_view_order', 'wcmp_display_order_balance_frontend', 20 );

/**
 * Optional: Inject balance in the order list (My Account > Orders)
 */
if ( ! function_exists( 'wcmp_display_balance_in_orders_list' ) ) {
    function wcmp_display_balance_in_orders_list( $order_get ) {
        // Handle different WC versions
        $order_id = is_numeric($order_get) ? $order_get : (is_object($order_get) ? $order_get->get_id() : 0);
        $order = is_object($order_get) ? $order_get : wc_get_order($order_id);
        
        if (!$order) return;

        $total_order = (float) $order->get_total();
        $total_paid = wcmp_get_order_payments_total( $order_id );
        $balance = $total_order - $total_paid;

        if ( $total_paid > 0 ) {
            echo '<br><small style="color: #666;">' . sprintf( __( 'Pagado: %s', 'wc-manual-payments' ), wc_price( $total_paid ) ) . '</small>';
            if ( $balance > 0 ) {
                echo '<br><small style="color: #d9534f;">' . sprintf( __( 'Saldo: %s', 'wc-manual-payments' ), wc_price( $balance ) ) . '</small>';
            }
        }
    }
}
add_action( 'woocommerce_my_account_my_orders_column_order-total', 'wcmp_display_balance_in_orders_list' );
