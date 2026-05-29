<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Field Debugger - Safe Version v2.0
 * Restrictive cleaning focused ONLY on attendee metadata.
 */

function wcmp_render_field_debugger_page() {
    global $wpdb;
    
    $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
    $target_label = isset($_GET['target_label']) ? sanitize_text_field($_GET['target_label']) : '';
    
    echo '<div class="wrap wcmp-cleaner-wrap">';
    echo '<h1>Depurador de Campos (Asistentes)</h1>';
    echo '<p>Esta herramienta solo permite corregir datos en los formularios de los asistentes (ej: Grupo, Nombre).</p>';

    // --- ACCIÓN: EJECUTAR LIMPIEZA SEGURA ---
    if ( isset($_POST['wcmp_execute_cleanup']) && !empty($target_label) ) {
        check_admin_referer('wcmp_cleanup_action');
        
        $mapping = $_POST['mapping'] ?? [];
        $affected_count = 0;
        
        // SOLO procesamos _tribe_tickets_meta (Cero riesgo para el resto del sistema)
        $results = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_tribe_tickets_meta'");
        
        foreach ( $results as $row ) {
            $data = maybe_unserialize($row->meta_value);
            if ( ! is_array($data) ) continue;
            
            $changed = false;
            foreach ( $data as $field_id => &$field_data ) {
                $label = is_array($field_data) ? ($field_data['label'] ?? $field_id) : $field_id;
                
                // Comparación robusta (Case-Insensitive)
                if ( strtolower(trim($label)) === strtolower(trim($target_label)) ) {
                    $current_val = is_array($field_data) ? ($field_data['value'] ?? '') : $field_data;
                    $normalized_key = md5(trim($current_val));
                    
                    if ( isset($mapping[$normalized_key]) ) {
                        $new_val = sanitize_text_field($mapping[$normalized_key]);
                        if ( trim($new_val) !== trim($current_val) ) {
                            if ( is_array($field_data) ) { $field_data['value'] = $new_val; } else { $field_data = $new_val; }
                            $changed = true;
                        }
                    }
                }
            }
            
            if ( $changed ) {
                update_post_meta($row->post_id, '_tribe_tickets_meta', $data);
                $affected_count++;
            }
        }
        echo '<div class="updated"><p>✅ Se han actualizado ' . $affected_count . ' asistentes correctamente.</p></div>';
        $step = 1;
    }

    // --- PASO 1: SELECCIÓN DE ETIQUETA ---
    if ( $step === 1 ) {
        echo '<h3>1. Selecciona el campo del asistente que deseas corregir</h3>';
        
        // Escaneo profundo SOLO de _tribe_tickets_meta
        $all_meta = $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_tribe_tickets_meta'");
        $found_labels = [];
        foreach ( $all_meta as $m ) {
            $data = maybe_unserialize($m->meta_value);
            if ( is_array($data) ) {
                foreach ( $data as $fid => $fd ) {
                    $lbl = is_array($fd) ? ($fd['label'] ?? $fid) : $fid;
                    if ( !empty($lbl) ) {
                        $norm = strtolower(trim($lbl));
                        if ( !isset($found_labels[$norm]) ) $found_labels[$norm] = $lbl;
                    }
                }
            }
        }
        asort($found_labels);

        if ( empty($found_labels) ) {
            echo '<p>No se encontraron campos de asistentes para limpiar.</p>';
        } else {
            echo '<form method="get" action="">';
            echo '<input type="hidden" name="page" value="wcmp-field-debugger">';
            echo '<input type="hidden" name="step" value="2">';
            echo '<select name="target_label" style="min-width:300px;">';
            foreach ( $found_labels as $norm => $display ) {
                echo '<option value="' . esc_attr($display) . '">' . esc_html($display) . '</option>';
            }
            echo '</select> ';
            echo '<button type="submit" class="button button-primary">Analizar Valores</button>';
            echo '</form>';
        }

        // Buscador Enfocado (Solo para encontrar en qué etiqueta está un valor)
        echo '<div style="margin-top:40px; padding:15px; background:#f0f0f1; border-radius:4px;">';
        echo '<h4>¿No encuentras el campo? Busca qué etiqueta tiene el valor sucio</h4>';
        echo '<form method="get" action="">';
        echo '<input type="hidden" name="page" value="wcmp-field-debugger">';
        echo '<input type="hidden" name="step" value="1">';
        echo '<input type="text" name="find_text" placeholder="Ej: GRUPO 69" class="regular-text" value="' . esc_attr($_GET['find_text'] ?? '') . '"> ';
        echo '<button type="submit" class="button">Buscar Etiqueta</button>';
        echo '</form>';

        if ( !empty($_GET['find_text']) ) {
            $txt = sanitize_text_field($_GET['find_text']);
            echo '<p>Resultados para "'.esc_html($txt).'":</p><ul>';
            foreach ( $all_meta as $m ) {
                $data = maybe_unserialize($m->meta_value);
                if ( is_array($data) ) {
                    foreach ( $data as $fid => $fd ) {
                        $val = is_array($fd) ? ($fd['value'] ?? '') : $fd;
                        if ( stripos($val, $txt) !== false ) {
                            $lbl = is_array($fd) ? ($fd['label'] ?? $fid) : $fid;
                            echo '<li>Etiqueta encontrada: <strong>' . esc_html($lbl) . '</strong> <a href="?page=wcmp-field-debugger&step=2&target_label='.urlencode($lbl).'">[Analizar este campo]</a></li>';
                        }
                    }
                }
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    // --- PASO 2: MAPEO DE VALORES ---
    if ( $step === 2 && !empty($target_label) ) {
        echo '<h3>2. Corrigiendo campo: "<strong>' . esc_html($target_label) . '</strong>"</h3>';
        
        $results = $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_tribe_tickets_meta'");
        $value_counts = [];
        
        foreach ( $results as $row ) {
            $data = maybe_unserialize($row->meta_value);
            if ( ! is_array($data) ) continue;
            foreach ( $data as $fid => $fd ) {
                $lbl = is_array($fd) ? ($fd['label'] ?? $fid) : $fid;
                if ( strtolower(trim($lbl)) === strtolower(trim($target_label)) ) {
                    $val = is_array($fd) ? ($fd['value'] ?? '') : $fd;
                    $val_clean = trim($val);
                    if ( !isset($value_counts[$val_clean]) ) $value_counts[$val_clean] = 0;
                    $value_counts[$val_clean]++;
                }
            }
        }
        arsort($value_counts);

        // Identificar sospechosos (contienen algo que no sea número)
        $dirty_count = 0;
        foreach($value_counts as $val => $qty) {
            if ( preg_match('/[^0-9]/', $val) ) $dirty_count++;
        }

        if ( $dirty_count > 0 ) {
            echo '<div style="background:#fff2f2; border:1px solid #d63638; padding:15px; margin-bottom:20px; border-radius:4px;">';
            echo '<h4 style="margin-top:0; color:#d63638;">⚠️ Se han detectado ' . $dirty_count . ' valores con "basura" (letras o símbolos).</h4>';
            echo '<p>¿Quieres limpiar todos estos registros automáticamente extrayendo solo los números?</p>';
            echo '<button type="button" class="button button-primary" onclick="jQuery(\'.is-dirty-input\').each(function(){ jQuery(this).val(jQuery(this).attr(\'data-suggest\')); }); jQuery(\'#submit-cleanup\').click();" style="background:#d63638; border-color:#d63638;">Auto-Limpiar Todo lo Suspechoso</button>';
            echo '</div>';
        }

        echo '<form method="post" action="">';
        wp_nonce_field('wcmp_cleanup_action');
        echo '<table class="wp-list-table widefat fixed striped" style="margin-bottom:20px;">';
        echo '<thead><tr><th>Valor actual en DB</th><th>Asistentes</th><th>Nuevo valor corregido</th></tr></thead>';
        echo '<tbody>';
        foreach ( $value_counts as $val => $count ) {
            $suggestion = preg_replace('/[^0-9]/', '', $val);
            if ( empty($suggestion) ) $suggestion = $val;
            
            $is_dirty = (preg_match('/[^0-9]/', $val));
            $row_style = $is_dirty ? 'background:#fff2f2;' : '';
            $input_class = $is_dirty ? 'is-dirty-input' : '';

            $input_id = md5(trim($val));
            echo '<tr style="' . $row_style . '">';
            echo '<td><code>' . esc_html($val) . '</code>' . ($is_dirty ? ' <span style="color:#d63638; font-size:10px;">[SUCIO]</span>' : '') . '</td>';
            echo '<td>' . $count . '</td>';
            echo '<td><input type="text" name="mapping[' . $input_id . ']" value="' . esc_attr($suggestion) . '" class="regular-text ' . $input_class . '" data-suggest="' . esc_attr($suggestion) . '"></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<button type="submit" name="wcmp_execute_cleanup" id="submit-cleanup" class="button button-primary" onclick="return confirm(\'¿Confirmas que deseas aplicar estas correcciones?\');">Aplicar Cambios</button>';
        echo ' <a href="?page=wcmp-field-debugger" class="button">Cancelar</a>';
        echo '</form>';
    }

    echo '</div>';
}
