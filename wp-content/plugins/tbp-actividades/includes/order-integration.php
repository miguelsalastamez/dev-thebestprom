<?php
/**
 * Order Integration for TBP Actividades
 * Adds a meta box to the WooCommerce order edit screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Meta Box for Orders
 */
add_action( 'add_meta_boxes', 'tbp_actividades_add_order_meta_box' );
function tbp_actividades_add_order_meta_box() {
    // Normal Shop Order (Legacy)
    $screens = array( 'shop_order' );
    
    // HPOS and newer compatibility
    if ( function_exists( 'wc_get_page_screen_id' ) ) {
        $hpos_screen = wc_get_page_screen_id( 'shop-order' );
        if ( $hpos_screen && ! in_array( $hpos_screen, $screens ) ) {
            $screens[] = $hpos_screen;
        }
    }

    foreach ( $screens as $screen ) {
        add_meta_box(
            'tbp_actividades_order_box',
            __( 'ACTIVIDADES DEL EVENTO', 'tbp-actividades' ),
            'tbp_actividades_order_meta_box_html',
            $screen,
            'normal',
            'high'
        );
    }
}

/**
 * Meta Box HTML
 */
function tbp_actividades_order_meta_box_html( $post_or_order ) {
    // Handle both WP_Post (legacy) and WC_Order (HPOS)
    $order = false;
    if ( $post_or_order instanceof WC_Order ) {
        $order = $post_or_order;
    } elseif ( $post_or_order instanceof WP_Post ) {
        $order = wc_get_order( $post_or_order->ID );
    } elseif ( is_numeric( $post_or_order ) ) {
        $order = wc_get_order( $post_or_order );
    }

    if ( ! $order ) return;

    $order_id = $order->get_id();

    ?>
    <div class="tbp-order-actividades-wrapper">
        <style>
            .tbp-order-section { margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
            .tbp-order-section:last-child { border-bottom: none; }
            .tbp-order-section h3 { color: #f39c12; text-transform: uppercase; font-size: 14px; margin-bottom: 10px; display: flex; align-items: center; font-family: sans-serif; }
            .tbp-order-section h3 span { margin-right: 8px; }
            .tbp-history-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-family: sans-serif; }
            .tbp-history-table th { text-align: left; background: #f8f8f8; padding: 8px; border: 1px solid #eee; font-size: 11px; color: #555; }
            .tbp-history-table td { padding: 8px; border: 1px solid #eee; font-size: 12px; }
            .tbp-badge-placeholder { background: #eee; color: #777; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: normal; margin-left: 10px; }
        </style>

        <!-- 1. RIFA -->
        <div class="tbp-order-section">
            <h3><span>🎫</span> <?php _e( 'Rifa', 'tbp-actividades' ); ?></h3>
            
            <?php
            global $wpdb;
            $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
            $logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_logs WHERE order_id = %d ORDER BY created_at DESC", $order_id ) );

            if ( $logs ) : ?>
                <table class="tbp-history-table">
                    <thead>
                        <tr>
                            <th><?php _e( 'FECHA DE ENTREGA', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'CANTIDAD DE BOLETOS', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'COSTO DEL BOLETO', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'CANTIDAD EQUIVALENTE', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'ACCIONES', 'tbp-actividades' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs as $log ) : 
                            $costo = floatval( get_post_meta( $log->rifa_id, '_tbp_cost_physical', true ) );
                            $total = $costo * $log->amount;
                            $fecha = date_i18n( 'd/F/Y', strtotime( $log->created_at ) );
                            $log_id = intval($log->id); // Primary Key of tbp_actividades_logs
                        ?>
                        <tr id="tbp-log-row-<?php echo $log_id; ?>">
                            <td><?php echo strtoupper($fecha); ?></td>
                            <td><?php echo $log->amount; ?></td>
                            <td><?php echo wc_price( $costo ); ?></td>
                            <td><strong><?php echo wc_price( $total ); ?></strong></td>
                            <td style="text-align:center;">
                                <button type="button" class="button tbp-btn-delete-log" data-log-id="<?php echo $log_id; ?>" title="<?php _e( 'Borrar este registro de entrega', 'tbp-actividades' ); ?>" style="color:#dc3232; border-color:#dc3232; background:transparent; padding:2px 6px; font-size:14px;">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <script>
                jQuery(document).ready(function($){
                    $('.tbp-btn-delete-log').on('click', function(e){
                        e.preventDefault();
                        var log_id = $(this).data('log-id');
                        
                        if(!confirm('¿Estás SEGURO de que deseas borrar este registro de entrega? Esto restará estos boletos del total del pedido.')) return;
                        
                        var btn = $(this);
                        btn.prop('disabled', true).text('...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'tbp_actividades_delete_single_log',
                                log_id: log_id,
                                _ajax_nonce: '<?php echo wp_create_nonce("tbp_delete_log"); ?>'
                            },
                            success: function(res) {
                                if(res.success) {
                                    $('#tbp-log-row-' + log_id).fadeOut(300, function(){ $(this).remove(); });
                                    // Optionally reload the page to refresh WooCommerce totals or let user do it
                                    setTimeout(function(){
                                        if(confirm('Registro eliminado con éxito. ¿Deseas recargar la página para actualizar los totales?')) {
                                            location.reload();
                                        }
                                    }, 500);
                                } else {
                                    alert('Error: ' + (res.data || 'Problema desconocido'));
                                    btn.prop('disabled', false).text('🗑️');
                                }
                            },
                            error: function() {
                                alert('Error de conexión.');
                                btn.prop('disabled', false).text('🗑️');
                            }
                        });
                    });
                });
                </script>
            <?php else : ?>
                <p><small><?php _e( 'No hay registros de entrega física para este pedido.', 'tbp-actividades' ); ?></small></p>
            <?php endif; ?>
        </div>

        <!-- 2. ASIGNACIÓN DE ASIENTOS (Placeholder) -->
        <div class="tbp-order-section">
            <h3><span>🪑</span> <?php _e( 'Asignación de Asientos', 'tbp-actividades' ); ?> <span class="tbp-badge-placeholder"><?php _e( 'PRÓXIMAMENTE', 'tbp-actividades' ); ?></span></h3>
            <p><small><?php _e( 'Información de mesa y asientos se mostrará aquí en la Fase 2.', 'tbp-actividades' ); ?></small></p>
        </div>

        <!-- 3. TOMA DE FOTOGRAFÍA (Placeholder) -->
        <div class="tbp-order-section">
            <h3><span>📸</span> <?php _e( 'Toma de Fotografía', 'tbp-actividades' ); ?> <span class="tbp-badge-placeholder"><?php _e( 'PRÓXIMAMENTE', 'tbp-actividades' ); ?></span></h3>
            <p><small><?php _e( 'Estado de fotos y citas se mostrará aquí en la Fase 2.', 'tbp-actividades' ); ?></small></p>
        </div>

        <!-- 4. DATOS DE ASISTENTES (Event Tickets Plus) -->
        <div class="tbp-order-section">
            <h3><span>👥</span> <?php _e( 'Datos de Asistentes', 'tbp-actividades' ); ?></h3>
            <?php tbp_actividades_render_attendee_editor( $order_id, $order ); ?>
        </div>
    </div>
    <?php
}

/**
 * Fetch all attendee metadata for this order, searching everywhere.
 */
function tbp_actividades_get_order_attendees_meta( $order_id, $order ) {
    global $wpdb;
    $attendees = [];
    $sourced_products = [];

    // 1. Check Database deep links (Attendee Posts) - THE SOURCE OF TRUTH
    $search_values = array( $order_id, strval($order_id) );
    foreach( $order->get_items() as $item_id => $item ) {
        $search_values[] = $item_id;
        $search_values[] = strval($item_id);
    }
    
    $placeholders = implode(',', array_fill(0, count($search_values), '%s'));
    $linked_posts = $wpdb->get_col( $wpdb->prepare("
        SELECT DISTINCT pm.post_id 
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_value IN ($placeholders)
        AND p.post_type IN ('tribe_wooticket', 'tribe_rsvp', 'attendee', 'tec_attendee')
    ", ...$search_values) );
    
    $direct_posts = get_posts( array(
        'post_type'      => array('tribe_wooticket', 'tribe_rsvp', 'attendee'), 
        'meta_query'     => array(
            array(
                'key'   => '_tribe_wooticket_order',
                'value' => $order_id
            )
        ),
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'post_status'    => 'any'
    ) );

    $all_post_ids = array_unique(array_merge($linked_posts, $direct_posts));
    
    foreach ( $all_post_ids as $p_id ) {
        $meta = get_post_meta( $p_id, '_tribe_tickets_meta', true );
        if ( is_array( $meta ) && !empty($meta) ) {
            $attendees[$p_id] = $meta;
            $prod_id = get_post_meta( $p_id, '_tribe_wooticket_product', true );
            if ( $prod_id ) $sourced_products[] = intval($prod_id);
        }
    }

    // 2. Check Order Meta directly (Pending/Draft orders) - ONLY if not already sourced deeper
    $order_direct_meta = get_post_meta( $order_id, '_tribe_tickets_meta', true );
    if ( is_array( $order_direct_meta ) && !empty($order_direct_meta) ) {
        foreach ( $order_direct_meta as $prod_id => $guests ) {
            if ( is_array($guests) && !in_array(intval($prod_id), $sourced_products) ) {
                $attendees["order_" . $order_id . "_prod_" . $prod_id] = $guests;
                $sourced_products[] = intval($prod_id);
            }
        }
    }

    // 3. Fallback: Check order items directly
    foreach ( $order->get_items() as $item_id => $item ) {
        $pid = $item->get_product_id();
        if ( in_array(intval($pid), $sourced_products) ) continue;
        
        $raw_tickets_meta = wc_get_order_item_meta( $item_id, '_tribe_tickets_meta', true );
        if ( is_array( $raw_tickets_meta ) && !empty($raw_tickets_meta) ) {
            $attendees['item_' . $item_id] = $raw_tickets_meta;
        }
    }
    
    return $attendees;
}

/**
 * Renders the HTML form
 */
function tbp_actividades_render_attendee_editor( $order_id, $order ) {
    $attendees = tbp_actividades_get_order_attendees_meta( $order_id, $order );
    
    if ( empty( $attendees ) ) {
        echo '<p><small>No hay datos de asistentes capturados todavía o el formato no es compatible.</small></p>';
        return;
    }

    echo '<div style="background:#fdfdfd; border:1px solid #ddd; padding:10px; border-radius:4px;">';
    wp_nonce_field( 'tbp_save_attendees_nonce', 'tbp_attendees_nonce' );

    $counter = 1;
    foreach ( $attendees as $source_id => $meta_groups ) {
        try {
            // 1. Identify Product ID to fetch schema
            $product_id = false;
            if ( strpos($source_id, 'order_') === 0 && strpos($source_id, '_prod_') !== false ) {
                preg_match('/_prod_(\d+)/', $source_id, $m);
                if (!empty($m)) $product_id = intval($m[1]);
            } else if ( strpos($source_id, 'item_') === 0 ) {
                $item_id = str_replace('item_', '', $source_id);
                $item = $order->get_item($item_id);
                if ($item) $product_id = $item->get_product_id();
            } else {
                // It's an attendee post ID
                $product_id = get_post_meta( $source_id, '_tribe_wooticket_product', true );
            }
            
            // 2. Load Schema safely
            $schema = [];
            if ( $product_id ) {
                $raw_schema = get_post_meta( $product_id, '_tribe_tickets_meta', true );

                if ( is_array($raw_schema) ) {
                    foreach($raw_schema as $def) {
                        if ( ! is_array($def) ) continue; // PREVENT FATAL ERROR
                        $slug = isset($def['slug']) ? sanitize_title($def['slug']) : sanitize_title($def['label'] ?? '');
                        if ( empty($slug) && isset($def['label']) ) {
                            $slug = sanitize_title($def['label']);
                        }
                        $schema[$slug] = $def;
                        if(isset($def['label'])) {
                            $schema[sanitize_title($def['label'])] = $def;
                        }
                    }
                }
            }

            // 2.5 NORMALIZE: Se le da formato de grupo si vienen campos sueltos (tickets ya emitidos)
            if ( ! empty($meta_groups) ) {
                reset($meta_groups);
                $first_key = key($meta_groups);
                // Si la primera llave NO es numérica y NO es 'Datos', asumimos que es un array plano de campos
                if ( ! is_numeric($first_key) && $first_key !== 'Datos' ) {
                    $meta_groups = array( 'Datos' => $meta_groups );
                }
            }

            foreach ( $meta_groups as $guest_index => $guest_data ) {
                if ( ! is_array($guest_data) ) {
                    $guest_data = [ $guest_index => $guest_data ];
                    $guest_index = 'Datos';
                }
                
                echo '<div style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dashed #ccc;">';
                echo '<strong style="color:#0073aa;font-size:13px;">🎟️ Asistente ' . $counter . ' <span style="font-size:10px;color:#999;">(Ref DB: ' . esc_html($source_id) . ')</span></strong>';
                
                echo '<table class="form-table" style="margin-top:5px; width:100%; border-spacing: 0;">';
                foreach ( $guest_data as $field_key => $field_value ) {
                    if ( ! is_string($field_key) && ! is_numeric($field_key) ) continue;
                    
                    $display_key = strval($field_key);
                    $actual_value = $field_value;
                    $is_complex_array = false;

                    if ( is_array($field_value) && isset($field_value['label']) ) {
                        $display_key = strval($field_value['label']);
                        $actual_value = isset($field_value['value']) ? $field_value['value'] : '';
                        $is_complex_array = true;
                    } else if ( is_array($field_value) ) {
                        $actual_value = wp_json_encode($field_value);
                    } else {
                        $display_key = str_replace( array('-', '_'), ' ', $display_key );
                        $display_key = ucwords( $display_key );
                    }
                    
                    // Desplegar arrays múltiples de Event Tickets Plus o separaciones por coma
                    if ( !is_array($actual_value) && strpos(strval($actual_value), ',') !== false && $field_type === 'checkbox' ) {
                        $actual_values_array = array_map('trim', explode(',', strval($actual_value)));
                    } else {
                        $actual_values_array = is_array($actual_value) ? $actual_value : array($actual_value);
                    }

                    $actual_values_normalized = array_map(function($v) {
                        return is_scalar($v) ? trim(strval($v)) : '';
                    }, $actual_values_array);

                    $actual_value_str = isset($actual_values_normalized[0]) ? $actual_values_normalized[0] : '';
                    if ( count($actual_values_normalized) > 1 ) {
                        $actual_value_str = implode(', ', $actual_values_normalized);
                    }

                    if ( $is_complex_array ) {
                        $input_name = sprintf('tbp_att_meta[%s][%s][%s][value]', esc_attr($source_id), esc_attr($guest_index), esc_attr($field_key));
                    } else {
                        $input_name = sprintf('tbp_att_meta[%s][%s][%s]', esc_attr($source_id), esc_attr($guest_index), esc_attr($field_key));
                    }
                    
                    $field_type    = 'text';
                    $field_options = [];
                    $lookup_key    = strtolower(sanitize_title($field_key));
                    $lookup_key_un = str_replace('-', '_', $lookup_key);
                    
                    // Encontrar la definición en el schema (buscando por slug-limpio, slug-original o label)
                    $field_def = false;
                    if ( isset($schema[$lookup_key]) ) $field_def = $schema[$lookup_key];
                    else if ( isset($schema[$lookup_key_un]) ) $field_def = $schema[$lookup_key_un];
                    else if ( isset($schema[$field_key]) ) $field_def = $schema[$field_key];
                    else {
                        // Búsqueda exhaustiva por si acaso
                        foreach($schema as $s_key => $s_val) {
                            if (strtolower($s_key) == $lookup_key || strtolower($s_key) == $lookup_key_un) {
                                $field_def = $s_val;
                                break;
                            }
                        }
                    }
                    
                    if ( is_array($field_def) ) {
                        $field_type = $field_def['type'] ?? 'text';
                        
                        // ET+ usualmente anida las opciones en extra['options']
                        $field_options = [];
                        if ( isset($field_def['extra']['options']) && is_array($field_def['extra']['options']) ) {
                            $field_options = $field_def['extra']['options'];
                        } else {
                            $raw_opts = $field_def['extra'] ?? ($field_def['options'] ?? ($field_def['values'] ?? ''));
                            if ( is_array($raw_opts) ) {
                                $field_options = $raw_opts;
                            } else if ( !empty($raw_opts) ) {
                                $tmp = maybe_unserialize($raw_opts);
                                $field_options = is_array($tmp) ? $tmp : array_map('trim', explode("\n", strval($raw_opts)));
                            }
                        }

                        // Normalizar opciones (ET+ usa tanto arrays simples como arrays de objetos)
                        $normalized_options = [];
                        foreach ( $field_options as $key => $val ) {
                            if ( is_array($val) ) {
                                $opt_label = $val['label'] ?? ($val['name'] ?? ($val['text'] ?? $key));
                                $opt_value = $val['value'] ?? ($val['slug'] ?? ($val['id'] ?? $opt_label));
                                $normalized_options[trim(strval($opt_value))] = trim(strval($opt_label));
                            } else {
                                $normalized_options[trim(strval($val))] = trim(strval($val));
                            }
                        }
                        $field_options = $normalized_options;
                    }
                    
                    echo '<tr>';
                    echo '<td style="padding:4px 0; width:40%; font-size:12px; font-weight:600; color:#444;">' . esc_html( $display_key ) . '</td>';
                    echo '<td style="padding:4px 0;">';
                    
                    if ( $field_type === 'select' || $field_type === 'dropdown' ) {
                        echo '<select name="' . $input_name . '" style="width:100%; font-size:12px;">';
                        echo '<option value="">' . esc_html__('Selecciona una opción', 'tbp-actividades') . '</option>';
                        foreach($field_options as $opt_val => $opt_label) {
                            $is_selected = in_array( $opt_val, $actual_values_normalized ) || in_array( $opt_label, $actual_values_normalized );
                            $selected = $is_selected ? 'selected="selected"' : '';
                            echo '<option value="'.esc_attr($opt_val).'" '.$selected.'>'.esc_html($opt_label).'</option>';
                        }
                        echo '</select>';
                    } else if ( $field_type === 'radio' ) {
                        echo '<div style="margin-top:5px;">';
                        foreach($field_options as $opt_val => $opt_label) {
                            $is_selected = in_array( $opt_val, $actual_values_normalized ) || in_array( $opt_label, $actual_values_normalized );
                            $checked = $is_selected ? 'checked="checked"' : '';
                            echo '<label style="display:inline-block; margin-right:15px; font-size:12px; cursor:pointer;">';
                            echo '<input type="radio" name="' . $input_name . '" value="'.esc_attr($opt_val).'" '.$checked.' style="margin-top:-2px;" /> ' . esc_html($opt_label);
                            echo '</label>';
                        }
                        echo '</div>';
                    } else if ( $field_type === 'checkbox' ) {
                        echo '<div style="margin-top:5px;">';
                        if ( empty($field_options) ) {
                             $checked = !empty($actual_value_str) ? 'checked' : '';
                             echo '<input type="checkbox" name="' . $input_name . '" value="1" '.$checked.' />';
                        } else {
                             foreach($field_options as $opt_val => $opt_label) {
                                  $is_selected = in_array( $opt_val, $actual_values_normalized ) || in_array( $opt_label, $actual_values_normalized );
                                  $checked = $is_selected ? 'checked="checked"' : '';
                                  echo '<label style="display:block; font-size:12px; margin-bottom:3px; cursor:pointer;">';
                                  echo '<input type="checkbox" name="' . $input_name . '[]" value="'.esc_attr($opt_val).'" '.$checked.' style="margin-top:-2px;" /> ' . esc_html($opt_label);
                                  echo '</label>';
                             }
                        }
                        echo '</div>';
                    } else if ( $field_type === 'textarea' ) {
                        echo '<textarea name="' . $input_name . '" style="width:100%; font-size:12px;" rows="3">' . esc_textarea( $actual_value_str ) . '</textarea>';
                    } else if ( in_array($field_type, ['date', 'number', 'email', 'url']) ) {
                        echo '<input type="'.esc_attr($field_type).'" name="' . $input_name . '" value="' . esc_attr( $actual_value_str ) . '" style="width:100%; font-size:12px;" />';
                    } else {
                        echo '<input type="text" name="' . $input_name . '" value="' . esc_attr( $actual_value_str ) . '" style="width:100%; font-size:12px;" />';
                    }
                    
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                echo '</div>';
                $counter++;
            }
        } catch (\Throwable $e) {
            echo '<div style="color:red; font-size:11px;">Error procesando asistente: ' . esc_html($e->getMessage()) . '</div>';
            $counter++;
        }
    }
    
    echo '<p><small>Haz clic en <strong>Actualizar</strong> el pedido (arriba a la derecha) para guardar permanentemente los cambios en Event Tickets Plus.</small></p>';
    echo '</div>';
}

/**
 * Save Handler
 */
add_action( 'woocommerce_process_shop_order_meta', 'tbp_actividades_save_attendee_editor', 60, 1 );
function tbp_actividades_save_attendee_editor( $post_id ) {
    if ( ! current_user_can( 'edit_shop_order', $post_id ) ) {
        return;
    }
    
    if ( ! isset( $_POST['tbp_attendees_nonce'] ) || ! wp_verify_nonce( $_POST['tbp_attendees_nonce'], 'tbp_save_attendees_nonce' ) ) {
        return;
    }
    
    if ( ! isset( $_POST['tbp_att_meta'] ) || ! is_array( $_POST['tbp_att_meta'] ) ) {
        return;
    }

    foreach ( $_POST['tbp_att_meta'] as $source_id => $guest_groups ) {
        
        // --- Handle Direct Order Meta ---
        if ( strpos($source_id, 'order_') === 0 && strpos($source_id, '_prod_') !== false ) {
            preg_match('/order_(\d+)_prod_(\d+)/', $source_id, $matches);
            if ( !empty($matches) ) {
                $o_id = intval($matches[1]);
                $p_id = intval($matches[2]);
                $original_meta = get_post_meta( $o_id, '_tribe_tickets_meta', true );
                if ( ! is_array( $original_meta ) ) $original_meta = [];
                
                foreach ( $guest_groups as $guest_index => $fields ) {
                    if ( ! isset($original_meta[$p_id][$guest_index]) ) $original_meta[$p_id][$guest_index] = [];
                    foreach( $fields as $k => $v ) {
                         if ( is_array($v) && isset($v['value']) ) {
                             if ( ! isset($original_meta[$p_id][$guest_index][$k]) || ! is_array($original_meta[$p_id][$guest_index][$k]) ) $original_meta[$p_id][$guest_index][$k] = [];
                             $original_meta[$p_id][$guest_index][$k]['value'] = sanitize_text_field($v['value']);
                         } else {
                             $original_meta[$p_id][$guest_index][$k] = sanitize_text_field($v);
                         }
                    }
                }
                update_post_meta( $o_id, '_tribe_tickets_meta', $original_meta );
            }
            continue; // Skip the rest of the loop for this source
        }
        // --- End Handle Direct Order Meta ---

        $original_meta = [];
        $is_item = false;
        
        if ( strpos($source_id, 'item_') === 0 ) {
            $is_item = true;
            $item_id = str_replace('item_', '', $source_id);
            $original_meta = wc_get_order_item_meta( $item_id, '_tribe_tickets_meta', true );
        } else {
            $p_id = intval($source_id);
            $original_meta = get_post_meta( $p_id, '_tribe_tickets_meta', true );
        }
        
        if ( ! is_array( $original_meta ) ) $original_meta = [];

        foreach ( $guest_groups as $guest_index => $fields ) {
            if ( $guest_index === 'Datos' ) {
                foreach( $fields as $k => $v ) {
                    if ( is_array($v) && isset($v['value']) ) {
                        // Complex array
                        if ( ! is_array($original_meta[$k]) ) $original_meta[$k] = [];
                        $original_meta[$k]['value'] = sanitize_text_field($v['value']);
                    } else {
                        $original_meta[$k] = sanitize_text_field($v);
                    }
                }
            } else {
                if ( ! isset($original_meta[$guest_index]) ) $original_meta[$guest_index] = [];
                
                foreach( $fields as $k => $v ) {
                    if ( is_array($v) && isset($v['value']) ) {
                        if ( ! isset($original_meta[$guest_index][$k]) || ! is_array($original_meta[$guest_index][$k]) ) $original_meta[$guest_index][$k] = [];
                        $original_meta[$guest_index][$k]['value'] = sanitize_text_field($v['value']);
                    } else {
                        $original_meta[$guest_index][$k] = sanitize_text_field($v);
                    }
                }
            }
        }
        
        if ( $is_item ) {
            wc_update_order_item_meta( $item_id, '_tribe_tickets_meta', $original_meta );
        } else {
            update_post_meta( $p_id, '_tribe_tickets_meta', $original_meta );
            
            global $wpdb;
            $tec_table = $wpdb->prefix . 'tec_tickets_attendees_meta';
            if ( $wpdb->get_var("SHOW TABLES LIKE '$tec_table'") === $tec_table ) {
                foreach ( $guest_groups as $guest_index => $fields ) {
                    foreach($fields as $k => $v) {
                        $wpdb->update( 
                            $tec_table, 
                            array( 'meta_value' => sanitize_text_field($v) ), 
                            array( 'attendee_id' => $p_id, 'meta_key' => $k ), 
                            array( '%s' ), 
                            array( '%d', '%s' ) 
                        );
                    }
                }
            }
        }
    }
}
