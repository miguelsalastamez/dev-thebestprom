<?php
/**
 * Frontend Integration for TBP Actividades
 * Adds an "Activities" button and modal for clients in My Account.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add "Actividades" button to My Account -> Orders actions
 */
add_filter( 'woocommerce_my_account_my_orders_actions', 'tbp_actividades_add_client_order_actions', 10, 2 );
function tbp_actividades_add_client_order_actions( $actions, $order ) {
    $actions['tbp_actividades'] = array(
        'url'  => '#',
        'name' => __( 'ACTIVIDADES', 'tbp-actividades' ),
    );
    return $actions;
}

/**
 * Render Modal and Scripts
 */
add_action( 'wp_footer', 'tbp_actividades_render_client_modal' );
function tbp_actividades_render_client_modal() {
    if ( ! is_account_page() ) return;
    ?>
    <!-- TBP Activities Modal -->
    <div id="tbp-modal-overlay" class="tbp-modal-hide">
        <div id="tbp-modal-content">
            <div class="tbp-modal-header">
                <h2><?php _e( 'ACTIVIDADES DEL EVENTO', 'tbp-actividades' ); ?></h2>
                <span id="tbp-modal-close">&times;</span>
            </div>
            <div id="tbp-modal-body">
                <div id="tbp-modal-loader"><?php _e( 'Cargando información...', 'tbp-actividades' ); ?></div>
                <div id="tbp-modal-dynamic-content"></div>
            </div>
        </div>
    </div>

    <style>
        #tbp-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 99999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); transition: all 0.3s ease; }
        .tbp-modal-hide { opacity: 0; pointer-events: none; visibility: hidden; }
        #tbp-modal-content { background: #fff; width: 90%; max-width: 600px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); transform: scale(0.9); transition: all 0.3s ease; }
        #tbp-modal-overlay:not(.tbp-modal-hide) #tbp-modal-content { transform: scale(1); }
        .tbp-modal-header { padding: 20px; background: #2c3e50; color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .tbp-modal-header h2 { margin: 0; font-size: 18px; color: #fff; font-weight: 600; }
        #tbp-modal-close { font-size: 28px; cursor: pointer; color: #fff; line-height: 1; }
        #tbp-modal-body { padding: 25px; max-height: 70vh; overflow-y: auto; }
        
        /* Table Styles */
        .tbp-fe-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tbp-fe-table th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #eee; color: #666; }
        .tbp-fe-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; color: #333; }
        .tbp-fe-table tr:last-child td { border-bottom: none; }
        .tbp-fe-total { font-weight: bold; color: #27ae60; }
        
        /* Button Specific style */
        .woocommerce-orders-table__cell-order-actions a.tbp_actividades {
            background-color: #f39c12 !important;
            color: #fff !important;
        }
        .woocommerce-orders-table__cell-order-actions a.tbp_actividades:hover {
            background-color: #e67e22 !important;
        }
        .tbp-modal-section.tbp-highlight-section { background: #fff8eb; border: 2px dashed #f39c12; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .tbp-modal-section.tbp-highlight-section h3 { color: #d35400; margin-top: 0; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.tbp_actividades', function(e) {
            e.preventDefault();
            var order_id = $(this).data('order-id');
            if (!order_id) {
                order_id = $(this).closest('tr').find('.woocommerce-orders-table__cell-order-number a').text().replace('#', '').trim();
            }
            if (!order_id) {
                // Fallback for some themes
                var href = $(this).attr('href') || '';
                order_id = href.split('view-order/')[1];
                if (order_id) order_id = order_id.replace('/', '');
            }

            $('#tbp-modal-overlay').removeClass('tbp-modal-hide');
            $('#tbp-modal-dynamic-content').hide();
            $('#tbp-modal-loader').show();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'tbp_actividades_get_client_history',
                    order_id: order_id
                },
                success: function(response) {
                    $('#tbp-modal-loader').hide();
                    $('#tbp-modal-dynamic-content').html(response).fadeIn();
                }
            });
        });

        $('#tbp-modal-close, #tbp-modal-overlay').on('click', function(e) {
            if (e.target !== this && e.target.id !== 'tbp-modal-close') return;
            $('#tbp-modal-overlay').addClass('tbp-modal-hide');
        });
    });
    </script>
    <?php
}

