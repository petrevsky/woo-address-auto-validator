<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


use ShipEngine\ShipEngine;
use ShipEngine\Message\ShipEngineException;


class Address_Validator {

    private $apiKey;

	public function __construct() {

        //$this->apiKey = 'TEST_7oWzD1wFdms731AFDpXDbXFxwTbGGMXPEERdYAtb6TI';
        $this->apiKey = wpsf_get_setting( 'waav', 'tab_1_general', 'api-shipengine' );

        add_action( 'woocommerce_subscription_payment_complete', array( $this, 'maybe_run_auto_correct_subscription_create' ), 9999 );
        add_action( 'woocommerce_order_payment_status_changed', array( $this, 'maybe_run_auto_correct_order_create' ), 9999 );
        // add_action( 'wcs_create_subscription', array( $this, 'maybe_run_auto_correct_subscription_create' ) );
        add_action( 'save_post_shop_order', array( $this, 'maybe_run_auto_correct_save' ) );
        add_action( 'save_post_shop_subscription', array( $this, 'maybe_run_auto_correct_save' ) );
        add_action( 'woocommerce_customer_save_address', array( $this, 'maybe_run_auto_correct_customer_save' ), 5, 2 );

    }

    public function run() {
        $this->$apiKey = wpsf_get_setting( 'waav', 'waav_general', 'api-shipengine' );
    }

    public function maybe_run_auto_correct_order_create( $post_id ) {

        $disable_order_validation = wpsf_get_setting( 'waav', 'tab_1_additional', 'disable-validation-orders' );

        if( !$disable_order_validation ) {
            $this->maybe_run_auto_correct_create( $post_id );
        }

    }

    public function maybe_run_auto_correct_subscription_create( $subscription ) {

        if ( 1 == $subscription->get_payment_count() ) {
            $disable_sub_validation = wpsf_get_setting( 'waav', 'tab_1_additional', 'disable-validation-subscriptions' );

            if( !$disable_sub_validation ) {
                $subscription_id = $subscription->get_id();
                $this->maybe_run_auto_correct_create( $subscription_id );
            }
        }

    }

    public function is_address_modified( $post_id ) {
        return get_post_meta( $post_id, '_waav_address_modified', 1 );
    }

    public function maybe_update_post_status( $post_id, $validated_data ) {

        $is_valid = $this->check_address_status( $validated_data );

        if( !$is_valid ) {
            if( $post_obj = $this->get_post_obj( $post_id ) ) {
                $post_obj->update_status( 'invalid-address' );

                $reminder_emails = new Reminder_Emails();
                $reminder_emails->maybe_send_reminder_email( $post_id );
                return 1;
            }
        }

        return 0;
    }

    public function check_address_status( $validated_data ) {

        $address_status = 1;

        if( !empty( $validated_data ) && isset( $validated_data['status'] ) ) {

            $valid_statuses = array(
                'verified'	// Address was successfully verified.
            );

            $invalid_statuses = array( 
                'unverified', // Address validation was not validated against the database because pre-validation failed.
                'warning',	// The address was validated, but the address should be double checked.
                'error'	// The address could not be validated with any degree of certainty against the database.
            );

            $address_status = 0;

            if( isset( $validated_data['status'] ) ) {
                $status = $validated_data['status'];

                if( in_array( $status, $valid_statuses ) ) {
                    $address_status = 1;
                }
            }

            
        }

        return $address_status;
        //if( isset( $validated_data['status'] )  )
    }

    public function maybe_change_post_status( $subscription, $note ) {

        // $order = method_exists( $subscription, 'get_parent' ) ? $subscription->get_parent() : $subscription->order;

        if( $subscription->get_status() == 'invalid-address' ) {
            update_post_meta( $subscription->get_id(), 'past_invalid', 1 );
            $subscription->update_status( 'active' );
            $subscription->add_order_note( $note );
        }

    }

