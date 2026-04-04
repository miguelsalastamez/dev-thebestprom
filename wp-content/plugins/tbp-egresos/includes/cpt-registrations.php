<?php
/**
 * Custom Post Types Registrations for TBP Egresos
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'tbp_egresos_register_cpts' );

function tbp_egresos_register_cpts() {
    // Proveedores
    $labels_proveedor = array(
        'name'               => _x( 'Proveedores', 'post type general name', 'tbp-egresos' ),
        'singular_name'      => _x( 'Proveedor', 'post type singular name', 'tbp-egresos' ),
        'menu_name'          => _x( 'Proveedores', 'admin menu', 'tbp-egresos' ),
        'add_new'            => _x( 'Añadir Nuevo', 'proveedor', 'tbp-egresos' ),
        'add_new_item'       => __( 'Añadir Nuevo Proveedor', 'tbp-egresos' ),
        'edit_item'          => __( 'Editar Proveedor', 'tbp-egresos' ),
        'new_item'           => __( 'Nuevo Proveedor', 'tbp-egresos' ),
        'view_item'          => __( 'Ver Proveedor', 'tbp-egresos' ),
        'search_items'       => __( 'Buscar Proveedores', 'tbp-egresos' ),
        'not_found'          => __( 'No se encontraron proveedores', 'tbp-egresos' ),
        'not_found_in_trash' => __( 'No hay proveedores en la papelera', 'tbp-egresos' )
    );

    $args_proveedor = array(
        'labels'             => $labels_proveedor,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false, // Handled by add_submenu_page
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'tbp_proveedor' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title' )
    );

    register_post_type( 'tbp_proveedor', $args_proveedor );

    // Órdenes de Compra
    $labels_oc = array(
        'name'               => _x( 'Órdenes de Compra', 'post type general name', 'tbp-egresos' ),
        'singular_name'      => _x( 'Orden de Compra', 'post type singular name', 'tbp-egresos' ),
        'menu_name'          => _x( 'Órdenes de Compra', 'admin menu', 'tbp-egresos' ),
        'add_new'            => _x( 'Añadir Nueva', 'orden de compra', 'tbp-egresos' ),
        'add_new_item'       => __( 'Añadir Nueva Orden de Compra', 'tbp-egresos' ),
        'edit_item'          => __( 'Editar Orden de Compra', 'tbp-egresos' ),
        'new_item'           => __( 'Nueva Orden de Compra', 'tbp-egresos' ),
        'view_item'          => __( 'Ver Orden de Compra', 'tbp-egresos' ),
        'search_items'       => __( 'Buscar Órdenes', 'tbp-egresos' ),
        'not_found'          => __( 'No se encontraron órdenes', 'tbp-egresos' ),
        'not_found_in_trash' => __( 'No hay órdenes en la papelera', 'tbp-egresos' )
    );

    $args_oc = array(
        'labels'             => $labels_oc,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'tbp_orden_compra' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title' )
    );

    register_post_type( 'tbp_orden_compra', $args_oc );

    // Pagos
    $labels_pago = array(
        'name'               => _x( 'Pagos', 'post type general name', 'tbp-egresos' ),
        'singular_name'      => _x( 'Pago', 'post type singular name', 'tbp-egresos' ),
        'menu_name'          => _x( 'Pagos', 'admin menu', 'tbp-egresos' ),
        'add_new'            => _x( 'Registrar Pago', 'pago', 'tbp-egresos' ),
        'add_new_item'       => __( 'Registrar Nuevo Pago', 'tbp-egresos' ),
        'edit_item'          => __( 'Editar Pago', 'tbp-egresos' ),
        'new_item'           => __( 'Nuevo Pago', 'tbp-egresos' ),
        'view_item'          => __( 'Ver Pago', 'tbp-egresos' ),
        'search_items'       => __( 'Buscar Pagos', 'tbp-egresos' ),
        'not_found'          => __( 'No se encontraron pagos', 'tbp-egresos' ),
        'not_found_in_trash' => __( 'No hay pagos en la papelera', 'tbp-egresos' )
    );

    $args_pago = array(
        'labels'             => $labels_pago,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'tbp_pago' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title' )
    );

    register_post_type( 'tbp_pago', $args_pago );
}
