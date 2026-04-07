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
    $table_mkt_lists = $wpdb->prefix . 'tbp_marketing_lists';
    $table_mkt_contacts = $wpdb->prefix . 'tbp_marketing_contacts';
    $table_mkt_campaigns = $wpdb->prefix . 'tbp_marketing_campaigns';

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

    $sql_mkt_lists = "CREATE TABLE $sql_mkt_lists (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $sql_mkt_contacts = "CREATE TABLE $table_mkt_contacts (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        list_id bigint(20) NOT NULL,
        full_name varchar(255) NOT NULL,
        email varchar(150) NOT NULL,
        phone varchar(30) DEFAULT '',
        meta_data longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        KEY list_id (list_id),
        KEY email (email)
    ) $charset_collate;";

    $sql_mkt_campaigns = "CREATE TABLE $table_mkt_campaigns (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        list_id bigint(20) NOT NULL,
        subject varchar(255) NOT NULL,
        message longtext NOT NULL,
        template_id varchar(50) DEFAULT '',
        stats_opened int(11) DEFAULT 0,
        stats_clicked int(11) DEFAULT 0,
        status varchar(20) DEFAULT 'sent',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        KEY list_id (list_id)
    ) $charset_collate;";

    $sql_mkt_stats = "CREATE TABLE $wpdb->prefix" . "tbp_marketing_stats (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        campaign_id bigint(20) NOT NULL,
        contact_id bigint(20) DEFAULT 0,
        event_type varchar(20) NOT NULL,
        user_agent text DEFAULT '',
        ip_address varchar(100) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        KEY campaign_event (campaign_id, event_type)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_logs );
    dbDelta( $sql_votos );
    dbDelta( $sql_mkt_lists );
    dbDelta( $sql_mkt_contacts );
    dbDelta( $sql_mkt_campaigns );
    dbDelta( $sql_mkt_stats );
}
