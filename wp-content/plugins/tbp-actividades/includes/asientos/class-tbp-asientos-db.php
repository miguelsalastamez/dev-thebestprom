<?php
/**
 * DB Helper: Asignación de Asientos
 *
 * CRUD de las 4 tablas del módulo de asientos.
 *
 * @package TBP_Actividades
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =====================================================================
// CONFIGURACIONES (wp_tbp_seat_configurations)
// =====================================================================

/**
 * Obtiene una configuración de asientos por ID.
 *
 * @param int $config_id
 * @return object|null
 */
function tbp_asientos_get_config( $config_id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tbp_seat_configurations WHERE id = %d",
        $config_id
    ) );
    if ( $row && ! empty( $row->zonas_config ) ) {
        $row->zonas_config = json_decode( $row->zonas_config, true );
    }
    return $row;
}

/**
 * Obtiene todas las configuraciones de un evento.
 *
 * @param int $event_id
 * @return array
 */
function tbp_asientos_get_configs_by_event( $event_id ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tbp_seat_configurations
         WHERE event_id = %d ORDER BY created_at DESC",
        $event_id
    ) );
}

/**
 * Crea o actualiza una configuración de asientos.
 *
 * @param array $data {
 *   @type int    $event_id
 *   @type string $nombre
 *   @type string $tipo          'grupal'
 *   @type string $group_field   'Grupo'
 *   @type string $status        'draft'|'active'|'closed'
 *   @type array  $zonas_config  JSON serializable
 * }
 * @param int|null $config_id  Si se pasa, actualiza en lugar de crear.
 * @return int|false  ID de la fila o false en error.
 */
function tbp_asientos_save_config( array $data, $config_id = null ) {
    global $wpdb;

    $payload = array(
        'event_id'     => (int) ( $data['event_id']    ?? 0 ),
        'proveedor_id' => (int) ( $data['proveedor_id'] ?? 0 ),
        'nombre'       => sanitize_text_field( $data['nombre']      ?? '' ),
        'tipo'         => sanitize_key( $data['tipo']         ?? 'grupal' ),
        'group_field'  => sanitize_text_field( $data['group_field'] ?? 'Grupo' ),
        'status'       => sanitize_key( $data['status']       ?? 'draft' ),
        'zonas_config' => wp_json_encode( $data['zonas_config'] ?? array() ),
    );

    if ( $config_id ) {
        $result = $wpdb->update(
            $wpdb->prefix . 'tbp_seat_configurations',
            $payload,
            array( 'id' => (int) $config_id ),
            array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );
        if ( false === $result ) {
            return false;
        }
        return (int) $config_id;
    }

    $wpdb->insert(
        $wpdb->prefix . 'tbp_seat_configurations',
        $payload,
        array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
    );
    return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
}

// =====================================================================
// MESAS (wp_tbp_seat_tables)
// =====================================================================

/**
 * Genera las mesas para una configuración a partir del zonas_config.
 * Borra las mesas existentes (solo si no hay asignaciones) y las recrea.
 *
 * @param int   $config_id
 * @param array $zonas_config  Estructura:
 *   [
 *     [ 'nombre'=>'Zona C', 'prioridad'=>1, 'mesas'=>62, 'capacidad'=>9,
 *       'mesas_por_fila'=>10, 'plano_url'=>'', 'bloqueos'=>[] ],
 *     ...
 *   ]
 * @return int  Número de mesas insertadas.
 */
