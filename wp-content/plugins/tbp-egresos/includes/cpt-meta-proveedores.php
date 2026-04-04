<?php
/**
 * Metaboxes for TBP Proveedor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'add_meta_boxes', 'tbp_egresos_add_proveedor_meta_boxes' );
function tbp_egresos_add_proveedor_meta_boxes() {
    add_meta_box(
        'tbp_proveedor_details',
        __( 'Detalles del Proveedor', 'tbp-egresos' ),
        'tbp_egresos_proveedor_details_html',
        'tbp_proveedor',
        'normal',
        'high'
    );

    add_meta_box(
        'tbp_proveedor_contactos',
        __( 'Multi Contactos', 'tbp-egresos' ),
        'tbp_egresos_proveedor_contactos_html',
        'tbp_proveedor',
        'normal',
        'high'
    );
}

function tbp_egresos_proveedor_details_html( $post ) {
    wp_nonce_field( 'tbp_proveedor_save', 'tbp_proveedor_nonce' );

    $domicilio = get_post_meta( $post->ID, '_tbp_domicilio', true );
    $datos_bancarios = get_post_meta( $post->ID, '_tbp_datos_bancarios', true );
    $notas = get_post_meta( $post->ID, '_tbp_notas', true );

    ?>
    <table class="form-table">
        <tr>
            <th><label for="tbp_domicilio"><?php _e( 'Domicilio', 'tbp-egresos' ); ?></label></th>
            <td><input type="text" id="tbp_domicilio" name="tbp_domicilio" value="<?php echo esc_attr( $domicilio ); ?>" class="large-text" /></td>
        </tr>
        <tr>
            <th><label for="tbp_datos_bancarios"><?php _e( 'Datos Bancarios', 'tbp-egresos' ); ?></label></th>
            <td><textarea id="tbp_datos_bancarios" name="tbp_datos_bancarios" rows="4" class="large-text"><?php echo esc_textarea( $datos_bancarios ); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="tbp_notas"><?php _e( 'Notas Generales', 'tbp-egresos' ); ?></label></th>
            <td><textarea id="tbp_notas" name="tbp_notas" rows="3" class="large-text"><?php echo esc_textarea( $notas ); ?></textarea></td>
        </tr>
    </table>
    <?php
}

function tbp_egresos_proveedor_contactos_html( $post ) {
    $contactos = get_post_meta( $post->ID, '_tbp_contactos', true );
    if ( ! is_array( $contactos ) ) {
        $contactos = array();
    }
    ?>
    <div id="tbp-contactos-wrapper">
        <style>
            .tbp-contacto-row { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; position: relative; border-radius: 4px; }
            .tbp-contacto-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
            .tbp-contacto-row .remove-contacto { position: absolute; top: 5px; right: 5px; color: #a00; cursor: pointer; text-decoration: none; font-weight: bold; }
            .tbp-contacto-row input, .tbp-contacto-row textarea { width: 100%; margin-top: 5px; }
            .tbp-contacto-row label { font-size: 11px; font-weight: bold; color: #666; display: block; }
            .tbp-btn-action { display: inline-block; padding: 2px 6px; background: #eee; border-radius: 3px; text-decoration: none; color: #555; font-size: 10px; margin-top: 2px; }
            .tbp-btn-whatsapp { background: #25D366; color: white; }
            .tbp-btn-email { background: #0073aa; color: white; }
        </style>

        <div class="contactos-list">
            <?php foreach ( $contactos as $index => $contacto ) : ?>
                <div class="tbp-contacto-row">
                    <a href="#" class="remove-contacto">×</a>
                    <div class="tbp-contacto-grid">
                        <div>
                            <label>Puesto</label>
                            <input type="text" name="tbp_contactos[<?php echo $index; ?>][puesto]" value="<?php echo esc_attr( $contacto['puesto'] ); ?>" />
                        </div>
                        <div>
                            <label>Nombre</label>
                            <input type="text" name="tbp_contactos[<?php echo $index; ?>][nombre]" value="<?php echo esc_attr( $contacto['nombre'] ); ?>" />
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="tbp_contactos[<?php echo $index; ?>][email]" value="<?php echo esc_attr( $contacto['email'] ); ?>" />
                            <?php if ( ! empty( $contacto['email'] ) ) : ?>
                                <a href="mailto:<?php echo esc_attr( $contacto['email'] ); ?>" class="tbp-btn-action tbp-btn-email" target="_blank">Enviar Mail</a>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label>Teléfono 1</label>
                            <input type="text" name="tbp_contactos[<?php echo $index; ?>][tel1]" value="<?php echo esc_attr( $contacto['tel1'] ); ?>" />
                            <?php if ( ! empty( $contacto['tel1'] ) ) : 
                                $wa_num = preg_replace('/[^0-9]/', '', $contacto['tel1']);
                                if ( strlen($wa_num) == 10 ) $wa_num = "52" . $wa_num;
                            ?>
                                <a href="https://wa.me/<?php echo $wa_num; ?>" class="tbp-btn-action tbp-btn-whatsapp" target="_blank">WhatsApp</a>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label>Teléfono 2</label>
                            <input type="text" name="tbp_contactos[<?php echo $index; ?>][tel2]" value="<?php echo esc_attr( $contacto['tel2'] ); ?>" />
                        </div>
                        <div>
                            <label>Notas</label>
                            <textarea name="tbp_contactos[<?php echo $index; ?>][notas]"><?php echo esc_textarea( $contacto['notas'] ); ?></textarea>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <p><button type="button" class="button add-contacto"><?php _e( '+ Añadir Contacto', 'tbp-egresos' ); ?></button></p>

        <script>
            jQuery(document).ready(function($) {
                $('.add-contacto').on('click', function() {
                    var index = $('.tbp-contacto-row').length;
                    var row = '<div class="tbp-contacto-row">' +
                        '<a href="#" class="remove-contacto">×</a>' +
                        '<div class="tbp-contacto-grid">' +
                        '<div><label>Puesto</label><input type="text" name="tbp_contactos['+index+'][puesto]" /></div>' +
                        '<div><label>Nombre</label><input type="text" name="tbp_contactos['+index+'][nombre]" /></div>' +
                        '<div><label>Email</label><input type="email" name="tbp_contactos['+index+'][email]" /></div>' +
                        '<div><label>Teléfono 1</label><input type="text" name="tbp_contactos['+index+'][tel1]" /></div>' +
                        '<div><label>Teléfono 2</label><input type="text" name="tbp_contactos['+index+'][tel2]" /></div>' +
                        '<div><label>Notas</label><textarea name="tbp_contactos['+index+'][notas]"></textarea></div>' +
                        '</div></div>';
                    $('.contactos-list').append(row);
                });

                $(document).on('click', '.remove-contacto', function(e) {
                    e.preventDefault();
                    if (confirm('¿Seguro que deseas eliminar este contacto?')) {
                        $(this).closest('.tbp-contacto-row').remove();
                    }
                });
            });
        </script>
    </div>
    <?php
}

add_action( 'save_post_tbp_proveedor', 'tbp_egresos_save_proveedor_meta' );
function tbp_egresos_save_proveedor_meta( $post_id ) {
    if ( ! isset( $_POST['tbp_proveedor_nonce'] ) || ! wp_verify_nonce( $_POST['tbp_proveedor_nonce'], 'tbp_proveedor_save' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['tbp_domicilio'] ) ) {
        update_post_meta( $post_id, '_tbp_domicilio', sanitize_text_field( $_POST['tbp_domicilio'] ) );
    }

    if ( isset( $_POST['tbp_datos_bancarios'] ) ) {
        update_post_meta( $post_id, '_tbp_datos_bancarios', sanitize_textarea_field( $_POST['tbp_datos_bancarios'] ) );
    }

    if ( isset( $_POST['tbp_notas'] ) ) {
        update_post_meta( $post_id, '_tbp_notas', sanitize_textarea_field( $_POST['tbp_notas'] ) );
    }

    if ( isset( $_POST['tbp_contactos'] ) && is_array( $_POST['tbp_contactos'] ) ) {
        $contactos = array();
        foreach ( $_POST['tbp_contactos'] as $contacto ) {
            if ( ! empty( $contacto['nombre'] ) ) {
                $contactos[] = array(
                    'puesto' => sanitize_text_field( $contacto['puesto'] ),
                    'nombre' => sanitize_text_field( $contacto['nombre'] ),
                    'email'  => sanitize_email( $contacto['email'] ),
                    'tel1'   => sanitize_text_field( $contacto['tel1'] ),
                    'tel2'   => sanitize_text_field( $contacto['tel2'] ),
                    'notas'  => sanitize_textarea_field( $contacto['notas'] ),
                );
            }
        }
        update_post_meta( $post_id, '_tbp_contactos', $contactos );
    } else {
        update_post_meta( $post_id, '_tbp_contactos', array() );
    }
}
