<?php
/*
 * Plugin Name:       Super Blank
 * Plugin URI:        https://tyler.com/
 * Description:       The best way in the world to make a website. Start from blank, and build any design you want.
 * Author:            Tyler Moore
 * Author URI:        https://tyler.com/
 * Version:           1.1.0
 * Text Domain:       super-blank
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */


// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define plugin version.
 */
if (!defined('SUPER_BLANK_PLUGIN_VERSION')) {

    define('SUPER_BLANK_PLUGIN_VERSION', '1.1.0'); // '1.1.0'
}

/**
 * Define plugin path.
 */
if (!defined('SUPER_BLANK_PLUGIN_PATH')) {

    define('SUPER_BLANK_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

/**
 * Define plugin url.
 */
if (!defined('SUPER_BLANK_PLUGIN_URL')) {

    define('SUPER_BLANK_PLUGIN_URL', plugin_dir_url(__FILE__));
}

/**
 * Define Production Env.
 */
if (!defined('SUPER_BLANK_PRODUCTION')) {

    define('SUPER_BLANK_PRODUCTION', true);
}

require_once SUPER_BLANK_PLUGIN_PATH . 'vendor/autoload.php';

require_once SUPER_BLANK_PLUGIN_PATH . 'inc/Endpoints/index.php';

require_once SUPER_BLANK_PLUGIN_PATH . 'inc/functions.php';

require_once SUPER_BLANK_PLUGIN_PATH . 'inc/admin-pages.php';

require_once SUPER_BLANK_PLUGIN_PATH . 'inc/enqueue-scripts.php';

require_once SUPER_BLANK_PLUGIN_PATH . 'inc/hooks.php';
