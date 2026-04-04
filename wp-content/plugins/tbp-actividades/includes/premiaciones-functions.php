<?php
/**
 * Helper Functions for Premiaciones
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Parses the raw nominees list into an array grouped by department.
 */
function tbp_actividades_normalize_group_name( $name ) {
    if ( empty( $name ) ) return '';
    $name = trim( strval( $name ) );
    $name = mb_strtolower( $name, 'UTF-8' );
    // Unify "065" and "65" by removing leading zeros in numeric sequences
    $name = preg_replace( '/\b0+([0-9]+)\b/', '$1', $name );
    return $name;
}

/**
 * Parses the raw nominees list into an array grouped by department.
 */
function tbp_actividades_get_parsed_nominees( $premiacion_id ) {
    $raw = get_post_meta( $premiacion_id, '_tbp_nominees_raw', true );
    if ( empty( $raw ) ) return [];

    $lines = explode( "\n", str_replace( "\r", "", $raw ) );
    $nominees = [];

    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( empty( $line ) ) continue;

        $parts = explode( '|', $line );
        if ( count( $parts ) >= 2 ) {
            $name = trim( $parts[0] );
            $group = tbp_actividades_normalize_group_name( $parts[1] );
            $nominees[$group][] = $name;
        }
    }

    return $nominees;
}

/**
 * Gets the user's group/department from their attendee metadata for a specific order.
 */
function tbp_actividades_get_user_group( $order_id, $premiacion_id ) {
    $group_field_name = get_post_meta( $premiacion_id, '_tbp_group_field', true );
    if ( empty( $group_field_name ) ) return false;

    $order = wc_get_order( $order_id );
    if ( ! $order ) return false;

    // Use existing function to get attendee meta
    if ( ! function_exists( 'tbp_actividades_get_order_attendees_meta' ) ) {
        return false;
    }

    $attendees = tbp_actividades_get_order_attendees_meta( $order_id, $order );
    if ( empty( $attendees ) ) return false;

    $normalized_field_name = strtolower( sanitize_title( $group_field_name ) );

    foreach ( $attendees as $source_id => $meta_groups ) {
        // Normalization for ET+
        if ( ! empty( $meta_groups ) ) {
            reset( $meta_groups );
            $first_key = key( $meta_groups );
            if ( ! is_numeric( $first_key ) && $first_key !== 'Datos' ) {
                $meta_groups = array( 'Datos' => $meta_groups );
            }
        }

        foreach ( $meta_groups as $guest_index => $guest_data ) {
            foreach ( $guest_data as $field_key => $field_value ) {
                $label = is_array( $field_value ) ? ( $field_value['label'] ?? '' ) : $field_key;
                $value = is_array( $field_value ) ? ( $field_value['value'] ?? '' ) : $field_value;

                if ( strtolower( sanitize_title( $label ) ) === $normalized_field_name || strtolower( sanitize_title( $field_key ) ) === $normalized_field_name ) {
                    return tbp_actividades_normalize_group_name( $value );
                }
            }
        }
    }

    return false;
}

/**
 * Checks if a user has already voted for a specific event.
 */
function tbp_actividades_has_user_voted( $user_id, $event_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'tbp_premiaciones_votos';
    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND event_id = %d",
        $user_id,
        $event_id
    ) );
    if (current_user_can('manage_options')) {
        return false;
    }
    return $count > 0;
}

/**
 * Gets voting results for a specific premiación and group.
 */
function tbp_actividades_get_voting_results( $premiacion_id, $group_name ) {
    global $wpdb;
    $table = $wpdb->prefix . 'tbp_premiaciones_votos';
    
    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT category_id, nominee_name, COUNT(*) as vote_count 
         FROM $table 
         WHERE premiacion_id = %d AND group_name = %s 
         GROUP BY category_id, nominee_name",
        $premiacion_id,
        $group_name
    ) );

    $formatted = [];
    foreach ( $results as $row ) {
        $formatted[$row->category_id][$row->nominee_name] = intval( $row->vote_count );
    }

    return $formatted;
}
