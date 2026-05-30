<?php
/**
 * Motor de Asignación: Algoritmo de Packing Grupal
 *
 * Implementa el algoritmo en 3 fases:
 *   Fase 0 — Preparación: carga grupos, zonas y overrides.
 *   Fase 1 — Asignación de grupos a zonas (por prioridad de tamaño).
 *   Fase 2 — Packing de pedidos dentro de cada mesa (bin-packing).
 *   Fase 3 — Generación del reporte de resultados.
 *
 * @package TBP_Actividades
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ejecuta el algoritmo de asignación grupal completo para una configuración.
 *
 * @param int  $config_id
 * @param bool $dry_run    Si es true, solo calcula sin guardar (preview).
 * @return array {
 *   @type bool   $success
 *   @type array  $assignments   Lista de asignaciones realizadas.
 *   @type array  $warnings      Advertencias (grupos divididos, mesas cortas, etc.)
 *   @type array  $unassigned    Pedidos que no pudieron asignarse (sin capacidad).
 *   @type array  $stats         Estadísticas del resultado.
 * }
 */
function tbp_asientos_run_packing( $config_id, $dry_run = false ) {
    global $wpdb;

    $config = tbp_asientos_get_config( $config_id );
    if ( ! $config ) {
        return array( 'success' => false, 'message' => 'Configuración no encontrada.' );
    }

    // Debug tracker para diagnóstico
    $debug = array(
        'config_id'     => $config_id,
        'event_id'      => $config->event_id ?? 0,
        'proveedor_id'  => $config->proveedor_id ?? 0,
        'group_field'   => $config->group_field ?? '',
        'zonas_count'   => 0,
        'zonas_nombres' => array(),
        'pedidos_source'=> 'none',
        'pedidos_raw_count' => 0,
        'mesas_por_zona'=> array(),
        'grupos_count'  => 0,
    );

    // ================================================================
    // FASE 0: PREPARACIÓN
    // ================================================================

    $zonas_config = $config->zonas_config; // Array de zonas ordenadas por prioridad
    if ( empty( $zonas_config ) ) {
        return array( 'success' => false, 'message' => 'No hay zonas configuradas.', 'debug' => $debug );
    }
    $debug['zonas_count'] = count( $zonas_config );
    $debug['zonas_nombres'] = array_column( $zonas_config, 'nombre' );

    // Ordenar zonas por prioridad (1 = mejor zona, asignada a grupos más grandes)
    usort( $zonas_config, fn( $a, $b ) => ( $a['prioridad'] ?? 99 ) <=> ( $b['prioridad'] ?? 99 ) );

    // Obtener todos los pedidos del evento con su grupo y cantidad de lugares
    // Verificar si ya tenemos el resultado escaneado en cache (transient)
    $pedidos_raw = get_transient( 'tbp_seat_scan_' . $config_id );
    
    if ( false === $pedidos_raw || empty( $pedidos_raw ) ) {
        // Fallback: si no hay escaneo previo, hacerlo en el momento
        $proveedor_id = (int) ($config->proveedor_id ?? 0);
        $pedidos_raw = tbp_asientos_get_all_orders_with_seats( $config->event_id, $config->group_field, $proveedor_id );
        $debug['pedidos_source'] = 'fallback_live';
    } else {
        $debug['pedidos_source'] = 'transient_cache';
    }
    $debug['pedidos_raw_count'] = count( $pedidos_raw );

    // Calcular grupos totales desde los pedidos (ordenados mayor → menor)
    $grupos_totales = array();
    foreach ( $pedidos_raw as $p ) {
        $g = $p['grupo'];
        if ( ! isset( $grupos_totales[ $g ] ) ) {
            $grupos_totales[ $g ] = 0;
        }
        $grupos_totales[ $g ] += $p['cantidad'];
    }
    arsort( $grupos_totales );

    // Obtener overrides manuales de zona (grupo → zona forzada)
    $zone_overrides = tbp_asientos_get_group_zone_overrides( $config_id );

    // Obtener mesas disponibles por zona
    $mesas_por_zona = array();
    foreach ( $zonas_config as $zona ) {
        $zona_nombre = $zona['nombre'];
        $mesas = tbp_asientos_get_tables( $config_id, $zona_nombre );
        // Filtrar mesas que no estén bloqueadas, permitiendo mesas normales con formas (round, rectangular, etc.)
        $mesas = array_values( array_filter( $mesas, function( $m ) {
            return $m->tipo !== 'bloqueada' && $m->tipo !== 'bloqueado' && $m->tipo !== 'blocked';
        } ) );
        $mesas_por_zona[ $zona_nombre ] = $mesas;
        $debug['mesas_por_zona'][ $zona_nombre ] = count( $mesas );
    }

    // Resetear capacidad_usada de TODAS las mesas de esta config
    // para que el algoritmo trabaje con mesas limpias (las asignaciones
    // previas se borrarán en la FASE 3).
    $wpdb->update(
        $wpdb->prefix . 'tbp_seat_tables',
        array( 'capacidad_usada' => 0 ),
        array( 'config_id' => $config_id ),
        array( '%d' ),
        array( '%d' )
    );

    // Agrupar pedidos por grupo
    $pedidos_por_grupo = array();
    foreach ( $pedidos_raw as $p ) {
        $g = $p['grupo'];
        if ( ! isset( $pedidos_por_grupo[ $g ] ) ) {
            $pedidos_por_grupo[ $g ] = array();
        }
        $pedidos_por_grupo[ $g ][] = $p;
    }
    $debug['grupos_count'] = count( $pedidos_por_grupo );

    // ================================================================
    // FASE 1: ASIGNACIÓN DE GRUPOS A ZONAS
    // ================================================================

    // Asignar cada grupo a una zona según prioridad (mayor grupo → mejor zona)
    // Los overrides manuales tienen precedencia.
    $grupo_zona_map = array(); // grupo => nombre de zona asignada

    $zona_capacidad_libre = array();
    foreach ( $zonas_config as $zona ) {
        $n = $zona['nombre'];
        $zona_capacidad_libre[ $n ] = 0;
        foreach ( $mesas_por_zona[ $n ] as $m ) {
            $zona_capacidad_libre[ $n ] += ( $m->capacidad - $m->capacidad_usada );
        }
    }

    $warnings = array();

    foreach ( $grupos_totales as $grupo => $total_seats ) {
        // Override manual
        if ( isset( $zone_overrides[ $grupo ] ) ) {
            $grupo_zona_map[ $grupo ] = $zone_overrides[ $grupo ];
            continue;
        }

        $asignado = false;
        
        // Asignar a la mejor zona con capacidad suficiente
        foreach ( $zonas_config as $zona ) {
            $n = $zona['nombre'];
            if ( $zona_capacidad_libre[ $n ] >= $total_seats ) {
                $grupo_zona_map[ $grupo ] = $n;
                $zona_capacidad_libre[ $n ] -= $total_seats;
                $asignado = true;
                break;
            }
        }

        // Desbordamiento: asignar a la zona con más espacio libre
        if ( ! $asignado ) {
            $mejor_zona = array_search( max( $zona_capacidad_libre ), $zona_capacidad_libre );
            $grupo_zona_map[ $grupo ] = $mejor_zona;
            $warnings[] = sprintf(
                'Grupo "%s" (%d lugares) desbordó a la zona "%s".',
                $grupo, $total_seats, $mejor_zona
            );
        }
    }

    // ================================================================
    // FASE 2: PACKING DE PEDIDOS DENTRO DE CADA MESA
    // ================================================================

    $assignments  = array(); // resultado de asignaciones a guardar
    $unassigned   = array(); // pedidos que no pudieron asignarse

    // Estado de mesas por zona: lista con capacidad restante
    // Usamos capacidad TOTAL (no restamos capacidad_usada) porque ya la reseteamos
    // y las asignaciones anteriores se borrarán en la FASE 3.
    $mesas_estado = array(); // zona => array de [ 'mesa' => obj, 'libre' => int ]
    foreach ( $zonas_config as $zona ) {
        $n = $zona['nombre'];
        $mesas_estado[ $n ] = array();
        foreach ( $mesas_por_zona[ $n ] as $m ) {
            $mesas_estado[ $n ][] = array(
                'mesa'      => $m,
                'libre'     => (int) $m->capacidad, // Capacidad TOTAL (partimos de mesas vacías)
                'tipo_mesa' => null, // 'singles' o 'families'
            );
        }
    }

    // Puntero actual de mesa por zona
    $mesa_cursor = array();
    foreach ( array_keys( $mesas_estado ) as $n ) {
        $mesa_cursor[ $n ] = 0;
    }

    foreach ( $grupos_totales as $grupo => $_ ) {
        if ( ! isset( $pedidos_por_grupo[ $grupo ] ) ) {
            continue;
        }

        $zona_nombre = $grupo_zona_map[ $grupo ] ?? null;
        if ( ! $zona_nombre || ! isset( $mesas_estado[ $zona_nombre ] ) ) {
            foreach ( $pedidos_por_grupo[ $grupo ] as $p ) {
                $unassigned[] = array_merge( $p, array( 'razon' => 'Sin zona asignada' ) );
            }
            continue;
        }

        // Ordenar pedidos del grupo de mayor a menor
        $pedidos_grupo = $pedidos_por_grupo[ $grupo ];
        usort( $pedidos_grupo, fn( $a, $b ) => $b['cantidad'] <=> $a['cantidad'] );

        foreach ( $pedidos_grupo as $pedido ) {
            $qty     = (int) $pedido['cantidad'];
            $asigned = false;

            // Validar reglas de la zona asignada
            $zona_asignada = null;
            foreach ( $zonas_config as $z ) {
                if ( $z['nombre'] === $zona_nombre ) {
                    $zona_asignada = $z;
                    break;
                }
            }

            $g_min = isset( $zona_asignada['grupo_min'] ) ? (int) $zona_asignada['grupo_min'] : 1;
            $g_max = isset( $zona_asignada['grupo_max'] ) ? (int) $zona_asignada['grupo_max'] : 999;

            // Determinar tipo de pedido
            $tipo_pedido = ( $qty === 1 ) ? 'singles' : 'families';

            // Si el pedido CUMPLE la regla de la zona asignada al grupo, intentamos colocarlo ahí
            if ( $qty >= $g_min && $qty <= $g_max ) {
                $total_mesas = count( $mesas_estado[ $zona_nombre ] );
                for ( $i = 0; $i < $total_mesas; $i++ ) {
                    $idx   = ( $mesa_cursor[ $zona_nombre ] + $i ) % $total_mesas;
                    $entry = &$mesas_estado[ $zona_nombre ][ $idx ];

                    // REGLA DE PURISMO: No mezclar tipos en la misma mesa
                    if ( $entry['tipo_mesa'] !== null && $entry['tipo_mesa'] !== $tipo_pedido ) {
                        continue;
                    }

                    if ( $entry['libre'] >= $qty ) {
                        $entry['libre']    -= $qty;
                        $entry['tipo_mesa'] = $tipo_pedido; // Bloquear mesa para este tipo

                        $assignments[] = array(
                            'config_id' => $config_id,
                            'mesa_id'   => $entry['mesa']->id,
                            'order_id'  => $pedido['order_id'],
                            'grupo'     => $grupo,
                            'cantidad'  => $qty,
                            'nombre'    => $pedido['nombre']    ?? '',
                            'apellidos' => $pedido['apellidos'] ?? '',
                        );

                        if ( $entry['libre'] === 0 ) {
                            $mesa_cursor[ $zona_nombre ] = ( $idx + 1 ) % $total_mesas;
                        }

                        $asigned = true;
                        break;
                    }
                }
            } else {
                $warnings[] = sprintf(
                    'Pedido #%d (Grupo "%s", %d lugares) fue rechazado de su zona principal "%s" por regla (Mín: %d, Máx: %d).',
                    $pedido['order_id'], $grupo, $qty, $zona_nombre, $g_min, $g_max
                );
            }

            if ( ! $asigned ) {
                // Intentar en otras zonas con espacio (fallback) que SÍ cumplan la regla para este pedido
                $assigned_fallback = false;
                foreach ( $zonas_config as $zona_fb ) {
                    $nfb = $zona_fb['nombre'];
                    if ( $nfb === $zona_nombre ) {
                        continue;
                    }

                    $fb_min = isset( $zona_fb['grupo_min'] ) ? (int) $zona_fb['grupo_min'] : 1;
                    $fb_max = isset( $zona_fb['grupo_max'] ) ? (int) $zona_fb['grupo_max'] : 999;

                    // El pedido DEBE cumplir la regla de la zona fallback
                    if ( $qty < $fb_min || $qty > $fb_max ) {
                        continue;
                    }

                    $total_fb = count( $mesas_estado[ $nfb ] );
                    for ( $j = 0; $j < $total_fb; $j++ ) {
                        $jdx   = ( ( $mesa_cursor[ $nfb ] ?? 0 ) + $j ) % $total_fb;
                        $entry = &$mesas_estado[ $nfb ][ $jdx ];
                        
                        // REGLA DE PURISMO: No mezclar tipos en la misma mesa
                        if ( $entry['tipo_mesa'] !== null && $entry['tipo_mesa'] !== $tipo_pedido ) {
                            continue;
                        }

                        if ( $entry['libre'] >= $qty ) {
                            $entry['libre']    -= $qty;
                            $entry['tipo_mesa'] = $tipo_pedido; // Bloquear mesa para este tipo

                            $assignments[] = array(
                                'config_id' => $config_id,
                                'mesa_id'   => $entry['mesa']->id,
                                'order_id'  => $pedido['order_id'],
                                'grupo'     => $grupo,
                                'cantidad'  => $qty,
                                'nombre'    => $pedido['nombre']    ?? '',
                                'apellidos' => $pedido['apellidos'] ?? '',
                            );
                            $warnings[] = sprintf(
                                'Pedido #%d (Grupo "%s", %d lugares) se asignó a zona "%s" (Fallback).',
                                $pedido['order_id'], $grupo, $qty, $nfb
                            );
                            $assigned_fallback = true;
                            break 2;
                        }
                    }
                }

                if ( ! $assigned_fallback ) {
                    // ========================================================
                    // LÓGICA DE MESAS DINÁMICAS (OVERBOOKING)
                    // Encontrar la mesa con MÁS espacio libre en las zonas permitidas
                    // ========================================================
                    $best_mesa_entry = null;
                    $best_zona = null;
                    $max_libre = -1;

                    foreach ( array_keys($mesas_estado) as $nz ) {
                        // Encontrar las reglas de esta zona
                        $fb_min = 1;
                        $fb_max = 999;
                        foreach ( $zonas_config as $zf ) {
                            if ( $zf['nombre'] === $nz ) {
                                $fb_min = isset( $zf['grupo_min'] ) ? (int) $zf['grupo_min'] : 1;
                                $fb_max = isset( $zf['grupo_max'] ) ? (int) $zf['grupo_max'] : 999;
                                break;
                            }
                        }

                        // Si la zona NO permite este pedido, NO hacemos overbooking aquí
                        if ( $qty < $fb_min || $qty > $fb_max ) {
                            continue;
                        }

                        for ( $k = 0; $k < count($mesas_estado[$nz]); $k++ ) {
                            $m_entry = &$mesas_estado[$nz][$k];

                            // REGLA DE PURISMO: No expandir una mesa que ya tiene gente de otro tipo
                            if ( $m_entry['tipo_mesa'] !== null && $m_entry['tipo_mesa'] !== $tipo_pedido ) {
                                continue;
                            }

                            if ( $m_entry['libre'] > $max_libre ) {
                                $max_libre = $m_entry['libre'];
                                $best_mesa_entry = &$m_entry;
                                $best_zona = $nz;
                            }
                        }
                    }

                    // Si no hay ninguna mesa del MISMO tipo, permitimos expandir una VACÍA
                    if ( ! $best_mesa_entry ) {
                         foreach ( array_keys($mesas_estado) as $nz ) {
                            for ( $k = 0; $k < count($mesas_estado[$nz]); $k++ ) {
                                $m_entry = &$mesas_estado[$nz][$k];
                                if ( $m_entry['tipo_mesa'] === null && $m_entry['libre'] > $max_libre ) {
                                    $max_libre = $m_entry['libre'];
                                    $best_mesa_entry = &$m_entry;
                                    $best_zona = $nz;
                                }
                            }
                        }
                    }

                    if ( $best_mesa_entry ) {
                        // Expandir capacidad
                        $espacio_faltante = $qty - $best_mesa_entry['libre'];
                        $best_mesa_entry['mesa']->capacidad += $espacio_faltante;
                        $best_mesa_entry['libre'] = 0; // se llena completamente
                        $best_mesa_entry['tipo_mesa'] = $tipo_pedido; // Bloquear tipo de mesa

                        $assignments[] = array(
                            'config_id' => $config_id,
                            'mesa_id'   => $best_mesa_entry['mesa']->id,
                            'order_id'  => $pedido['order_id'],
                            'grupo'     => $grupo,
                            'cantidad'  => $qty,
                            'nombre'    => $pedido['nombre']    ?? '',
                            'apellidos' => $pedido['apellidos'] ?? '',
                            'expandida' => true // flag interno
                        );
                        $warnings[] = sprintf(
                            'Overbooking: Pedido #%d (Grupo "%s", %d lugares) expandió la Mesa %s en "%s" (+%d sillas).',
                            $pedido['order_id'], $grupo, $qty, $best_mesa_entry['mesa']->numero, $best_zona, $espacio_faltante
                        );
                    } else {
                        $unassigned[] = array_merge( $pedido, array( 'razon' => 'Sin mesas disponibles para expandir' ) );
                    }
                }
            }
        }
    }

    // ================================================================
    // FASE 3: GUARDAR (si no es dry_run) Y GENERAR REPORTE
    // ================================================================

    if ( ! $dry_run ) {
        global $wpdb;

        // Iniciar transacción para máxima velocidad y seguridad
        $wpdb->query( 'START TRANSACTION' );

        try {
            // Limpiar asignaciones previas
            $wpdb->delete( $wpdb->prefix . 'tbp_seat_assignments', array( 'config_id' => $config_id ), array( '%d' ) );

            // --- 1. INSERT MASIVO DE ASIGNACIONES ---
            if ( ! empty( $assignments ) ) {
                $table_name = $wpdb->prefix . 'tbp_seat_assignments';
                $query = "INSERT INTO $table_name (config_id, mesa_id, order_id, grupo, cantidad, nombre, apellidos) VALUES ";
                $values = array();
                $placeholders = array();

                foreach ( $assignments as $a ) {
                    $placeholders[] = "(%d, %d, %d, %s, %d, %s, %s)";
                    $values[] = $a['config_id'];
                    $values[] = $a['mesa_id'];
                    $values[] = $a['order_id'];
                    $values[] = $a['grupo'];
                    $values[] = $a['cantidad'];
                    $values[] = $a['nombre'];
                    $values[] = $a['apellidos'];

                    // Enviar en lotes de 500 para no exceder max_allowed_packet
                    if ( count( $placeholders ) >= 500 ) {
                        $wpdb->query( $wpdb->prepare( $query . implode( ',', $placeholders ), $values ) );
                        $placeholders = array();
                        $values = array();
                    }
                }

                // Insertar el remanente
                if ( ! empty( $placeholders ) ) {
                    $wpdb->query( $wpdb->prepare( $query . implode( ',', $placeholders ), $values ) );
                }
            }

            // --- 2. OPTIMIZACIÓN DE ACTUALIZACIÓN DE MESAS ---
            foreach ( $mesas_estado as $zona_nombre => $lista_mesas ) {
                foreach ( $lista_mesas as $entry ) {
                    $cap_usada = $entry['mesa']->capacidad - $entry['libre'];
                    
                    // Solo actualizar si la mesa se usó o su capacidad cambió (overbooking)
                    // Esto evita cientos de UPDATES innecesarios en mesas vacías.
                    if ( $cap_usada > 0 || $entry['mesa']->capacidad != $entry['mesa']->capacidad_original ) {
                         $wpdb->update(
                            $wpdb->prefix . 'tbp_seat_tables',
                            array(
                                'capacidad'       => $entry['mesa']->capacidad,
                                'capacidad_usada' => $cap_usada
                            ),
                            array( 'id' => $entry['mesa']->id ),
                            array( '%d', '%d' ),
                            array( '%d' )
                        );
                    }
                }
            }

            // --- 3. ACTUALIZAR STATUS ---
            $wpdb->update(
                $wpdb->prefix . 'tbp_seat_configurations',
                array( 'status' => 'active' ),
                array( 'id' => $config_id ),
                array( '%s' ),
                array( '%d' )
            );

            $wpdb->query( 'COMMIT' );

            // --- 4. GENERAR SNAPSHOT PARA CONSULTA PÚBLICA ---
            tbp_asientos_generate_public_snapshot( $config_id );

        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            return array( 'success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage() );
        }
    }

    // Estadísticas
    $total_pedidos  = count( $pedidos_raw );
    $total_asignados = count( $assignments );
    $total_lugares  = array_sum( array_column( $assignments, 'cantidad' ) );
    $eficiencia     = $total_pedidos > 0
        ? round( ( $total_asignados / $total_pedidos ) * 100, 1 )
        : 0;

    return array(
        'success'     => true,
        'dry_run'     => $dry_run,
        'assignments' => $assignments,
        'warnings'    => $warnings,
        'unassigned'  => $unassigned,
        'stats'       => array(
            'total_pedidos'   => $total_pedidos,
            'total_asignados' => $total_asignados,
            'total_lugares'   => $total_lugares,
            'sin_asignar'     => count( $unassigned ),
            'advertencias'    => count( $warnings ),
            'eficiencia_pct'  => $eficiencia,
        ),
        'debug'       => $debug,
    );
}

/**
 * Obtiene todos los pedidos con su grupo y asientos. (Fallback)
 *
 * @param int    $event_id
 * @param string $group_field
 * @param int    $proveedor_id
 * @return array  Lista de [ order_id, grupo, cantidad, nombre, apellidos ]
 */
function tbp_asientos_get_all_orders_with_seats( $event_id, $group_field, $proveedor_id = 0 ) {
    $order_ids = tbp_asientos_get_orders_for_event( $event_id );
    $result    = array();

    foreach ( $order_ids as $order_id ) {
        $orden = wc_get_order( $order_id );
        if ( ! $orden ) {
            continue;
        }

        $cantidad = tbp_get_order_seat_qty( $order_id, $proveedor_id );
        if ( $cantidad <= 0 ) {
            continue;
        }

        $grupo = tbp_asientos_get_order_group_value( $order_id, $group_field );
        if ( empty( $grupo ) ) {
            $grupo = 'Sin grupo';
        }

        $result[] = array(
            'order_id'  => $order_id,
            'grupo'     => $grupo,
            'cantidad'  => $cantidad,
            'nombre'    => $orden->get_billing_first_name(),
            'apellidos' => $orden->get_billing_last_name(),
        );
    }

    return $result;
}

// =====================================================================
// AJAX HANDLERS
// =====================================================================

/**
 * AJAX: Obtener datos del escaneo para la tabla de la Etapa 3.
 */
add_action( 'wp_ajax_tbp_asientos_get_scan_data', 'tbp_asientos_ajax_get_scan_data' );
function tbp_asientos_ajax_get_scan_data() {
    check_ajax_referer( 'tbp_asientos_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

    $config_id = (int) ( $_POST['config_id'] ?? 0 );
    if ( ! $config_id ) wp_send_json_error( 'ID de configuración inválido.' );

    $scan_data = get_transient( 'tbp_seat_scan_' . $config_id );
    if ( empty( $scan_data ) || ! is_array( $scan_data ) ) {
        wp_send_json_error( array(
            'message' => 'no_scan',
            'label'   => 'No hay datos de escaneo. Ejecuta "🔍 Escanear Asistentes" en la Etapa 1 primero.'
        ) );
    }

    // Calcular estadísticas por grupo
    $grupos_stats = array();
    $total_piezas = 0;
    foreach ( $scan_data as $p ) {
        $g = $p['grupo'] ?? 'Sin grupo';
        if ( ! isset( $grupos_stats[ $g ] ) ) {
            $grupos_stats[ $g ] = array( 'pedidos' => 0, 'piezas' => 0 );
        }
        $grupos_stats[ $g ]['pedidos']++;
        $grupos_stats[ $g ]['piezas'] += (int) $p['cantidad'];
        $total_piezas += (int) $p['cantidad'];
    }

    // Ordenar grupos naturalmente
    uksort( $grupos_stats, 'strnatcasecmp' );

    wp_send_json_success( array(
        'pedidos'      => $scan_data,
        'grupos_stats' => $grupos_stats,
        'totales'      => array(
            'pedidos' => count( $scan_data ),
            'piezas'  => $total_piezas,
            'grupos'  => count( $grupos_stats ),
        ),
    ) );
}

/**
 * AJAX: Init Scan (Paso 1: Obtener todos los IDs de pedidos a procesar)
 */
add_action( 'wp_ajax_tbp_asientos_scan_init', 'tbp_asientos_ajax_scan_init' );
function tbp_asientos_ajax_scan_init() {
    check_ajax_referer( 'tbp_asientos_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

    $config_id = (int) ( $_POST['config_id'] ?? 0 );
    $config = tbp_asientos_get_config( $config_id );
    if ( ! $config ) wp_send_json_error( 'Configuración no encontrada.' );

    // Eliminar caché viejo
    delete_transient( 'tbp_seat_scan_' . $config_id );

    // Solo obtener la lista de order IDs
    $order_ids = tbp_asientos_get_orders_for_event( $config->event_id );
    
    // Filtrar pedidos que no tienen cantidad (optimización rápida)
    $valid_orders = [];
    $proveedor_id = (int) ($config->proveedor_id ?? 0);
    foreach ($order_ids as $oid) {
        if ( tbp_get_order_seat_qty( $oid, $proveedor_id ) > 0 ) {
            $valid_orders[] = $oid;
        }
    }

    wp_send_json_success( array( 'order_ids' => $valid_orders ) );
}

/**
 * AJAX: Scan Batch (Paso 2: Procesar un lote de pedidos)
 */
add_action( 'wp_ajax_tbp_asientos_scan_batch', 'tbp_asientos_ajax_scan_batch' );
function tbp_asientos_ajax_scan_batch() {
    check_ajax_referer( 'tbp_asientos_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

    $config_id = (int) ( $_POST['config_id'] ?? 0 );
    $order_ids = isset($_POST['order_ids']) && is_array($_POST['order_ids']) ? array_map('intval', $_POST['order_ids']) : [];
    
    $config = tbp_asientos_get_config( $config_id );
    if ( ! $config || empty($order_ids) ) wp_send_json_error( 'Datos inválidos.' );

    $batch_results = [];
    $proveedor_id = (int) ($config->proveedor_id ?? 0);
    foreach ( $order_ids as $order_id ) {
        $orden = wc_get_order( $order_id );
        if ( ! $orden ) continue;

        $cantidad = tbp_get_order_seat_qty( $order_id, $proveedor_id );
        $grupo = tbp_asientos_get_order_group_value( $order_id, $config->group_field );
        if ( empty( $grupo ) ) $grupo = 'Sin grupo';

        $batch_results[] = array(
            'order_id'  => $order_id,
            'grupo'     => $grupo,
            'cantidad'  => $cantidad,
            'nombre'    => $orden->get_billing_first_name(),
            'apellidos' => $orden->get_billing_last_name(),
        );
    }

    wp_send_json_success( $batch_results );
}

/**
 * AJAX: Finish Scan (Paso 3: Consolidar y guardar en transient)
 */
add_action( 'wp_ajax_tbp_asientos_scan_finish', 'tbp_asientos_ajax_scan_finish' );
function tbp_asientos_ajax_scan_finish() {
    check_ajax_referer( 'tbp_asientos_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

    $config_id = (int) ( $_POST['config_id'] ?? 0 );
    $results = isset($_POST['results']) && is_array($_POST['results']) ? wp_unslash($_POST['results']) : [];
    
    if ( ! $config_id ) wp_send_json_error( 'ID inválido.' );

    // Guardar array completo en Transient por 12 horas
    set_transient( 'tbp_seat_scan_' . $config_id, $results, 12 * HOUR_IN_SECONDS );

    // Calcular estadísticas
    $total_asistentes = 0;
    $grupos_unicos = array();
    foreach ( $results as $p ) {
        $total_asistentes += (int) $p['cantidad'];
        $grupos_unicos[ $p['grupo'] ] = true;
    }

    wp_send_json_success( array(
        'asistentes' => $total_asistentes,
        'grupos'     => count( $grupos_unicos ),
        'pedidos'    => count( $results )
    ) );
}

/**
 * AJAX: Ejecutar algoritmo (preview o confirmar).
 */
add_action( 'wp_ajax_tbp_asientos_run_packing', 'tbp_asientos_ajax_run_packing' );
function tbp_asientos_ajax_run_packing() {
    @set_time_limit( 0 );
    @ini_set( 'memory_limit', '2048M' );

    check_ajax_referer( 'tbp_asientos_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Sin permisos.' );
    }

    $config_id = (int) ( $_POST['config_id'] ?? 0 );
    $dry_run   = ! empty( $_POST['dry_run'] );

    if ( ! $config_id ) {
        wp_send_json_error( 'ID de configuración inválido.' );
    }

    $result = tbp_asientos_run_packing( $config_id, $dry_run );
    wp_send_json_success( $result );
}

/**
 * AJAX: Mover un pedido de mesa (override manual post-algoritmo).
 */
add_action( 'wp_ajax_tbp_asientos_move_order', 'tbp_asientos_ajax_move_order' );
function tbp_asientos_ajax_move_order() {
    check_ajax_referer( 'tbp_asientos_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Sin permisos.' );
    }

    $config_id   = (int) ( $_POST['config_id']   ?? 0 );
    $order_id    = (int) ( $_POST['order_id']    ?? 0 );
    $new_mesa_id = (int) ( $_POST['new_mesa_id'] ?? 0 );

    if ( ! $config_id || ! $order_id || ! $new_mesa_id ) {
        wp_send_json_error( 'Parámetros inválidos.' );
    }

    // Obtener datos actuales del pedido
    global $wpdb;
    $current = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tbp_seat_assignments
         WHERE config_id = %d AND order_id = %d",
        $config_id, $order_id
    ) );
    if ( ! $current ) {
        wp_send_json_error( 'Asignación no encontrada.' );
    }

    // Verificar capacidad en la nueva mesa
    $nueva_mesa = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tbp_seat_tables WHERE id = %d AND config_id = %d",
        $new_mesa_id, $config_id
    ) );
    if ( ! $nueva_mesa ) {
        wp_send_json_error( 'Mesa destino no encontrada.' );
    }

    $libre = $nueva_mesa->capacidad - $nueva_mesa->capacidad_usada;
    if ( $libre < (int) $current->cantidad ) {
        wp_send_json_error( sprintf(
            'Capacidad insuficiente en la mesa destino (%d libre, %d necesarios).',
            $libre, $current->cantidad
        ) );
    }

    // Ejecutar el movimiento
    $result = tbp_asientos_assign_order(
        $config_id,
        $new_mesa_id,
        $order_id,
        $current->grupo,
        $current->cantidad,
        $current->nombre,
        $current->apellidos
    );

    if ( $result ) {
        wp_send_json_success( array( 'assignment_id' => $result ) );
    } else {
        wp_send_json_error( 'No se pudo actualizar la asignación.' );
    }
}

