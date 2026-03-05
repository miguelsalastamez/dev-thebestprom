<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($registro['nombre'] . ' ' . $registro['apellido']); ?> - QR</title>
    <style>
    @media print {
    @page {
        size: 62mm 80mm; /* Tamaño exacto de la etiqueta */
        margin: 3mm;     /* Margen obligatorio de 3mm */
    }
    
    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        width: 56mm;     /* Ancho disponible después de márgenes */
    }
    
    .qr-container {
        width: 100%;
        text-align: center;
        padding: 0;
        margin: 0;
    }
    
    .qr-image {
        width: 56mm;     /* Ancho fijo en milímetros */
        height: auto;
        max-width: none; /* Eliminar restricción de tamaño máximo */
        display: block;
        margin: 0 auto;
        transform: none !important; /* Prevenir escalado automático */
    }
    
    .qr-info {
        font-size: 16px !important; /* Reducir tamaño de texto */
        font-weight: bold;
        margin-top: 5px;
        text-align: center;
    }
    
    .print-button, .back-link {
        display: none !important;
    }
}

    /* Estilos para visualización en pantalla */
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
        text-align: center;
    }

    .qr-container {
        max-width: 300px;
        margin: 0 auto;
        padding: 20px 0;
    }

    .qr-image {
        width: 75%;
        max-width: 250px;
        height: auto;
        display: block;
        margin: 0 auto;
    }

    .qr-info {
        font-size: 24px;
        font-weight: bold;
        margin-top: 15px;
    }

    .print-button {
        background: #0073aa;
        color: white;
        border: none;
        padding: 8px 16px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 20px;
    }

    .back-link {
        display: block;
        margin-top: 15px;
        color: #0073aa;
        text-decoration: none;
    }
    </style>
</head>
<body>
    <div class="qr-container">
        <img class="qr-image" src="<?php echo esc_url(EXPOSCAN_QR_UPLOAD_URL . '/' . $registro['qr_path']); ?>" alt="QR Code">
        <div class="qr-info"><?php echo esc_html($registro['nombre'] . ' ' . $registro['apellido']); ?></div>
    </div>
    
    <button class="print-button" onclick="window.print();"><?php _e('Imprimir', 'exposcan-qr'); ?></button>
    
    <?php if (current_user_can('manage_options')): ?>
        <a href="<?php echo admin_url('admin.php?page=exposcan-qr'); ?>" class="back-link"><?php _e('Volver a la lista', 'exposcan-qr'); ?></a>
    <?php endif; ?>
    
    <script>
    <?php if (isset($_GET['auto_print']) && $_GET['auto_print'] == '1'): ?>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 300);
    };
    <?php endif; ?>
    </script>
</body>
</html>