<?php
/**
 * Database Table Creation for TBP Actividades
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tbp_actividades_create_db_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $table_votos = $wpdb->prefix . 'tbp_premiaciones_votos';

    $sql_logs = "CREATE TABLE $table_logs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        staff_id bigint(20) NOT NULL,
        order_id bigint(20) NOT NULL,
        rifa_id bigint(20) NOT NULL,
        amount int(11) NOT NULL,
        type varchar(20) DEFAULT 'physical' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $sql_votos = "CREATE TABLE $table_votos (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        premiacion_id bigint(20) NOT NULL,
        event_id bigint(20) NOT NULL,
        category_id varchar(50) NOT NULL,
        nominee_name varchar(255) NOT NULL,
        user_id bigint(20) NOT NULL,
        order_id bigint(20) NOT NULL,
        group_name varchar(100) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        KEY user_event (user_id, event_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_logs );
    dbDelta( $sql_votos );
}