/**
 * Genera un Snapshot JSON estático de todos los asistentes asignados.
 * Esto permite que la consulta pública sea instantánea y no consuma CPU.
 */
function tbp_asientos_generate_public_snapshot( $config_id ) {
    $config = tbp_asientos_get_config( $config_id );
    if ( ! $config ) return false;

    global $wpdb;

    // 1. Obtener todas las asignaciones confirmadas
    $assignments = $wpdb->get_results( $wpdb->prepare(
        "SELECT a.*, t.numero as mesa_numero, t.zona as zona_nombre
         FROM {$wpdb->prefix}tbp_seat_assignments a
         JOIN {$wpdb->prefix}tbp_seat_tables t ON a.mesa_id = t.id
         WHERE a.config_id = %d
         ORDER BY t.zona ASC, CAST(t.numero AS UNSIGNED) ASC, a.id ASC",
        $config_id
    ) );

    // 2. Obtener Pedidos Pagados Parcialmente (fuera de asignación)
    // Usamos el listado de IDs del evento
    $all_event_order_ids = tbp_report_get_event_order_ids( $config->event_id );
    $partial_rows = [];
    if ( ! empty($all_event_order_ids) ) {
        $ids_str = implode(',', array_map('intval', $all_event_order_ids));
        $partial_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE ID IN ($ids_str) AND post_status = 'wc-p-pagado'"
        );
        foreach ( $partial_ids as $oid ) {
            $order = wc_get_order($oid);
            if (!$order) continue;
            $qty = tbp_get_order_seat_qty($oid, $config->proveedor_id);
            if ($qty <= 0) continue;

            $partial_rows[] = [
                'order_id'     => $oid,
                'status'       => 'p-pagado',
                'status_label' => 'Pago Parcial',
                'titular'      => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                'producto'     => $order->get_items() ? current($order->get_items())->get_name() : 'Ticket',
                'cantidad'     => $qty,
                'details'      => tbp_report_format_details(tbp_actividades_get_order_attendees_meta($oid, $order))
            ];
        }
    }

    // 3. Formatear Filas Confirmadas
    $rows = [];
    $aggregates = [];
    $stats = ['confirmed' => 0, 'partial' => count($partial_rows), 'total' => 0];

    foreach ( $assignments as $a ) {
        $stats['confirmed']++;
        
        // Detalles para agregados (chips)
        $full_details = tbp_report_format_details(tbp_actividades_get_order_attendees_meta($a->order_id));

        $rows[] = [
            'order_id'     => $a->order_id,
            'status'       => 'completed',
            'status_label' => 'Confirmado',
            'titular'      => $a->nombre . ' ' . $a->apellidos,
            'producto'     => 'Asignado (Mesa ' . $a->mesa_numero . ')',
            'cantidad'     => $a->cantidad,
            'details'      => $full_details,
            'mesa'         => $a->mesa_numero,
            'zona'         => $a->zona_nombre,
            'grupo'        => $a->grupo
        ];

        // Calcular agregados para chips
        foreach ( $full_details as $d ) {
            $clean = strip_tags($d);
            $parts = explode(':', $clean, 2);
            if (count($parts) < 2) continue;
            $f = trim($parts[0]); $v = trim($parts[1]);
            if (!isset($aggregates[$f])) $aggregates[$f] = [];
            if (!isset($aggregates[$f][$v])) $aggregates[$f][$v] = 0;
            $aggregates[$f][$v]++;
        }
    }

    $stats['total'] = $stats['confirmed'] + $stats['partial'];

    // 4. Obtener Estructura del Mapa (Geometría)
    $table_seats    = $wpdb->prefix . 'tbp_seat_tables';
    $table_elements = $wpdb->prefix . 'tbp_seat_elements';

    $map_tables = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_seats WHERE config_id = %d", $config_id ) );
    $map_elements = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_elements WHERE config_id = %d", $config_id ) );

    $map_data = [
        'tables'   => [],
        'elements' => []
    ];

    // Mapear invitados a mesas para el plano
    $guests_by_mesa = [];
    foreach ( $assignments as $a ) {
        if ( ! isset($guests_by_mesa[$a->mesa_id]) ) $guests_by_mesa[$a->mesa_id] = [];
        $guests_by_mesa[$a->mesa_id][] = [
            'n' => $a->nombre . ' ' . $a->apellidos,
            'o' => '#' . $a->order_id,
            'g' => $a->grupo
        ];
    }

    foreach ( $map_tables as $mt ) {
        $map_data['tables'][] = [
            'id'    => (int) $mt->id,
            'lbl'   => $mt->numero,
            'z'     => $mt->zona,
            'type'  => $mt->tipo,
            'x'     => (int) $mt->pos_x,
            'y'     => (int) $mt->pos_y,
            'w'     => (int) $mt->width,
            'h'     => (int) $mt->height,
            'cap'   => (int) $mt->capacidad,
            'used'  => (int) $mt->capacidad_usada,
            'gs'    => $guests_by_mesa[$mt->id] ?? []
        ];
    }

    foreach ( $map_elements as $me ) {
        $map_data['elements'][] = [
            'type'  => $me->tipo,
            'lbl'   => $me->label,
            'x'     => (int) $me->pos_x,
            'y'     => (int) $me->pos_y,
            'w'     => (int) $me->width,
            'h'     => (int) $me->height
        ];
    }

    // 5. Compilar Snapshot
    $snapshot = [
        'last_updated' => current_time('mysql'),
        'event_id'     => $config->event_id,
        'stats'        => $stats,
        'filters'      => $aggregates,
        'rows'         => array_merge($rows, $partial_rows),
        'map'          => $map_data
    ];

    // 5. Guardar en carpeta de uploads
    $upload_dir = wp_upload_dir();
    $base_dir   = $upload_dir['basedir'] . '/tbp-snapshots';
    if ( ! file_exists($base_dir) ) {
        wp_mkdir_p($base_dir);
        file_put_contents($base_dir . '/index.php', '<?php // Silence');
    }

    $file_path = $base_dir . '/event-' . $config->event_id . '.json';
    file_put_contents( $file_path, json_encode($snapshot) );

    return true;
}

/**
 * AJAX: Regenerar Snapshot manualmente
 */
add_action( 'wp_ajax_tbp_asientos_regenerate_snapshot', 'tbp_asientos_ajax_regenerate_snapshot' );
function tbp_asientos_ajax_regenerate_snapshot() {
    check_ajax_referer( 'tbp_asientos_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error('Sin permisos.');
    $config_id = (int)($_POST['config_id'] ?? 0);
    if (!$config_id) wp_send_json_error('ID inválido.');
    
    if (tbp_asientos_generate_public_snapshot($config_id)) {
        wp_send_json_success('Snapshot actualizado correctamente.');
    } else {
        wp_send_json_error('Error al generar snapshot.');
    }
}
