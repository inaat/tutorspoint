<?php

define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);



//define( 'WP_DEBUG', true );
//define( 'WP_DEBUG_LOG', true );
//define( 'WP_DEBUG_DISPLAY', false );



/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'tutorspoint' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'Root123!' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '[Sk3*}P7D}_Q+oODV};pq^H;?F`60/fkbLC5Hz(#O4tH)vA3eXrLO((h$hEW*N/U' );
define( 'SECURE_AUTH_KEY',   'c[H/q1Lr~u#F_38*,$D-`,=IfJI7y_|`#-.NYO$YWoV#{38$VT5U7W-w~f+u%L:N' );
define( 'LOGGED_IN_KEY',     'za,UZ#/DYj3xf~b?#eV@cB yZ$4Rg(>HuK3`~ut?.b2XAoJ-ur@MnH*.Tjr-.8i.' );
define( 'NONCE_KEY',         'ZjDwl;?pHC}f65o0r#Hz2jrD8%hgOi{1QkhF)l0R~3S^ qo;H,E#&/dQ|LmR;yIM' );
define( 'AUTH_SALT',         'v}K)vfQPSO.=3NAE1%?{A^H=i[{Z&S4,%3gA]L&>kT~nViK}dp-<~l|znK-zr|5;' );
define( 'SECURE_AUTH_SALT',  ',haF,*;xts/Bn=!2aAoaL^0mf`j!!QC4*rsFWc!`4UO7=smw o2YuzV#!c-Uz~J>' );
define( 'LOGGED_IN_SALT',    ' 5eXTAXCvjYr$g8}XhBtZAT;5@yN~5|R6T+K?b2&vdx,;ea5as(Cu~wWy;au>DqD' );
define( 'NONCE_SALT',        '8EaVt~go?c;apV(*/ViXEW%}XzFb}`eqH2uu0GU`8GH?t>*JEO-{(`MbjF sQ/=m' );
define( 'WP_CACHE_KEY_SALT', 'R&hB5z:S-%&d0|q,e~i/SlzC<zBm6bxUD}kIp4z#(Z;8l0sGJ<G:^=N~Y{x,:pN?' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

// ZEGO (Tutors Point)
define('TP_ZEGO_APP_ID', 1508961959);
define('TP_ZEGO_SERVER_SECRET', '37632cf0e4e0e463917c60fdd53573e1');



/* === ZEGO CLOUD CREDENTIALS === 
if (!defined('ZEGO_APP_ID')) {
  define('ZEGO_APP_ID', 1508961959); // <-- your AppID (integer)
}
if (!defined('ZEGO_SERVER_SECRET')) {
  define('ZEGO_SERVER_SECRET', '37632cf0e4e0e463917c60fdd53573e1'); // <-- your Server Secret (string)
}

*/

/* Add any custom values between this line and the "stop editing" line. */

// Local development URL overrides
define('WP_HOME','http://public_html.test');
define('WP_SITEURL','http://public_html.test');



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '6f8a131c58c3ab717dd4efdb0cedb1c2' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';



