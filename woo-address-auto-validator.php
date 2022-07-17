<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.upwork.com/freelancers/~0147062c274db19c47
 * @since             1.0.0
 * @package           Woo_Address_Auto_Validator
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Address Auto-Correct
 * Plugin URI:        https://www.upwork.com/freelancers/~0147062c274db19c47
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Chris Petrevski
 * Author URI:        https://www.upwork.com/freelancers/~0147062c274db19c47
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-address-auto-validator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOO_ADDRESS_AUTO_VALIDATOR_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-address-auto-validator-activator.php
 */
function activate_woo_address_auto_validator() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-address-auto-validator-activator.php';
	Woo_Address_Auto_Validator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-address-auto-validator-deactivator.php
 */
function deactivate_woo_address_auto_validator() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-address-auto-validator-deactivator.php';
	Woo_Address_Auto_Validator_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woo_address_auto_validator' );
register_deactivation_hook( __FILE__, 'deactivate_woo_address_auto_validator' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woo-address-auto-validator.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_address_auto_validator() {

	$plugin = new Woo_Address_Auto_Validator();
	$plugin->run();

}
run_woo_address_auto_validator();
