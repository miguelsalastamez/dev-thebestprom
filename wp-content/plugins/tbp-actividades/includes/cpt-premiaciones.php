<?php
/**
 * Custom Post Type: tbp_premiaciones
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tbp_actividades_register_cpt_premiaciones() {
    $labels = array(
        'name'               => _x( 'Premiaciones', 'post type general name', 'tbp-actividades' ),
        'singular_name'      => _x( 'Premiación', 'post type singular name', 'tbp-actividades' ),
        'menu_name'          => _x( 'Premiaciones', 'admin menu', 'tbp-actividades' ),
        'add_new'            => _x( 'Crear Premiación', 'premiación', 'tbp-actividades' ),
        'add_new_item'       => __( 'Nueva Premiación', 'tbp-actividades' ),
        'edit_item'          => __( 'Editar Premiación', 'tbp-actividades' ),
        'new_item'           => __( 'Nueva Premiación', 'tbp-actividades' ),
        'all_items'          => __( 'Todas las Premiaciones', 'tbp-actividades' ),
        'view_item'          => __( 'Ver Premiación', 'tbp-actividades' ),
        'search_items'       => __( 'Buscar Premiaciones', 'tbp-actividades' ),
        'not_found'          => __( 'No se encontraron Premiaciones', 'tbp-actividades' ),
        'not_found_in_trash' => __( 'No se encontraron Premiaciones en la papelera', 'tbp-actividades' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => false, // Handled in tbp-actividades.php
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'tbp-premiaciones' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title' ),
    );

    register_post_type( 'tbp_premiaciones', $args );
}
add_action( 'init', 'tbp_actividades_register_cpt_premiaciones' );

/**
 * Enqueue scripts for the admin area
 */
function tbp_actividades_premiaciones_admin_scripts( $hook ) {
    global $post;
    if ( ( $hook == 'post-new.php' || $hook == 'post.php' ) && $post->post_type == 'tbp_premiaciones' ) {
        wp_enqueue_media();
        // SheetJS for Excel Parsing (Client-side)
        wp_enqueue_script( 'xlsx-cdn', 'https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js', array(), 'latest', true );
    }
}
add_action( 'admin_enqueue_scripts', 'tbp_actividades_premiaciones_admin_scripts' );

/**
 * Meta Boxes for Premiaciones
 */
