<?php
/**
 * Plugin Name: Fran Export Plugin ZIP
 * Description: Añade un botón para descargar cualquier plugin instalado como archivo ZIP desde el panel de administración.
 * Version: 1.0
 * Author: Fran Salas
 * License: GPL2+
 */

defined('ABSPATH') || exit;

// Añadir botón de descarga ZIP a cada plugin en el listado
add_filter('plugin_action_links', function ($actions, $plugin_file) {
    if (current_user_can('manage_options')) {
        $plugin_slug = dirname($plugin_file);
        $url = admin_url('admin-ajax.php?action=download_plugin_zip&plugin=' . urlencode($plugin_slug));
        $actions['download_zip'] = '<a href="' . esc_url($url) . '">Descargar ZIP</a>';
    }
    return $actions;
}, 10, 2);

// Acción AJAX para generar y descargar el ZIP del plugin
add_action('wp_ajax_download_plugin_zip', function () {
    if (!current_user_can('manage_options') || empty($_GET['plugin'])) {
        wp_die(__('No autorizado.', 'fran-export-plugin-zip'));
    }

    $plugin_slug = sanitize_text_field(wp_unslash($_GET['plugin']));
    $plugin_dir = trailingslashit(WP_PLUGIN_DIR) . $plugin_slug;

    if (!is_dir($plugin_dir)) {
        wp_die(__('Plugin no encontrado.', 'fran-export-plugin-zip'));
    }

    $zip_file = tempnam(sys_get_temp_dir(), 'plugin_') . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        wp_die(__('No se pudo crear el archivo ZIP.', 'fran-export-plugin-zip'));
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($plugin_dir) + 1);
            $zip->addFile($file_path, $plugin_slug . '/' . $relative_path);
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=' . basename($plugin_slug) . '.zip');
    header('Content-Length: ' . filesize($zip_file));
    readfile($zip_file);
    unlink($zip_file);
    exit;
});
