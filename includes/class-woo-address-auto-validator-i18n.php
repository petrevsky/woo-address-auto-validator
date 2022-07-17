<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.upwork.com/freelancers/~0147062c274db19c47
 * @since      1.0.0
 *
 * @package    Woo_Address_Auto_Validator
 * @subpackage Woo_Address_Auto_Validator/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woo_Address_Auto_Validator
 * @subpackage Woo_Address_Auto_Validator/includes
 * @author     Chris Petrevski <chrispetrevsky@gmail.com>
 */
class Woo_Address_Auto_Validator_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'woo-address-auto-validator',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
