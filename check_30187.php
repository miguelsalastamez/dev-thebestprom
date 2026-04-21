<?php
require_once('wp-load.php');
$order_id = 30187;
$order = wc_get_order($order_id);
if (!$order) die("Order not found");

echo "Checking order $order_id\n";
$meta = get_post_meta($order_id, '_tribe_tickets_meta', true);
echo "Order direct meta: " . (is_array($meta) ? count($meta) : "None") . "\n";
print_r($meta);

global $wpdb;
$search_values = array( $order_id, strval($order_id) );
foreach( $order->get_items() as $item_id => $item ) {
    $search_values[] = $item_id;
    echo "Item ID: $item_id\n";
}

$placeholders = implode(',', array_fill(0, count($search_values), '%s'));
$sql = $wpdb->prepare("
    SELECT pm.post_id, pm.meta_key, pm.meta_value
    FROM {$wpdb->postmeta} pm
    JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE pm.meta_value IN ($placeholders)
    AND p.post_type IN ('tribe_wooticket', 'tribe_rsvp', 'attendee', 'tec_attendee')
", ...$search_values);

$results = $wpdb->get_results($sql);
echo "Database survey results: " . count($results) . "\n";
foreach($results as $res) {
    echo "Post ID: {$res->post_id} | Key: {$res->meta_key} | Value: {$res->meta_value}\n";
    $m = get_post_meta($res->post_id, '_tribe_tickets_meta', true);
    if ($m) echo "  - Has _tribe_tickets_meta!\n";
}
