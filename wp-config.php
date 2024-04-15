<?php
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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_hellotech' );

/** MySQL database username */
define( 'DB_USER', 'wp_hellotech' );

/** MySQL database password */
define( 'DB_PASSWORD', 'wp_hellotech' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '-<;nD1K}fw9Yl_g0Ip,zE?F3oX[T`8QI+ap!wX3Fugu@!j4z<WL!5a0GXRqIS7xM');
define('SECURE_AUTH_KEY',  'm[wZN+u?3SL,V@B>,/IsT`=<k R+hXON]/BFp0=;,+ 2-DZ-h}lDF.RZA~w[$c8O');
define('LOGGED_IN_KEY',    'p6(,q7q{Lj5yRs-c?HqNXm|)6p78aCSX0s}0G|f}i+A1YQYeKRBk7N!O.ce9giRh');
define('NONCE_KEY',        'n)zQwQ^uF[hOX;Jo7:VL3!.p3HPQtRin%D:Zz;^6E0h:%er 5^Ov!j&6PL^pnLVT');
define('AUTH_SALT',        ']H.RU%:14jEK+n]+JByX e)f1F+)!X]rz>.z:Y%?!3QZ4`FiQb-tE4{@P]={=;vg');
define('SECURE_AUTH_SALT', '4e[)@b.=Xs(Bdo<zQv5Ty =3gi6)hMx5&B^^;oCG IfM}=-weYc>4Brhz )@MMjJ');
define('LOGGED_IN_SALT',   '89ngKvVVCeR]W8n@6ZNLAK&ABy2ISR)edhD~8PP@YQ h<kc{qYPe@=>?2+;u7rQU');
define('NONCE_SALT',       ']TNJOad{u8C<M>x.w!mHm`yLxbT4t-Pr?WEy<%n0DdQ8N-=@>Z5b+-yrJ@.y|+a[');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
