<?php

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
define( 'DB_NAME', 'u244383518_XiZjc' );

/** Database username */
define( 'DB_USER', 'u244383518_OWpmT' );

/** Database password */
define( 'DB_PASSWORD', 'JPwsZ7AIhs' );

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
define( 'AUTH_KEY',          'c+vKj._d!CYn7+])97=K?[k/xhqFa~5Zug/sEmb?Ej=4w`o@b6ZmL%Zp6hOw+Qo=' );
define( 'SECURE_AUTH_KEY',   '>sNh8V>6&$0wm)#vrK(9q%4Q)4@qnB2s74W{V%iDmfJjb7zfY}R;+DJH7*(eEo7h' );
define( 'LOGGED_IN_KEY',     'l?D_Q>:|c!)1.j.i&RuL|XyADz$C,FIE.t3BXeF1~T{`J4QF,~}($`.C}_U;h`Dy' );
define( 'NONCE_KEY',         's70rY6MzVMX[9Wyj#!(w2:w+y!*iMD9LdxLA7s7cn8vXN)/}f0<?6v-J!S/Tn=k{' );
define( 'AUTH_SALT',         'Sk&tE>l],;Kf`O]9,Z|,s0p?FV6hbzezSeV@x[kjZ%K{VtjSHp`}n%gX^l_?BxEr' );
define( 'SECURE_AUTH_SALT',  'zK@cB/)}R5{h5VNT`U!5FJz]=KzBWd@+9ThzZa4P0<2<gCBj`lzHH}xKxE#2z;x_' );
define( 'LOGGED_IN_SALT',    ':Rs=+/,L+vFD*u7H^mml?/c`o@>81#NSK>j}Tar^31Wh9|aWFrM<GNXu9E@*g-a)' );
define( 'NONCE_SALT',        'f:AL3tPv;=O~qZ>?XmZ~>FOuTi.6h__8iRc(R#)Y+2XG~YWBR9-G_rWJ?[hO0bum' );
define( 'WP_CACHE_KEY_SALT', 'i/bfE0UAT>Hx+{oa*]9AR.cokH;2Tx1jRD~VK*1@UeHC]Z7~s%RHHT49bVZ*lA%(' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */

// define(‘WP_HOME’, ‘http://hbmflower-city.com’);
// define(‘WP_SITEURL’, ‘http://hbmflower-city.com’);



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
define( 'COOKIEHASH', '099a8971c591349de4b8f60f90729d06' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