function tbp_actividades_add_premiaciones_meta_boxes() {
    add_meta_box(
        'tbp_premiacion_settings',
        __( 'Configuración de la Premiación', 'tbp-actividades' ),
        'tbp_actividades_premiacion_settings_callback',
        'tbp_premiaciones',
        'normal',
        'high'
    );

    add_meta_box(
        'tbp_premiacion_integracion',
        __( 'SHORTCODE E INTEGRACIÓN', 'tbp-actividades' ),
        'tbp_actividades_premiacion_integration_html',
        'tbp_premiaciones',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'tbp_actividades_add_premiaciones_meta_boxes' );

function tbp_actividades_premiacion_settings_callback( $post ) {
    wp_nonce_field( 'tbp_premiacion_save', 'tbp_premiacion_nonce' );

    $event_id = get_post_meta( $post->ID, '_tbp_event_id', true );
    $categories = get_post_meta( $post->ID, '_tbp_categories', true ) ?: [];
    $nominees_raw = get_post_meta( $post->ID, '_tbp_nominees_raw', true );
    $group_field = get_post_meta( $post->ID, '_tbp_group_field', true );
    $current_event_id = $event_id; // For script use

    // Get Tribe Events
    $events = get_posts( array(
        'post_type' => 'tribe_events',
        'posts_per_page' => -1,
        'post_status' => array( 'publish', 'private' )
    ) );

    ?>
    <style>
        .tbp-cat-row { background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px; position: relative; }
        .tbp-cat-row .remove-cat { position: absolute; right: 10px; top: 10px; color: #a00; cursor: pointer; text-decoration: none; font-weight: bold; }
        .tbp-cat-row input[type="text"], .tbp-cat-row textarea { width: 100%; margin-bottom: 5px; }
        .tbp-cat-img-preview { width: 60px; height: 60px; background: #eee; border: 1px solid #ccc; display: inline-block; vertical-align: middle; margin-right: 10px; background-size: cover; background-position: center; }
    </style>

    <table class="form-table">
        <tr>
            <th><label for="tbp_event_id"><?php _e( 'Evento Vinculado', 'tbp-actividades' ); ?></label></th>
            <td>
                <select id="tbp_event_id" name="tbp_event_id">
                    <option value=""><?php _e( 'Seleccionar Evento...', 'tbp-actividades' ); ?></option>
                    <?php foreach ( $events as $event ) : ?>
                        <option value="<?php echo $event->ID; ?>" <?php selected( $event_id, $event->ID ); ?>><?php echo esc_html( $event->post_title ); ?> (ID: <?php echo $event->ID; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="tbp_group_field"><?php _e( 'Campo de Grupo/Departamento', 'tbp-actividades' ); ?></label></th>
            <td>
                <select id="tbp_group_field" name="tbp_group_field" style="min-width: 200px;">
                    <option value=""><?php _e( 'Seleccionar Evento primero...', 'tbp-actividades' ); ?></option>
                    <?php if ( $group_field ) : ?>
                        <option value="<?php echo esc_attr( $group_field ); ?>" selected><?php echo esc_html( $group_field ); ?></option>
                    <?php endif; ?>
                </select>
                <p class="description"><?php _e( 'Selecciona el campo del formulario del asistente que define el grupo.', 'tbp-actividades' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><?php _e( 'Categorías Dinámicas', 'tbp-actividades' ); ?></th>
            <td>
                <div id="tbp-categories-wrapper">
                    <?php foreach ( $categories as $idx => $cat ) : ?>
                        <div class="tbp-cat-row">
                            <a href="#" class="remove-cat">×</a>
                            <input type="text" name="tbp_cats[<?php echo $idx; ?>][title]" placeholder="Título de la Categoría" value="<?php echo esc_attr($cat['title']); ?>" />
                            <textarea name="tbp_cats[<?php echo $idx; ?>][desc]" placeholder="Descripción"><?php echo esc_textarea($cat['desc']); ?></textarea>
                            <div class="tbp-img-uploader">
                                <div class="tbp-cat-img-preview" style="background-image: url('<?php echo esc_url($cat['img']); ?>');"></div>
                                <input type="hidden" name="tbp_cats[<?php echo $idx; ?>][img]" value="<?php echo esc_attr($cat['img']); ?>" class="tbp-cat-img-url" />
                                <button type="button" class="button tbp-upload-img-btn"><?php _e( 'Subir Imagen', 'tbp-actividades' ); ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-tbp-cat" class="button button-primary"><?php _e( 'Añadir Categoría', 'tbp-actividades' ); ?></button>
            </td>
        </tr>
        <tr>
            <th><label for="tbp_nominees_raw"><?php _e( 'Lista de Nominados (Nombre | Grupo)', 'tbp-actividades' ); ?></label></th>
            <td>
                <div style="margin-bottom: 10px;">
                    <button type="button" id="tbp-upload-nominees-btn" class="button button-secondary">📂 <?php _e( 'Subir Excel / CSV', 'tbp-actividades' ); ?></button>
                    <input type="file" id="tbp-nominees-file-input" style="display:none;" accept=".xlsx, .xls, .csv" />
                    <span id="tbp-upload-status" style="margin-left: 10px; font-size: 11px; color: #666;"></span>
                </div>
                <textarea id="tbp_nominees_raw" name="tbp_nominees_raw" rows="10" style="width:100%; font-family:monospace;" placeholder="Juan Perez | Sistemas&#10;Maria Lopez | Ventas"><?php echo esc_textarea( $nominees_raw ); ?></textarea>
                <p class="description"><?php _e( 'Uno por línea. El grupo debe coincidir con el campo de asistente.', 'tbp-actividades' ); ?></p>
            </td>
        </tr>
    </table>

    <script>
    jQuery(document).ready(function($) {
        var frame;
        var currentGroupField = '<?php echo esc_js($group_field); ?>';

        function refreshGroupFields(eventId) {
            var fieldSelect = $('#tbp_group_field');
            if (!eventId) {
                fieldSelect.html('<option value=""><?php _e( 'Seleccionar Evento primero...', 'tbp-actividades' ); ?></option>');
                return;
            }

            fieldSelect.html('<option value=""><?php _e( 'Cargando campos...', 'tbp-actividades' ); ?></option>');

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'tbp_actividades_get_event_attendee_fields',
                    event_id: eventId
                },
                success: function(response) {
                    var html = '<option value=""><?php _e( '-- Seleccionar Campo --', 'tbp-actividades' ); ?></option>';
                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(i, label) {
                            var selected = (label === currentGroupField) ? 'selected' : '';
                            html += '<option value="'+label+'" '+selected+'>'+label+'</option>';
                        });
                    } else {
                        html = '<option value=""><?php _e( 'No se encontraron campos editables', 'tbp-actividades' ); ?></option>';
                        // If we have a saved value but no schema found, keep the saved value as an option
                        if (currentGroupField) {
                             html += '<option value="'+currentGroupField+'" selected>'+currentGroupField+' (Actual)</option>';
                        }
                    }
                    fieldSelect.html(html);
                }
            });
        }

        // Initialize fields if event is already selected
        if ($('#tbp_event_id').val()) {
            refreshGroupFields($('#tbp_event_id').val());
        }

        $('#tbp_event_id').on('change', function() {
            refreshGroupFields($(this).val());
        });

        $('#add-tbp-cat').on('click', function() {
            var idx = $('#tbp-categories-wrapper .tbp-cat-row').length;
            var html = '<div class="tbp-cat-row">' +
                       '<a href="#" class="remove-cat">×</a>' +
                       '<input type="text" name="tbp_cats['+idx+'][title]" placeholder="Título de la Categoría" />' +
                       '<textarea name="tbp_cats['+idx+'][desc]" placeholder="Descripción"></textarea>' +
                       '<div class="tbp-img-uploader">' +
                       '<div class="tbp-cat-img-preview"></div>' +
                       '<input type="hidden" name="tbp_cats['+idx+'][img]" class="tbp-cat-img-url" />' +
                       '<button type="button" class="button tbp-upload-img-btn">Subir Imagen</button>' +
                       '</div>' +
                       '</div>';
            $('#tbp-categories-wrapper').append(html);
        });

        $(document).on('click', '.remove-cat', function(e) {
            e.preventDefault();
            $(this).parent().remove();
        });

        var frame;
        var activeUploader;

        $(document).on('click', '.tbp-upload-img-btn', function(e) {
            e.preventDefault();
            activeUploader = $(this).closest('.tbp-img-uploader');

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: 'Seleccionar Imagen',
                button: { text: 'Usar imagen' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                if (activeUploader) {
                    activeUploader.find('.tbp-cat-img-url').val(attachment.url);
                    activeUploader.find('.tbp-cat-img-preview').css('background-image', 'url('+attachment.url+')');
                }
            });

            frame.open();
        });

        // --- EXCEL/CSV UPLOAD LOGIC ---
        $('#tbp-upload-nominees-btn').on('click', function() {
            $('#tbp-nominees-file-input').click();
        });

        $('#tbp-nominees-file-input').on('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;

            var reader = new FileReader();
            var status = $('#tbp-upload-status');
            status.text('<?php _e('Procesando archivo...', 'tbp-actividades'); ?>').css('color', '#666');

            reader.onload = function(e) {
                try {
                    var data = new Uint8Array(e.target.result);
                    var workbook = XLSX.read(data, {type: 'array'});
                    var firstSheetName = workbook.SheetNames[0];
                    var worksheet = workbook.Sheets[firstSheetName];
                    var jsonData = XLSX.utils.sheet_to_json(worksheet, {header: 1});

                    var nomineesTextarea = $('#tbp_nominees_raw');
                    var currentContent = nomineesTextarea.val().trim();
                    var newNominees = [];

                    jsonData.forEach(function(row, idx) {
                        if (row.length >= 2) {
                            var name = String(row[0]).trim();
                            var group = String(row[1]).trim();
                            
                            // Basic validation: skip headers if they look like "Nombre" or "Grupo"
                            if (idx === 0 && (name.toLowerCase() === 'nombre' || name.toLowerCase() === 'name')) {
                                return;
                            }

                            if (name && group) {
                                newNominees.push(name + ' | ' + group);
                            }
                        }
                    });

                    if (newNominees.length > 0) {
                        var addedLines = newNominees.join('\n');
                        var finalContent = currentContent ? currentContent + '\n' + addedLines : addedLines;
                        nomineesTextarea.val(finalContent);
                        status.text('<?php _e('¡Carga exitosa!', 'tbp-actividades'); ?> (' + newNominees.length + ' <?php _e('añadidos', 'tbp-actividades'); ?>)').css('color', '#27ae60');
                    } else {
                        status.text('<?php _e('No se encontraron nominados válidos.', 'tbp-actividades'); ?>').css('color', '#a00');
                    }
                } catch (err) {
                    console.error(err);
                    status.text('<?php _e('Error al leer el archivo.', 'tbp-actividades'); ?>').css('color', '#a00');
                }
                // Clear input to allow same file re-upload
                $('#tbp-nominees-file-input').val('');
            };

            reader.readAsArrayBuffer(file);
        });
    });
    </script>
    <?php
}

