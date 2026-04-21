<?php
require_once('wp-load.php');
if ( class_exists( 'Tribe__Tickets__Tickets' ) ) {
    echo "Tribe__Tickets__Tickets exists\n";
}
if ( class_exists( 'TEC\Tickets\Commerce\Attendee' ) ) {
    echo "TEC\Tickets\Commerce\Attendee exists\n";
}
