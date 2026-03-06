<?php
define( 'WP_CACHE', false ); // By Speed Optimizer by SiteGround

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wp_xrfn4');

/** MySQL database username */
define('DB_USER', 'wp_v1isw');

/** MySQL database password */
define('DB_PASSWORD', 'LjD%lU81G7CNXS_I');

/** MySQL hostname */
define('DB_HOST', 'localhost:3306');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', '~2X_Z6t6t)/o6Q-/e3!G7gd/j;(qx4A+ux4ZrPq00/V:6G|uuHNXrUK8cV70&]bd');
define('SECURE_AUTH_KEY', '6p4Oj)4jP2MYK&]q;)m)l1|Lv8xl2@V:ybT|+0:8BS466FwK_76WZh!M]n7oN%k/');
define('LOGGED_IN_KEY', 'Rr702gioGxv-rJ1|9N:/f+Ar1bh4Vt3ZNlJtoxjw2qz785Co%78cHvEv8V)RoK~O');
define('NONCE_KEY', 'Np2s%n+78uhS6)g-sH8c(6O_V5of_:1rJ]Fu4zg_2xt(e_WRv8xjS|QF_-66/a!2');
define('AUTH_SALT', '0P#b6DQf)U67[D)L1317-7/2QfTlkZcSur-@40BK4gbqfo_E;u;4|18K[;%:#FB#');
define('SECURE_AUTH_SALT', 'zn]%B3mTw3zD!jh9ws77R7k5;EUT|*/x8!16fR0WK%;jc5Pf3MMyrkc2NuFO45g[');
define('LOGGED_IN_SALT', '0sZ8Z9D&@2*uykJ0Mmi49S7#1g%2w%g0K1i9geN+P6B9]KPs0K8D9[vqN4r7rakR');
define('NONCE_SALT', 'j8;Mz7#[MO/9oH|#BE3RSF06jU]3:O)(Cgz:9eH4rLim4Bzd|/c4yt@&]KO00MNa');
define( 'WP_CACHE_KEY_SALT', 'b6f6b44439f71fc9f87ae0e914dbb917' );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';




define( 'WP_AUTO_UPDATE_CORE', false );
define( 'WP_DEBUG', true );
define( 'SCRIPT_DEBUG', true );
define( 'WP_DEBUG_LOG', '/var/www/vhosts/thebestprom.com/httpdocs/wp-content/uploads/debug-log-manager/thebestpromcom_20250403011328865813_debug.log' );
define( 'WP_DEBUG_DISPLAY', false );
define( 'DISALLOW_FILE_EDIT', false );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