    public function maybe_run_auto_correct_customer_save( $user_id, $address_type ) {

        
        if ( ! wcs_user_has_subscription( $user_id ) || wc_notice_count( 'error' ) > 0 || empty( $_POST['_wcsnonce'] ) || ! wp_verify_nonce( $_POST['_wcsnonce'], 'wcs_edit_address' ) ) {
			return;
		}

        if ( isset( $_POST['update_subscription_address'] ) ) {

            $post_id = absint( $_POST['update_subscription_address'] );

            $subscription = wcs_get_subscription( $post_id );
            
            if ( $subscription && self::can_user_edit_subscription_address( $subscription->get_id() ) ) {

                $this->maybe_change_post_status( $subscription, "Customer has updated shipping address from subscription area." );
                                
                // Run check here
                /* $past_shipping_address = $this->get_post_shipping_address( $post_id );
                $current_shipping_address = $this->customer_current_shipping_address();

                if( $this->get_shipping_changes( $past_shipping_address, $current_shipping_address ) ) {
                    $this->run_auto_correct_on_save( $post_id, $past_shipping_address, $current_shipping_address );
                    $this->add_post_note( $post_id, 'Customer updated shipping address from the account area.' );
                } */

            }
        }
    }

    private static function can_user_edit_subscription_address( $subscription, $user_id = 0 ) {
		$subscription = wcs_get_subscription( $subscription );
		$user_id      = empty( $user_id ) ? get_current_user_id() : absint( $user_id );

		return $subscription ? user_can( $user_id, 'view_order', $subscription->get_id() ) : false;
	}

    public function get_post_obj( $post_id ) {

        $post_obj = 0;

        if( get_post_type( $post_id ) == "shop_order" ) {
            $post_obj = new WC_Order( $post_id );
        } else if ( get_post_type( $post_id ) == "shop_subscription" ) {
            $post_obj = new WC_Subscription( $post_id );
        }

        return $post_obj;
    }

