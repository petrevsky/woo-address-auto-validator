<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Woo_Address_Auto_Validator_Menu {
	/**
	 * @var string
	 */
	private $plugin_path;

	/**
	 * @var WordPressSettingsFramework
	 */
	private $wpsf;

	/**
	 * WPSFTest constructor.
	 */
	public function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );

		// Include and create a new WordPressSettingsFramework
		
		$this->wpsf = new WordPressSettingsFramework( $this->plugin_path . 'class-woo-address-auto-validator-settings.php', 'waav' );

		// Add admin menu
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );
		
		// Add an optional settings validation filter (recommended)
		add_filter( $this->wpsf->get_option_group() . '_settings_validate', array( &$this, 'validate_api' ) );

		add_filter( $this->wpsf->get_option_group() . '_settings_validate', array( &$this, 'validate_email' ) );

		add_action( 'wpsf_after_field_' . $this->wpsf->get_option_group() . '_tab_2_email_reminder_body', array( $this, 'show_shortcodes_after_editor' ) );

		add_filter( 'plugin_action_links_woo-address-auto-validator/woo-address-auto-validator.php', array( $this, 'link_to_settings_menu' ) );
		

	}

	public function link_to_settings_menu( $links ) {

		$url = esc_url( add_query_arg(
			'page',
			'waav-settings',
			get_admin_url() . 'admin.php'
		) );

		// Create the link.
		$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';

		// Adds the link to the end of the array.
		array_unshift(
			$links,
			$settings_link
		);

		return $links;
	
	}


	/**
	 * Add settings page.
	 */
	public function add_settings_page() {

		$this->wpsf->add_settings_page( 
			array(
				'parent_slug' => 'woocommerce',
				'page_title'  => esc_html__( 'WooCommerce Address Validator', 'text-domain' ),
				'menu_title'  => esc_html__( 'Address Validator', 'text-domain' ),
				'capability'  => 'manage_woocommerce',
			)
		);

	}


	public function validate_email( $input ) {

		if( isset( $input['tab_2_email_reminder_send-reminder-emails'] ) && $input['tab_2_email_reminder_send-reminder-emails'] ) {

			$keys_check = array(  
				'tab_2_email_reminder_subject', 
				'tab_2_email_reminder_body' 
			);
	
			$pass = 0;
			foreach( $keys_check as $key ) {
				if( isset( $input[$key] ) && !empty( $input[$key] ) ) {
					$pass = 1;
				} else {
					$pass = 0;
					break;
				}
			}


			if( !$pass ) {
				$input['tab_2_email_reminder_send-reminder-emails'] = 0;

				add_settings_error(
					'error_email_tab', // Slug title of setting
					'tab_2', // Slug-name , Used as part of 'id' attribute in HTML output.
					__( 'Subject and body of email cannot be empty.', 'text-domain' ), // message text, will be shown inside styled <div> and <p> tags
					'error' // Message type, controls HTML class. Accepts 'error' or 'updated'.
				);
			}

			$required_body_params = array(
				'[subscription_address_change]',
				'[subscription_address_confirm]'
			);
			$pass_body = 1;

			$email_body = $input['tab_2_email_reminder_body'];

			foreach( $required_body_params as $param ) {
				if( strpos( $email_body, $param ) == false ) {
					$pass_body = 0;
					break;
				}
			}

			if( !$pass_body ) {

				$input['tab_2_email_reminder_send-reminder-emails'] = 0;

				add_settings_error(
					'error_email_tab', // Slug title of setting
					'tab_2', // Slug-name , Used as part of 'id' attribute in HTML output.
					__( 'E-mail body must contain <b>[subscription_address_change]</b> and <b>[subscription_address_confirm]</b>.', 'text-domain' ), // message text, will be shown inside styled <div> and <p> tags
					'error' // Message type, controls HTML class. Accepts 'error' or 'updated'.
				);

			}

			if( isset( $input['tab_2_email_reminder_reocurring-enable'] ) && $input['tab_2_email_reminder_reocurring-enable'] ) {
				if( !($input['tab_2_email_reminder_reocurring-days'] > 0) ) {

					$input['tab_2_email_reminder_reocurring-enable'] = 0;

					add_settings_error(
						'error_email_tab', // Slug title of setting
						'tab_2', // Slug-name , Used as part of 'id' attribute in HTML output.
						__( 'Reocurring day(s) must be a positive integer.', 'text-domain' ), // message text, will be shown inside styled <div> and <p> tags
						'error' // Message type, controls HTML class. Accepts 'error' or 'updated'.
					);


					
				}
			}

			


		}

		return $input;
	} 

	/**
	 * Validate settings.
	 * 
	 * @param $input
	 *
	 * @return mixed
	 */
	public function validate_api( $input ) {

		$keys_check = array( 'tab_1_general_enable', 'tab_1_general_api-shipengine' );

		$pass = 0;
		foreach( $keys_check as $key ){
			if( isset( $input[$key] ) && !empty( $input[$key] ) ) {
				$pass = 1;
			} else {
				$pass = 0;
				break;
			}
		}

		if( $pass ) {

			$api_key = $input['tab_1_general_api-shipengine'];
	
			$address_validator = new Address_Validator();

			$key_test = $address_validator->api_key_test( $api_key );

			if( $key_test === 1 ) {
				$success = 1;
			} else {

				add_settings_error(
					'error_api_connection', // Slug title of setting
					'tab_1_general_api-shipengine', // Slug-name , Used as part of 'id' attribute in HTML output.
					__( 'Error connecting to ShipEngine. Message: ' . $key_test, 'text-domain' ), // message text, will be shown inside styled <div> and <p> tags
					'error' // Message type, controls HTML class. Accepts 'error' or 'updated'.
				);

				$input['tab_1_general_enable'] = 0;
			}

			// check API key
		} else {

			if( isset( $input['tab_1_general_enable'] ) && $input['tab_1_general_enable'] == 1 ) {
				$input['tab_1_general_enable'] = 0;

				add_settings_error(
					'error_api_connection', // Slug title of setting
					'tab_1_general_api-shipengine', // Slug-name , Used as part of 'id' attribute in HTML output.
					__( 'Error connecting to ShipEngine. Please check your API key.', 'text-domain' ), // message text, will be shown inside styled <div> and <p> tags
					'error' // Message type, controls HTML class. Accepts 'error' or 'updated'.
				);
			}
		}
		
		// Do your settings validation here
		// Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
		return $input;
	}


	public function show_shortcodes_after_editor() {


		// var_dump(  wpsf_get_setting( 'waav', 'tab_1_general', 'enable' ) );
		?>

		<div class="shortcodes-container">
			<h4>Available Shortcodes</h4>

			<p>[subscription_first_name]</p>
			<p>[subscription_last_name]</p>
			<p>[subscription_id]</p>
			<p>[subscription_address]</p>
			<p>[subscription_address_confirm]</p>
			<p>[subscription_address_change]</p>

		</div>

		<?php
	}
	
}