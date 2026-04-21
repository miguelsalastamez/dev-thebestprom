<?php
require_once('wp-load.php');
$order_id = 30187;
$meta = get_post_meta($order_id);
foreach($meta as $key => $val) {
    if (strpos($key, 'tribe') !== false) {
        echo "Key: $key | Value: " . $val[0] . "\n";
    }
}
$order = wc_get_order($order_id);
foreach($order->get_items() as $item_id => $item) {
    echo "Item $item_id meta:\n";
    $im = get_metadata('order_item', $item_id);
    foreach($im as $k => $v) {
        if (strpos($k, 'tribe') !== false) {
             echo "  Key: $k | Value: " . $v[0] . "\n";
        }
    }
}
