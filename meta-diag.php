<?php
require_once('wp-load.php');
global $wpdb;

echo "--- META KEY CHECK ---\n";
$rifa_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '_tbp_entregas_fisicas' AND meta_value > 0");
$pkg_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '_tbp_entrega_paquetes'");
$pkg_yes_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '_tbp_entrega_paquetes' AND meta_value IN ('1', 'yes')");

echo "Rifa meta (>0): $rifa_count\n";
echo "Package meta (exists): $pkg_count\n";
echo "Package meta (yes/1): $pkg_yes_count\n";

$sample_pkg = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_tbp_entrega_paquetes' LIMIT 5");
echo "\nSample Package Metas:\n";
foreach($sample_pkg as $s) {
    echo "Post ID: {$s->post_id} | Value: [{$s->meta_value}]\n";
}
