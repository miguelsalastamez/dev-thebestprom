<?php
/**
 * Clase para integración con Google Sheets
 */
class ExpoScan_Sheets {
    
    /**
     * ID de la hoja de cálculo
     */
    private $spreadsheet_id;
    
    /**
     * Nombre de la hoja
     */
    private $sheet_name;
    
    /**
     * Ruta al archivo de credenciales
     */
    private $credentials_path;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Obtener configuración
        $this->spreadsheet_id = get_option('exposcan_spreadsheet_id', '');
        $this->sheet_name = get_option('exposcan_sheet_name', 'Registros');
        $this->credentials_path = get_option('exposcan_credentials_path', '');
        
        // Verificar que la librería Google API esté disponible
        if (!class_exists('Google_Client')) {
            add_action('admin_notices', array($this, 'missing_library_notice'));
        }
    }
    
    /**
     * Aviso de biblioteca faltante
     */
    public function missing_library_notice() {
        ?>
        <div class="error">
            <p><?php _e('ExpoScan QR requiere la biblioteca Google API Client. Por favor, instálala mediante Composer.', 'exposcan-qr'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Agregar fila a Google Sheets
     */
    public function add_row($data) {
        // Verificar configuración
        if (empty($this->spreadsheet_id) || empty($this->credentials_path) || !file_exists($this->credentials_path)) {
            error_log('ExpoScan QR: No se pudo sincronizar con Google Sheets. Configuración incompleta.');
            return false;
        }
        
        // Verificar que la clase exista
        if (!class_exists('Google_Client')) {
            error_log('ExpoScan QR: La biblioteca Google API Client no está disponible.');
            return false;
        }
        
        try {
            // Inicializar cliente
            $client = new Google_Client();
            $client->setApplicationName('ExpoScan QR');
            $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
            $client->setAuthConfig($this->credentials_path);
            $client->setAccessType('offline');
            
            // Inicializar servicio de Sheets
            $service = new Google_Service_Sheets($client);
            
           // Preparar valores para la fila
$values = [
    [
        $data['empresa'],
        $data['nombre'],
        $data['apellido'],
        $data['telefono'],
        $data['email'],
        $data['web'],
        $data['requerimientos'],
        EXPOSCAN_QR_UPLOAD_URL . '/' . $data['qr_path'], // URL directa al PNG
        date('Y-m-d H:i:s')
    ]
];
            
            // Rango donde agregar datos (agrega al final)
            $range = $this->sheet_name;
            
            // Configurar cuerpo de la solicitud
            $body = new Google_Service_Sheets_ValueRange([
                'values' => $values
            ]);
            
            // Ejecutar solicitud para agregar datos
            $result = $service->spreadsheets_values->append(
                $this->spreadsheet_id,
                $range,
                $body,
                ['valueInputOption' => 'RAW']
            );
            
            // Verificar resultado
            return isset($result->updates->updatedRows) && $result->updates->updatedRows == 1;
            
        } catch (Exception $e) {
            // Registrar error
            error_log('ExpoScan QR - Error al sincronizar con Google Sheets: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Configurar la integración con Google Sheets
     */
    public function setup($spreadsheet_id, $sheet_name, $credentials_path) {
        update_option('exposcan_spreadsheet_id', $spreadsheet_id);
        update_option('exposcan_sheet_name', $sheet_name);
        update_option('exposcan_credentials_path', $credentials_path);
        
        $this->spreadsheet_id = $spreadsheet_id;
        $this->sheet_name = $sheet_name;
        $this->credentials_path = $credentials_path;
        
        return true;
    }
    
    /**
     * Verificar conexión con Google Sheets
     */
    public function test_connection() {
        // Verificar configuración
        if (empty($this->spreadsheet_id) || empty($this->credentials_path) || !file_exists($this->credentials_path)) {
            return false;
        }
        
        try {
            // Inicializar cliente
            $client = new Google_Client();
            $client->setApplicationName('ExpoScan QR');
            $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
            $client->setAuthConfig($this->credentials_path);
            $client->setAccessType('offline');
            
            // Inicializar servicio de Sheets
            $service = new Google_Service_Sheets($client);
            
            // Intentar obtener metadata de la hoja
            $spreadsheet = $service->spreadsheets->get($this->spreadsheet_id);
            
            return !empty($spreadsheet->getSpreadsheetId());
            
        } catch (Exception $e) {
            // Registrar error
            error_log('ExpoScan QR - Error al probar conexión con Google Sheets: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Inicializar hoja con encabezados
     */
    public function initialize_sheet() {
        // Verificar configuración
        if (empty($this->spreadsheet_id) || empty($this->credentials_path) || !file_exists($this->credentials_path)) {
            return false;
        }
        
        try {
            // Inicializar cliente
            $client = new Google_Client();
            $client->setApplicationName('ExpoScan QR');
            $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
            $client->setAuthConfig($this->credentials_path);
            $client->setAccessType('offline');
            
            // Inicializar servicio de Sheets
            $service = new Google_Service_Sheets($client);
            
            // Encabezados
            $headers = [
                ['Empresa', 'Nombre', 'Apellido', 'Teléfono', 'Email', 'Web', 'Requerimientos', 'URL QR', 'Fecha Registro']
            ];
            
            // Rango para encabezados
            $range = $this->sheet_name . '!A1:I1';
            
            // Configurar cuerpo de la solicitud
            $body = new Google_Service_Sheets_ValueRange([
                'values' => $headers
            ]);
            
            // Ejecutar solicitud para agregar encabezados
            $result = $service->spreadsheets_values->update(
                $this->spreadsheet_id,
                $range,
                $body,
                ['valueInputOption' => 'RAW']
            );
            
            // Aplicar formato a encabezados (negrita)
            $requests = [
                new Google_Service_Sheets_Request([
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => 0,
                            'startRowIndex' => 0,
                            'endRowIndex' => 1,
                            'startColumnIndex' => 0,
                            'endColumnIndex' => 9
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'textFormat' => [
                                    'bold' => true
                                ]
                            ]
                        ],
                        'fields' => 'userEnteredFormat.textFormat.bold'
                    ]
                ])
            ];
            
            $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]);
            
            $service->spreadsheets->batchUpdate(
                $this->spreadsheet_id,
                $batchUpdateRequest
            );
            
            return true;
            
        } catch (Exception $e) {
            // Registrar error
            error_log('ExpoScan QR - Error al inicializar hoja: ' . $e->getMessage());
            return false;
        }
    }
}