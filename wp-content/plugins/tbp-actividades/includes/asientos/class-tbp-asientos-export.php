<?php
/**
 * Exportación: Módulo de Asignación de Asientos
 *
 * Funciones para exportar la lista de asignaciones a CSV
 * y generar un reporte de mesas.
 *
 * @package TBP_Actividades
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registra los endpoints o hooks para descargar los reportes.
 */
add_action( 'admin_init', 'tbp_asientos_handle_exports' );
function tbp_asientos_handle_exports() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    if ( isset( $_GET['tbp_export_asientos'] ) && isset( $_GET['config_id'] ) ) {
        $config_id = (int) $_GET['config_id'];
        $type      = sanitize_key( $_GET['tbp_export_asientos'] );

        if ( $type === 'mesas' ) {
            tbp_asientos_export_csv_mesas( $config_id );
        } elseif ( $type === 'asistentes' ) {
            tbp_asientos_export_csv_asistentes( $config_id );
        }
    }
}

/**
 * Exporta CSV agrupado por mesa
 */
function tbp_asientos_export_csv_mesas( $config_id ) {
    $config = tbp_asientos_get_config( $config_id );
    if ( ! $config ) wp_die( 'Config no encontrada.' );

    $asignaciones = tbp_asientos_get_assignments( $config_id );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=reporte_mesas_' . sanitize_file_name( $config->nombre ) . '_' . date( 'Y-m-d' ) . '.csv' );

    $output = fopen( 'php://output', 'w' );
    // BOM for Excel UTF-8
    fputs( $output, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ) );

    fputcsv( $output, array( 'Zona', 'Mesa', 'Capacidad Total', 'Lugares Ocupados', 'Estado', 'Grupos Asignados' ) );

    $mesas = tbp_asientos_get_tables( $config_id );
    $mesas_map = array();
    foreach ( $mesas as $m ) {
        $m->grupos = array();
        $mesas_map[ $m->id ] = $m;
    }

    foreach ( $asignaciones as $a ) {
        if ( isset( $mesas_map[ $a->mesa_id ] ) ) {
            $mesas_map[ $a->mesa_id ]->grupos[] = $a->grupo . ' (' . $a->cantidad . ' lug.)';
        }
    }

    foreach ( $mesas_map as $m ) {
        $estado = $m->tipo === 'bloqueada' ? 'Bloqueada' : ( $m->capacidad_usada >= $m->capacidad ? 'Llena' : 'Parcial' );
        fputcsv( $output, array(
            $m->zona,
            $m->numero,
            $m->capacidad,
            $m->capacidad_usada,
            $estado,
            implode( ' | ', $m->grupos )
        ) );
    }

    fclose( $output );
    exit;
}

/**
 * Exporta CSV detallado por asistente (útil para check-in)
 */
function tbp_asientos_export_csv_asistentes( $config_id ) {
    $config = tbp_asientos_get_config( $config_id );
    if ( ! $config ) wp_die( 'Config no encontrada.' );

    $asignaciones = tbp_asientos_get_assignments( $config_id );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=checkin_asistentes_' . sanitize_file_name( $config->nombre ) . '_' . date( 'Y-m-d' ) . '.csv' );

    $output = fopen( 'php://output', 'w' );
    fputs( $output, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ) );

    fputcsv( $output, array( 'Pedido', 'Nombre Comprador', 'Grupo', 'Zona', 'Mesa', 'Lugares Asignados' ) );

    foreach ( $asignaciones as $a ) {
        fputcsv( $output, array(
            $a->order_id,
            $a->nombre . ' ' . $a->apellidos,
            $a->grupo,
            $a->zona,
            $a->numero,
            $a->cantidad
        ) );
    }

    fclose( $output );
    exit;
}