function tbp_asientos_generate_tables( $config_id, array $zonas_config ) {
    global $wpdb;
    $table = $wpdb->prefix . 'tbp_seat_tables';

    // 1. Obtener mesas existentes para esta configuración
    $existing_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$table} WHERE config_id = %d",
        $config_id
    ), ARRAY_A );

    // Indexar por zona y número
    $existing_idx = array();
    foreach ( $existing_rows as $row ) {
        $z = $row['zona'];
        $num = (string) $row['numero'];
        if ( ! isset( $existing_idx[ $z ] ) ) {
            $existing_idx[ $z ] = array();
        }
        $existing_idx[ $z ][ $num ] = $row;
    }

    $keep_ids = array();
    $inserted = 0;
    $updated  = 0;

    // 2. Procesar la configuración de zonas
    foreach ( $zonas_config as $zona ) {
        $nombre    = sanitize_text_field( $zona['nombre']    ?? '' );
        $total     = (int) ( $zona['mesas']     ?? 0 );
        $capacidad = (int) ( $zona['capacidad'] ?? 10 );
        $bloqueos  = $zona['bloqueos'] ?? array(); // array de [ 'numero'=>X, 'tipo'=>'vip', 'etiqueta'=>'' ]

        // Índice rápido de bloqueos por número
        $bloqueo_idx = array();
        foreach ( $bloqueos as $b ) {
            $bloqueo_idx[ (int) $b['numero'] ] = $b;
        }

        for ( $n = 1; $n <= $total; $n++ ) {
            $num_str  = (string) $n;
            $tipo     = 'normal';
            $etiqueta = '';

            if ( isset( $bloqueo_idx[ $n ] ) ) {
                $tipo     = sanitize_key( $bloqueo_idx[ $n ]['tipo'] ?? 'bloqueada' );
                $etiqueta = sanitize_text_field( $bloqueo_idx[ $n ]['etiqueta'] ?? '' );
            }

            if ( isset( $existing_idx[ $nombre ][ $num_str ] ) ) {
                // Ya existe: actualizamos y lo mantenemos
                $existing_row = $existing_idx[ $nombre ][ $num_str ];
                $existing_id  = (int) $existing_row['id'];
                $keep_ids[]   = $existing_id;

                // Solo actualizamos capacidad y etiqueta de bloqueo para no perder los campos visuales/espaciales
                $wpdb->update(
                    $table,
                    array(
                        'capacidad'        => $capacidad,
                        'etiqueta_bloqueo' => $etiqueta,
                        'tipo'             => ( $tipo !== 'normal' ) ? $tipo : $existing_row['tipo'],
                    ),
                    array( 'id' => $existing_id ),
                    array( '%d', '%s', '%s' ),
                    array( '%d' )
                );
                $updated++;
            } else {
                // No existe: lo insertamos
                $wpdb->insert(
                    $table,
                    array(
                        'config_id'        => $config_id,
                        'zona'             => $nombre,
                        'numero'           => $num_str,
                        'capacidad'        => $capacidad,
                        'capacidad_usada'  => 0,
                        'tipo'             => ( $tipo !== 'normal' ) ? $tipo : 'round', // default shape is round
                        'etiqueta_bloqueo' => $etiqueta,
                    ),
                    array( '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
                );
                if ( $wpdb->insert_id ) {
                    $keep_ids[] = (int) $wpdb->insert_id;
                    $inserted++;
                }
            }
        }
    }

    // 3. Eliminar mesas que no estén en la nueva configuración y que no tengan asignaciones
    if ( ! empty( $keep_ids ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $keep_ids ), '%d' ) );
        $wpdb->query( $wpdb->prepare(
            "DELETE t FROM {$table} t
             LEFT JOIN {$wpdb->prefix}tbp_seat_assignments a ON a.mesa_id = t.id
             WHERE t.config_id = %d AND a.id IS NULL AND t.id NOT IN ($placeholders)",
            array_merge( array( $config_id ), $keep_ids )
        ) );
    } else {
        $wpdb->query( $wpdb->prepare(
            "DELETE t FROM {$table} t
             LEFT JOIN {$wpdb->prefix}tbp_seat_assignments a ON a.mesa_id = t.id
             WHERE t.config_id = %d AND a.id IS NULL",
            $config_id
        ) );
    }

    return $inserted + $updated;
}

/**
 * Obtiene las mesas de una configuración, con filtro opcional por zona y tipo.
 *
 * @param int    $config_id
 * @param string $zona   '' para todas.
 * @param string $tipo   '' para todas | 'normal' | 'vip' | etc.
 * @return array
 */
function tbp_asientos_get_tables( $config_id, $zona = '', $tipo = '' ) {
    global $wpdb;
    $where = $wpdb->prepare( 'WHERE config_id = %d', $config_id );
    if ( $zona ) {
        $where .= $wpdb->prepare( ' AND zona = %s', $zona );
    }
    if ( $tipo ) {
        $where .= $wpdb->prepare( ' AND tipo = %s', $tipo );
    }
    return $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}tbp_seat_tables $where ORDER BY zona, CAST(numero AS UNSIGNED)"
    );
}