function tbp_actividades_save_premiacion_settings( $post_id ) {
    if ( ! isset( $_POST['tbp_premiacion_nonce'] ) || ! wp_verify_nonce( $_POST['tbp_premiacion_nonce'], 'tbp_premiacion_save' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['tbp_event_id'] ) ) {
        update_post_meta( $post_id, '_tbp_event_id', sanitize_text_field( $_POST['tbp_event_id'] ) );
    }
    if ( isset( $_POST['tbp_group_field'] ) ) {
        update_post_meta( $post_id, '_tbp_group_field', sanitize_text_field( $_POST['tbp_group_field'] ) );
    }
    if ( isset( $_POST['tbp_nominees_raw'] ) ) {
        update_post_meta( $post_id, '_tbp_nominees_raw', sanitize_textarea_field( $_POST['tbp_nominees_raw'] ) );
    }

    if ( isset( $_POST['tbp_cats'] ) && is_array( $_POST['tbp_cats'] ) ) {
        $cats = [];
        foreach ( $_POST['tbp_cats'] as $cat ) {
            if ( ! empty( $cat['title'] ) ) {
                $cats[] = array(
                    'title' => sanitize_text_field( $cat['title'] ),
                    'desc'  => sanitize_textarea_field( $cat['desc'] ),
                    'img'   => esc_url_raw( $cat['img'] )
                );
            }
        }
        update_post_meta( $post_id, '_tbp_categories', $cats );
    } else {
        delete_post_meta( $post_id, '_tbp_categories' );
    }
}
add_action( 'save_post_tbp_premiaciones', 'tbp_actividades_save_premiacion_settings' );

