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

    // =========================================================
    // MÓDULO: ASIGNACIÓN DE ASIENTOS
    // =========================================================
    $table_seat_configs     = $wpdb->prefix . 'tbp_seat_configurations';
    $table_seat_tables      = $wpdb->prefix . 'tbp_seat_tables';
    $table_seat_assignments = $wpdb->prefix . 'tbp_seat_assignments';
    $table_seat_group_zones = $wpdb->prefix . 'tbp_seat_group_zones';

    // Tabla 1: Configuraciones de asignación (una por evento)
    $sql_seat_configs = "CREATE TABLE $table_seat_configs (
        id            bigint(20)   NOT NULL AUTO_INCREMENT,
        event_id      bigint(20)   NOT NULL,
        proveedor_id  bigint(20)   NOT NULL DEFAULT 0,
        nombre        varchar(100) NOT NULL,
        tipo          varchar(20)  NOT NULL DEFAULT 'grupal',
        group_field   varchar(100) NOT NULL DEFAULT 'Grupo',
        status        varchar(20)  NOT NULL DEFAULT 'draft',
        zonas_config  longtext     NOT NULL DEFAULT '',
        created_at    datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_id (event_id),
        KEY status   (status)
    ) $charset_collate;";

    // Tabla 2: Mesas generadas por zona dentro de una configuración
    $sql_seat_tables = "CREATE TABLE $table_seat_tables (
        id               bigint(20)   NOT NULL AUTO_INCREMENT,
        config_id        bigint(20)   NOT NULL,
        zona             varchar(100) NOT NULL,
        numero           varchar(10)  NOT NULL,
        capacidad        tinyint(3)   NOT NULL DEFAULT 10,
        capacidad_usada  tinyint(3)   NOT NULL DEFAULT 0,
        tipo             varchar(20)  NOT NULL DEFAULT 'round', -- round, rectangular, square, cocktail, stool
        etiqueta_bloqueo varchar(100) NOT NULL DEFAULT '',
        pos_x            int(11)      NOT NULL DEFAULT 0,
        pos_y            int(11)      NOT NULL DEFAULT 0,
        width            int(11)      NOT NULL DEFAULT 60,
        height           int(11)      NOT NULL DEFAULT 60,
        color            varchar(20)  NOT NULL DEFAULT '#2271b1',
        PRIMARY KEY  (id),
        KEY  config_id        (config_id),
        KEY  zona             (zona),
        UNIQUE KEY config_zona_num (config_id, zona(80), numero)
    ) $charset_collate;";

    // Tabla 3: Asignaciones (quién va en qué mesa)
    $sql_seat_assignments = "CREATE TABLE $table_seat_assignments (
        id          bigint(20)   NOT NULL AUTO_INCREMENT,
        config_id   bigint(20)   NOT NULL,
        mesa_id     bigint(20)   NOT NULL,
        order_id    bigint(20)   NOT NULL,
        grupo       varchar(100) NOT NULL DEFAULT '',
        cantidad    tinyint(3)   NOT NULL DEFAULT 1,
        nombre      varchar(200) NOT NULL DEFAULT '',
        apellidos   varchar(200) NOT NULL DEFAULT '',
        status      varchar(20)  NOT NULL DEFAULT 'assigned',
        created_at  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY  config_id  (config_id),
        KEY  mesa_id    (mesa_id),
        KEY  order_id   (order_id),
        KEY  grupo      (grupo),
        UNIQUE KEY order_config (order_id, config_id)
    ) $charset_collate;";

    // Tabla 4: Override de zona por grupo (asignación manual de grupo → zona)
    $sql_seat_group_zones = "CREATE TABLE $table_seat_group_zones (
        id        bigint(20)   NOT NULL AUTO_INCREMENT,
        config_id bigint(20)   NOT NULL,
        grupo     varchar(100) NOT NULL,
        zona      varchar(100) NOT NULL,
        PRIMARY KEY  (id),
        KEY config_grupo (config_id, grupo(80)),
        UNIQUE KEY config_grupo_unique (config_id, grupo(80))
    ) $charset_collate;";

    // Tabla 5: Elementos del plano (Escenarios, Pistas, Baños, etc.)
    $table_seat_elements = $wpdb->prefix . 'tbp_seat_elements';
    $sql_seat_elements = "CREATE TABLE $table_seat_elements (
        id          bigint(20)   NOT NULL AUTO_INCREMENT,
        config_id   bigint(20)   NOT NULL,
        tipo        varchar(50)  NOT NULL, -- stage, dance_floor, exit, wc, bar
        label       varchar(255) NOT NULL DEFAULT '',
        pos_x       int(11)      NOT NULL DEFAULT 0,
        pos_y       int(11)      NOT NULL DEFAULT 0,
        width       int(11)      NOT NULL DEFAULT 100,
        height      int(11)      NOT NULL DEFAULT 100,
        color       varchar(20)  NOT NULL DEFAULT '#334155',
        PRIMARY KEY  (id),
        KEY config_id (config_id)
    ) $charset_collate;";

    dbDelta( $sql_seat_configs );
    dbDelta( $sql_seat_tables );
    dbDelta( $sql_seat_assignments );
    dbDelta( $sql_seat_group_zones );
    dbDelta( $sql_seat_elements );
}
