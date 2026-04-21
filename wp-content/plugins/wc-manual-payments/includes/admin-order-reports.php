<?php
/**
 * Order Reports for WC Manual Payments - Baseline
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Submenu Page
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'wcmp-reports',
        __( 'Reportes de Pedidos', 'wc-manual-payments' ),
        __( 'Reportes de Pedidos', 'wc-manual-payments' ),
        'manage_woocommerce',
        'wcmp-order-reports',
        'wcmp_render_baseline_order_reports_page'
    );
}, 100 );


function wcmp_get_baseline_orders_data( $ids_only = false ) {
    try {
        $limit = isset($_GET['report_limit']) ? intval($_GET['report_limit']) : 50;
        $exporting = ( isset($_GET['export_wcmp_xlsx']) || $ids_only ) ? true : false;

    // BLOQUEO INICIAL: Si no se ha dado clic en filtrar, retornamos vacío para no colgar la base de datos
    $is_filtering = isset($_GET['wcmp_filter_active']) || isset($_GET['paged']) || isset($_GET['orderby']);
    if ( ! $is_filtering ) {
        return array(
            'results' => array(),
            'total'   => 0,
            'pages'   => 0,
            'paged'   => 1,
            'args'    => array()
        );
    }
    
    $manual_products = isset($_GET['product_ids']) ? array_filter(array_map('intval', $_GET['product_ids'])) : array();
        $manual_events   = isset($_GET['event_ids']) ? array_filter(array_map('intval', $_GET['event_ids'])) : array();

        if ( ! empty( $manual_events ) ) {
            global $wpdb;
            $event_ids_sql = implode( ',', $manual_events );
            // Escáner Universal: Busca cualquier meta que enlace hacia el ID del Evento dentro de un Producto WC.
            // Esto vence cualquier cambio estructural de Event Tickets Plus y sus llaves secretas.
            $linked_products = $wpdb->get_col("
                SELECT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key LIKE '%event%' 
                AND meta_value IN ($event_ids_sql)
                AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='product')
            ");
            
            // Intersección Estricta: Si ya había productos buscados, solo dejamos los que crucen con el evento.
            if ( empty($manual_products) ) {
                $manual_products = $linked_products;
            } else {
                $manual_products = array_intersect( $manual_products, $linked_products );
            }
            
            $manual_products = array_unique( array_filter( array_map('intval', $manual_products) ) );
            
            // Si eligieron evento pero no arrojó tickets (quizá no tiene), colapsamos intencionalmente el arreglo
            if ( empty($manual_products) ) {
                $manual_products = array(-1);
            }
        }

        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
        $order_dir = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
        
        // v1.8.22 Fix: No sobrescribir $exporting aquí, ya se definió al inicio considerando $ids_only

        $args = array(
            'type'    => 'shop_order',
            'orderby' => $orderby,
            'order'   => $order_dir,
            'return'  => $ids_only ? 'ids' : 'objects',
        );

        if ( $exporting ) {
            $args['limit'] = -1; // Todo el evento para exportar
        } else {
            $args['limit']    = $limit;
            $args['paginate'] = true;
            $args['page']     = $paged;
        }

        if ( ! empty( $manual_products ) ) {
            global $wpdb;
            $product_ids_sql = implode( ',', $manual_products );
            $order_ids = $wpdb->get_col( "
                SELECT DISTINCT order_items.order_id
                FROM {$wpdb->prefix}woocommerce_order_items AS order_items
                LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta 
                    ON order_items.order_item_id = itemmeta.order_item_id
                WHERE itemmeta.meta_key IN ('_product_id', '_variation_id') AND itemmeta.meta_value IN ($product_ids_sql)
                ORDER BY order_items.order_id DESC
            " );
            
            $args['post__in'] = array_map('intval', $order_ids);
            
            if ( empty($args['post__in']) ) {
                $args['post__in'] = array(-1);
            }
        }

        $manual_statuses = isset($_GET['order_status']) ? array_filter(array_map('sanitize_text_field', $_GET['order_status'])) : array();
        if ( ! empty( $manual_statuses ) ) {
            $args['status'] = $manual_statuses;
        }

        // Aplicar filtros de fecha HTML
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to   = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $date_type = isset($_GET['date_type']) ? sanitize_text_field($_GET['date_type']) : 'date_created';

        if ( $date_from || $date_to ) {
            $date_query_str = '';
            if ( $date_from && $date_to ) {
                $date_query_str = $date_from . '...' . $date_to;
            } elseif ( $date_from ) {
                $date_query_str = '>=' . $date_from;
            } elseif ( $date_to ) {
                $date_query_str = '<=' . $date_to;
            }
            $args[ $date_type ] = $date_query_str;
        }

        // Filtro de Tómbola (Con Rifa / Sin Rifa)
        $raffle_filter = isset($_GET['raffle_filter']) ? sanitize_text_field($_GET['raffle_filter']) : '';
        if ( ! empty( $raffle_filter ) ) {
            $meta_query = isset($args['meta_query']) ? $args['meta_query'] : array();
            
            if ( $raffle_filter === 'with_raffle' ) {
                $meta_query[] = array(
                    'key'     => '_tbp_boletos_tombola',
                    'value'   => 0,
                    'compare' => '>',
                    'type'    => 'NUMERIC'
                );
            } elseif ( $raffle_filter === 'no_raffle' ) {
                // Para "Sin Rifa", buscamos que no exista el meta o que sea 0
                $meta_query[] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_tbp_boletos_tombola',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key'     => '_tbp_boletos_tombola',
                        'value'   => 0,
                        'compare' => '<=',
                        'type'    => 'NUMERIC'
                    )
                );
            }
            $args['meta_query'] = $meta_query;
        }

        // Búsqueda Libre de Textos (Nombre de Cliente, Email, o #Pedido numérico)
        $search_term = isset($_GET['wcmp_search']) ? sanitize_text_field($_GET['wcmp_search']) : '';
        if ( ! empty( $search_term ) ) {
            $searched_ids = wc_order_search( ltrim($search_term, '#') ); // Permite buscar exacto "31144" o "#31144"
            
            if ( empty( $searched_ids ) ) {
                $searched_ids = array(-1); // Forzar cero resultados si la búsqueda de texto libre falló
            }
            
            // Intersectar con el filtro de Tickets (si $args['post__in'] ya estaba poblado)
            if ( isset( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
                $args['post__in'] = array_intersect( $args['post__in'], $searched_ids );
                if ( empty($args['post__in']) ) {
                    $args['post__in'] = array(-1);
                }
            } else {
                $args['post__in'] = $searched_ids;
            }
        }

        $query_results = wc_get_orders( $args );
        
        if ( $ids_only ) {
            return $query_results; // Retorna array de IDs
        }

        // Manejar estructura según si se pidió paginación o no
        if ( ! $exporting ) {
            $orders = $query_results->orders;
            $total_orders = $query_results->total;
            $max_pages = $query_results->max_num_pages;
        } else {
            $orders = $query_results;
            $total_orders = count($orders);
            $max_pages = 1;
        }

        $results = array();

        foreach ( $orders as $order ) {
            // Failsafe inquebrantable: Si por alguna razón el objeto no es un pedido válido o es un reembolso infiltrado, lo descartamos.
            if ( ! is_object($order) || ! method_exists( $order, 'get_formatted_billing_full_name' ) || is_a( $order, 'WC_Order_Refund' ) ) {
                continue;
            }

            $order_id = $order->get_id();
            $date     = $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '';
            
            $customer = $order->get_formatted_billing_full_name();
            if ( empty( trim( $customer ) ) ) {
                $customer = $order->get_meta( '_billing_first_name' ) . ' ' . $order->get_meta( '_billing_last_name' );
            }

            $product_names = array();
            foreach ( $order->get_items() as $item ) {
                $pid = $item->get_product_id();
                // Filtrar visualmente los ítems si hay un filtro activo, para mostrar solo lo relevante
                if ( empty($manual_products) || in_array($pid, $manual_products) ) {
                    $product_names[] = $item->get_name() . ' (x' . $item->get_quantity() . ')';
                }
            }

            $wcmp_payments = wcmp_get_order_payments_total( $order_id );
            $total         = floatval($order->get_total());
            $ord_status    = $order->get_status();

            if ( in_array( $ord_status, array( 'processing', 'completed' ) ) && $wcmp_payments <= 0 ) {
                $paid = $total;
            } else {
                $paid = $wcmp_payments;
            }
            
            $balance = max( 0, $total - $paid );

            $results[] = array(
                'id'       => $order_id,
                'status'   => wc_get_order_status_name( $ord_status ),
                'date'     => $date,
                'name'     => $customer,
                'products' => implode( '<br>', $product_names ),
                'total'    => $total,
                'paid'     => $paid,
                'balance'  => $balance
            );
        }

        if ( $exporting ) {
            set_time_limit(600);
            ini_set('memory_limit', '512M');
            
            require_once dirname(__FILE__) . '/lib/SimpleXLSXGen.php';
            
            $all_order_ids = array_column($results, 'id');
            $batch_attendees = [];
            if ( function_exists('tbp_actividades_get_batch_attendees_meta') ) {
                $batch_attendees = tbp_actividades_get_batch_attendees_meta($all_order_ids);
            }
            
            $dynamic_headers = array();
            $master_export_data = array();

            // Pass 1: Prepare Flattened Data and Collect Unique Meta Labels
            foreach ( $results as $r ) {
                $order_id = $r['id'];
                $attendees = isset($batch_attendees[$order_id]) ? $batch_attendees[$order_id] : [];

                if ( empty($attendees) ) {
                    // One row per order if no attendees
                    $master_export_data[] = array( 'order' => $r, 'attendee_map' => null );
                } else {
                    foreach ( $attendees as $source_id => $groups ) {
                        // Support for multiple guests in one source (tbp-actividades logic)
                        if ( ! empty($groups) ) {
                            reset($groups);
                            $first_key = key($groups);
                            if ( ! is_numeric($first_key) && $first_key !== 'Datos' ) {
                                $groups = array( 'Datos' => $groups );
                            }
                        }

                        foreach ( $groups as $guest ) {
                            if ( ! is_array($guest) ) continue;
                            
                            $guest_map = [];
                            foreach ( $guest as $key => $val ) {
                                $label = is_array($val) ? ($val['label'] ?? $key) : $key;
                                $normalized_label = ucwords(str_replace(array('-', '_'), ' ', $label));
                                $guest_map[$normalized_label] = is_array($val) ? ($val['value'] ?? '') : $val;
                                
                                if ( ! in_array($normalized_label, $dynamic_headers) ) {
                                    $dynamic_headers[] = $normalized_label;
                                }
                            }
                            
                            $master_export_data[] = array( 'order' => $r, 'attendee_map' => $guest_map );
                        }
                    }
                }
            }

            // Pass 2: Build XLSX Rows
            $rows = array();
            $header_row = array_merge( 
                array( 'ID Pedido', 'Estado', 'Fecha', 'Cliente', 'Productos', 'Total', 'Pagos Realizados', 'Saldo' ), 
                $dynamic_headers 
            );
            $rows[] = $header_row;

            foreach ( $master_export_data as $entry ) {
                $r = $entry['order'];
                $att_map = $entry['attendee_map'];

                $row = array(
                    $r['id'],
                    $r['status'],
                    $r['date'],
                    $r['name'],
                    str_replace('<br>', ', ', $r['products']),
                    $r['total'],
                    $r['paid'],
                    $r['balance']
                );

                // Add dynamic meta values using the map (O(1) lookup instead of nested loops)
                foreach ( $dynamic_headers as $h ) {
                    $row[] = isset($att_map[$h]) ? $att_map[$h] : '';
                }
                $rows[] = $row;
            }

            $xlsx = \Shuchkin\SimpleXLSXGen::fromArray( $rows );
            $xlsx->downloadAs( 'Reporte_Pedidos_Detallado_' . date('Y-m-d_H-i') . '.xlsx' );
            exit;
        }

        return array(
            'results' => $results,
            'total'   => $total_orders,
            'pages'   => $max_pages,
            'paged'   => $paged,
            'args'    => $args,
            'manual_products' => $manual_products // Pass this for the grand totals calculation
        );
        
    } catch (\Throwable $t) {
        return array('FATAL_ERROR' => "Error detectado en la línea " . $t->getLine() . " de " . basename($t->getFile()) . ": " . $t->getMessage());
    }
}


function wcmp_report_sort_url($field) {
    $current_orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'date';
    $current_order   = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    $new_order       = ($current_orderby === $field && $current_order === 'DESC') ? 'ASC' : 'DESC';
    
    $params = $_GET;
    $params['orderby'] = $field;
    $params['order']   = $new_order;
    unset($params['paged']); // Reset pagination on sort
    
    return admin_url('admin.php?' . http_build_query($params));
}

function wcmp_get_grand_totals_safe( $args, $manual_products ) {
    $args['limit'] = -1;
    $args['return'] = 'ids';
    unset($args['paginate']);
    unset($args['page']);
    
    $all_ids = wc_get_orders( $args );
    if ( empty($all_ids) ) {
        return array('piezas' => 0, 'vendido' => 0, 'recibido' => 0, 'saldo' => 0);
    }
    
    $piezas = 0;
    $vendido = 0;
    $recibido = 0;
    
    foreach ( $all_ids as $oid ) {
        $order = wc_get_order( $oid );
        if ( ! is_object($order) || ! method_exists( $order, 'get_formatted_billing_full_name' ) || is_a( $order, 'WC_Order_Refund' ) ) {
            continue;
        }
        
        $ord_status    = $order->get_status();
        $order_total   = (float) $order->get_total();
        $wcmp_payments = (float) wcmp_get_order_payments_total( $oid );
        
        if ( in_array( $ord_status, array( 'processing', 'completed' ) ) && $wcmp_payments <= 0 ) {
            $order_paid = $order_total;
        } else {
            $order_paid = $wcmp_payments;
        }
        
        $vendido  += $order_total;
        $recibido += $order_paid;
        
        foreach ( $order->get_items() as $item ) {
            $pid = $item->get_product_id();
            if ( empty($manual_products) || in_array($pid, $manual_products) ) {
                $piezas += $item->get_quantity();
            }
        }
        
        // Memory leak prevention
        if ( function_exists('clean_post_cache') ) {
            clean_post_cache( $oid );
        }
        unset($order);
    }
    
    return array(
        'piezas'   => $piezas,
        'vendido'  => $vendido,
        'recibido' => $recibido,
        'saldo'    => $vendido - $recibido
    );
}

function wcmp_render_baseline_order_reports_page() {
    $report_payload = wcmp_get_baseline_orders_data();
    $current_limit  = isset($_GET['report_limit']) ? intval($_GET['report_limit']) : 50;
    ?>
    <div class="wrap wcmp-reports-wrap">
        <?php if ( isset($report_payload['FATAL_ERROR']) ) : ?>
            <div style="background: #f8dbdb; border-left: 5px solid #d63638; padding: 15px; margin-bottom: 20px; font-weight: 500; font-size: 15px; font-family: monospace; color: #a00;">
                <?php echo esc_html($report_payload['FATAL_ERROR']); ?>
            </div>
            <?php return; ?>
        <?php endif; ?>

        <?php 
        $report_data = $report_payload['results'];
        $total_orders = $report_payload['total'];
        $max_pages = $report_payload['pages'] ?? 1;
        $current_page = $report_payload['paged'] ?? 1;
        $raw_args = $report_payload['args'] ?? array();
        $manual_products = $report_payload['manual_products'] ?? array();

        $grand_totals = array('piezas'=>0,'vendido'=>0,'recibido'=>0,'saldo'=>0);
        $is_filtering = isset($_GET['wcmp_filter_active']) || isset($_GET['paged']) || isset($_GET['orderby']);
        
        if ( $is_filtering ) {
            $grand_totals = wcmp_get_grand_totals_safe( $raw_args, $manual_products );
        }
        ?>

        <h1 class="wp-heading-inline"><?php _e( 'Reportes de Pedidos', 'wc-manual-payments' ); ?></h1>
        <hr class="wp-header-end">

        <!-- Toolbar Sencillo con Select2 de Productos y Eventos -->
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <form method="get" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <input type="hidden" name="page" value="wcmp-order-reports">
                <input type="hidden" name="wcmp_filter_active" value="1">
                
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; font-size: 12px; color: #50575e;"><?php _e( 'Eventos (Tribe Events):', 'wc-manual-payments' ); ?></label>
                    <select name="event_ids[]" class="wcmp-event-select2" multiple="multiple" style="min-width: 300px;" data-placeholder="Teclea para buscar evento...">
                        <?php
                        $events = get_posts( array( 'post_type' => 'tribe_events', 'numberposts' => 500, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );
                        $selected_evts = isset($_GET['event_ids']) ? array_map('intval', $_GET['event_ids']) : array();
                        foreach ( $events as $ev ) {
                            $sel = in_array( $ev->ID, $selected_evts ) ? 'selected' : '';
                            echo '<option value="' . esc_attr($ev->ID) . '" ' . $sel . '>' . esc_html($ev->post_title) . ' (ID: ' . $ev->ID . ')</option>';
                        }
                        ?>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; font-size: 12px; color: #50575e;"><?php _e( 'Tickets o Productos específicos:', 'wc-manual-payments' ); ?></label>
                    <select name="product_ids[]" class="wcmp-product-select2" multiple="multiple" style="min-width: 300px;" data-placeholder="Teclea para buscar ticket...">
                        <?php
                        // Fetch the most recent 500 published products to populate the dropdown
                        $all_prods = wc_get_products( array( 'limit' => 500, 'status' => 'publish', 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects' ) );
                        $selected_prods = isset($_GET['product_ids']) ? array_map('intval', $_GET['product_ids']) : array();
                        foreach ( $all_prods as $p ) {
                            $sel = in_array( $p->get_id(), $selected_prods ) ? 'selected' : '';
                            echo '<option value="' . esc_attr($p->get_id()) . '" ' . $sel . '>' . esc_html($p->get_name()) . ' (ID: ' . $p->get_id() . ')</option>';
                        }
                        ?>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; font-size: 12px; color: #50575e;"><?php _e( 'Estado del Pedido:', 'wc-manual-payments' ); ?></label>
                    <select name="order_status[]" class="wcmp-status-select2" multiple="multiple" style="min-width: 250px;" data-placeholder="Filtrar por estados...">
                        <?php
                        $statuses = wc_get_order_statuses();
                        $selected_statuses = isset($_GET['order_status']) ? array_map('sanitize_text_field', $_GET['order_status']) : array();
                        foreach ( $statuses as $status_key => $status_name ) {
                            $sel = in_array( $status_key, $selected_statuses ) ? 'selected' : '';
                            echo '<option value="' . esc_attr($status_key) . '" ' . $sel . '>' . esc_html($status_name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; font-size: 12px; color: #50575e;"><?php _e( '¿En Tómbola? (Rifa):', 'wc-manual-payments' ); ?></label>
                    <select name="raffle_filter" style="min-width: 150px; height: 30px;">
                        <option value=""><?php _e( 'Todos', 'wc-manual-payments' ); ?></option>
                        <option value="with_raffle" <?php selected(isset($_GET['raffle_filter']) ? $_GET['raffle_filter'] : '', 'with_raffle'); ?>><?php _e( 'Solo CON Rifa', 'wc-manual-payments' ); ?></option>
                        <option value="no_raffle" <?php selected(isset($_GET['raffle_filter']) ? $_GET['raffle_filter'] : '', 'no_raffle'); ?>><?php _e( 'Solo SIN Rifa', 'wc-manual-payments' ); ?></option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px; flex-grow: 1;">
                    <label style="font-weight: 600; font-size: 12px; color: #50575e;"><?php _e( 'Buscar Cliente o # Pedido:', 'wc-manual-payments' ); ?></label>
                    <div style="position: relative;">
                        <span class="dashicons dashicons-search" style="position: absolute; left: 8px; top: 6px; color: #8c8f94;"></span>
                        <input type="text" name="wcmp_search" 
                               value="<?php echo isset($_GET['wcmp_search']) ? esc_attr($_GET['wcmp_search']) : ''; ?>" 
                               placeholder="<?php _e('Ej: Juan, perez@mail.com, 31144', 'wc-manual-payments'); ?>" 
                               style="width: 100%; height: 30px; padding-left: 30px; border: 1px solid #8c8f94; border-radius: 3px;" />
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; font-size: 12px; color: #50575e;"><?php _e( 'Tipo de Fecha:', 'wc-manual-payments' ); ?></label>
                    <select name="date_type" style="min-width: 150px; height: 30px;">
                        <option value="date_created" <?php selected(isset($_GET['date_type']) ? $_GET['date_type'] : '', 'date_created'); ?>><?php _e( 'Fecha de Compra', 'wc-manual-payments' ); ?></option>
                        <option value="date_paid" <?php selected(isset($_GET['date_type']) ? $_GET['date_type'] : '', 'date_paid'); ?>><?php _e( 'Fecha Pagado', 'wc-manual-payments' ); ?></option>
                        <option value="date_modified" <?php selected(isset($_GET['date_type']) ? $_GET['date_type'] : '', 'date_modified'); ?>><?php _e( 'Última Modificación', 'wc-manual-payments' ); ?></option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; font-size: 12px; color: #50575e;"><?php _e( 'Desde:', 'wc-manual-payments' ); ?></label>
                    <input type="date" name="date_from" value="<?php echo esc_attr(isset($_GET['date_from']) ? $_GET['date_from'] : ''); ?>" style="height: 30px; line-height: 1;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; font-size: 12px; color: #50575e;"><?php _e( 'Hasta:', 'wc-manual-payments' ); ?></label>
                    <input type="date" name="date_to" value="<?php echo esc_attr(isset($_GET['date_to']) ? $_GET['date_to'] : ''); ?>" style="height: 30px; line-height: 1;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; font-size: 12px; color: #50575e;"><?php _e( 'Mostrar:', 'wc-manual-payments' ); ?></label>
                    <select name="report_limit" style="min-width: 80px; height: 30px;">
                        <option value="50" <?php selected($current_limit, 50); ?>>50</option>
                        <option value="100" <?php selected($current_limit, 100); ?>>100</option>
                        <option value="250" <?php selected($current_limit, 250); ?>>250</option>
                        <option value="500" <?php selected($current_limit, 500); ?>>500</option>
                        <option value="1000" <?php selected($current_limit, 1000); ?>>1000</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="button button-primary" style="height: 30px;"><?php _e( 'Filtrar Pedidos', 'wc-manual-payments' ); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=wcmp-order-reports'); ?>" class="button" style="height: 30px; line-height: 28px; text-decoration: none; text-align: center; padding: 0 10px;"><?php _e( 'Limpiar', 'wc-manual-payments' ); ?></a>
                    
                    <button type="submit" name="export_wcmp_xlsx" value="1" class="button" style="height: 30px; border-color: #1e8c38; color: #1e8c38; font-weight: 600;">
                        <span class="dashicons dashicons-media-spreadsheet" style="margin-top: 3px;"></span> <?php _e( 'Exportar a XLSX', 'wc-manual-payments' ); ?>
                    </button>

                    <?php if ( $is_filtering && $total_orders > 0 ) : ?>
                    <button type="button" class="button button-primary" id="wcmp-open-messaging" style="height: 30px; background: #673ab7; border-color: #512da8;">
                        <span class="dashicons dashicons-email-alt" style="margin-top: 3px;"></span> <?php _e( 'Centro de Mensajes', 'wc-manual-payments' ); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <?php $is_filtering = isset($_GET['wcmp_filter_active']) || isset($_GET['paged']) || isset($_GET['orderby']); ?>
        
        <?php if ( ! $is_filtering ) : ?>
            <div style="background: #e7f5ea; border-left: 5px solid #1e8c38; padding: 20px; font-size: 16px; color: #111;">
                <strong><?php _e( '👋 ¡Bienvenido!', 'wc-manual-payments' ); ?></strong><br>
                <?php _e( 'Por favor utiliza los filtros de arriba y oprime <strong>Filtrar Pedidos</strong> para comenzar a cargar transacciones. Esto asegura que el servidor se mantenga súper veloz en todo momento.', 'wc-manual-payments' ); ?>
            </div>
        <?php else : ?>

            <!-- Panel de Mensajes Masivos -->
            <div id="wcmp-messaging-panel" style="display:none; background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:4px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; color:#673ab7;"><?php _e( 'Centro de Mensajes Masivos', 'wc-manual-payments' ); ?> (<strong id="wcmp-sel-count"><?php echo number_format($total_orders); ?></strong> <?php _e( 'destinatarios filtrados', 'wc-manual-payments'); ?>)</h2>
                
                <p style="margin-bottom:10px;"><strong><?php _e( 'Asunto del Correo:', 'wc-manual-payments' ); ?></strong></p>
                <input type="text" id="wcmp-bulk-subject" class="regular-text" style="width:100%; margin-bottom:15px;" placeholder="Ej. Aviso sobre tu Saldo Pendiente">

                <?php $mkt_templates = get_option( 'tbp_marketing_templates', array() ); ?>
                <p style="margin-bottom:10px;"><strong><?php _e( 'Plantilla a enviar (Opcional Canva):', 'wc-manual-payments' ); ?></strong></p>
                <select id="wcmp-bulk-template" style="width:100%; margin-bottom:15px; border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px;">
                    <option value=""><?php _e( '-- Escribir Mensaje Manualmente (Abajo) --', 'wc-manual-payments' ); ?></option>
                    <?php if ( ! empty( $mkt_templates ) ) : ?>
                        <?php foreach ( $mkt_templates as $tid => $tdata ) : ?>
                            <option value="<?php echo esc_attr( $tid ); ?>">Plantilla Canva: <?php echo esc_html( $tdata['name'] ); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <div id="wcmp-manual-editor-wrap">
                    <p style="margin-bottom:5px;"><strong><?php _e( 'Variables (clic para insertar):', 'wc-manual-payments' ); ?></strong></p>
                    <div style="margin-bottom:15px; display:flex; gap:5px; flex-wrap:wrap;">
                        <button type="button" class="button button-small wcmp-insert-var" data-var="[nombre]">Nombre</button>
                        <button type="button" class="button button-small wcmp-insert-var" data-var="[pedido]"># Pedido</button>
                        <button type="button" class="button button-small wcmp-insert-var" data-var="[monto]">Monto Total</button>
                        <button type="button" class="button button-small wcmp-insert-var" data-var="[pagado]">Pagado</button>
                        <button type="button" class="button button-small wcmp-insert-var" data-var="[saldo]">Saldo Pendiente</button>
                    </div>

                    <?php
                    wp_editor( '', 'wcmp_bulk_message', array('media_buttons' => true, 'textarea_rows' => 8) );
                    ?>
                </div>

                <div style="margin-top:20px; padding: 15px; background: #fff8e1; border: 1px solid #ffe082; border-radius: 4px;">
                    <p style="margin-top:0;"><strong>🧪 <?php _e( 'Modo Sandbox: Enviar Prueba', 'wc-manual-payments' ); ?></strong></p>
                    <div style="display:flex; gap:10px;">
                        <input type="email" id="wcmp-bulk-test-email" class="regular-text" style="flex:1;" placeholder="comprobacion@tu-mail.com">
                        <button type="button" class="button button-secondary" id="wcmp-btn-bulk-send-test"><?php _e( 'Enviar Prueba', 'wc-manual-payments' ); ?></button>
                    </div>
                    <p class="description"><?php _e( 'Verifica cómo se reemplazan las variables financieros ([saldo], [monto]) en tu bandeja de entrada.', 'wc-manual-payments' ); ?></p>
                </div>

                <div style="margin-top:20px; display:flex; align-items:center; gap:15px;">
                    <button type="button" class="button button-primary" id="wcmp-btn-send-bulk" style="background:#673ab7; border-color:#512da8;"><?php _e( '¡Enviar a Todos los Filtrados!', 'wc-manual-payments' ); ?></button>
                    <button type="button" class="button" id="wcmp-btn-cancel-bulk"><?php _e( 'Cancelar', 'wc-manual-payments' ); ?></button>
                </div>
                
                <!-- Progress Bar -->
                <div id="wcmp-bulk-progress-wrapper" style="display:none; margin-top:20px;">
                    <p><strong id="wcmp-bulk-status-text" style="color:#00875a;"><?php _e( 'Preparando envíos...', 'wc-manual-payments' ); ?></strong> (<span id="wcmp-bulk-progress-count">0</span> / <span id="wcmp-bulk-total-count">0</span>)</p>
                    <div style="width:100%; background:#e9ecef; border-radius:4px; height:20px; overflow:hidden;">
                        <div id="wcmp-bulk-progress-bar" style="width:0%; background:#28a745; height:100%; transition:width 0.3s ease;"></div>
                    </div>
                </div>
            </div>

            <!-- Totales Globales Header -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); display: flex; gap: 40px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 13px; color: #646970; font-weight: 600; text-transform: uppercase;">Piezas Totales</span>
                    <span style="font-size: 28px; color: #1d2327; font-weight: bold;"><?php echo number_format($grand_totals['piezas']); ?></span>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 13px; color: #646970; font-weight: 600; text-transform: uppercase;">Total Vendido</span>
                    <span style="font-size: 28px; color: #111; font-weight: bold;"><?php echo wc_price($grand_totals['vendido']); ?></span>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 13px; color: #646970; font-weight: 600; text-transform: uppercase;">Total Recibido</span>
                    <span style="font-size: 28px; color: #2271b1; font-weight: bold;"><?php echo wc_price($grand_totals['recibido']); ?></span>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 13px; color: #646970; font-weight: 600; text-transform: uppercase;">Total por Recibir</span>
                    <span style="font-size: 28px; color: #d63638; font-weight: bold;"><?php echo wc_price($grand_totals['saldo']); ?></span>
                </div>
            </div>

            <!-- Paginación Top -->
            <?php if ( $max_pages > 1 ) : ?>
            <div class="tablenav top">
                <div class="tablenav-pages">
                    <span class="displaying-num" style="font-size: 13px; margin-right: 15px;"><?php echo number_format_i18n($total_orders); ?> elementos encontrados</span>
                    <?php
                    echo paginate_links( array(
                        'base'    => add_query_arg( 'paged', '%#%' ),
                        'format'  => '',
                        'current' => $current_page,
                        'total'   => $max_pages,
                        'prev_text' => '&laquo; Anterior',
                        'next_text' => 'Siguiente &raquo;',
                    ) );
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Data Table -->
            <table class="wp-list-table widefat fixed striped wcmp-data-table">
                <thead>
                    <tr>
                        <th style="width: 30px; text-align:center;"><input type="checkbox" id="wcmp-cb-select-all" checked></th>
                        <th style="width: 80px;"><a href="<?php echo wcmp_report_sort_url('id'); ?>"><strong><?php _e( '# Pedido', 'wc-manual-payments' ); ?></strong></a></th>
                        <th style="width: 120px;"><a href="<?php echo wcmp_report_sort_url('status'); ?>"><strong><?php _e( 'Status', 'wc-manual-payments' ); ?></strong></a></th>
                        <th style="width: 120px;"><a href="<?php echo wcmp_report_sort_url('date'); ?>"><strong><?php _e( 'Fecha', 'wc-manual-payments' ); ?></strong></a></th>
                        <th style="width: 15%"><strong><?php _e( 'Cliente', 'wc-manual-payments' ); ?></strong></th>
                        <th><strong><?php _e( 'Productos', 'wc-manual-payments' ); ?></strong></th>
                        <th style="width: 100px; text-align: right;"><a href="<?php echo wcmp_report_sort_url('order_total'); ?>"><strong><?php _e( 'Total', 'wc-manual-payments' ); ?></strong></a></th>
                        <th style="width: 100px; text-align: right;"><strong><?php _e( 'Pagado', 'wc-manual-payments' ); ?></strong></th>
                        <th style="width: 100px; text-align: right; color:#d63638;"><strong><?php _e( 'Saldo', 'wc-manual-payments' ); ?></strong></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $report_data ) ) : ?>
                        <tr><td colspan="8" style="text-align:center; padding: 20px;">
                            <?php _e( 'No hay pedidos disponibles para mostrar.', 'wc-manual-payments' ); ?>
                        </td></tr>
                    <?php else : ?>
                        <?php 
                        $sum_total   = 0;
                        $sum_paid    = 0;
                        $sum_balance = 0;
                        foreach ( $report_data as $row ) : 
                            $sum_total   += $row['total'];
                            $sum_paid    += $row['paid'];
                            $sum_balance += $row['balance'];
                        ?>
                            <tr>
                                <td style="text-align:center;"><input type="checkbox" class="wcmp-cb-row" value="<?php echo esc_attr( $row['id'] ); ?>" checked></td>
                                <td><a href="<?php echo esc_url( get_edit_post_link( $row['id'] ) ); ?>"><strong>#<?php echo esc_html( $row['id'] ); ?></strong></a></td>
                                <td><?php echo esc_html( $row['status'] ); ?></td>
                                <td><small><?php echo esc_html( $row['date'] ); ?></small></td>
                                <td><strong><?php echo esc_html( $row['name'] ); ?></strong></td>
                                <td style="font-size: 12px; line-height: 1.4; color: #444;"><?php echo wp_kses_post( $row['products'] ); ?></td>
                                <td style="text-align: right; font-weight: bold;"><?php echo wc_price( $row['total'] ); ?></td>
                                <td style="text-align: right; color: #2271b1;"><?php echo wc_price( $row['paid'] ); ?></td>
                                <td style="text-align: right; color: #d63638; font-weight: 600;"><?php echo wc_price( $row['balance'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if ( ! empty( $report_data ) ) : ?>
                <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th style="text-align: right; font-size: 15px;"><strong><?php _e('SubTotales (Esta página):', 'wc-manual-payments'); ?></strong></th>
                        <th style="text-align: right; font-size: 14px; color: #111;"><strong><?php echo wc_price($sum_total ?? 0); ?></strong></th>
                        <th style="text-align: right; font-size: 14px; color: #2271b1;"><strong><?php echo wc_price($sum_paid ?? 0); ?></strong></th>
                        <th style="text-align: right; font-size: 14px; color: #d63638;"><strong><?php echo wc_price($sum_balance ?? 0); ?></strong></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
            
            <!-- Paginación Bottom -->
            <?php if ( $max_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num" style="font-size: 13px; margin-right: 15px;"><?php echo number_format_i18n($total_orders); ?> elementos encontrados</span>
                    <?php
                    echo paginate_links( array(
                        'base'    => add_query_arg( 'paged', '%#%' ),
                        'format'  => '',
                        'current' => $current_page,
                        'total'   => $max_pages,
                        'prev_text' => '&laquo; Anterior',
                        'next_text' => 'Siguiente &raquo;',
                    ) );
                    ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?> <!-- /is_filtering closure -->
        
        <script>
        jQuery(document).ready(function($) {
            // UI Toggle
            $('#wcmp-open-messaging').on('click', function() {
                $('#wcmp-messaging-panel').slideToggle();
            });
            $('#wcmp-btn-cancel-bulk').on('click', function() {
                $('#wcmp-messaging-panel').slideUp();
            });

            // Select All Toggle
            $('#wcmp-cb-select-all').on('change', function() {
                $('.wcmp-cb-row').prop('checked', $(this).is(':checked'));
                updateSelCount();
            });
            $(document).on('change', '.wcmp-cb-row', function() {
                updateSelCount();
            });

            function updateSelCount() {
                var count = $('.wcmp-cb-row:checked').length;
                // Si estamos en una página parcial pero el total filtrado es mayor, 
                // el botón de "Enviar a todos" sigue siendo la opción principal.
            }

            // Smart Tags Insertion
            $('.wcmp-insert-var').on('click', function() {
                var val = $(this).data('var');
                if (typeof tinymce != "undefined" && tinymce.get("wcmp_bulk_message") && !tinymce.get("wcmp_bulk_message").isHidden()) {
                    tinymce.get("wcmp_bulk_message").execCommand('mceInsertContent', false, val);
                } else {
                    var $txt = $('#wcmp_bulk_message');
                    var caretPos = $txt[0].selectionStart || $txt.val().length;
                    var textAreaTxt = $txt.val();
                    $txt.val(textAreaTxt.substring(0, caretPos) + val + textAreaTxt.substring(caretPos) );
                }
            });

            // Test Sending Logic
            $('#wcmp-btn-bulk-send-test').on('click', function() {
                var email = $('#wcmp-bulk-test-email').val().trim();
                var subject = $('#wcmp-bulk-subject').val().trim();
                
                if (!email || !subject) {
                    alert('Ingresa un correo de prueba y un asunto.');
                    return;
                }

                $(this).prop('disabled', true).text('Enviando...');
                var $btn = $(this);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tbp_actividades_send_test_mkt',
                        email: email,
                        subject: subject,
                        template_id: $('#wcmp-bulk-template').val(),
                        message: (typeof tinymce != "undefined" && tinymce.get("wcmp_bulk_message") && !tinymce.get("wcmp_bulk_message").isHidden()) ? tinymce.get("wcmp_bulk_message").getContent() : $('#wcmp_bulk_message').val(),
                        _ajax_nonce: '<?php echo wp_create_nonce("tbp_ext_mkt"); ?>'
                    },
                    success: function(res) {
                        if (res.success) alert('¡Correo de prueba enviado con éxito!');
                        else alert('Error: ' + res.data);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Enviar Prueba');
                    }
                });
            });

            // Template Toggle
            $('#wcmp-bulk-template').on('change', function() {
                if ( $(this).val() !== '' ) {
                    $('#wcmp-manual-editor-wrap').slideUp();
                } else {
                    $('#wcmp-manual-editor-wrap').slideDown();
                }
            });

            // AJAX Batch Execution
            var bulk_queue = [];
            var bulk_total = 0;
            var bulk_subject = '';
            var bulk_message = '';
            var bulk_batch_size = 25;
            var bulk_current_batch_count = 0;
            var bulk_pause_ms = 60000; // 60 segundos
            
            $('#wcmp-btn-send-bulk').on('click', function() {
                var template_id = $('#wcmp-bulk-template').val();
                bulk_subject = $('#wcmp-bulk-subject').val().trim();
                
                if ( template_id === '' ) {
                    if (typeof tinymce != "undefined" && tinymce.get("wcmp_bulk_message") && !tinymce.get("wcmp_bulk_message").isHidden()) {
                        bulk_message = tinymce.get("wcmp_bulk_message").getContent();
                    } else {
                        bulk_message = $('#wcmp_bulk_message').val();
                    }
                    if (!bulk_subject || !bulk_message) {
                        alert('El asunto y el mensaje son obligatorios.');
                        return;
                    }
                } else {
                    bulk_message = '';
                    if (!bulk_subject) {
                        alert('El asunto es obligatorio.');
                        return;
                    }
                }

                // Determinar si enviamos a "Seleccionados en pantalla" o "Todos los filtrados"
                var selected_ids = [];
                $('.wcmp-cb-row:checked').each(function() {
                    selected_ids.push($(this).val());
                });

                if ( selected_ids.length === 0 ) {
                    alert('No hay pedidos seleccionados.');
                    return;
                }

                // SI el número de seleccionados es igual al de la página, pero hay más en el total,
                // preguntamos si quiere enviar a ABSOLUTAMENTE TODOS los que cumplen el filtro.
                var total_filtered = parseInt($('#wcmp-sel-count').text().replace(/,/g, ''));
                var use_all_filtered = false;

                if ( total_filtered > selected_ids.length ) {
                    if ( confirm('Has filtrado un total de ' + total_filtered + ' pedidos. ¿Deseas enviar este mensaje a TODOS ellos (incluso los de otras páginas)?\n\nPresiona OK para Enviar a TODOS (' + total_filtered + ')\nPresiona CANCELAR para enviar solo a los ' + selected_ids.length + ' visibles.') ) {
                        use_all_filtered = true;
                    }
                } else {
                    if ( ! confirm('¿Enviar mensaje a los ' + selected_ids.length + ' pedidos seleccionados?') ) return;
                }

                $('#wcmp-btn-send-bulk, #wcmp-btn-cancel-bulk').prop('disabled', true);
                $('#wcmp-bulk-progress-wrapper').slideDown();
                $('#wcmp-bulk-status-text').text('Obteniendo lista de IDs...');
                
                if ( use_all_filtered ) {
                    // Obtener todos los IDs vía AJAX (reutilizando los argumentos de la URL actual)
                    var current_query = window.location.search;
                    $.ajax({
                        url: ajaxurl + current_query + '&action=wcmp_get_all_filtered_order_ids',
                        success: function(res) {
                            if ( res.success ) {
                                startBulkProcess(res.data);
                            } else {
                                alert('Error al obtener IDs: ' + res.data);
                                resetUI();
                            }
                        }
                    });
                } else {
                    startBulkProcess(selected_ids);
                }
            });

            function startBulkProcess(ids) {
                bulk_queue = ids;
                bulk_total = ids.length;
                $('#wcmp-bulk-total-count').text(bulk_total);
                $('#wcmp-bulk-progress-count').text('0');
                $('#wcmp-bulk-progress-bar').css('width', '0%');
                $('#wcmp-bulk-status-text').text('Enviando...');
                bulk_current_batch_count = 0;
                processNext();
            }

            function processNext() {
                if ( bulk_queue.length === 0 ) {
                    $('#wcmp-bulk-status-text').text('¡Envío Masivo Completado!');
                    alert('Proceso finalizado con éxito.');
                    resetUI();
                    return;
                }

                var order_id = bulk_queue.shift();
                var current_idx = bulk_total - bulk_queue.length;

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tbp_actividades_send_bulk_message',
                        order_id: order_id,
                        subject: bulk_subject,
                        message: bulk_message,
                        template_id: $('#wcmp-bulk-template').val(),
                        _ajax_nonce: '<?php echo wp_create_nonce("tbp_bulk_msg"); ?>'
                    },
                    complete: function() {
                        $('#wcmp-bulk-progress-count').text(current_idx);
                        var pct = (current_idx / bulk_total) * 100;
                        $('#wcmp-bulk-progress-bar').css('width', pct + '%');
                        
                        bulk_current_batch_count++;
                        if ( bulk_current_batch_count >= bulk_batch_size && bulk_queue.length > 0 ) {
                            bulk_current_batch_count = 0;
                            pauseProcess();
                        } else {
                            setTimeout(processNext, 300);
                        }
                    }
                });
            }

            function pauseProcess() {
                var sec = bulk_pause_ms / 1000;
                var timer = setInterval(function() {
                    sec--;
                    $('#wcmp-bulk-status-text').text('Pausa Anti-Spam: Reanudando en ' + sec + 's...');
                    if ( sec <= 0 ) {
                        clearInterval(timer);
                        $('#wcmp-bulk-status-text').text('Enviando siguiente bloque...');
                        processNext();
                    }
                }, 1000);
            }

            function resetUI() {
                $('#wcmp-btn-send-bulk, #wcmp-btn-cancel-bulk').prop('disabled', false);
            }

            if ( $.fn.select2 ) {
                $('.wcmp-product-select2, .wcmp-event-select2, .wcmp-status-select2').select2({
                    allowClear: true
                });
            }
        });
        </script>
    </div>
    <?php
}
