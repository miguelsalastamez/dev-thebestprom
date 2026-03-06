<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('Impresión de QRs por lotes', 'exposcan-qr'); ?></title>
    <style>
        @media print {
            @page {
                size: 62mm 80mm; /* Tamaño actualizado para etiquetas 62mm x 80mm */
                margin: 3mm;     /* Margen obligatorio de 3mm */
            }
            
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
                width: 56mm;     /* Ancho disponible después de márgenes (62mm - 6mm) */
            }
            
            .no-print {
                display: none; /* Ocultar elementos en impresión */
            }
            
            .page-break {
                page-break-after: always; /* Forzar salto de página */
            }
            
            .qr-container {
                page-break-inside: avoid; /* Evitar que se divida un QR */
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .qr-image {
                width: 56mm !important;     /* Ancho fijo en milímetros */
                height: auto !important;
                max-width: none !important; /* Eliminar restricción de tamaño máximo */
                display: block !important;
                margin: 0 auto !important;
                transform: none !important; /* Prevenir escalado automático */
            }
            
            .qr-info {
                font-size: 16px !important; /* Reducir tamaño de texto */
                font-weight: bold !important;
                margin-top: 5px !important;
                text-align: center !important;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .qr-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        
        .qr-container {
            width: 250px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .qr-image {
            width: 100%;
            max-width: 200px;
            height: auto;
        }
        
        .qr-info {
            margin-top: 10px;
            font-size: 14px;
            font-weight: bold;
            word-wrap: break-word;
        }
        
        .print-button {
            background-color: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            margin: 20px auto;
            display: block;
            border-radius: 4px;
        }
        
        .print-button:hover {
            background-color: #005177;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin: 20px 0;
            color: #0073aa;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .print-options {
            max-width: 400px;
            margin: 0 auto 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .print-options label {
            display: block;
            margin-bottom: 10px;
        }
        
        .print-options input[type="radio"] {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <h1><?php _e('Impresión de QRs por lotes', 'exposcan-qr'); ?></h1>
        
        <div class="print-options">
            <label>
                <input type="radio" name="print-layout" value="grid" checked> 
                <?php _e('Vista de cuadrícula (múltiples QRs por página)', 'exposcan-qr'); ?>
            </label>
            <label>
                <input type="radio" name="print-layout" value="single"> 
                <?php _e('Vista individual (un QR por página)', 'exposcan-qr'); ?>
            </label>
        </div>
        
        <button class="print-button" onclick="window.print();"><?php _e('Imprimir todos', 'exposcan-qr'); ?></button>
        <a href="<?php echo admin_url('admin.php?page=exposcan-qr'); ?>" class="back-link"><?php _e('Volver a la lista', 'exposcan-qr'); ?></a>
    </div>
    
    <div class="qr-grid">
        <?php foreach ($registros as $index => $registro): ?>
            <div class="qr-container<?php echo ($index > 0 && isset($_GET['single_view'])) ? ' page-break' : ''; ?>">
                <img class="qr-image" src="<?php echo esc_url(EXPOSCAN_QR_UPLOAD_URL . '/' . $registro['qr_path']); ?>" alt="QR Code">
                <div class="qr-info"><?php echo esc_html($registro['nombre'] . ' ' . $registro['apellido']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        // Cambiar vista de impresión
        var layoutOptions = document.querySelectorAll('input[name="print-layout"]');
        var qrContainers = document.querySelectorAll('.qr-container');
        
        for (var i = 0; i < layoutOptions.length; i++) {
            layoutOptions[i].addEventListener('change', function() {
                if (this.value === 'single') {
                    // Un QR por página
                    for (var j = 0; j < qrContainers.length; j++) {
                        if (j > 0) {
                            qrContainers[j].classList.add('page-break');
                        }
                    }
                } else {
                    // Vista de cuadrícula
                    for (var j = 0; j < qrContainers.length; j++) {
                        qrContainers[j].classList.remove('page-break');
                    }
                }
            });
        }
    </script>
</body>
</html>