/**
 * Actualiza la capacidad usada de una mesa.
 *
 * @param int $mesa_id
 * @param int $delta  Positivo para agregar, negativo para restar.
 * @return bool
 */
function tbp_asientos_update_table_used( $mesa_id, $delta ) {
    global $wpdb;
    return false !== $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}tbp_seat_tables
         SET capacidad_usada = GREATEST(0, capacidad_usada + %d)
         WHERE id = %d",
        $delta,
        $mesa_id
    ) );
}

// =====================================================================
// ASIGNACIONES (wp_tbp_seat_assignments)
// =====================================================================

/**
 * Asigna un pedido a una mesa.
 * Si el pedido ya está asignado en esta config, actualiza la asignación.
 *
 * @param int    $config_id
 * @param int    $mesa_id
 * @param int    $order_id
 * @param string $grupo
 * @param int    $cantidad
 * @param string $nombre
 * @param string $apellidos
 * @return int|false  ID de la asignación o false.
 */
function tbp_asientos_assign_order( $config_id, $mesa_id, $order_id, $grupo, $cantidad, $nombre = '', $apellidos = '' ) {
    global $wpdb;

    // ¿Ya existe una asignación para este pedido+config?
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}tbp_seat_assignments
         WHERE config_id = %d AND order_id = %d",
        $config_id, $order_id
    ) );

    if ( $existing ) {
        // Obtener la mesa anterior para restituir su capacidad
        $old = $wpdb->get_row( $wpdb->prepare(
            "SELECT mesa_id, cantidad FROM {$wpdb->prefix}tbp_seat_assignments WHERE id = %d",
            $existing
        ) );
        if ( $old ) {
            tbp_asientos_update_table_used( $old->mesa_id, - (int) $old->cantidad );
        }

        $wpdb->update(
            $wpdb->prefix . 'tbp_seat_assignments',
            array(
                'mesa_id'   => $mesa_id,
                'grupo'     => sanitize_text_field( $grupo ),
                'cantidad'  => $cantidad,
                'nombre'    => sanitize_text_field( $nombre ),
                'apellidos' => sanitize_text_field( $apellidos ),
                'status'    => 'assigned',
            ),
            array( 'id' => (int) $existing ),
            array( '%d', '%s', '%d', '%s', '%s', '%s' ),
            array( '%d' )
        );
        $assignment_id = (int) $existing;
    } else {
        $wpdb->insert(
            $wpdb->prefix . 'tbp_seat_assignments',
            array(
                'config_id' => $config_id,
                'mesa_id'   => $mesa_id,
                'order_id'  => $order_id,
                'grupo'     => sanitize_text_field( $grupo ),
                'cantidad'  => $cantidad,
                'nombre'    => sanitize_text_field( $nombre ),
                'apellidos' => sanitize_text_field( $apellidos ),
                'status'    => 'assigned',
            ),
            array( '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s' )
        );
        $assignment_id = $wpdb->insert_id ? (int) $wpdb->insert_id : false;
    }

    if ( $assignment_id ) {
        // Actualizar capacidad usada de la mesa
        tbp_asientos_update_table_used( $mesa_id, $cantidad );

        // Escribir caché en post_meta del pedido
        tbp_asientos_write_order_cache( $config_id, $mesa_id, $order_id, $grupo, $cantidad );
    }

    return $assignment_id;
}

/**
 * Escribe el post_meta caché de asignación en el pedido WooCommerce.
 *
 * @param int    $config_id
 * @param int    $mesa_id
 * @param int    $order_id
 * @param string $grupo
 * @param int    $cantidad
 */
