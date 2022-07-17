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


        // add_filter( 'woocommerce_before_order_object_save', array( $this, 'keep_past_address_records' ), 10, 2 );
	}


    public function keep_past_address_records( $post_obj, $data_store ) {


        $changes = $post_obj->get_changes();
	    $data = $post_obj->get_data();

        $past_address = array(
            'shipping_address_1' => $data['shipping']['address_1'],
            'shipping_address_2' => $data['shipping']['address_2'],
            'shipping_city' => $data['shipping']['city'],
            'shipping_state' => $data['shipping']['state'],
            'shipping_postcode' => $data['shipping']['postcode'],
            'shipping_country' => $data['shipping']['country']
        );

        $current_address_check = array(
            'address_1' => isset($changes['shipping_address_1']) ? $changes['shipping_address_1'] : "",
            'address_2' => isset($changes['shipping_address_2']) ? $changes['shipping_address_2'] : "",
            'city' => isset($changes['shipping_city']) ? $changes['shipping_city'] : "",
            'state' => isset($changes['shipping_state']) ? $changes['shipping_state'] : "",
            'postcode' => isset($changes['shipping_postcode']) ? $changes['shipping_postcode'] : "",
            'country' => isset($changes['shipping_country']) ? $changes['shipping_country'] : ""
        );

        $current_address = array(
            'address_1' => isset($changes['shipping_address_1']) ? $changes['shipping_address_1'] : $past_address['shipping_address_1'],
            'address_2' => isset($changes['shipping_address_2']) ? $changes['shipping_address_2'] : $past_address['shipping_address_2'],
            'city' => isset($changes['shipping_city']) ? $changes['shipping_city'] : $past_address['shipping_city'],
            'state' => isset($changes['shipping_state']) ? $changes['shipping_state'] : $past_address['shipping_state'],
            'postcode' => isset($changes['shipping_postcode']) ? $changes['shipping_postcode'] : $past_address['shipping_postcode'],
            'country' => isset($changes['shipping_country']) ? $changes['shipping_country'] : $past_address['shipping_country']
        );

        error_log( print_r( $current_address_check , true)  );
        
        if( !empty(array_filter($past_address)) && !empty(array_filter($current_address_check)) ) {

            $post_obj->add_order_note( '<b>Address before:</b> ' . implode(',', array_filter($past_address)), 0, 1 );
		    $post_obj->add_order_note( '<b>Address after:</b> ' . implode(',', array_filter($current_address) ), 0, 1 );

        }

    }

    public function add_post_note( $post_id, $note ) {

        $post_obj = 0;

        if( get_post_type( $post_id ) == 'shop_order' ) {
            $post_obj = new WC_Order( $post_id );
        } else if ( get_post_type( $post_id == 'shop_subscription' ) ) {
            $post_obj = new WC_Subscription( $post_id );
        }

        if( $post_obj ) {
            $post_obj->add_order_note( $note, 0, 1 );
        }

    }

    public function get_shipping_changes( $past_shipping_address, $current_shipping_address ) {

        if( $current_shipping_address !== $past_shipping_address ) {
            return $current_shipping_address;
        } else {
            return 0;
        }

    }

    public function current_shipping_address() {

        $current_shipping_address = array(
            // Address Data
            '_shipping_address_1' => isset( $_POST['_shipping_address_1'] ) ? $_POST['_shipping_address_1'] : "",
            '_shipping_address_2' => isset( $_POST['_shipping_address_2'] ) ? $_POST['_shipping_address_2'] : "",
            '_shipping_city' => isset( $_POST['_shipping_city'] ) ? $_POST['_shipping_city'] : "",
            '_shipping_state' => isset( $_POST['_shipping_state'] ) ? $_POST['_shipping_state'] : "",
            '_shipping_postcode' => isset( $_POST['_shipping_postcode'] ) ? $_POST['_shipping_postcode'] : "",
            '_shipping_country' => isset( $_POST['_shipping_country'] ) ? $_POST['_shipping_country'] : ""
        );

        return $current_shipping_address;

    }

    public function maybe_run_auto_correct_save() {


        $post_id = $_POST['post_ID'];

        $past_shipping_address = $this->past_shipping_address( $post_id );
        $current_shipping_address = $this->current_shipping_address();

        if( $this->get_shipping_changes( $past_shipping_address, $current_shipping_address ) ) {

            $this->add_post_note( $post_id, "<b>Past Address:</b> " . $this->get_pretty_address( $past_shipping_address ) );
            $this->add_post_note( $post_id, "<b>Current Address:</b> " . $this->get_pretty_address( $current_shipping_address ) );


            if( ( isset( $_POST[ 'auto_correct_shipping' ] ) && $_POST[ 'auto_correct_shipping' ] ) && isset( $_POST['post_ID'] ) ) {
                $first_name = isset( $_POST['_shipping_first_name'] ) ? $_POST['_shipping_first_name'] : "";
                $last_name = isset( $_POST['_shipping_last_name'] ) ? $_POST['_shipping_last_name'] : "";
                $current_shipping_address['_shipping_first_name'] = $first_name;
                $current_shipping_address['_shipping_last_name'] = $last_name;
                $current_shipping_address['_shipping_full_name'] = $first_name . " " . $last_name;
                $current_shipping_address['_shipping_company'] = isset( $_POST['_shipping_company'] ) ? $_POST['_shipping_company'] : "";
    
                $this->run_auto_correct_on_save( $post_id, $current_shipping_address );
            } else {
                // No address correction
                $this->add_post_note( $post_id, "Address Correction: disabled" );
            }

        }

        

    }

    public function past_shipping_address( $post_id ) {

        $shipping_data = array(
			'_shipping_address_1' => get_post_meta( $post_id, '_shipping_address_1', 1 ),
			'_shipping_address_2' => get_post_meta( $post_id, '_shipping_address_2', 1 ),
			'_shipping_city' => get_post_meta( $post_id, '_shipping_city', 1 ),
			'_shipping_state' => get_post_meta( $post_id, '_shipping_state', 1 ),
			'_shipping_postcode' => get_post_meta( $post_id, '_shipping_postcode', 1 ),
			'_shipping_country' => get_post_meta( $post_id, '_shipping_country', 1 ),
		);

        return $shipping_data;

    }

    public function get_pretty_address( $address_fields ) {

        $address_format = "";

        $i = 0;
        foreach( $address_fields as $key => $value ) {
            
            if( !empty( $value ) ) {
                if( $i == 0 ) {
                    $address_format .= $value;
                } else {
                    $address_format .= ', ' . $value;
                }
            }

            $i++;
        }

        return $address_format;
    }


    public function run_auto_correct_on_save( $post_id, $shipping_data ) {

        $validated_data = $this->validate_address( $shipping_data );

        if( $validated_data ) {

            

            $original_address_formatted = $validated_data['original_address_formatted'];
            $matched_address_formatted = $validated_data['matched_address_formatted'];

            foreach( $matched_address_formatted as $key => $value ) {
                $_POST[$key] = $value;
            }

            $this->add_post_note( $post_id, '<b>Matched Address:</b> ' . $this->get_pretty_address( $matched_address_formatted ) );
            $this->add_post_note( $post_id, 'Address Correction: ' . $validated_data['status'] );

            /* if( $validated_data['status'] == 'verified' ) {

            } else {
                // invalid address

                if( get_post_type( $post_id ) == 'shop_order' ) {
                    $order = new WC_Order( $post_id );
                    $order->add_order_note( 'Address Status: ' . $validated_data['status'] );
                }
            } */

            
            
        } else {
            // fail
            if( get_post_type( $post_id ) == 'shop_order' ) {
                $order = new WC_Order( $post_id );
                $order->add_order_note( 'Address verification failed. Please contact developer.' );
            }

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
            "name" => $address_data['_shipping_full_name'],
            "company_name" => $address_data['_shipping_company'],
            "address_line1" => $address_data['_shipping_address_1'],
            "address_line2" => $address_data['_shipping_address_2'],
            "city_locality" => $address_data['_shipping_city'],
            "state_province" => $address_data['_shipping_state'],
            "postal_code" => $address_data['_shipping_postcode'],
            "country_code" => $address_data['_shipping_country']
          ]
        ];

      
        try {

            $address_validate = $client->validateAddresses($address)[0];

            
            $matched_address = $address_validate['matched_address'];
            $original_address = $address_validate['original_address'];

            $match_address_formatted = array();
            $original_address_formatted = array();

            if( !empty( $matched_address ) ) {
                $match_address_formatted = $this->generate_address_fields( $matched_address );
            }
            
            $original_address_formatted = $this->generate_address_fields( $original_address );

            $address_validate['matched_address_formatted'] = $match_address_formatted;
            $address_validate['original_address_formatted'] = $original_address_formatted;
            
            return $address_validate;
            
        } catch (ShipEngineException $e) {
            error_log( print_r( $e -> getMessage(), true ) );
        }

        return 0;
        

      }
    


}