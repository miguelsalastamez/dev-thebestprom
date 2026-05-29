<?php
/**
 * Custom Post Type and Taxonomies Registration for TBP Soporte
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hooks
add_action( 'init', 'tbp_soporte_register_cpt' );
add_action( 'init', 'tbp_soporte_register_taxonomy' );

/**
 * Register Custom Post Type: tbp_ticket
 */
function tbp_soporte_register_cpt() {
    $labels = array(
        'name'               => _x( 'Tickets de Soporte', 'post type general name', 'tbp-soporte' ),
        'singular_name'      => _x( 'Ticket de Soporte', 'post type singular name', 'tbp-soporte' ),
        'menu_name'          => _x( 'Soporte (Tickets)', 'admin menu', 'tbp-soporte' ),
        'add_new'            => _x( 'Añadir Nuevo', 'ticket', 'tbp-soporte' ),
        'add_new_item'       => __( 'Añadir Nuevo Ticket', 'tbp-soporte' ),
        'edit_item'          => __( 'Editar Ticket', 'tbp-soporte' ),
        'new_item'           => __( 'Nuevo Ticket', 'tbp-soporte' ),
        'view_item'          => __( 'Ver Ticket', 'tbp-soporte' ),
        'search_items'       => __( 'Buscar Tickets', 'tbp-soporte' ),
        'not_found'          => __( 'No se encontraron tickets', 'tbp-soporte' ),
        'not_found_in_trash' => __( 'No hay tickets en la papelera', 'tbp-soporte' )
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => false, // We will show it under our custom submenu
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'tbp-ticket' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title', 'editor', 'comments', 'author' ),
        'show_in_rest'       => false,
    );

    register_post_type( 'tbp_ticket', $args );
}

/**
 * Register Custom Taxonomy: tbp_ticket_category
 */
function tbp_soporte_register_taxonomy() {
    $labels = array(
        'name'              => _x( 'Categorías de Soporte', 'taxonomy general name', 'tbp-soporte' ),
        'singular_name'     => _x( 'Categoría de Soporte', 'taxonomy singular name', 'tbp-soporte' ),
        'search_items'      => __( 'Buscar Categorías', 'tbp-soporte' ),
        'all_items'         => __( 'Todas las Categorías', 'tbp-soporte' ),
        'parent_item'       => __( 'Categoría Padre', 'tbp-soporte' ),
        'parent_item_colon' => __( 'Categoría Padre:', 'tbp-soporte' ),
        'edit_item'         => __( 'Editar Categoría', 'tbp-soporte' ),
        'update_item'       => __( 'Actualizar Categoría', 'tbp-soporte' ),
        'add_new_item'      => __( 'Añadir Nueva Categoría', 'tbp-soporte' ),
        'new_item_name'     => __( 'Nombre de la Nueva Categoría', 'tbp-soporte' ),
        'menu_name'         => __( 'Categorías', 'tbp-soporte' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'tbp-ticket-category' ),
        'show_in_rest'      => false,
    );

    register_taxonomy( 'tbp_ticket_category', array( 'tbp_ticket' ), $args );
}

/**
 * Seed default categories on activation
 */
function tbp_soporte_create_default_categories() {
    $categories = array(
        'Entregas' => 'entregas',
        'Pagos y Administración' => 'pagos-y-administracion',
        'Soporte Técnico' => 'soporte-tecnico'
    );

    foreach ( $categories as $name => $slug ) {
        if ( ! term_exists( $slug, 'tbp_ticket_category' ) ) {
            wp_insert_term( $name, 'tbp_ticket_category', array(
                'slug' => $slug
            ) );
        }
    }
}