    public function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
     }
     

    public function maybe_run_auto_correct_create( $post_id ) {

        

        if( isset( $_POST['post_ID'] ) ) {
            return;
        }
        
        $post_obj = $this->get_post_obj( $post_id );

        if( $post_obj ) {

            $shipping_data = $this->get_post_shipping_address( $post_id );

            $is_renewal = get_post_meta( $post_id, '_subscription_renewal', 1 );

            if ( !empty( array_filter( $shipping_data ) ) && !$this->first_run_check( $post_id ) && !$is_renewal ) {
                // start validating data
                $this->run_auto_correct_on_create( $post_id, $shipping_data );
            }

        }
    }

    public function add_post_note( $post_id, $note ) {

        $post_obj = $this->get_post_obj( $post_id );

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
            '_shipping_first_name' => isset( $_POST['_shipping_first_name'] ) ? $_POST['_shipping_first_name'] : "",
            '_shipping_last_name' => isset( $_POST['_shipping_last_name'] ) ? $_POST['_shipping_last_name'] : "",
            '_shipping_company' => isset( $_POST['_shipping_last_name'] ) ? $_POST['_shipping_company'] : "",
            '_shipping_address_1' => isset( $_POST['_shipping_address_1'] ) ? $_POST['_shipping_address_1'] : "",
            '_shipping_address_2' => isset( $_POST['_shipping_address_2'] ) ? $_POST['_shipping_address_2'] : "",
            '_shipping_city' => isset( $_POST['_shipping_city'] ) ? $_POST['_shipping_city'] : "",
            '_shipping_state' => isset( $_POST['_shipping_state'] ) ? $_POST['_shipping_state'] : "",
            '_shipping_postcode' => isset( $_POST['_shipping_postcode'] ) ? $_POST['_shipping_postcode'] : "",
            '_shipping_country' => isset( $_POST['_shipping_country'] ) ? $_POST['_shipping_country'] : ""
        );

        return $current_shipping_address;

    }

    public function customer_current_shipping_address() {

        $current_shipping_address = array(
            '_shipping_first_name' => isset( $_POST['shipping_first_name'] ) ? $_POST['shipping_first_name'] : "",
            '_shipping_last_name' => isset( $_POST['shipping_last_name'] ) ? $_POST['shipping_last_name'] : "",
            '_shipping_company' => isset( $_POST['shipping_last_name'] ) ? $_POST['shipping_company'] : "",
            '_shipping_address_1' => isset( $_POST['shipping_address_1'] ) ? $_POST['shipping_address_1'] : "",
            '_shipping_address_2' => isset( $_POST['shipping_address_2'] ) ? $_POST['shipping_address_2'] : "",
            '_shipping_city' => isset( $_POST['shipping_city'] ) ? $_POST['shipping_city'] : "",
            '_shipping_state' => isset( $_POST['shipping_state'] ) ? $_POST['shipping_state'] : "",
            '_shipping_postcode' => isset( $_POST['shipping_postcode'] ) ? $_POST['shipping_postcode'] : "",
            '_shipping_country' => isset( $_POST['shipping_country'] ) ? $_POST['shipping_country'] : ""
        );

        return $current_shipping_address;

    }

    public function maybe_run_auto_correct_save() {

        if( isset( $_POST['post_ID'] ) ) {

            $post_id = $_POST['post_ID'];

            $past_shipping_address = $this->get_post_shipping_address( $post_id );
            $current_shipping_address = $this->current_shipping_address();

            if( $this->get_shipping_changes( $past_shipping_address, $current_shipping_address ) ) {

                $address_correct = 0;

                if( ( isset( $_POST[ 'auto_correct_shipping' ] ) && $_POST[ 'auto_correct_shipping' ] ) ) {
                    $address_correct = 1;
                }

                $this->run_auto_correct_on_save( $post_id, $past_shipping_address, $current_shipping_address, $address_correct );

                if( !$address_correct ) {
                    $this->add_post_note( $post_id, "Address correction is disabled, no changes were made to the current address." );
                }

            }

        }

    }

    public function get_post_shipping_address( $post_id ) {

        $shipping_data = array(
			'_shipping_first_name' => get_post_meta( $post_id, '_shipping_first_name', 1 ),
			'_shipping_last_name' => get_post_meta( $post_id, '_shipping_last_name', 1 ),
			'_shipping_company' => get_post_meta( $post_id, '_shipping_company', 1 ),
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

        $unset_vars = ['_shipping_first_name', '_shipping_last_name', '_shipping_company'];

        foreach($unset_vars as $var) {
            if( array_key_exists( $var, $address_fields ) ) {
                unset( $address_fields[$var] );
            }
        }

        $address_format = implode( ', ', array_filter( $address_fields ) );

        return $address_format;
    }

    public function post_address_modified( $post_id, $modified, $validated_data = array() ) {

        update_post_meta( $post_id, '_waav_address_modified', $modified );
        update_post_meta( $post_id, '_waav_verification_status', isset( $validated_data['status'] ) ? $validated_data['status'] : 0 );
        update_post_meta( $post_id, '_waav_validated_data', $validated_data );
        
    }

    public function run_auto_correct_on_save( $post_id, $past_shipping_address, $current_shipping_address, $address_correct = false ) {


        $validated_data = $this->validate_address( $current_shipping_address );

        $this->add_post_note( $post_id, "<b>Past Address:</b> " . $this->get_pretty_address( $past_shipping_address ) );
        $this->add_post_note( $post_id, "<b>Current Address:</b> " . $this->get_pretty_address( $current_shipping_address ) );

        if( $validated_data ) {

            $original_address_formatted = $validated_data['original_address_formatted'];
            $matched_address_formatted = $validated_data['matched_address_formatted'];

            // Setting matched_address as $_POST
            if( $address_correct && !empty( $matched_address_formatted ) ) {

                foreach( $matched_address_formatted as $key => $value ) {
                    $_POST[$key] = $value;
                }

                $this->post_address_modified( $post_id, true, $validated_data );
                $this->add_post_note( $post_id, '<b>Matched Address:</b> ' . $this->get_pretty_address( $matched_address_formatted ) );

            } else {
                $this->post_address_modified( $post_id, false, $validated_data );
            }

            
            $this->add_post_note( $post_id, 'Address Verification: ' . $validated_data['status'] );

        } else {
            $post_obj->add_post_note( 'Address verification failed. Please contact developer.' );
        }

    }

    public function related_subscription_invalid_address( $post_id ) {

        $subscriptions_ids = array();
        if( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
            $subscriptions_ids = wcs_get_subscriptions_for_order( $post_id, array( 'order_type' => 'any' ) );
        }

        $checker = 0;
        if( !empty( $subscriptions_ids ) ) { 
            foreach( $subscriptions_ids as $subscription_id => $subscription_obj ) {
                $status = $subscription_obj->get_status();

                if( $status == 'invalid-address' ) {
                    $checker = 1;
                }
            }
        
        }

        return $checker;
    }

    public function has_related_subscription( $post_id ) {

        $subscriptions_ids = array();
        if( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
            $subscriptions_ids = wcs_get_subscriptions_for_order( $post_id, array( 'order_type' => 'any' ) );
        }

        if( !empty( $subscriptions_ids ) ) {
            return 1;
        }

        return 0;
    }

    public function run_auto_correct_on_create( $post_id, $shipping_data ) {

        $validated_data = $this->validate_address( $shipping_data );

        $disable_auto_correct = wpsf_get_setting( 'waav', 'tab_1_additional', 'disable-auto-correct' );

        if( $validated_data ) {

            $address_status = isset( $validated_data['status'] ) ? $validated_data['status'] : 0;

            $original_address_formatted = $validated_data['original_address_formatted'];
            $matched_address_formatted = $validated_data['matched_address_formatted'];

            $this->add_post_note( $post_id, "<b>Current Address:</b> " . $this->get_pretty_address( $shipping_data ) );

            if( !empty( $validated_data['matched_address'] ) && $this->check_address_status( $validated_data ) && !$disable_auto_correct ) {
                // Disable updating to auto corrected address
                $this->post_address_modified( $post_id, true, $validated_data );
                $this->update_address_fields( $post_id, $matched_address_formatted );
                $this->add_post_note( $post_id, '<b>Matched Address:</b> ' . $this->get_pretty_address( $matched_address_formatted ) );
            } else {
                $this->post_address_modified( $post_id, false, $validated_data );
            }

            $this->add_post_note( $post_id, 'Address Verification: ' . $validated_data['status'] );

            if( $disable_auto_correct ) {
                $this->add_post_note( $post_id, 'Address correction is disabled, no changes were made to the current address.' );
            }

            if( !$this->has_related_subscription( $post_id ) ) {
                $this->maybe_update_post_status( $post_id, $validated_data ); // update post status according data validation
            }

        } else {
            $this->post_address_modified( $post_id, false, $validated_data );
            $this->add_post_note( $post_id, 'Address verification failed. Please contact developer.' );
        }

        $this->first_run_save( $post_id );
    }

    public function first_run_check( $post_id ) {
        $address_correct_first_run = get_post_meta( $post_id, '_waav_validator_first_run', 1 );
        return $address_correct_first_run;
    }

    public function first_run_save( $post_id ) {
        $address_correct_first_run = update_post_meta( $post_id, '_waav_validator_first_run', 1 );
        return $address_correct_first_run;
    }

    public function generate_address_fields( $validated_data ) {

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

    public function update_address_fields( $post_id, $match_address ) {

        foreach( $match_address as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

    }

    public function api_key_test( $api_key ) {

        $config = array(
            'apiKey' => $api_key,
            'pageSize' => 75,
            'retries' => 3,
            'timeout' => new DateInterval('PT15S')
        );

        $client = new ShipEngine( $config );

        $dummy_data = array(
            [
                "address_line1" => "525 S Winchester Blvd",
                "city_locality" => "San Jose",
                "state_province" => "CA",
                "postal_code" => "95128",
                "country_code" => "US"
            ]
        );


        $success = 0;

        try {
            $client->validateAddresses($dummy_data);
            $success = 1;
        } catch (ShipEngineException $e) {
            $error = $e -> getMessage();
        }

        if( $success ) {
            return 1;
        } else {
            return $error;
        }

    }


    

    public function validate_address( $address_data ) {

        $config = array(
            'apiKey' => $this->apiKey,
            'pageSize' => 75,
            'retries' => 3,
            'timeout' => new DateInterval('PT15S')
        );
        

        try {

            $client = new ShipEngine( $config );

        } catch (ShipEngineException $e) {
            error_log( print_r( $e -> getMessage(), true ) );

            return 0;
        }

        $admin_notices = new Admin_Notices();
      
        $address = [
          [
            "name" => $address_data['_shipping_first_name'] . " " . $address_data['_shipping_last_name'],
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

            $admin_notices->update_option_value( 'address_validaton_failed', 0 );
            
            return $address_validate;
            
        } catch (ShipEngineException $e) {

            $admin_notices->update_option_value( 'address_validaton_failed', 1 );

            error_log( print_r( $e -> getMessage(), true ) );
        }

        return 0;
        

      }
    


}