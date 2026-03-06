<?php
/**
 * Clase para tabla de listado de registros
 */

// Cargar WP_List_Table si no está disponible
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class ExpoScan_List_Table extends WP_List_Table {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'registro',
            'plural'   => 'registros',
            'ajax'     => false
        ));
    }
    
    /**
     * Columnas por defecto
     */
    public function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'id'        => __('ID', 'exposcan-qr'),
            'empresa'   => __('Empresa', 'exposcan-qr'),
            'nombre'    => __('Nombre', 'exposcan-qr'),
            'apellido'  => __('Apellido', 'exposcan-qr'),
            'telefono'  => __('Teléfono', 'exposcan-qr'),
            'email'     => __('Email', 'exposcan-qr'),
            'fecha'     => __('Fecha', 'exposcan-qr'),
            'qr'        => __('QR', 'exposcan-qr'),
            'acciones'  => __('Acciones', 'exposcan-qr')
        );
        
        return $columns;
    }
    
    /**
     * Columnas que se pueden ordenar
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'id'       => array('id', true),
            'empresa'  => array('empresa', false),
            'nombre'   => array('nombre', false),
            'apellido' => array('apellido', false),
            'fecha'    => array('created_at', true)
        );
        
        return $sortable_columns;
    }
    
    /**
     * Obtener acciones en masa
     */
    protected function get_bulk_actions() {
        $actions = array(
            'print' => __('Imprimir', 'exposcan-qr'),
            'delete' => __('Eliminar', 'exposcan-qr')
        );
        
        return $actions;
    }
    
    /**
     * Columna checkbox para acciones en masa
     */
    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="registro_ids[]" value="%s" />',
            $item['id']
        );
    }
    
    /**
     * Columna ID
     */
    public function column_id($item) {
        return $item['id'];
    }
    
    /**
     * Columna Empresa
     */
    public function column_empresa($item) {
        return esc_html($item['empresa']);
    }
    
    /**
     * Columna Nombre
     */
    public function column_nombre($item) {
        return esc_html($item['nombre']);
    }
    
    /**
     * Columna Apellido
     */
    public function column_apellido($item) {
        return esc_html($item['apellido']);
    }
    
    /**
     * Columna Teléfono
     */
    public function column_telefono($item) {
        return esc_html($item['telefono']);
    }
    
    /**
     * Columna Email
     */
    public function column_email($item) {
        return '<a href="mailto:' . esc_attr($item['email']) . '">' . esc_html($item['email']) . '</a>';
    }
    
    /**
     * Columna Fecha
     */
    public function column_fecha($item) {
        $date = new DateTime($item['created_at']);
        return $date->format('d/m/Y H:i');
    }
    
    /**
     * Columna QR
     */
    public function column_qr($item) {
        $qr_url = EXPOSCAN_QR_UPLOAD_URL . '/' . $item['qr_path'];
        return '<a href="' . esc_url($qr_url) . '" target="_blank"><img src="' . esc_url($qr_url) . '" width="50" height="50" /></a>';
    }
    
    /**
     * Columna Acciones
     */
    public function column_acciones($item) {
        $print_url = add_query_arg(
            array(
                'action' => 'exposcan_print_qr',
                'id' => $item['id'],
                'nonce' => wp_create_nonce('exposcan_print_qr_' . $item['id'])
            ),
            admin_url('admin-ajax.php')
        );
        
        $delete_url = add_query_arg(
            array(
                'page' => 'exposcan-qr',
                'action' => 'delete',
                'id' => $item['id'],
                'nonce' => wp_create_nonce('exposcan_delete_registro_' . $item['id'])
            ),
            admin_url('admin.php')
        );
        
        $actions = array(
            'print' => '<a href="' . esc_url($print_url) . '" target="_blank">' . __('Imprimir', 'exposcan-qr') . '</a>',
            'delete' => '<a href="' . esc_url($delete_url) . '" class="exposcan-delete">' . __('Eliminar', 'exposcan-qr') . '</a>'
        );
        
        return $this->row_actions($actions);
    }
    
    /**
     * Procesar datos para la tabla
     */
    public function prepare_items() {
        // Opciones de paginación
        $per_page = 10;
        $current_page = $this->get_pagenum();
        
        // Columnas
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Procesar acciones en masa
        $this->process_bulk_action();
        
        // Parámetros de búsqueda y ordenamiento
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'id';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        
        // Obtener registros
        $database = new ExpoScan_Database();
        $data = $database->get_registros($per_page, $current_page, $orderby, $order, $search);
        $total_items = $database->count_registros($search);
        
        // Asignar registros a la tabla
        $this->items = $data;
        
        // Configurar paginación
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
    
    /**
     * Procesar acciones en masa
     */
    public function process_bulk_action() {
        // Verificar acción
        $action = $this->current_action();
        
        if (empty($action)) {
            return;
        }
        
        // Verificar IDs seleccionados
        $ids = isset($_REQUEST['registro_ids']) ? $_REQUEST['registro_ids'] : array();
        
        if (empty($ids)) {
            return;
        }
        
        // Sanitizar IDs
        $ids = array_map('intval', $ids);
        
        // Procesar acción
        switch ($action) {
            case 'delete':
                // Verificar nonce
                check_admin_referer('bulk-' . $this->_args['plural']);
                
                // Eliminar registros
                $database = new ExpoScan_Database();
                foreach ($ids as $id) {
                    $registro = $database->get_registro($id);
                    if ($registro) {
                        // Eliminar archivo QR
                        $qr_path = EXPOSCAN_QR_UPLOAD_DIR . '/' . $registro['qr_path'];
                        if (file_exists($qr_path)) {
                            unlink($qr_path);
                        }
                        
                        // Eliminar registro
                        $database->delete_registro($id);
                    }
                }
                
                // Redirigir
                wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=exposcan-qr')));
                exit;
                
            case 'print':
                // Verificar nonce
                check_admin_referer('bulk-' . $this->_args['plural']);
                
                // Generar formulario para imprimir
                ?>
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="exposcan-print-form">
                    <input type="hidden" name="action" value="exposcan_print_batch">
                    <?php wp_nonce_field('exposcan_print_batch', 'exposcan_nonce'); ?>
                    <?php foreach ($ids as $id): ?>
                        <input type="hidden" name="registro_ids[]" value="<?php echo esc_attr($id); ?>">
                    <?php endforeach; ?>
                </form>
                <script>
                    document.getElementById('exposcan-print-form').submit();
                </script>
                <?php
                exit;
        }
    }
    
    /**
     * Mensaje para cuando no hay registros
     */
    public function no_items() {
        _e('No se encontraron registros.', 'exposcan-qr');
    }
    
    /**
     * Filtros adicionales encima de la tabla
     */
    public function extra_tablenav($which) {
        if ($which === 'top') {
            ?>
            <div class="alignleft actions">
                <a href="<?php echo admin_url('admin-post.php?action=exposcan_export_csv&nonce=' . wp_create_nonce('exposcan_export_csv')); ?>" class="button">
                    <?php _e('Exportar CSV', 'exposcan-qr'); ?>
                </a>
            </div>
            <?php
        }
    }
}