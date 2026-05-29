<?php
/**
 * Frontend Staff Shortcode
 * Generates the form for staff to create tickets from the frontend delivery zone.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register the shortcode [tbp_soporte_staff_form]
 */
function tbp_soporte_register_staff_shortcode() {
    add_shortcode( 'tbp_soporte_staff_form', 'tbp_soporte_staff_form_html' );
}
add_action( 'init', 'tbp_soporte_register_staff_shortcode' );

/**
 * Render the shortcode HTML
 */
function tbp_soporte_staff_form_html( $atts ) {
    // Check if user is logged in and not a regular customer/subscriber
    $user = wp_get_current_user();
    if ( ! is_user_logged_in() || in_array( 'customer', (array) $user->roles, true ) || in_array( 'subscriber', (array) $user->roles, true ) ) {
        return '<div class="woocommerce-error">' . esc_html__( 'No tienes permisos para ver este formulario.', 'tbp-soporte' ) . '</div>';
    }

    wp_enqueue_script( 'tbp-soporte-frontend-js' );

    // Pre-defined incidence types
    $incidences = array(
        'Falta de producto / entregable',
        'Cambio de talla / modelo',
        'Producto dañado / defectuoso',
        'Problema con el pago',
        'Otro (Especificar)'
    );

    ob_start();
    ?>
    <div class="tbp-soporte-staff-form-container" style="background: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #f1f5f9; max-width: 650px; margin: 40px auto; font-family: inherit;">
        <h3 style="margin-top: 0; color: #0f172a; text-align: center; font-size: 24px; font-weight: 800; text-transform: uppercase;">🚨 Levantar Incidencia</h3>
        <p style="font-size: 15px; color: #64748b; margin-bottom: 30px; text-align: center;">Uso Interno de Staff. Se generará un ticket y se notificará al cliente automáticamente.</p>

        <form id="tbp-soporte-staff-form" class="tbp-soporte-form" enctype="multipart/form-data">
            
            <div class="tbp-soporte-form-row">
                <label for="staff_order_id" style="display: block; font-weight: 600; margin-bottom: 8px; color: #1e293b; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;"><?php esc_html_e( 'ID del Pedido', 'tbp-soporte' ); ?> *</label>
                <input type="text" inputmode="numeric" pattern="[0-9]*" id="staff_order_id" name="staff_order_id" required placeholder="Ej. 36100" style="width: 100% !important; max-width: 100%; box-sizing: border-box; padding: 15px 20px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; background-color: #f8fafc; color: #333; transition: all 0.3s ease; height: 52px; outline: none;" onfocus="this.style.borderColor='#3b82f6'; this.style.backgroundColor='#ffffff';" onblur="this.style.borderColor='#e2e8f0'; this.style.backgroundColor='#f8fafc';" />
                <small style="color: #94a3b8; display: block; margin-top: 5px; font-size: 12px;">Ingresa solo el número (Ej: 36100)</small>
            </div>

            <div class="tbp-soporte-form-row" style="margin-top: 25px;">
                <label for="staff_incidence_type" style="display: block; font-weight: 600; margin-bottom: 8px; color: #1e293b; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;"><?php esc_html_e( 'Tipo de Incidencia', 'tbp-soporte' ); ?> *</label>
                <select id="staff_incidence_type" name="staff_incidence_type" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; background-color: #f8fafc; color: #333; transition: all 0.3s ease; height: 52px; outline: none; appearance: auto;" onfocus="this.style.borderColor='#3b82f6'; this.style.backgroundColor='#ffffff';" onblur="this.style.borderColor='#e2e8f0'; this.style.backgroundColor='#f8fafc';">
                    <option value=""><?php esc_html_e( '-- Selecciona una opción --', 'tbp-soporte' ); ?></option>
                    <?php foreach ( $incidences as $incidence ) : ?>
                        <option value="<?php echo esc_attr( $incidence ); ?>"><?php echo esc_html( $incidence ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="tbp-soporte-form-row" style="margin-top: 25px;">
                <label for="staff_comments" style="display: block; font-weight: 600; margin-bottom: 8px; color: #1e293b; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;"><?php esc_html_e( 'Comentarios Adicionales', 'tbp-soporte' ); ?> *</label>
                <textarea id="staff_comments" name="staff_comments" rows="4" required placeholder="Detalla la situación..." style="width: 100%; box-sizing: border-box; padding: 15px 20px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; background-color: #f8fafc; color: #333; transition: all 0.3s ease; outline: none;" onfocus="this.style.borderColor='#3b82f6'; this.style.backgroundColor='#ffffff';" onblur="this.style.borderColor='#e2e8f0'; this.style.backgroundColor='#f8fafc';"></textarea>
            </div>

            <div class="tbp-soporte-form-row" style="margin-top: 25px;">
                <label for="staff_ticket_file" style="display: block; font-weight: 600; margin-bottom: 8px; color: #1e293b; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;"><?php esc_html_e( 'Evidencia (Opcional)', 'tbp-soporte' ); ?></label>
                <div style="border: 2px dashed #cbd5e1; padding: 20px; border-radius: 10px; background-color: #f8fafc; text-align: center;">
                    <input type="file" id="staff_ticket_file" name="staff_ticket_file" accept=".jpg,.jpeg,.png,.pdf" style="width: 100%; box-sizing: border-box; font-size: 14px; color: #64748b;" />
                </div>
                <small style="color: #94a3b8; display: block; margin-top: 5px; font-size: 12px;">Sube una foto si el producto está dañado (JPG, PNG, PDF).</small>
            </div>

            <div class="tbp-soporte-form-row tbp-soporte-submit" style="margin-top: 35px;">
                <?php wp_nonce_field( 'tbp_soporte_staff_nonce', 'staff_nonce' ); ?>
                <button type="submit" id="tbp-staff-submit-btn" class="button alt" style="width: 100%; background-color: #f5a623; color: #ffffff; border: none; padding: 16px; font-size: 16px; font-weight: 800; border-radius: 10px; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(245, 166, 35, 0.3);" onmouseover="this.style.backgroundColor='#e0961d'; this.style.transform='translateY(-2px)';" onmouseout="this.style.backgroundColor='#f5a623'; this.style.transform='translateY(0)';"><?php esc_html_e( 'Generar Ticket y Notificar', 'tbp-soporte' ); ?></button>
            </div>

            <div id="tbp-staff-form-message" style="margin-top: 20px; display: none; padding: 15px; border-radius: 8px; text-align: center; font-weight: 600; border: 1px solid transparent;"></div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#tbp-soporte-staff-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $('#tbp-staff-submit-btn');
            var $msg = $('#tbp-staff-form-message');
            
            $btn.prop('disabled', true).text('Procesando...');
            $msg.hide().removeClass('woocommerce-error woocommerce-message');

            var formData = new FormData($form[0]);
            formData.append('action', 'tbp_soporte_staff_create_ticket');

            $.ajax({
                url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $msg.addClass('woocommerce-message').html(response.data.message).show();
                        $form[0].reset();
                    } else {
                        $msg.addClass('woocommerce-error').html(response.data.message).show();
                    }
                    $btn.prop('disabled', false).text('Generar Ticket y Notificar al Cliente');
                },
                error: function() {
                    $msg.addClass('woocommerce-error').html('Error de red. Inténtalo de nuevo.').show();
                    $btn.prop('disabled', false).text('Generar Ticket y Notificar al Cliente');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