/**
 * AJAX Handler for Client History
 */
add_action( 'wp_ajax_tbp_actividades_get_client_history', 'tbp_actividades_get_client_history_ajax' );
add_action( 'wp_ajax_nopriv_tbp_actividades_get_client_history', 'tbp_actividades_get_client_history_ajax' );

function tbp_actividades_get_client_history_ajax() {
    $order_id = intval( $_POST['order_id'] );
    if ( ! $order_id ) wp_die( 'Pedido inválido' );

    // Check if current user is the owner of the order
    $order = wc_get_order( $order_id );
    if ( ! $order || ( get_current_user_id() !== $order->get_customer_id() && ! current_user_can('manage_options') ) ) {
        echo '<p>' . __( 'No tienes permiso para ver esta información.', 'tbp-actividades' ) . '</p>';
        wp_die();
    }

    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_logs WHERE order_id = %d ORDER BY created_at DESC", $order_id ) );

    // Robust search for Tribe Event ID
    $tribe_event_id = 0;
    
    // 1. Try order meta keywords used by ET+
    $tribe_event_id = get_post_meta($order_id, '_tribe_tickets_event', true); 
    if (!$tribe_event_id) $tribe_event_id = get_post_meta($order_id, '_tribe_wooticket_event', true);

    // 2. Try product items
    if (!$tribe_event_id) {
        $items = $order->get_items();
        foreach($items as $item) {
            $p_id = $item->get_product_id();
            $tribe_event_id = get_post_meta($p_id, '_tribe_tickets_event', true);
            if (!$tribe_event_id) $tribe_event_id = get_post_meta($p_id, '_tribe_wooticket_event', true);
            if ($tribe_event_id) break;
        }
    }

    if ($tribe_event_id) {
        $premiaciones = get_posts(array(
            'post_type' => 'tbp_premiaciones',
            'meta_query' => array(
                array(
                    'key' => '_tbp_event_id',
                    'value' => $tribe_event_id
                )
            ),
            'posts_per_page' => 1
        ));

        if ($premiaciones) {
            $prem = $premiaciones[0];
            $user_group = tbp_actividades_get_user_group($order_id, $prem->ID);
            
            echo '<div class="tbp-modal-section tbp-highlight-section">';
            echo '<h3>🏆 ' . __( 'PREMIACIONES / NOMINACIONES', 'tbp-actividades' ) . '</h3>';

            if ($user_group) {
                // Admins can vote multiple times for testing
                $has_voted = !current_user_can('manage_options') && tbp_actividades_has_user_voted(get_current_user_id(), $tribe_event_id);
                
                if ($has_voted) {
                    // Show Results
                    tbp_actividades_render_voting_results($prem->ID, $user_group);
                } else {
                    // Show Voting Form
                    tbp_actividades_render_voting_form($prem->ID, $user_group, $order_id);
                }
            } else {
                echo '<p style="color:#d35400;"><small>' . __( 'No se detectó tu grupo o departamento. Asegúrate de haber completado tus datos de asistente.', 'tbp-actividades' ) . '</small></p>';
            }
            echo '</div>';
        }
    }

    echo '<div class="tbp-modal-section">';
    echo '<h3><span>🎫</span> ' . __( 'Registro de Boletos (RIFA)', 'tbp-actividades' ) . '</h3>';

    if ( $logs ) {
        echo '<table class="tbp-fe-table">';
        echo '<thead><tr>';
        echo '<th>' . __( 'FECHA DE ENTREGA', 'tbp-actividades' ) . '</th>';
        echo '<th>' . __( 'CANTIDAD', 'tbp-actividades' ) . '</th>';
        echo '<th>' . __( 'COSTO', 'tbp-actividades' ) . '</th>';
        echo '<th>' . __( 'TOTAL ($)', 'tbp-actividades' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        foreach ( $logs as $log ) {
            $costo = floatval( get_post_meta( $log->rifa_id, '_tbp_cost_physical', true ) );
            $total = $costo * $log->amount;
            $fecha = date_i18n( 'd/F/Y', strtotime( $log->created_at ) );
            
            echo '<tr>';
            echo '<td>' . strtoupper($fecha) . '</td>';
            echo '<td>' . $log->amount . '</td>';
            echo '<td>' . wc_price( $costo ) . '</td>';
            echo '<td class="tbp-fe-total">' . wc_price( $total ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p style="color: #999; font-style: italic; margin-top: 10px;">' . __( 'Aún no se han registrado entregas físicas para este pedido.', 'tbp-actividades' ) . '</p>';
    }
    echo '</div>';

    // Placeholders for future modules
    echo '<div class="tbp-modal-section" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; opacity: 0.6;">';
    echo '<p><strong>🪑 ' . __( 'Asignación de Asientos:', 'tbp-actividades' ) . '</strong> <small>' . __( 'Próximamente', 'tbp-actividades' ) . '</small></p>';
    echo '<p><strong>📸 ' . __( 'Toma de Fotografía:', 'tbp-actividades' ) . '</strong> <small>' . __( 'Próximamente', 'tbp-actividades' ) . '</small></p>';
    echo '</div>';

    wp_die();
}

/**
 * Display Attendee Metadata in Order Details Page (Frontend)
 */
add_action( 'woocommerce_order_details_after_order_table', 'tbp_actividades_display_attendee_meta_frontend', 15 );
function tbp_actividades_display_attendee_meta_frontend( $order ) {
    $order_id = $order->get_id();
    $attendees = tbp_actividades_get_order_attendees_meta( $order_id, $order );

    if ( empty( $attendees ) ) return;

    echo '<section class="tbp-frontend-attendees" style="margin-top: 2.5em; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif;">';
    echo '<h2 style="font-size: 1.25em; font-weight: 600; margin-bottom: 1em; color: #2c3e50; border-bottom: 2px solid #f1f1f1; padding-bottom: 10px;">👥 ' . __( 'Datos de Asistentes', 'tbp-actividades' ) . '</h2>';
    
    echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';

    $counter = 1;
    foreach ( $attendees as $source_id => $meta_groups ) {
        // Normalización para arrays planos (v5.0.2 logic)
        if ( ! empty($meta_groups) ) {
            reset($meta_groups);
            $first_key = key($meta_groups);
            if ( ! is_numeric($first_key) && $first_key !== 'Datos' ) {
                $meta_groups = array( 'Datos' => $meta_groups );
            }
        }

        foreach ( $meta_groups as $guest_index => $guest_data ) {
            if ( ! is_array($guest_data) ) continue;

            echo '<div style="background: #fff; border: 1px solid #e1e4e8; border-radius: 10px; padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s ease;">';
            echo '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px;">';
            echo '<span style="background: #edf2f7; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #4a5568;">' . $counter . '</span>';
            echo '<span style="font-weight: 600; color: #1a202c;">' . __( 'Asistente', 'tbp-actividades' ) . '</span>';
            echo '</div>';

            echo '<table style="width: 100%; border-collapse: collapse; font-size: 14px;">';
            foreach ( $guest_data as $field_key => $field_value ) {
                $display_label = $field_key;
                $display_value = '';

                if ( is_array($field_value) ) {
                    $display_label = $field_value['label'] ?? $field_key;
                    $display_value = $field_value['value'] ?? '';
                } else {
                    $display_value = $field_value;
                }

                if ( empty($display_value) ) continue;
                
                // Cleanup label
                $display_label = str_replace(['-', '_'], ' ', $display_label);
                $display_label = ucwords($display_label);

                echo '<tr>';
                echo '<td style="color: #718096; padding: 6px 0; width: 45%; border-bottom: 1px solid #f7fafc;">' . esc_html($display_label) . '</td>';
                echo '<td style="color: #2d3748; padding: 6px 0; font-weight: 500; text-align: right; border-bottom: 1px solid #f7fafc;">' . esc_html($display_value) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            $counter++;
        }
    }

    echo '</div>';

    // Add "VOTAR" Button directly in order details
    $items = $order->get_items();
    $tribe_event_id = 0;
    foreach($items as $item) {
        $p_id = $item->get_product_id();
        $tribe_event_id = get_post_meta($p_id, '_tribe_tickets_event', true);
        if (!$tribe_event_id) $tribe_event_id = get_post_meta($p_id, '_tribe_wooticket_event', true);
        if ($tribe_event_id) break;
    }

    if ($tribe_event_id) {
        $premiaciones = get_posts(array(
            'post_type' => 'tbp_premiaciones',
            'meta_query' => array(
                array(
                    'key' => '_tbp_event_id',
                    'value' => $tribe_event_id
                )
            ),
            'posts_per_page' => 1
        ));

        if ($premiaciones) {
            echo '<div style="margin-top: 30px; text-align: center; background: #fff8eb; padding: 20px; border: 2px dashed #f39c12; border-radius: 12px;">';
            echo '<h3 style="margin: 0 0 10px 0; color: #d35400;">🏆 ' . __('¡PARTICIPA EN LAS PREMIACIONES!', 'tbp-actividades') . '</h3>';
            echo '<p style="margin-bottom: 15px; font-size: 14px;">' . __('Haz clic en el botón para ver las categorías y votar por tus favoritos.', 'tbp-actividades') . '</p>';
            echo '<a href="#" class="tbp_actividades button" data-order-id="'.esc_attr($order_id).'" style="background: #f39c12 !important; color: #fff !important; font-weight: bold; padding: 12px 30px; border-radius: 8px; text-decoration: none;">' . __('🗳️ VOTAR AHORA', 'tbp-actividades') . '</a>';
            echo '</div>';
        }
    }

    echo '</section>';
}

/**
 * Renders the voting form
 */
function tbp_actividades_render_voting_form($premiacion_id, $group_name, $order_id) {
    $categories = get_post_meta($premiacion_id, '_tbp_categories', true);
    $nominees_by_group = tbp_actividades_get_parsed_nominees($premiacion_id);
    $group_nominees = $nominees_by_group[$group_name] ?? [];

    if (empty($group_nominees)) {
        echo '<p style="color:#d35400;"><small>' . sprintf(__('No hay nominados registrados para el grupo: %s', 'tbp-actividades'), esc_html($group_name)) . '</small></p>';
        return;
    }

    echo '<form id="tbp-voting-form" style="margin-top:15px;">';
    wp_nonce_field('tbp_vote_nonce', 'vote_nonce');
    echo '<input type="hidden" name="premiacion_id" value="'.esc_attr($premiacion_id).'">';
    echo '<input type="hidden" name="order_id" value="'.esc_attr($order_id).'">';
    echo '<input type="hidden" name="group_name" value="'.esc_attr($group_name).'">';

    echo '<div style="display: grid; grid-template-columns: 1fr; gap: 15px;">';
    foreach ($categories as $idx => $cat) {
        echo '<div style="border: 1px solid #eee; border-radius: 8px; padding: 15px; background: #fafafa;">';
        if (!empty($cat['img'])) {
            echo '<div style="width: 100%; height: 120px; background-image: url(\''.esc_url($cat['img']).'\'); background-size: cover; background-position: center; border-radius: 6px; margin-bottom: 10px;"></div>';
        }
        echo '<h4 style="margin: 0 0 5px 0;">'.esc_html($cat['title']).'</h4>';
        echo '<p style="font-size: 12px; color: #666; margin-bottom: 10px;">'.esc_html($cat['desc']).'</p>';
        echo '<select name="votes['.esc_attr($idx).']" required style="width: 100%;">';
        echo '<option value="">' . __('Seleccionar nominado...', 'tbp-actividades') . '</option>';
        foreach ($group_nominees as $nominee) {
            echo '<option value="'.esc_attr($nominee).'">'.esc_html($nominee).'</option>';
        }
        echo '</select>';
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="submit" class="button button-primary" style="margin-top: 20px; width: 100%; background: #27ae60; border: none; height: 40px; font-weight: bold; color: #fff; border-radius: 6px; cursor: pointer;">' . __('ENVIAR MI VOTACIÓN', 'tbp-actividades') . '</button>';
    echo '</form>';

    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#tbp-voting-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button');
            var originalText = btn.text();

            btn.prop('disabled', true).text('<?php _e('Enviando...', 'tbp-actividades'); ?>');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: form.serialize() + '&action=tbp_actividades_submit_vote',
                success: function(response) {
                    if (response.success) {
                        $('#tbp-modal-dynamic-content').html('<div style="text-align:center; padding: 30px;"><h2 style="color:#27ae60;">¡Gracias por tu voto!</h2><p>Actualizando resultados...</p></div>');
                        // Refresh status
                        setTimeout(function() {
                            $('.tbp_actividades').filter(function() {
                                return $(this).closest('tr').find('.woocommerce-orders-table__cell-order-number a').text().replace('#', '').trim() == '<?php echo $order_id; ?>';
                            }).first().click();
                        }, 1500);
                    } else {
                        alert(response.data || 'Error al enviar voto');
                        btn.prop('disabled', false).text(originalText);
                    }
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Renders the results view
 */
function tbp_actividades_render_voting_results($premiacion_id, $group_name) {
    $categories = get_post_meta($premiacion_id, '_tbp_categories', true);
    $results = tbp_actividades_get_voting_results($premiacion_id, $group_name);

    echo '<div class="tbp-results-view" style="margin-top:15px;">';
    echo '<p style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 13px; text-align: center; margin-bottom: 20px;">✓ ' . __('Ya has participado en esta votación. Aquí van los resultados de tu grupo:', 'tbp-actividades') . '</p>';

    foreach ($categories as $idx => $cat) {
        echo '<div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">';
        echo '<h4 style="margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase;">'.esc_html($cat['title']).'</h4>';
        
        $cat_votes = $results[$idx] ?? [];
        $total_cat_votes = array_sum($cat_votes);

        if ($total_cat_votes > 0) {
            arsort($cat_votes); // Sort by votes desc
            foreach ($cat_votes as $name => $count) {
                $percentage = ($count / $total_cat_votes) * 100;
                echo '<div style="margin-bottom: 10px;">';
                echo '<div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px;">';
                echo '<span>'.esc_html($name).'</span>';
                echo '<strong>'.esc_html($count).' '.__('votos', 'tbp-actividades').'</strong>';
                echo '</div>';
                echo '<div style="background: #eee; border-radius: 10px; height: 8px; overflow: hidden;">';
                echo '<div style="background: #f39c12; width: '.$percentage.'%; height: 100%; border-radius: 10px;"></div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p style="color:#999;font-size:12px;font-style:italic;">' . __('Aún no hay votos registrados.', 'tbp-actividades') . '</p>';
        }
        echo '</div>';
    }
    echo '</div>';
}

/**
 * AJAX Handler for Vote Submission
 */
add_action( 'wp_ajax_tbp_actividades_submit_vote', 'tbp_actividades_submit_vote_ajax' );
function tbp_actividades_submit_vote_ajax() {
    check_ajax_referer( 'tbp_vote_nonce', 'vote_nonce' );

    $user_id = get_current_user_id();
    $premiacion_id = intval( $_POST['premiacion_id'] );
    $order_id = intval( $_POST['order_id'] );
    $group_name = sanitize_text_field( $_POST['group_name'] );
    $votes = $_POST['votes'] ?? [];

    if ( ! $user_id || ! $premiacion_id || ! $order_id || empty( $votes ) ) {
        wp_send_json_error( 'Datos incompletos.' );
    }

    $event_id = get_post_meta($premiacion_id, '_tbp_event_id', true);
    if ( ! $event_id ) wp_send_json_error( 'Evento no configurado.' );

    // Double check if already voted
    if ( tbp_actividades_has_user_voted( $user_id, $event_id ) ) {
        wp_send_json_error( 'Ya has participado en esta votación.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'tbp_premiaciones_votos';

    foreach ( $votes as $cat_idx => $nominee_name ) {
        $wpdb->insert( $table, array(
            'premiacion_id' => $premiacion_id,
            'event_id'      => $event_id,
            'category_id'   => sanitize_text_field( $cat_idx ),
            'nominee_name'  => sanitize_text_field( $nominee_name ),
            'user_id'       => $user_id,
            'order_id'      => $order_id,
            'group_name'    => $group_name,
        ) );
    }

    wp_send_json_success();
}

/**
 * Shortcode for Voting: [tbp_votar_premiacion]
 * Use: [tbp_votar_premiacion order_id="123"] or just [tbp_votar_premiacion] and it will try to get order_id from URL
 */
add_shortcode( 'tbp_votar_premiacion', 'tbp_actividades_voting_shortcode' );
function tbp_actividades_voting_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'order_id' => isset($_GET['order_id']) ? intval($_GET['order_id']) : 0,
        'event_id' => isset($_GET['event_id']) ? intval($_GET['event_id']) : 0,
    ), $atts, 'tbp_votar_premiacion' );

    $order_id = intval( $atts['order_id'] );
    $event_id_override = intval( $atts['event_id'] );
    if ( ! $order_id ) {
        return '<div style="background:#fff3cd; padding:20px; border-radius:8px; border:1px solid #ffeeba; color:#856404;">' . 
               __( 'Por favor, accede a esta página a través del enlace en tu pedido o proporciona un ID de pedido válido (?order_id=XXXX).', 'tbp-actividades' ) . 
               '</div>';
    }

    $order = wc_get_order( $order_id );
    if ( ! $order || ( get_current_user_id() !== $order->get_customer_id() && ! current_user_can('manage_options') ) ) {
        return '<div style="background:#f8d7da; padding:20px; border-radius:8px; border:1px solid #f5c6cb; color:#721c24;">' . 
               __( 'No tienes permiso para ver esta votación o el pedido no existe.', 'tbp-actividades' ) . 
               '</div>';
    }

    // Super-Robust search for Tribe Event ID
    $tribe_event_id = 0;
    
    // DEBUG for Admins
    if ( current_user_can('manage_options') ) {
        echo '<div style="background:#222; color:#0f0; padding:15px; font-family:monospace; font-size:11px; margin:20px 0; border-radius:8px; border-left: 5px solid #0f0;">';
        echo '<strong>[DEBUG ADMIN]</strong> Análisis del Pedido #' . $order_id . '<br><br>';
    }

    // 1. Check order meta
    $tribe_event_id = get_post_meta($order_id, '_tribe_tickets_event', true); 
    if (!$tribe_event_id) $tribe_event_id = get_post_meta($order_id, '_tribe_wooticket_event', true);
    if (!$tribe_event_id) $tribe_event_id = get_post_meta($order_id, '_event_id', true);

    if ( current_user_can('manage_options') ) echo '1. ID Evento en Meta del Pedido: ' . ($tribe_event_id ?: 'No hallado') . '<br>';

    // 2. Check each order item meta
    if (!$tribe_event_id) {
        foreach($order->get_items() as $item_id => $item) {
            $p_id = $item->get_product_id();
            if ( current_user_can('manage_options') ) {
                $item_meta_keys = array_keys(get_metadata('order_item', $item_id));
                echo '&nbsp;&nbsp;- Item: ' . $item->get_name() . ' (Prod: '.$p_id.')<br>';
                echo '&nbsp;&nbsp;- Meta Keys: ' . implode(', ', $item_meta_keys) . '<br>';
            }
            $tribe_event_id = wc_get_order_item_meta( $item_id, '_tribe_tickets_event', true );
            if (!$tribe_event_id) $tribe_event_id = wc_get_order_item_meta( $item_id, '_event_id', true );
            if (!$tribe_event_id) $tribe_event_id = wc_get_order_item_meta( $item_id, '_tribe_wooticket_event', true );
            if ($tribe_event_id) break;
        }
        if ( current_user_can('manage_options') ) echo '2. ID Evento en Meta de Items: ' . ($tribe_event_id ?: 'No hallado') . '<br>';
    }

    // 3. Check each product meta
    if (!$tribe_event_id) {
        foreach($order->get_items() as $item) {
            $p_id = $item->get_product_id();
            $tribe_event_id = get_post_meta($p_id, '_tribe_tickets_event', true);
            if (!$tribe_event_id) $tribe_event_id = get_post_meta($p_id, '_tribe_wooticket_event', true);
            if ($tribe_event_id) break;
        }
        if ( current_user_can('manage_options') ) echo '3. ID Evento en Meta de Productos: ' . ($tribe_event_id ?: 'No hallado') . '<br>';
    }

    // 4. Fallback: Search for attendee posts linked to this order
    if (!$tribe_event_id) {
        global $wpdb;
        $attendee_id = $wpdb->get_var( $wpdb->prepare( "
            SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_tribe_wooticket_order' AND (meta_value = %s OR meta_value = %d)
            LIMIT 1", $order_id, $order_id ) );
        
        if ($attendee_id) {
            $tribe_event_id = get_post_meta($attendee_id, '_tribe_tickets_event', true);
            if (!$tribe_event_id) {
                // Try parent post for Event Tickets Plus
                $tribe_event_id = get_post_meta($attendee_id, '_event_id', true);
            }
        }
        if ( current_user_can('manage_options') ) echo '4. ID Evento en Post de Asistente: ' . ($tribe_event_id ?: 'No hallado') . '<br>';
    }

    if ( current_user_can('manage_options') ) echo '</div>';

    // Apply Override if provided
    if ($event_id_override) {
        $tribe_event_id = $event_id_override;
        if ( current_user_can('manage_options') ) {
            echo '<div style="background:#004d40; color:#fff; padding:10px; margin-bottom:10px;">';
            echo '<strong>INFO:</strong> Usando ID de Evento Manual: ' . $tribe_event_id;
            echo '</div>';
        }
    }

    if ( ! $tribe_event_id ) {
        return '<p>' . __( 'No se encontró un evento vinculado a este pedido.', 'tbp-actividades' ) . '</p>';
    }

    $premiaciones = get_posts(array(
        'post_type' => 'tbp_premiaciones',
        'meta_query' => array(
            array(
                'key' => '_tbp_event_id',
                'value' => $tribe_event_id
            )
        ),
        'posts_per_page' => 1
    ));

    if ( empty($premiaciones) ) {
        return '<p>' . __( 'No hay una votación activa para este evento.', 'tbp-actividades' ) . '</p>';
    }

    $prem = $premiaciones[0];
    $user_group = tbp_actividades_get_user_group($order_id, $prem->ID);

    ob_start();
    echo '<div class="tbp-voting-shortcode-wrapper" style="max-width: 600px; margin: 20px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top: 5px solid #f39c12;">';
    echo '<h2 style="text-align:center; color: #2c3e50; margin-bottom: 25px;">🏆 ' . esc_html($prem->post_title) . '</h2>';

    if ( $user_group ) {
        // Admins can vote multiple times
        $has_voted = !current_user_can('manage_options') && tbp_actividades_has_user_voted(get_current_user_id(), $tribe_event_id);
        
        if ($has_voted) {
            tbp_actividades_render_voting_results($prem->ID, $user_group);
        } else {
            echo '<p style="text-align:center; background:#e3f2fd; color:#1e88e5; padding:10px; border-radius:6px; font-weight:bold; margin-bottom:20px;">' . sprintf(__('Grupo detectado: %s', 'tbp-actividades'), esc_html($user_group)) . '</p>';
            tbp_actividades_render_voting_form($prem->ID, $user_group, $order_id);
        }
    } else {
        echo '<div style="background: #fff5f5; color: #c53030; padding: 20px; border-radius: 8px; border: 1px solid #feb2b2;">';
        echo '<p><strong>' . __( 'Grupo no detectado', 'tbp-actividades' ) . '</strong></p>';
        echo '<p>' . __( 'No pudimos encontrar tu grupo/departamento en los datos de asistente de este pedido.', 'tbp-actividades' ) . '</p>';
        echo '</div>';
    }
    echo '</div>';
    
    ?>
    <style>
        .tbp-voting-shortcode-wrapper select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd; margin-top: 5px; font-size: 14px; }
        .tbp-voting-shortcode-wrapper .button-primary { background: #27ae60 !important; border: none !important; padding: 15px !important; width: 100%; font-weight: bold; cursor: pointer; border-radius: 8px; color: #fff; font-size: 16px; margin-top: 20px; }
        .tbp-voting-shortcode-wrapper h4 { color: #2c3e50; font-size: 16px; margin-top: 0; }
    </style>
    <?php

    return ob_get_clean();
}