/**
 * AJAX Handler for fetching attendee fields from event tickets
 */
add_action( 'wp_ajax_tbp_actividades_get_event_attendee_fields', 'tbp_actividades_get_event_attendee_fields_ajax' );
function tbp_actividades_get_event_attendee_fields_ajax() {
    $event_id = intval( $_GET['event_id'] );
    if ( ! $event_id ) wp_send_json_error( 'ID de evento no válido' );

    // Attempt 1: Using Tribe Core API (The most reliable way)
    if ( class_exists( 'Tribe__Tickets__Tickets' ) ) {
        $tribe_tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $event_id );
        if ( ! empty( $tribe_tickets ) ) {
            foreach ( $tribe_tickets as $ticket ) {
                $ticket_id = is_object( $ticket ) ? ( $ticket->ID ?? ( $ticket->ticket_id ?? 0 ) ) : intval( $ticket );
                if ( ! $ticket_id ) continue;

                $meta = get_post_meta( $ticket_id, '_tribe_tickets_meta', true );
                if ( is_array( $meta ) ) {
                    foreach ( $meta as $field ) {
                        if ( isset( $field['label'] ) ) {
                            $fields[] = $field['label'];
                        }
                    }
                }
            }
        }
    }

    // Attempt 2: Direct query for WooCommerce products linked to the event (Fallback)
    if ( empty( $fields ) ) {
        $tickets = get_posts( array(
            'post_type'      => 'product',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'   => '_tribe_tickets_event',
                    'value' => $event_id,
                ),
                array(
                    'key'   => '_tribe_wooticket_event',
                    'value' => $event_id,
                )
            ),
            'posts_per_page' => -1,
            'fields'         => 'ids'
        ) );

        foreach ( $tickets as $ticket_id ) {
            $meta = get_post_meta( $ticket_id, '_tribe_tickets_meta', true );
            if ( is_array( $meta ) ) {
                foreach ( $meta as $field ) {
                    if ( isset( $field['label'] ) ) {
                        $fields[] = $field['label'];
                    }
                }
            }
        }
    }

    // Attempt 3: Check HivePress or other meta locations if possible (General search)
    if ( empty( $fields ) ) {
        // Search for any post that has the event ID and a tickets meta key
        global $wpdb;
        $linked_products = $wpdb->get_col( $wpdb->prepare( "
            SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_value = %d 
            AND meta_key IN ('_tribe_tickets_event', '_tribe_wooticket_event', '_event_id')
        ", $event_id ) );

        foreach ( array_unique($linked_products) as $p_id ) {
            $meta = get_post_meta( $p_id, '_tribe_tickets_meta', true );
            if ( is_array( $meta ) ) {
                foreach ( $meta as $field ) {
                    if ( isset( $field['label'] ) ) {
                        $fields[] = $field['label'];
                    }
                }
            }
        }
    }

    $unique_fields = array_unique( $fields );
    
    if ( empty( $unique_fields ) ) {
        wp_send_json_error( 'No se encontraron campos de asistente para este evento.' );
    }

    wp_send_json_success( array_values( $unique_fields ) );
}

/**
 * Integration Meta Box HTML
 */
function tbp_actividades_premiacion_integration_html( $post ) {
    ?>
    <div style="background: #fdfdfd; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        <p style="margin-top: 0;"><strong><?php _e( 'Shortcode:', 'tbp-actividades' ); ?></strong></p>
        <code style="display: block; background: #eee; padding: 5px; border-radius: 3px; font-weight: bold;">[tbp_votar_premiacion]</code>
        
        <p style="margin-top: 15px;"><strong><?php _e( 'Instrucciones:', 'tbp-actividades' ); ?></strong></p>
        <small style="display: block; color: #666; font-size: 11px;">
            <?php _e( '1. Pegue el shortcode en una página pública.', 'tbp-actividades' ); ?><br>
            <?php _e( '2. Use la URL de esa página con ?order_id=X para ver la votación del pedido X.', 'tbp-actividades' ); ?>
        </small>
        
        <p style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
            <a href="<?php echo admin_url('admin.php?page=tbp-actividades-reporte-premiaciones'); ?>" class="button button-secondary" style="width: 100%; text-align: center;">📊 <?php _e( 'Ver Reportes', 'tbp-actividades' ); ?></a>
        </p>
    </div>
    <?php
}
