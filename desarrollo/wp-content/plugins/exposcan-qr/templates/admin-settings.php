<div class="wrap">
    <h1><?php _e('Ajustes de ExpoScan QR', 'exposcan-qr'); ?></h1>
    
    <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Ajustes guardados correctamente.', 'exposcan-qr'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="exposcan-admin-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#sheets-config" class="nav-tab nav-tab-active"><?php _e('Google Sheets', 'exposcan-qr'); ?></a>
            <a href="#shortcode-info" class="nav-tab"><?php _e('Shortcode', 'exposcan-qr'); ?></a>
            <a href="#about" class="nav-tab"><?php _e('Acerca de', 'exposcan-qr'); ?></a>
        </nav>
        
        <div class="tab-content">
            <!-- Configuración de Google Sheets -->
            <div id="sheets-config" class="tab-pane active">
                <form method="post" action="options.php">
                    <?php settings_fields('exposcan_options'); ?>
                    <?php do_settings_sections('exposcan-qr-settings'); ?>
                    
                    <h3><?php _e('Guía de configuración', 'exposcan-qr'); ?></h3>
                    <ol>
                        <li><?php _e('Crea un proyecto en <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>', 'exposcan-qr'); ?></li>
                        <li><?php _e('Habilita la API de Google Sheets', 'exposcan-qr'); ?></li>
                        <li><?php _e('Crea una cuenta de servicio y descarga el archivo JSON de credenciales', 'exposcan-qr'); ?></li>
                        <li><?php _e('Sube el archivo de credenciales a un directorio seguro en tu servidor', 'exposcan-qr'); ?></li>
                        <li><?php _e('Crea una hoja de cálculo en Google Sheets y compártela con la dirección de email de la cuenta de servicio', 'exposcan-qr'); ?></li>
                        <li><?php _e('Copia el ID de la hoja de cálculo (de la URL) y configúralo aquí', 'exposcan-qr'); ?></li>
                    </ol>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
            
            <!-- Información de Shortcode -->
            <div id="shortcode-info" class="tab-pane">
                <h3><?php _e('Uso del Shortcode', 'exposcan-qr'); ?></h3>
                <p><?php _e('Para mostrar el formulario de registro en cualquier página o entrada, utiliza el siguiente shortcode:', 'exposcan-qr'); ?></p>
                <code>[exposcan_formulario]</code>
                
                <h3><?php _e('Personalización del formulario', 'exposcan-qr'); ?></h3>
                <p><?php _e('Puedes personalizar la apariencia del formulario mediante CSS. Las clases principales son:', 'exposcan-qr'); ?></p>
                <ul>
                    <li><code>.exposcan-form-container</code> - <?php _e('Contenedor principal', 'exposcan-qr'); ?></li>
                    <li><code>.exposcan-form</code> - <?php _e('Formulario', 'exposcan-qr'); ?></li>
                    <li><code>.exposcan-form-row</code> - <?php _e('Fila del formulario', 'exposcan-qr'); ?></li>
                    <li><code>.exposcan-form-group</code> - <?php _e('Grupo de campo', 'exposcan-qr'); ?></li>
                    <li><code>.exposcan-submit-btn</code> - <?php _e('Botón de envío', 'exposcan-qr'); ?></li>
                </ul>
                
                <h3><?php _e('Ejemplo de CSS personalizado', 'exposcan-qr'); ?></h3>
                <pre>
.exposcan-form-container {
    max-width: 800px;
    margin: 0 auto;
}

.exposcan-submit-btn {
    background-color: #0073aa;
    color: white;
}
                </pre>
            </div>
            
            <!-- Acerca de -->
            <div id="about" class="tab-pane">
                <h3><?php _e('ExpoScan QR', 'exposcan-qr'); ?></h3>
                <p><?php _e('Versión:', 'exposcan-qr'); ?> <?php echo EXPOSCAN_QR_VERSION; ?></p>
                <p><?php _e('ExpoScan QR es un plugin para WordPress que permite crear un sistema de registro para exposiciones con generación de códigos QR que enlazan con WhatsApp.', 'exposcan-qr'); ?></p>
                
                <h3><?php _e('Características', 'exposcan-qr'); ?></h3>
                <ul>
                    <li><?php _e('Formulario de registro personalizable', 'exposcan-qr'); ?></li>
                    <li><?php _e('Generación automática de códigos QR para WhatsApp', 'exposcan-qr'); ?></li>
                    <li><?php _e('Integración con Google Sheets', 'exposcan-qr'); ?></li>
                    <li><?php _e('Impresión individual y por lotes de códigos QR', 'exposcan-qr'); ?></li>
                    <li><?php _e('Gestión completa de registros', 'exposcan-qr'); ?></li>
                </ul>
                
                <h3><?php _e('Soporte', 'exposcan-qr'); ?></h3>
                <p><?php _e('Para soporte, contacta con el desarrollador.', 'exposcan-qr'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tabs de navegación
    $('.exposcan-admin-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Obtener el target
        var target = $(this).attr('href');
        
        // Activar tab
        $('.exposcan-admin-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Mostrar contenido
        $('.tab-pane').removeClass('active');
        $(target).addClass('active');
    });
});
</script>

<style>
.exposcan-admin-tabs .tab-content {
    margin-top: 20px;
}

.exposcan-admin-tabs .tab-pane {
    display: none;
}

.exposcan-admin-tabs .tab-pane.active {
    display: block;
}

.exposcan-admin-tabs pre {
    background: #f5f5f5;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 3px;
    overflow: auto;
}

.exposcan-admin-tabs ol,
.exposcan-admin-tabs ul {
    margin-left: 20px;
}
</style>