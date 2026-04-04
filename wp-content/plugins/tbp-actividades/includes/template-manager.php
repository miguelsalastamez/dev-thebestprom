<?php
/**
 * Canva / HTML Template Manager for The Best Prom
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

    // Hook moved to tbp-actividades.php for correct menu ordering

function tbp_actividades_plantillas_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    $message = '';
    $message_type = 'success';

    // Handle Deletion
    if ( isset( $_GET['delete_template'] ) && check_admin_referer( 'delete_template_' . $_GET['delete_template'] ) ) {
        $templates = get_option( 'tbp_marketing_templates', array() );
        $t_id = sanitize_text_field( $_GET['delete_template'] );
        if ( isset( $templates[ $t_id ] ) ) {
            unset( $templates[ $t_id ] );
            update_option( 'tbp_marketing_templates', $templates );
            $message = 'Plantilla eliminada correctamente.';
        }
    }

    // Handle Upload
    if ( isset( $_POST['tbp_upload_template'] ) && wp_verify_nonce( $_POST['tbp_template_nonce'], 'tbp_upload_template_action' ) ) {
        $template_name = sanitize_text_field( $_POST['template_name'] );
        
        if ( empty( $template_name ) ) {
            $message = 'El nombre de la plantilla es obligatorio.';
            $message_type = 'error';
        } elseif ( empty( $_FILES['template_zip']['name'] ) ) {
            $message = 'Debes subir un archivo .zip de Canva.';
            $message_type = 'error';
        } else {
            $upload = $_FILES['template_zip'];
            $ext = pathinfo( $upload['name'], PATHINFO_EXTENSION );
            
            if ( strtolower( $ext ) !== 'zip' ) {
                $message = 'Solo se permiten archivos .zip';
                $message_type = 'error';
            } else {
                // Initialize WP Filesystem
                WP_Filesystem();
                global $wp_filesystem;

                $upload_dir = wp_upload_dir();
                $target_dir = $upload_dir['basedir'] . '/tbp-templates/';
                if ( ! file_exists( $target_dir ) ) {
                    wp_mkdir_p( $target_dir );
                }

                $template_id = 'tpl_' . time();
                $unzip_dir = $target_dir . $template_id . '/';
                wp_mkdir_p( $unzip_dir );

                $zip_path = $upload['tmp_name'];
                $unzip_result = unzip_file( $zip_path, $unzip_dir );

                if ( is_wp_error( $unzip_result ) ) {
                    $message = 'Error al descomprimir: ' . $unzip_result->get_error_message();
                    $message_type = 'error';
                } else {
                    // Search for email.html
                    $html_file = '';
                    $images_dir = '';

                    // Check if it extracted a root folder or directly
                    if ( file_exists( $unzip_dir . 'email.html' ) ) {
                        $html_file = $unzip_dir . 'email.html';
                        $images_dir = $unzip_dir . 'images/';
                    } else {
                        // Scan subdirectories
                        $dirs = glob( $unzip_dir . '*', GLOB_ONLYDIR );
                        if ( ! empty( $dirs ) && file_exists( $dirs[0] . '/email.html' ) ) {
                            $html_file = $dirs[0] . '/email.html';
                            $images_dir = $dirs[0] . '/images/';
                        }
                    }

                    if ( ! empty( $html_file ) && file_exists( $html_file ) ) {
                        $html_content = $wp_filesystem->get_contents( $html_file );

                        // The base URL for the images folder
                        $base_url = $upload_dir['baseurl'] . '/tbp-templates/' . $template_id . '/';
                        if ( strpos( $html_file, $template_id . '/email.html' ) === false ) {
                            // Extracted into a subfolder
                            $subfolder = basename( dirname( $html_file ) );
                            $base_url .= $subfolder . '/';
                        }
                        
                        $images_url = $base_url . 'images/';

                        // Replace relative image paths (Canva uses src="images/... " or background="images/..." )
                        $html_content = preg_replace( '/(src|background)=["\']images\//i', '$1="' . $images_url, $html_content );

                        // Save to database
                        $templates = get_option( 'tbp_marketing_templates', array() );
                        $templates[ $template_id ] = array(
                            'name' => $template_name,
                            'html' => $html_content,
                            'date' => current_time( 'mysql' )
                        );
                        update_option( 'tbp_marketing_templates', $templates );

                        $message = '¡Plantilla subida y procesada con éxito!';
                        $message_type = 'success';
                    } else {
                        $message = 'No se encontró el archivo email.html en el ZIP. Asegúrate de exportarlo correctamente desde Canva.';
                        $message_type = 'error';
                    }
                }
            }
        }
    }

    $templates = get_option( 'tbp_marketing_templates', array() );
    ?>
    <div class="wrap">
        <h1><?php _e( 'Plantillas de Marketing (Canva)', 'tbp-actividades' ); ?></h1>
        <p><?php _e( 'Sube los archivos <b>.zip</b> exportados de Canva para usarlos en tus correos masivos. El sistema adaptará las imágenes automáticamente.', 'tbp-actividades' ); ?></p>

        <?php if ( ! empty( $message ) ) : ?>
            <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
                <p><strong><?php echo esc_html( $message ); ?></strong></p>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 20px; align-items: flex-start; margin-top:20px;">
            <!-- Upload Form -->
            <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:4px; max-width:400px; width:100%; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;"><?php _e( 'Subir Nueva Plantilla', 'tbp-actividades' ); ?></h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'tbp_upload_template_action', 'tbp_template_nonce' ); ?>
                    
                    <p>
                        <label for="template_name"><strong>Nombre de la Plantilla:</strong></label><br>
                        <input type="text" name="template_name" id="template_name" class="regular-text" style="width:100%;" required placeholder="Ej: Invitación Graduación">
                    </p>
                    
                    <p>
                        <label for="template_zip"><strong>Archivo .ZIP de Canva:</strong></label><br>
                        <input type="file" name="template_zip" id="template_zip" accept=".zip" required style="margin-top:5px;">
                        <small style="display:block; color:#666; margin-top:5px;">Asegúrate de exportar desde Canva como "HTML".</small>
                    </p>
                    
                    <p style="margin-top:20px;">
                        <input type="submit" name="tbp_upload_template" class="button button-primary" value="Subir y Procesar Plantilla">
                    </p>
                </form>
            </div>

            <!-- List Templates -->
            <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:4px; flex-grow:1; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;"><?php _e( 'Plantillas Disponibles', 'tbp-actividades' ); ?></h2>
                
                <?php if ( empty( $templates ) ) : ?>
                    <p style="color:#666; font-style:italic;">No hay plantillas subidas todavía.</p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Fecha Subida</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $templates as $id => $data ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $data['name'] ); ?></strong></td>
                                    <td><?php echo esc_html( date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($data['date']) ) ); ?></td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=tbp-actividades-plantillas&delete_template=' . $id), 'delete_template_' . $id ); ?>" class="button button-small" style="color:#a00;" onclick="return confirm('¿Borrar esta plantilla permanentemente?');">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="description">Estas plantillas aparecerán en el "Centro de Mensajes Masivos" de los reportes.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
