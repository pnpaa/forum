<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link http://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'u411505508_forum');

/** MySQL database username */
define('DB_USER', 'u411505508_forum');

/** MySQL database password */
define('DB_PASSWORD', 'ofh7EB1lZQsua4224U');

/** MySQL hostname */
define('DB_HOST', 'mysql.hostinger.ph');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'g%UV*dfs`[,t<p)&l[1?|q-Gs|@ImB8UVbPwWOHOdHQTGUMqDUvXJ<+j8FQ8T[/*');
define('SECURE_AUTH_KEY',  'z1kmN*A/ Yp{dFcj%V&llR+lEXeU/TcbIu/cIc5|w|0XASc;k_;<+r;[-$h43pP[');
define('LOGGED_IN_KEY',    '-w+1qUNpw9@TU3Hf$--Q[D*Da>ts*Fp,he!{@}4.W?QcF2db#Li(ffb++%rA+Y0Q');
define('NONCE_KEY',        'eDQ<atfX;}$XDu5LBD6kbI/6q[W%A!S*BiL%uK#hGr>CZ6}Y%0EIf@X:j0|g56M0');
define('AUTH_SALT',        'bdL~c0(x5ox#/zXFVVk5l~-g8` z9b.d8( P.usfwF6`+PQL]owr*OtI`&(xeU7[');
define('SECURE_AUTH_SALT', '#0+8,22+WZx5U.O!g}ln}ZWa*>iXmZ&UphqUmgR%jvS7a1BAAXTla6eoVW=BctHk');
define('LOGGED_IN_SALT',   'r<=XV<$+JBoYhyKLwgRVc$d%vga`v1&+VT(DF01vT0[:$y.f.0r!j$- e@U8|7/2');
define('NONCE_SALT',       'uA62+c=ry?*OO&0Cbvy;X-0rMtl,1{JqiE&,A~*3D?URSG-knqEp[T,Lk@Sb*_8N');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
