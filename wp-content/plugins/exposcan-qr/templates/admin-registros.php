<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Registros de ExpoScan QR', 'exposcan-qr'); ?></h1>
    
    <?php
    // Mensajes
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'deleted':
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Registros eliminados correctamente.', 'exposcan-qr') . '</p></div>';
                break;
        }
    }
    
    // Errores
    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 'no-selection':
                echo '<div class="notice notice-error is-dismissible"><p>' . __('No has seleccionado ningún registro.', 'exposcan-qr') . '</p></div>';
                break;
            case 'no-records':
                echo '<div class="notice notice-error is-dismissible"><p>' . __('No se encontraron los registros seleccionados.', 'exposcan-qr') . '</p></div>';
                break;
        }
    }
    ?>
    
    <form id="registros-filter" method="get">
        <input type="hidden" name="page" value="exposcan-qr" />
        
        <?php
        // Mostrar cuadro de búsqueda
        $registros_table->search_box(__('Buscar registros', 'exposcan-qr'), 'exposcan-search');
        
        // Mostrar tabla
        $registros_table->display();
        ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Confirmación para eliminar
    $('.exposcan-delete').on('click', function(e) {
        if (!confirm('<?php _e('¿Estás seguro de querer eliminar este registro?', 'exposcan-qr'); ?>')) {
            e.preventDefault();
        }
    });
});
</script>

<style>
/* Estilos para la tabla de registros */
.wp-list-table .column-id {
    width: 5%;
}

.wp-list-table .column-empresa {
    width: 15%;
}

.wp-list-table .column-nombre,
.wp-list-table .column-apellido {
    width: 10%;
}

.wp-list-table .column-telefono,
.wp-list-table .column-email {
    width: 15%;
}

.wp-list-table .column-fecha {
    width: 10%;
}

.wp-list-table .column-qr {
    width: 8%;
    text-align: center;
}

.wp-list-table .column-acciones {
    width: 12%;
}

.wp-list-table img {
    border: 1px solid #ddd;
    padding: 2px;
    background: #fff;
}
</style>