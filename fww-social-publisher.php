<?php
/**
 * Plugin Name: FWW Social Publisher
 * Plugin URI:  https://feuerwehr.wolfurt.at
 * Description: Publishes posts to Facebook, Instagram and Telegram for Feuerwehr Wolfurt. Includes WhatsApp copy helper. Uses social media text from KI Content Creator when available.
 * Version:     1.1.0
 * Author:      Feuerwehr Wolfurt
 * Author URI:  https://feuerwehr.wolfurt.at
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fww-social-publisher
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FWW_SP_VERSION',    '1.1.0' );
define( 'FWW_SP_PLUGIN_FILE', __FILE__ );
define( 'FWW_SP_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'FWW_SP_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

require_once FWW_SP_PLUGIN_DIR . 'includes/class-fww-facebook-api.php';
require_once FWW_SP_PLUGIN_DIR . 'includes/class-fww-instagram-api.php';
require_once FWW_SP_PLUGIN_DIR . 'includes/class-fww-telegram-api.php';
require_once FWW_SP_PLUGIN_DIR . 'includes/class-fww-social-publisher.php';

register_activation_hook( __FILE__,   [ 'FWW_Social_Publisher', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'FWW_Social_Publisher', 'deactivate' ] );

add_action( 'plugins_loaded', static function () {
	load_plugin_textdomain( 'fww-social-publisher', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	FWW_Social_Publisher::get_instance();
} );
