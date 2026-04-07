<?php
/**
 * Analytics and Tracking Engine for TBP Marketing
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API Tracking Route
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'tbp-mkt/v1', '/track-open', array(
        'methods'  => 'GET',
        'callback' => 'tbp_mkt_track_open_callback',
        'permission_callback' => '__return_true',
    ));

    register_rest_route( 'tbp-mkt/v1', '/track-click', array(
        'methods'  => 'GET',
        'callback' => 'tbp_mkt_track_click_callback',
        'permission_callback' => '__return_true',
    ));
});

/**
 * Tracking Callback: Records the open and returns a pixel
 */
function tbp_mkt_track_open_callback( $data ) {
    global $wpdb;
    $campaign_id = intval( $data['c'] );
    $contact_id  = intval( $data['u'] );

    if ( $campaign_id > 0 ) {
        $table_stats = $wpdb->prefix . 'tbp_marketing_stats';
        $table_camps = $wpdb->prefix . 'tbp_marketing_campaigns';

        // Evitar duplicados rápidos de la misma IP en los últimos 5 minutos
        $ip = $_SERVER['REMOTE_ADDR'];
        $recent = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_stats WHERE campaign_id = %d AND contact_id = %d AND event_type = 'open' AND ip_address = %s AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)", $campaign_id, $contact_id, $ip ) );

        if ( ! $recent ) {
            $wpdb->insert( $table_stats, array(
                'campaign_id' => $campaign_id,
                'contact_id'  => $contact_id,
                'event_type'  => 'open',
                'ip_address'  => $ip,
                'user_agent'  => $_SERVER['HTTP_USER_AGENT']
            ) );

            // Update campaign totals
            $wpdb->query( $wpdb->prepare( "UPDATE $table_camps SET stats_opened = stats_opened + 1 WHERE id = %d", $campaign_id ) );
        }
    }

    // Output 1x1 transparent pixel
    header( 'Content-Type: image/gif' );
    echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
    exit;
}

/**
 * Tracking Callback: Records the click and redirects
 */
function tbp_mkt_track_click_callback( $data ) {
    global $wpdb;
    $campaign_id = intval( $data['c'] );
    $contact_id  = intval( $data['u'] );
    $target_url  = esc_url_raw( $data['url'] );

    if ( $campaign_id > 0 && ! empty( $target_url ) ) {
        $table_stats = $wpdb->prefix . 'tbp_marketing_stats';
        $table_camps = $wpdb->prefix . 'tbp_marketing_campaigns';

        $wpdb->insert( $table_stats, array(
            'campaign_id' => $campaign_id,
            'contact_id'  => $contact_id,
            'event_type'  => 'click',
            'ip_address'  => $_SERVER['REMOTE_ADDR'],
            'user_agent'  => $_SERVER['HTTP_USER_AGENT']
        ) );

        $wpdb->query( $wpdb->prepare( "UPDATE $table_camps SET stats_clicked = stats_clicked + 1 WHERE id = %d", $campaign_id ) );
    }

    wp_redirect( $target_url );
    exit;
}

/**
 * Helper to wrap links in a message with tracking redirects
 */
function tbp_mkt_wrap_links( $message, $campaign_id, $contact_id ) {
    if ( ! $campaign_id ) return $message;

    $tracking_base = get_rest_url( null, 'tbp-mkt/v1/track-click' );
    
    // Regex to find all hrefs
    return preg_replace_callback( '/href=["\'](https?:\/\/[^"\']+)["\']/i', function( $matches ) use ( $tracking_base, $campaign_id, $contact_id ) {
        $original_url = $matches[1];
        
        // Don't track if it already looks like a tracking link or is an unsubscribe link
        if ( strpos( $original_url, 'track-click' ) !== false ) return $matches[0];

        $tracked_url = add_query_arg( array(
            'c'   => $campaign_id,
            'u'   => $contact_id,
            'url' => urlencode( $original_url )
        ), $tracking_base );

        return 'href="' . $tracked_url . '"';
    }, $message );
}
