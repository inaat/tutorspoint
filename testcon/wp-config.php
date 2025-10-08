<?php
define( 'WP_CACHE', true );

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
define( 'DB_NAME', 'u835889135_Xq2Ds' );

/** Database username */
define( 'DB_USER', 'u835889135_Mi5YJ' );

/** Database password */
define( 'DB_PASSWORD', 'FrE3y7rMqU' );

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
define( 'AUTH_KEY',          '(r1*D;tRA6Ujj[y@_7RzQtzP3k~d_% my}<T{,%iUkekp+uur+b(xQ>3NQoo$keY' );
define( 'SECURE_AUTH_KEY',   'csaW4zqc0L:#&=<f[WR|#7F6)>*ZM/TcE%F+|59%X9vBv[Kz%6s~`AIF(&i!62W^' );
define( 'LOGGED_IN_KEY',     '.Y!rT6SX)Q8itH42/8MfOjr];x<e,a>H3>AvPgfLs!)1$&LxZFs|k6vECiS%r=4(' );
define( 'NONCE_KEY',         '{@?Z3 LVZJM%C)579-?9#jq=9a7o{v#Asx{CIEAv?-?..Xj| #2EzbV :=2?Ik1]' );
define( 'AUTH_SALT',         'tB!Ch>153n#):U,h0*%hWI >H+l!F~h6Ci9NP#kN7pbFoj@N1JwNn-|]`xR7M;&P' );
define( 'SECURE_AUTH_SALT',  'tVeLYLH<N?1|/;`s4{J@.=dcrJfY$(5*Vn_ZKb5aiEa^/Q?uoHE$F-;TPTxv7).7' );
define( 'LOGGED_IN_SALT',    'fZ)*]*0.6AU*o@w2-,xrQ2Vn31+?KJNDf;5/w%k7]GAX;8QQ<3%#Lq]c;A~+)xE=' );
define( 'NONCE_SALT',        '~xZUH]eUJM6o@xw&h5mMS/-c}eRHNCB0tYv>-BOJ?2541#B3 pB5rf-n!4yeXruQ' );
define( 'WP_CACHE_KEY_SALT', '-eOsh](*bcD:s);eNUAX%4@o>E56k*NQ69AdMM_%uZ-%&U{&@lv)ZK|=N}.2[j{D' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
define( 'COOKIEHASH', 'e8bef5fe1e2598101d89a6142f9df166' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
