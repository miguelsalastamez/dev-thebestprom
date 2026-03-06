<?php
/**
 * Clase para gestionar la base de datos
 */
class ExpoScan_Database {
    
    /**
     * Nombre de la tabla de registros
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'exposcan_registros';
    }
    
    /**
     * Crear tablas en la base de datos
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            empresa VARCHAR(100) NOT NULL,
            nombre VARCHAR(50) NOT NULL,
            apellido VARCHAR(50) NOT NULL,
            telefono VARCHAR(20) NOT NULL,
            email VARCHAR(100) NOT NULL,
            web VARCHAR(100),
            requerimientos TEXT,
            qr_path VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Insertar un nuevo registro
     */
    public function insert_registro($data) {
        global $wpdb;
        
        $now = current_time('mysql');
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'empresa' => sanitize_text_field($data['empresa']),
                'nombre' => sanitize_text_field($data['nombre']),
                'apellido' => sanitize_text_field($data['apellido']),
                'telefono' => sanitize_text_field($data['telefono']),
                'email' => sanitize_email($data['email']),
                'web' => esc_url_raw($data['web']),
                'requerimientos' => sanitize_textarea_field($data['requerimientos']),
                'qr_path' => $data['qr_path'],
                'created_at' => $now
            ),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Obtener un registro por ID
     */
    public function get_registro($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id),
            ARRAY_A
        );
    }
    
    /**
     * Obtener todos los registros
     */
    public function get_registros($per_page = 10, $page_number = 1, $orderby = 'id', $order = 'DESC', $search = '') {
        global $wpdb;
        
        $sql = "SELECT * FROM $this->table_name";
        
        // Búsqueda
        if (!empty($search)) {
            $sql .= $wpdb->prepare(
                " WHERE empresa LIKE '%%%s%%' OR nombre LIKE '%%%s%%' OR apellido LIKE '%%%s%%' OR email LIKE '%%%s%%'",
                $search, $search, $search, $search
            );
        }
        
        // Ordenamiento
        $sql .= " ORDER BY $orderby $order";
        
        // Paginación
        $sql .= " LIMIT %d OFFSET %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($sql, $per_page, ($page_number - 1) * $per_page),
            ARRAY_A
        );
    }
    
    /**
     * Contar total de registros
     */
    public function count_registros($search = '') {
        global $wpdb;
        
        $sql = "SELECT COUNT(*) FROM $this->table_name";
        
        // Búsqueda
        if (!empty($search)) {
            $sql .= $wpdb->prepare(
                " WHERE empresa LIKE '%%%s%%' OR nombre LIKE '%%%s%%' OR apellido LIKE '%%%s%%' OR email LIKE '%%%s%%'",
                $search, $search, $search, $search
            );
        }
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Actualizar un registro
     */
    public function update_registro($id, $data) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'empresa' => sanitize_text_field($data['empresa']),
                'nombre' => sanitize_text_field($data['nombre']),
                'apellido' => sanitize_text_field($data['apellido']),
                'telefono' => sanitize_text_field($data['telefono']),
                'email' => sanitize_email($data['email']),
                'web' => esc_url_raw($data['web']),
                'requerimientos' => sanitize_textarea_field($data['requerimientos']),
                'qr_path' => $data['qr_path']
            ),
            array('id' => $id),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            ),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Eliminar un registro
     */
    public function delete_registro($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }
	
	/**
 * Verificar si ya existe un registro con el mismo email o teléfono
 */
public function check_duplicate($email, $telefono) {
    global $wpdb;
    
    $sql = $wpdb->prepare(
        "SELECT id FROM $this->table_name WHERE email = %s OR telefono = %s LIMIT 1",
        $email, $telefono
    );
    
    return $wpdb->get_var($sql);
}
	
}