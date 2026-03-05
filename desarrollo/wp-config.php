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
define('DB_NAME', 'wp_2vnm8');

/** MySQL database username */
define('DB_USER', 'wp_1qtr7');

/** MySQL database password */
define('DB_PASSWORD', '?E8qj9zve$f8fSA_');

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
define('AUTH_KEY', '%GvC:hqra0:S*#)pYAsUf~Ok1;36qyUv9-Tc35&!eKhOd80x##w/D6AXHzjw78kB');
define('SECURE_AUTH_KEY', '77JXj@E#:_6K5zAj)(0l@32Lq%+Xv3Z5e+Ls]+b8xx-6xtBt655DQ~/b7fa1BtrR');
define('LOGGED_IN_KEY', 'rtFwX(06e15PT42rBe9%bEgVK[9b&6R(In7w-Q996nD:m(XOEUc875!5*:_Cw@N)');
define('NONCE_KEY', 'j9yfv8G0|gr;d+)vn+3%H[]G247/U]~j%)@U(7)#Fr~y614FWIFxK33]23%~zDWw');
define('AUTH_SALT', 'nU[s~]Uu)b9F3t9yvgX!J7Znx~4Tyy1*1_EXVVY(lf4D53nq_4]4@U7++W;H3)OV');
define('SECURE_AUTH_SALT', 'Pwq9l+u*q9c0f42A0D91rA+eStjg5M28S*TGCbSX[OC04YS4l37~GW:064%SL9/s');
define('LOGGED_IN_SALT', '5NPQ|AdmFp/sa8%m-f(_Nrhv0ESYaKA9WLPpHMzESy:Fo3981]kX!az+P8O)2|5s');
define('NONCE_SALT', 'u;)vCau+a:_;CB[Uy#p|35-Y63yk5rC/G#c+Jbu22J12MZ~&M7-:!9@Hn21l~+cK');
define( 'WP_CACHE_KEY_SALT', '0375f18aef9ca3bb9516a6106c12642c' );

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
