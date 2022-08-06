<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class Admin_Notices {

	public function __construct() {
        add_action( 'admin_notices', array( $this, 'address_validaton_failed' ) );
    }


    public function update_option_value( $option_name, $value ) {
        
        if( get_option( $option_name ) ){
            update_option( $option_name, $value );
       } else {
         add_option( $option_name, $value );
       }
   
    }

    public function address_validaton_failed() {

        if( get_option( 'address_validaton_failed' ) ) {
            $class = 'notice notice-error';
            $message = __( 'Whoops! <b>WooCommerce Address Auto Validator</b> is failing to validate. Please urge the developer to look into this!' );
         
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); 
        }
        
    }

}