function tbp_asientos_write_order_cache( $config_id, $mesa_id, $order_id, $grupo, $cantidad ) {
    global $wpdb;

    // Obtener datos de la mesa para el caché legible
    $mesa = $wpdb->get_row( $wpdb->prepare(
        "SELECT t.numero, t.zona, c.nombre as config_nombre
         FROM {$wpdb->prefix}tbp_seat_tables t
         INNER JOIN {$wpdb->prefix}tbp_seat_configurations c ON c.id = t.config_id
         WHERE t.id = %d",
        $mesa_id
    ) );

    if ( ! $mesa ) {
        return;
    }

    $zona_inicial = strtoupper( substr( trim( $mesa->zona ), -1 ) ); // ej: 'C'

    update_post_meta( $order_id, '_tbp_seat_assignment', array(
        'config_id'   => $config_id,
        'mesa_id'     => $mesa_id,
        'zona'        => $mesa->zona,
        'mesa_numero' => $zona_inicial . '-' . $mesa->numero,
        'grupo'       => $grupo,
        'cantidad'    => $cantidad,
        'status'      => 'assigned',
        'assigned_at' => current_time( 'mysql' ),
    ) );
}

/**
 * Obtiene todas las asignaciones de una configuración.
 *
 * @param int $config_id
 * @return array
 */
function tbp_asientos_get_assignments( $config_id ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT a.*, t.zona, t.numero, t.capacidad, t.capacidad_usada, t.tipo
         FROM {$wpdb->prefix}tbp_seat_assignments a
         INNER JOIN {$wpdb->prefix}tbp_seat_tables t ON t.id = a.mesa_id
         WHERE a.config_id = %d
         ORDER BY t.zona, CAST(t.numero AS UNSIGNED), a.grupo",
        $config_id
    ) );
}

/**
 * Borra todas las asignaciones de una configuración (para re-ejecutar el algoritmo).
 *
 * @param int $config_id
 * @return bool
 */
function tbp_asientos_clear_assignments( $config_id ) {
    global $wpdb;

    // Obtener order_ids afectados antes de borrar
    $order_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT order_id FROM {$wpdb->prefix}tbp_seat_assignments WHERE config_id = %d",
        $config_id
    ) );

    // Borrar asignaciones
    $wpdb->delete(
        $wpdb->prefix . 'tbp_seat_assignments',
        array( 'config_id' => $config_id ),
        array( '%d' )
    );

    // Resetear capacidad usada de todas las mesas
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}tbp_seat_tables SET capacidad_usada = 0 WHERE config_id = %d",
        $config_id
    ) );

    // Limpiar caché de post_meta en todos los pedidos afectados
    foreach ( $order_ids as $oid ) {
        delete_post_meta( (int) $oid, '_tbp_seat_assignment' );
    }

    return true;
}

// =====================================================================
// OVERRIDES DE ZONA (wp_tbp_seat_group_zones)
// =====================================================================

/**
 * Guarda un override manual: este grupo irá a esta zona específica.
 *
 * @param int    $config_id
 * @param string $grupo
 * @param string $zona
 * @return bool
 */
function tbp_asientos_set_group_zone_override( $config_id, $grupo, $zona ) {
    global $wpdb;

    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}tbp_seat_group_zones
         WHERE config_id = %d AND grupo = %s",
        $config_id, $grupo
    ) );

    if ( $existing ) {
        return false !== $wpdb->update(
            $wpdb->prefix . 'tbp_seat_group_zones',
            array( 'zona' => sanitize_text_field( $zona ) ),
            array( 'id' => (int) $existing ),
            array( '%s' ),
            array( '%d' )
        );
    }

    return false !== $wpdb->insert(
        $wpdb->prefix . 'tbp_seat_group_zones',
        array(
            'config_id' => $config_id,
            'grupo'     => sanitize_text_field( $grupo ),
            'zona'      => sanitize_text_field( $zona ),
        ),
        array( '%d', '%s', '%s' )
    );
}

/**
 * Obtiene todos los overrides de zona para una configuración.
 *
 * @param int $config_id
 * @return array  Asociativo: [ 'Grupo 116' => 'Zona C', ... ]
 */
function tbp_asientos_get_group_zone_overrides( $config_id ) {
    global $wpdb;
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT grupo, zona FROM {$wpdb->prefix}tbp_seat_group_zones WHERE config_id = %d",
        $config_id
    ) );

    $map = array();
    foreach ( $rows as $row ) {
        $map[ $row->grupo ] = $row->zona;
    }
    return $map;
}
