<?php
/**
 * MySEO Plugin.
 * @wordpress-plugin
 * Plugin Name: MySEO Premium
 * Version:     50.5
 * Plugin URI:  http://www.shcontrol.net/MySEO
 * Description: The first true all-in-one SEO solution for WordPress, including on-page content analysis, XML sitemaps and much more.
 * Author:      SHControl Limited
 * Author URI:  http://www.shcontrol.net/
 * Text Domain: wordpress-seo
 * Domain Path: /languages/
 * License:     GPL v3
*/

if ( ! defined( 'WPSEO_FILE' ) ) {
	define( 'WPSEO_FILE', __FILE__ );
}

if ( ! defined( 'WPSEO_PREMIUM_PLUGIN_FILE' ) ) {
	define( 'WPSEO_PREMIUM_PLUGIN_FILE', __FILE__ );
}

$wpseo_premium_dir = plugin_dir_path( WPSEO_PREMIUM_PLUGIN_FILE ) . 'premium/';

if ( ! is_admin() ) {
	require_once $wpseo_premium_dir . 'classes/redirect/redirect-util.php';
	require_once $wpseo_premium_dir . 'classes/redirect/redirect-handler.php';

	$wpseo_redirect_handler = new WPSEO_Redirect_Handler();
	$wpseo_redirect_handler->load();
}

function wpseo_premium_add_general_option_defaults( array $wpseo_defaults ) {
	$premium_defaults = array(
		'enable_metabox_insights' => true,
		'enable_link_suggestions' => true,
	);

	return array_merge( $wpseo_defaults, $premium_defaults );
}
add_filter( 'wpseo_option_wpseo_defaults', 'wpseo_premium_add_general_option_defaults' );

require_once dirname( WPSEO_FILE ) . '/wp-seo-main.php';
require_once $wpseo_premium_dir . 'premium.php';

WPSEO_Premium::autoloader();

$wpseo_premium_capabilities = new WPSEO_Premium_Register_Capabilities();
$wpseo_premium_capabilities->register_hooks();

function wpseo_premium_run_upgrade() {
	$upgrade_manager = new WPSEO_Upgrade_Manager();
	$upgrade_manager->run_upgrade( WPSEO_VERSION );
}

if ( is_admin() ) {
	add_action( 'init', 'wpseo_premium_run_upgrade' );
}

function wpseo_premium_init() {
	new WPSEO_Premium();
}

if ( ! wp_installing() ) {
	add_action( 'plugins_loaded', 'wpseo_premium_init', 14 );
}

if ( is_admin() ) {
	register_activation_hook( __FILE__, array( 'WPSEO_Premium', 'install' ) );
}
