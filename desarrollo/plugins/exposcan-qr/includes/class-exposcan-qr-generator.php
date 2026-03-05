<?php
/**
 * Clase para generar códigos QR con soporte mejorado para impresión
 */
class ExpoScan_QR_Generator {
    
    /**
     * Opciones por defecto para los QR
     */
    private $default_options = array(
        'size' => 300,            // Tamaño en píxeles
        'margin' => 10,           // Margen
        'error_correction' => 'H', // Alta corrección de errores
        'foreground' => array(0, 0, 0), // Negro
        'background' => array(255, 255, 255), // Blanco
        'logo' => true,           // Incluir logo por defecto
        'logo_path' => 'logo-qr/logo-bookit.png', // Ruta relativa al logo
        'logo_height' => 80       // Altura del logo en píxeles
    );
    
    /**
     * Opciones por defecto para impresión
     */
    private $print_options = array(
        'width_mm' => 62,         // Ancho en milímetros
        'height_mm' => 90,        // Alto en milímetros
        'dpi' => 300,             // Resolución de impresión
        'logo_height_mm' => 15    // Altura del logo en milímetros
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        // No intentamos cargar la librería aquí, lo haremos al generar el QR
    }
    
    /**
     * Aviso de biblioteca faltante
     */
    public function missing_library_notice() {
        ?>
        <div class="error">
            <p><?php _e('ExpoScan QR requiere la biblioteca phpqrcode. Por favor, instálala mediante Composer o cópiala en la carpeta libs del plugin.', 'exposcan-qr'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Convertir milímetros a píxeles según DPI
     *
     * @param float $mm Medida en milímetros
     * @param int $dpi Resolución en puntos por pulgada
     * @return int Medida en píxeles
     */
    private function mm_to_pixels($mm, $dpi = 300) {
        // 1 pulgada = 25.4 mm
        return round(($mm / 25.4) * $dpi);
    }
    
    /**
     * Generar código QR optimizado para impresión
     *
     * @param string $data Datos a codificar en el QR
     * @param string $file_path Ruta donde guardar el archivo
     * @param array $print_opts Opciones de impresión
     * @param array $qr_opts Opciones adicionales para el QR
     * @return bool Éxito o fracaso
     */
    public function generate_for_print($data, $file_path, $print_opts = array(), $qr_opts = array()) {
        // Combinar opciones de impresión con valores predeterminados
        $print_options = array_merge($this->print_options, $print_opts);
        
        // Calcular dimensiones en píxeles según DPI
        $width_px = $this->mm_to_pixels($print_options['width_mm'], $print_options['dpi']);
        $height_px = $this->mm_to_pixels($print_options['height_mm'], $print_options['dpi']);
        $logo_height_px = $this->mm_to_pixels($print_options['logo_height_mm'], $print_options['dpi']);
        
        // Calcular tamaño del QR para que quepa en el ancho disponible con margen
        $qr_size = $width_px - 20; // 10px de margen a cada lado
        
        // Preparar opciones para el generador de QR
        $options = array_merge(
            $this->default_options,
            $qr_opts,
            array(
                'size' => $qr_size,
                'logo_height' => $logo_height_px
            )
        );
        
        // Generar el QR con las nuevas dimensiones
        return $this->generate($data, $file_path, '', $options, true, $height_px);
    }
    
    /**
     * Generar código QR con logo
     *
     * @param string $data Datos a codificar en el QR
     * @param string $file_path Ruta donde guardar el archivo
     * @param string $label Etiqueta (no se usa en esta versión)
     * @param array $options Opciones adicionales
     * @param bool $adjust_height Si se debe ajustar la altura total
     * @param int $total_height Altura total en píxeles (si $adjust_height es true)
     * @return bool Éxito o fracaso
     */
    public function generate($data, $file_path, $label = '', $options = array(), $adjust_height = false, $total_height = 0) {
        // Combinar opciones con valores predeterminados
        $options = array_merge($this->default_options, $options);
        
        // Cargar manualmente la biblioteca phpqrcode
        $phpqrcode_lib = EXPOSCAN_QR_PLUGIN_DIR . 'libs/phpqrcode/qrlib.php';
        
        if (file_exists($phpqrcode_lib)) {
            require_once $phpqrcode_lib;
        } else {
            error_log('ExpoScan QR: No se encuentra la biblioteca phpqrcode en: ' . $phpqrcode_lib);
            return false;
        }
        
        // Verificar que la clase QRcode esté disponible
        if (!class_exists('QRcode')) {
            error_log('ExpoScan QR: La clase QRcode no está disponible después de incluir la biblioteca.');
            return false;
        }
        
        try {
            // Crear un archivo temporal para el QR
            $temp_qr_file = $file_path . '.temp.png';
            
            // Generar QR en el archivo temporal
            QRcode::png(
                $data,
                $temp_qr_file,
                $options['error_correction'],
                $options['size'] / 25, // El tamaño se divide por 25 para adaptarse a phpqrcode
                $options['margin']
            );
            
            // Verificar si se debe agregar el logo
            if ($options['logo'] && function_exists('imagecreatefrompng')) {
                // Obtener la ruta absoluta del logo
                $logo_path = EXPOSCAN_QR_PLUGIN_DIR . $options['logo_path'];
                
                // Verificar si existe el logo
                if (!file_exists($logo_path)) {
                    error_log('ExpoScan QR: No se encuentra el logo en: ' . $logo_path);
                    // Si no hay logo, usar solo el QR
                    rename($temp_qr_file, $file_path);
                    return file_exists($file_path);
                }
                
                // Cargar el QR generado
                $qr_image = imagecreatefrompng($temp_qr_file);
                if (!$qr_image) {
                    error_log('ExpoScan QR: No se pudo cargar la imagen QR temporal.');
                    return false;
                }
                
                // Cargar el logo
                $logo_image = imagecreatefrompng($logo_path);
                if (!$logo_image) {
                    error_log('ExpoScan QR: No se pudo cargar el logo.');
                    imagedestroy($qr_image);
                    rename($temp_qr_file, $file_path);
                    return file_exists($file_path);
                }
                
                // Obtener dimensiones
                $qr_width = imagesx($qr_image);
                $qr_height = imagesy($qr_image);
                $logo_width = imagesx($logo_image);
                $logo_height = imagesy($logo_image);
                
                // Calcular proporción para redimensionar el logo si es necesario
                $new_logo_height = $options['logo_height'];
                $new_logo_width = ($logo_width / $logo_height) * $new_logo_height;
                
                // Espacio entre logo y QR
                $spacing = 10;
                
                // Determinar la altura final de la imagen
                $final_height = $adjust_height && $total_height > 0 
                    ? $total_height 
                    : $qr_height + $new_logo_height + $spacing;
                
                // Crear la imagen final con las dimensiones adecuadas
                $final_image = imagecreatetruecolor($qr_width, $final_height);
                
                // Llenar con fondo blanco
                $white = imagecolorallocate($final_image, 255, 255, 255);
                imagefill($final_image, 0, 0, $white);
                
                // Copiar el logo redimensionado en la parte superior (centrado)
                $logo_x = ($qr_width - $new_logo_width) / 2;
                imagecopyresampled(
                    $final_image,      // Imagen destino
                    $logo_image,       // Imagen origen (logo)
                    $logo_x,           // X destino (centrado)
                    0,                 // Y destino (arriba)
                    0,                 // X origen
                    0,                 // Y origen
                    $new_logo_width,   // Ancho destino
                    $new_logo_height,  // Alto destino
                    $logo_width,       // Ancho origen
                    $logo_height       // Alto origen
                );
                
                // Copiar el QR en la parte inferior
                imagecopy(
                    $final_image,      // Imagen destino
                    $qr_image,         // Imagen origen (QR)
                    0,                 // X destino
                    $new_logo_height + $spacing, // Y destino (debajo del logo)
                    0,                 // X origen
                    0,                 // Y origen
                    $qr_width,         // Ancho
                    $qr_height         // Alto
                );
                
                // Agregar metadatos de DPI para impresión (72 DPI es el valor estándar)
                $res_x = $res_y = 72; // DPI por defecto
                
                if (function_exists('imageresolution')) {
                    // Establecer resolución si la función está disponible (PHP 7.2+)
                    imageresolution($final_image, $options['dpi'] ?? 300, $options['dpi'] ?? 300);
                    $res_x = $res_y = $options['dpi'] ?? 300;
                }
                
                // Guardar la imagen final con información de resolución
                $png_chunks = array(
                    // pHYs chunk for DPI - 3 bytes/pixels per meter + 1 byte unit specifier
                    'pHYs' => pack('NNC', round($res_x * 39.37), round($res_y * 39.37), 1)
                );
                
                // Guardar la imagen final
                imagepng($final_image, $file_path, 9); // Máxima compresión (0-9)
                
                // Liberar memoria
                imagedestroy($qr_image);
                imagedestroy($logo_image);
                imagedestroy($final_image);
                
                // Eliminar archivo temporal
                if (file_exists($temp_qr_file)) {
                    unlink($temp_qr_file);
                }
                
                return file_exists($file_path);
            } else {
                // Si no se debe agregar logo o no hay soporte GD, usar solo el QR
                rename($temp_qr_file, $file_path);
                return file_exists($file_path);
            }
            
        } catch (Exception $e) {
            // Registrar error
            error_log('Error al generar QR: ' . $e->getMessage());
            return false;
        }
    }
}