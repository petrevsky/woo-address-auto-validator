<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


use ShipEngine\ShipEngine;
use ShipEngine\Message\ShipEngineException;


class Address_Validator {

    public $apiKey = 'TEST_7oWzD1wFdms731AFDpXDbXFxwTbGGMXPEERdYAtb6TI';

	public function __construct() {
		add_action( 'woocommerce_new_order', array( $this, 'validation_start' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'auto_correct_shipping_checkbox' ) );

        add_action( 'save_post_shop_order', array( $this, 'maybe_run_auto_correct_save' ) );
		#add_action( 'woocommerce_new_subscription', array( $this, 'validation_start' ) );
	}

    public function maybe_run_auto_correct_save() {

        if( ( isset( $_POST[ 'auto_correct_shipping' ] ) && $_POST[ 'auto_correct_shipping' ] ) && isset( $_POST['post_ID'] ) ) {

            $post_id = $_POST['post_ID'];

            $first_name = isset( $_POST['_shipping_first_name'] ) ? $_POST['_shipping_first_name'] : "";
            $last_name = isset( $_POST['_shipping_last_name'] ) ? $_POST['_shipping_last_name'] : "";

            $shipping_data = array(
                'shipping_first_name' => $first_name,
                'shipping_last_name' => $last_name,
                'shipping_full_name' => $first_name . " " . $last_name,
                'shipping_company' => isset( $_POST['_shipping_company'] ) ? $_POST['_shipping_company'] : "",
                // Address Data
                'shipping_address_1' => isset( $_POST['_shipping_address_1'] ) ? $_POST['_shipping_address_1'] : "",
                'shipping_address_2' => isset( $_POST['_shipping_address_2'] ) ? $_POST['_shipping_address_2'] : "",
                'shipping_city' => isset( $_POST['_shipping_city'] ) ? $_POST['_shipping_city'] : "",
                'shipping_state' => isset( $_POST['_shipping_state'] ) ? $_POST['_shipping_state'] : "",
                'shipping_postcode' => isset( $_POST['_shipping_postcode'] ) ? $_POST['_shipping_postcode'] : "",
                'shipping_country' => isset( $_POST['_shipping_country'] ) ? $_POST['_shipping_country'] : ""
            );

            $this->run_auto_correct_on_save( $post_id, $shipping_data );

        }

    }


    public function run_auto_correct_on_save( $post_id, $shipping_data ) {
        $validated_data = $this->validate_address( $shipping_data );

        if( $validated_data ) {

            $matched_address_formatted = $validated_data['matched_address_formatted'];

            foreach( $matched_address_formatted as $key => $value ) {
                $_POST[$key] = $value;
            }
            
        } else {
            // fail


        }
    }

    public function auto_correct_shipping_checkbox() {
        ?>

        <div class="wac-after-address">

            <p class="form-field form-field auto-correct-field">
                <label for="excerpt"><?php _e( 'Auto-correct', 'woocommerce' ); ?>:</label>
                <input type="checkbox" name="auto_correct_shipping">
            </p>

        </div>

        <?php
        
    }

    /* public function maybe_auto_correct_create( $post_id ) {

        
		$shipping_data_temp = array(
			'shipping_first_name' => $order->get_shipping_first_name(),
			'shipping_last_name' => $order->get_shipping_last_name(),
			'shipping_full_name' => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
            'shipping_company' => $order->get_shipping_company(),
			// Address Data
			'shipping_address_1' => $order->get_shipping_address_1(),
			'shipping_address_2' => $order->get_shipping_address_2(),
			'shipping_city' => $order->get_shipping_city(),
			'shipping_state' => $order->get_shipping_state(),
			'shipping_postcode' => $order->get_shipping_postcode(),
			'shipping_country' => $order->get_shipping_country(),
		);

        $shipping_data = array(
			'shipping_first_name' => get_post_meta( $post_id, '_shipping_first_name', 1 ),
			'shipping_last_name' => get_post_meta( $post_id, '_shipping_last_name', 1 ),
			'shipping_full_name' => get_post_meta( $post_id, '_shipping_first_name', 1 ) . " " . get_post_meta( $post_id, '_shipping_last_name', 1 ),
            'shipping_company' => get_post_meta( $post_id, '_shipping_company', 1 ),
			// Address Data
			'shipping_address_1' => get_post_meta( $post_id, '_shipping_address_1', 1 ),
			'shipping_address_2' => get_post_meta( $post_id, '_shipping_address_2', 1 ),
			'shipping_city' => get_post_meta( $post_id, '_shipping_city', 1 ),
			'shipping_state' => get_post_meta( $post_id, '_shipping_state', 1 ),
			'shipping_postcode' => get_post_meta( $post_id, '_shipping_postcode', 1 ),
			'shipping_country' => get_post_meta( $post_id, '_shipping_country', 1 ),
		);


		$this->run_validation( $post_id, $shipping_data );

	} */

    function generate_address_fields( $validated_data ) {

        $address_fields = array(
            '_shipping_address_1' => $validated_data['address_line1'],
            '_shipping_address_2' => $validated_data['address_line2'],
            '_shipping_city' => $validated_data['city_locality'],
            '_shipping_state' => $validated_data['state_province'],
            '_shipping_postcode' => $validated_data['postal_code'],
            '_shipping_country' => $validated_data['country_code']
        );

        return $address_fields;
        
    }

    function update_address_fields( $match_address, $post_id ) {

        foreach( $match_address as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

    }

    function validate_address( $address_data ) {

        $config = array(
            'apiKey' => $this->apiKey,
            'pageSize' => 75,
            'retries' => 3,
            'timeout' => new DateInterval('PT15S')
        );
        

        $client = new ShipEngine( $config );
      
        $address = [
          [
            "name" => $address_data['shipping_full_name'],
            "company_name" => $address_data['shipping_company'],
            "address_line1" => $address_data['shipping_address_1'],
            "address_line2" => $address_data['shipping_address_2'],
            "city_locality" => $address_data['shipping_city'],
            "state_province" => $address_data['shipping_state'],
            "postal_code" => $address_data['shipping_postcode'],
            "country_code" => $address_data['shipping_country']
          ]
        ];

      
        try {

            $address_validate = $client->validateAddresses($address);
            $matched_address = $address_validate[0]['matched_address'];
            $original_address = $address_validate[0]['original_address'];

            $match_address_formatted = array();
            $original_address_formatted = array();

            if( !empty( $matched_address ) ) {
                $match_address_formatted = $this->generate_address_fields( $matched_address );
                $original_address_formatted = $this->generate_address_fields( $original_address );
            }

            $validated_data['matched_address_formatted'] = $match_address_formatted;
            $validated_data['original_address_formatted'] = $original_address_formatted;
            
            return $validated_data;
            
        } catch (ShipEngineException $e) {
            error_log( print_r( $e -> getMessage(), true ) );
        }

        return 0;
        

      }
    


}