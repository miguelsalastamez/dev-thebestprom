<?php
/**
 * Admin Reports & Alerts for TBP Egresos (Master Dashboard)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the Report Page
 */
function tbp_egresos_reportes_page_content() {
    // 1. Get Filters
    $f_event      = isset( $_GET['f_event'] ) ? intval( $_GET['f_event'] ) : 0;
    $f_provider   = isset( $_GET['f_provider'] ) ? intval( $_GET['f_provider'] ) : 0;
    $f_category   = isset( $_GET['f_category'] ) ? sanitize_text_field( $_GET['f_category'] ) : '';
    $f_type       = isset( $_GET['f_type'] ) ? sanitize_text_field( $_GET['f_type'] ) : ''; // 'event' or 'opera'
    $f_status     = isset( $_GET['f_status'] ) ? sanitize_text_field( $_GET['f_status'] ) : ''; // 'pending' or 'paid'
    $f_date_from  = isset( $_GET['f_date_from'] ) ? sanitize_text_field( $_GET['f_date_from'] ) : '';
    $f_date_to    = isset( $_GET['f_date_to'] ) ? sanitize_text_field( $_GET['f_date_to'] ) : '';
    $f_method     = isset( $_GET['f_method'] ) ? sanitize_text_field( $_GET['f_method'] ) : '';

    // 2. Build Query
    $args = array(
        'post_type'      => 'tbp_orden_compra',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array( 'relation' => 'AND' )
    );

    if ( $f_event ) $args['meta_query'][] = array( 'key' => '_tbp_event_id', 'value' => $f_event );
    if ( $f_provider ) $args['meta_query'][] = array( 'key' => '_tbp_proveedor_id', 'value' => $f_provider );
    if ( $f_category ) $args['meta_query'][] = array( 'key' => '_tbp_category', 'value' => $f_category );
    
    if ( $f_type === 'event' ) {
        $args['meta_query'][] = array( 'key' => '_tbp_event_id', 'value' => 0, 'compare' => '>' );
    } elseif ( $f_type === 'opera' ) {
        $args['meta_query'][] = array( 'key' => '_tbp_event_id', 'value' => array(0, ''), 'compare' => 'IN' );
    }

    if ( $f_date_from && $f_date_to ) {
        $args['date_query'] = array(
            array(
                'after'     => $f_date_from,
                'before'    => $f_date_to,
                'inclusive' => true,
            ),
        );
    }

    $has_filter = ( !empty($_GET['f_event']) || !empty($_GET['f_provider']) || !empty($_GET['f_category']) || !empty($_GET['f_date_from']) || !empty($_GET['f_status']) );

    $pos = array();
    if ( $has_filter ) {
        $pos = get_posts( $args );
    }

    // 3. Totals Calculation & Filter Logic for Status/Balance
    $filtered_pos = [];
    $total_proyectada = 0;
    $total_autorizada = 0;
    $total_pagado = 0;

    foreach ( $pos as $po ) {
        $proj = floatval( get_post_meta( $po->ID, '_tbp_cantidad_proyectada', true ) );
        $auth = floatval( get_post_meta( $po->ID, '_tbp_cantidad_autorizada', true ) );
        $paid = floatval( get_post_meta( $po->ID, '_tbp_total_pagado', true ) );
        $saldo = $auth - $paid;

        // Apply Status Filter
        if ( $f_status === 'pending' && $saldo <= 0.01 ) continue;
        if ( $f_status === 'paid' && $saldo > 0.01 ) continue;

        $filtered_pos[] = $po;
        $total_proyectada += $proj;
        $total_autorizada += $auth;
        $total_pagado += $paid;
    }

    $total_deuda = $total_autorizada - $total_pagado;
    
    // Pieces Totals
    $total_piezas_proj = 0;
    $total_piezas_auth = 0;
    foreach ( $filtered_pos as $po ) {
        $total_piezas_proj += floatval( get_post_meta( $po->ID, '_tbp_piezas_proyectadas', true ) );
        $total_piezas_auth += floatval( get_post_meta( $po->ID, '_tbp_piezas_autorizadas', true ) );
    }

    // 4. Render UI
    ?>
    <div class="wrap tbp-reports-wrap">
        <h1 class="wp-heading-inline">📊 Reporte de Egresos Maestro (360°)</h1>
        <hr class="wp-header-end">

        <!-- DASHBOARD CARDS -->
        <div class="tbp-dashboard-cards">
            <div class="tbp-card tbp-card-blue">
                <label>Total Proyectado</label>
                <div class="amount"><?php echo wc_price( $total_proyectada ); ?></div>
                <small>Compromiso Total | <strong>Piezas: <?php echo number_format($total_piezas_proj, 0); ?></strong></small>
            </div>
            <div class="tbp-card tbp-card-cyan">
                <label>Total Autorizado</label>
                <div class="amount"><?php echo wc_price( $total_autorizada ); ?></div>
                <small>Presupuesto Liberado | <strong>Piezas: <?php echo number_format($total_piezas_auth, 0); ?></strong></small>
            </div>
            <div class="tbp-card tbp-card-green">
                <label>Total Pagado</label>
                <div class="amount"><?php echo wc_price( $total_pagado ); ?></div>
                <small>Flujo de Caja Real</small>
            </div>
            <div class="tbp-card tbp-card-red">
                <label>Por Pagar (Deuda)</label>
                <div class="amount"><?php echo wc_price( $total_deuda ); ?></div>
                <small>Saldos de OCs Autorizadas</small>
            </div>
        </div>

        <!-- FILTERS PANEL -->
        <div class="tbp-filters-panel">
            <form method="get">
                <input type="hidden" name="page" value="tbp-egresos-reportes">
                <div class="filters-grid">
                    <div>
                        <label>Evento (Tribe Events)</label>
                        <select name="f_event" class="tbp-select2">
                            <option value="">-- Todos los Eventos --</option>
                            <?php 
                            $events = get_posts( array( 'post_type' => 'tribe_events', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
                            foreach ( $events as $ev ) echo '<option value="'.$ev->ID.'" '.selected($f_event, $ev->ID, false).'>'.$ev->post_title.'</option>';
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Proveedor</label>
                        <select name="f_provider" class="tbp-select2">
                            <option value="">-- Todos los Proveedores --</option>
                            <?php 
                            $provs = get_posts( array( 'post_type' => 'tbp_proveedor', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
                            foreach ( $provs as $pr ) echo '<option value="'.$pr->ID.'" '.selected($f_provider, $pr->ID, false).'>'.$pr->post_title.'</option>';
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Categoría</label>
                        <select name="f_category">
                            <option value="">-- Todas las Categorías --</option>
                            <option value="Administrativo" <?php selected($f_category, 'Administrativo'); ?>>Administrativo</option>
                            <option value="Marketing" <?php selected($f_category, 'Marketing'); ?>>Marketing</option>
                            <option value="Nómina" <?php selected($f_category, 'Nómina'); ?>>Nómina</option>
                            <option value="Operaciones" <?php selected($f_category, 'Operaciones'); ?>>Operaciones</option>
                            <option value="Servicios" <?php selected($f_category, 'Servicios'); ?>>Servicios</option>
                        </select>
                    </div>
                    <div>
                        <label>Tipo de Gasto</label>
                        <select name="f_type">
                            <option value="">-- Todos los Gastos --</option>
                            <option value="event" <?php selected($f_type, 'event'); ?>>Relacionados a Eventos</option>
                            <option value="opera" <?php selected($f_type, 'opera'); ?>>Gastos Operativos (Grales)</option>
                        </select>
                    </div>
                    <div>
                        <label>Rango de Fechas (OC)</label>
                        <div style="display:flex; gap:5px;">
                            <input type="date" name="f_date_from" value="<?php echo $f_date_from; ?>" style="flex:1;">
                            <input type="date" name="f_date_to" value="<?php echo $f_date_to; ?>" style="flex:1;">
                        </div>
                    </div>
                    <div>
                        <label>Estatus de Pago</label>
                        <select name="f_status">
                            <option value="">-- Todos los Estatus --</option>
                            <option value="pending" <?php selected($f_status, 'pending'); ?>>Con Saldo Pendiente</option>
                            <option value="paid" <?php selected($f_status, 'paid'); ?>>Liquidados al 100%</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="button button-primary">Filtrar Egresos</button>
                    <a href="<?php echo admin_url('admin.php?page=tbp-egresos-reportes'); ?>" class="button">Limpiar</a>
                    <button type="button" class="button button-secondary" onclick="window.print();">Imprimir Reporte</button>
                </div>
            </form>
        </div>

        <!-- RESULTS TABLE -->
        <table class="wp-list-table widefat fixed striped tbp-reports-table">
            <thead>
                <tr>
                    <th width="80">ID OC</th>
                    <th width="150">PROVEEDOR</th>
                    <th>CONCEPTO / RELACIÓN</th>
                    <th width="100">PRÓX. PAGO</th>
                    <th width="80" style="text-align:right;">PIEZAS</th>
                    <th width="120">AUTORIZADO</th>
                    <th width="120">PAGADO</th>
                    <th width="120">SALDO</th>
                    <th width="80" style="text-align:center;">ESTADO</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty($filtered_pos) ) : ?>
                    <tr><td colspan="8">No se encontraron egresos con los filtros aplicados.</td></tr>
                <?php else : ?>
                    <?php foreach ( $filtered_pos as $po ) : 
                        $ev_id = get_post_meta( $po->ID, '_tbp_event_id', true );
                        $pr_id = get_post_meta( $po->ID, '_tbp_proveedor_id', true );
                        $cat = get_post_meta( $po->ID, '_tbp_category', true );
                        $auth = floatval( get_post_meta( $po->ID, '_tbp_cantidad_autorizada', true ) );
                        $pzs  = floatval( get_post_meta( $po->ID, '_tbp_piezas_autorizadas', true ) );
                        $paid = floatval( get_post_meta( $po->ID, '_tbp_total_pagado', true ) );
                        $next = get_post_meta( $po->ID, '_tbp_next_renewal', true );
                        $saldo = $auth - $paid;
                        $exec_pct = ( $auth > 0 ) ? ($paid / $auth) * 100 : 0;
                        ?>
                        <tr>
                            <td>#<?php echo $po->ID; ?></td>
                            <td><strong><?php echo get_the_title($pr_id); ?></strong></td>
                            <td>
                                <div><?php echo $po->post_title; ?></div>
                                <div style="font-size:10px; color:#888;">
                                    <?php if ($ev_id) : ?>
                                        <span class="dashicons dashicons-calendar-alt" style="font-size:12px; width:12px; height:12px;"></span> <?php echo get_the_title($ev_id); ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-admin-generic" style="font-size:12px; width:12px; height:12px;"></span> Operación / <?php echo $cat; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo $next ? date('d/m/Y', strtotime($next)) : '--'; ?></td>
                            <td style="text-align:right; font-weight:bold;">
                                <?php echo number_format($pzs, 0); ?>
                                <?php if ( $auth > 0 && $pzs <= 0 ) : ?>
                                    <div style="font-size:7px; color:#d63638; text-transform:uppercase;">Sync Req</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo wc_price($auth); ?></td>
                            <td><?php echo wc_price($paid); ?></td>
                            <td style="font-weight:bold; <?php echo ($saldo > 0.01) ? 'color:#c62828;' : 'color:#2e7d32;'; ?>">
                                <?php echo wc_price($saldo); ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ( $exec_pct >= 95 ) : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color:#2e7d32;" title="Completado"></span>
                                <?php elseif ( $exec_pct > 80 ) : ?>
                                    <span class="dashicons dashicons-warning" style="color:#f9a825;" title="Cerca del límite"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-chart-line" style="color:#2271b1;" title="En ejecución"></span>
                                <?php endif; ?>
                                <br/>
                                <a href="<?php echo get_edit_post_link($po->ID); ?>" class="button button-small" style="font-size:9px; height:20px; line-height:18px; margin-top:5px;">VER</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f0f0f1; font-weight:bold;">
                    <td colspan="4" style="text-align:right;">TOTALES DEL REPORTE:</td>
                    <td style="text-align:right;"><?php echo number_format($total_piezas_auth, 0); ?></td>
                    <td><?php echo wc_price($total_autorizada); ?></td>
                    <td><?php echo wc_price($total_pagado); ?></td>
                    <td colspan="2"><?php echo wc_price($total_deuda); ?></td>
                </tr>
            </tfoot>
        </table>

        <style>
            .tbp-reports-wrap { padding: 10px 20px 0 0; }
            .tbp-dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .tbp-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #ccc; }
            .tbp-card label { display: block; font-weight: bold; color: #50575e; margin-bottom: 10px; text-transform: uppercase; font-size: 11px; }
            .tbp-card .amount { font-size: 24px; font-weight: bold; color: #1d2327; margin-bottom: 5px; }
            .tbp-card small { color: #888; font-size: 11px; }
            
            .tbp-card-blue { border-left-color: #2271b1; }
            .tbp-card-cyan { border-left-color: #72aee6; }
            .tbp-card-green { border-left-color: #00a32a; }
            .tbp-card-red { border-left-color: #d63638; }

            .tbp-filters-panel { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ccd0d4; }
            .filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
            .filters-grid label { display: block; font-weight: bold; margin-bottom: 8px; color: #1d2327; }
            .filters-grid select, .filters-grid input { width: 100% !important; }
            
            .filter-actions { border-top: 1px solid #eee; padding-top: 20px; text-align: right; gap: 10px; display: flex; justify-content: flex-end; }
            
            .tbp-reports-table th { background: #f0f0f1; font-weight: bold; text-transform: uppercase; font-size: 11px; }
            .tbp-reports-table td { vertical-align: middle; }
            
            @media print {
                .tbp-filters-panel, .wp-heading-inline, #adminmenumain, #wpadminbar, .filter-actions { display: none !important; }
                #wpcontent { margin-left: 0 !important; }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($){
            if ( $.fn.select2 ) {
                $('.tbp-select2').select2({ width: '100%' });
            }
        });
        </script>
    </div>
    <?php
}

// Update the menu callback in tbp-egresos.php
add_filter( 'tbp_egresos_reportes_callback', function() { return 'tbp_egresos_reportes_page_content'; } );
