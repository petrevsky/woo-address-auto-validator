<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.upwork.com/freelancers/~0147062c274db19c47
 * @since      1.0.0
 *
 * @package    Woo_Address_Auto_Validator
 * @subpackage Woo_Address_Auto_Validator/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Address_Auto_Validator
 * @subpackage Woo_Address_Auto_Validator/admin
 * @author     Chris Petrevski <chrispetrevsky@gmail.com>
 */
class Woo_Address_Auto_Validator_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;


		//add_action('admin_menu', array( $this, 'plugin_configure_menu' ) );

	}

	public function plugin_configure_menu() {
        
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Address_Auto_Validator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Address_Auto_Validator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-address-auto-validator-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Address_Auto_Validator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Address_Auto_Validator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( 'popper', plugin_dir_url( __FILE__ ) . 'js/popper.min.js' );
		wp_enqueue_script( 'tippy', plugin_dir_url( __FILE__ ) . 'js/tippy-bundle.umd.min.js' );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-address-auto-validator-admin.js', array( 'jquery' ), $this->version, false );


		wp_localize_script( $this->plugin_name, 'waav_var', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ajax-nonce')
		));
   

	}

}
