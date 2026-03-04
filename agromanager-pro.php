<?php
/**
 * Plugin Name: AgroManager Pro
 * Plugin URI: https://example.com/agromanager-pro
 * Description: Komplex mezőgazdasági vállalatirányítási rendszer – földterületek, kultúrák, géppark, időjárás, pénzügyek és dolgozók kezelése.
 * Version: 1.0.0
 * Author: Korfanti Dániel
 * Author URI: https://example.com
 * Text Domain: agromanager-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AGROMANAGER_VERSION', '1.0.0' );
define( 'AGROMANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AGROMANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AGROMANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin activation.
 */
function agromanager_activate() {
    require_once AGROMANAGER_PLUGIN_DIR . 'includes/class-agromanager-activator.php';
    AgroManager_Activator::activate();
}
register_activation_hook( __FILE__, 'agromanager_activate' );

/**
 * Plugin deactivation.
 */
function agromanager_deactivate() {
    require_once AGROMANAGER_PLUGIN_DIR . 'includes/class-agromanager-deactivator.php';
    AgroManager_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'agromanager_deactivate' );

/**
 * Include required files.
 */
require_once AGROMANAGER_PLUGIN_DIR . 'includes/class-agromanager-activator.php';
require_once AGROMANAGER_PLUGIN_DIR . 'includes/class-agromanager-parcels.php';
require_once AGROMANAGER_PLUGIN_DIR . 'includes/class-agromanager-crops.php';
require_once AGROMANAGER_PLUGIN_DIR . 'includes/class-agromanager-machines.php';
require_once AGROMANAGER_PLUGIN_DIR . 'includes/class-agromanager-weather.php';
require_once AGROMANAGER_PLUGIN_DIR . 'includes/class-agromanager-finances.php';
require_once AGROMANAGER_PLUGIN_DIR . 'includes/class-agromanager-workers.php';
require_once AGROMANAGER_PLUGIN_DIR . 'admin/class-agromanager-admin.php';

/**
 * Initialize the plugin.
 */
function agromanager_init() {
    $admin = new AgroManager_Admin();
    $admin->init();
}
add_action( 'plugins_loaded', 'agromanager_init' );
