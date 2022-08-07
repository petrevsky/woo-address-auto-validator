<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


use ShipEngine\ShipEngine;
use ShipEngine\Message\ShipEngineException;


class Shipping_Post_View {

    public $apiKey = 'TEST_7oWzD1wFdms731AFDpXDbXFxwTbGGMXPEERdYAtb6TI';

	public function __construct() {
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'generate_shipping_html' ) );
        add_action( 'add_meta_boxes', array( $this, 'order_meta_display' ) );
        add_action( 'wp_ajax_waav_validate_address', array( $this, 'validate_address_ajax' ) );
        add_action( 'wp_ajax_waav_generate_address_html', array( $this, 'generate_address_html' ) );
        add_action( 'wp_ajax_waav_reset_address', array( $this, 'reset_address_ajax' ) );
        add_action( 'wp_ajax_waav_check_invalid_address', array( $this, 'check_invalid_address_ajax' ) );
	}
    
    public function check_invalid_address_ajax() {

        $post_id = $_POST['post_id'];

        $address_validator = new Address_Validator();

        $checker = $address_validator->related_subscription_invalid_address( $post_id );

        echo $checker;
        die;
    } 

    public function reset_address_ajax() {

        $address_validator = new Address_Validator();
        $validated_data = 0;

        if ( wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {

            $post_id = $_POST['post_id'];
            $validated_data_temp = $this->get_verification_data( $post_id );
            
            if( $validated_data_temp ) {

                $original_address_formatted = $validated_data_temp['original_address_formatted'];
                $validated_data = $validated_data_temp;

                $address_validator->update_address_fields( $post_id, $original_address_formatted );
                $validated_data['replace_address'] = $this->replace_address( $original_address_formatted, $post_id );
                $address_validator = $address_validator->post_address_modified( $post_id, false, $validated_data_temp );
                
            }

        }

        echo json_encode( $validated_data );
        die;
    }

    public function generate_address_html() {

        $html = 0;

        if ( wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {

            ob_start();
            $this->auto_correct_shipping_checkbox( $_POST['post_id'] );
            $html = ob_get_contents();
            ob_end_clean();

        }

        echo $html;
        die;
    }

    public function validate_address_ajax() {
        
        $validated_data = 0;

        if ( wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {

            if( isset( $_POST['post_id'] ) && get_post_status ( $_POST['post_id'] ) ) {

                $post_id = $_POST['post_id'];

                $validated_data = $this->ajax_auto_correct( $post_id );
            }
            
        }

        // error_log( print_r( $validated_data , true ) );

        echo json_encode($validated_data);
        die;
    }


    public function replace_address( $address_formatted, $post_id ) {

        $additional_data = array();
        $additional_data['_shipping_first_name'] = get_post_meta( $post_id, '_shipping_first_name', 1 );
        $additional_data['_shipping_last_name'] = get_post_meta( $post_id, '_shipping_last_name', 1 );
        $additional_data['_shipping_company'] = get_post_meta( $post_id, '_shipping_company', 1 );

        $address_formatted = array_merge( $additional_data, $address_formatted );

        return $address_formatted;
    }


    public function ajax_auto_correct( $post_id ) {

        $address_validator = new Address_Validator();

        $current_shipping_address = $address_validator->get_post_shipping_address( $post_id );

        $validated_data = $address_validator->validate_address( $current_shipping_address );

        if( isset( $validated_data['matched_address_formatted'] ) && !empty( $validated_data['matched_address_formatted'] ) ) {
            $validated_data['replace_address'] = array();
            $matched_address_formatted = $validated_data['matched_address_formatted'];
            $address_validator->update_address_fields( $post_id, $matched_address_formatted );
            $address_validator->post_address_modified( $post_id, true, $validated_data );
            $validated_data['replace_address'] = $this->replace_address( $matched_address_formatted, $post_id ); 
        } else {
            $address_validator->post_address_modified( $post_id, false, $validated_data );
        }

        return $validated_data;

    }

    public function order_meta_display() {

        global $post;
        $id = $post->ID;
        $verification_status = get_post_meta( $id, '_verification_status', 1 );
        
        if( !empty( $verification_status ) ) {
        
            add_meta_box(
                'past-address-validation-data',
                'Past Address Validation',
                array( $this, 'past_validation_data' ),
                'shop_subscription',
                'side',
                'core'
            );

        }


        add_meta_box(
            'order-details',
            'Order Details',
            array( $this, 'display_order_meta' ),
            'shop_order',
            'normal',
            'core'
        );

    }

    public function display_order_meta( $post ) {
        
        print_r( get_post_meta( $post->ID ) );
    }

    public function past_validation_data( $post ) {

        $id = $post->ID;

        $verification_status = get_post_meta($id, '_verification_status', 1);
        
        
        if( !empty( $verification_status ) ) {

            $verification_status = get_post_meta($id, '_verification_status', 1);
            $address_precision = get_post_meta($id, '_address_precision', 1);
            $latitude = get_post_meta($id, '_latitude', 1);
            $longitude = get_post_meta($id, '_longitude', 1);
            $url = "http://www.google.com/maps/place/$latitude,$longitude";
       
    ?>
            <h3>    
                Verification
            </h3>
            <div>
                <p>
                    Address is: <strong><?php echo $verification_status; ?></strong>
                    <br>Address precision: <strong><?php echo $address_precision; ?></strong>

                    <?php if(!empty($latitude) && !empty($longitude)) { ?>
                        <br>Latitude: <strong><?php echo $latitude; ?></strong>
                        <br>Longtitude: <strong><?php echo $longitude; ?></strong>
                        <br><a href="<?php echo $url; ?>">View on Google Maps →</a>
                    <?php } ?> 
                </p>
            
            </div>

	<?php
        }
    }


    public function get_verification_data( $post_id ) {
        return get_post_meta( $post_id, '_waav_validated_data', 1 );
    }

    public function get_verification_message_merge( $verification_data ) {
        $message_merge = 0;

        if( isset( $verification_data['status'] ) && $verification_data['status'] == 'verified' ) {
            $message_merge = "Address Validated";
        }

        if( isset( $verification_data['messages'] ) ) {
            $messages = $verification_data['messages'];
            

            $i = 0;
            foreach( $messages as $message_data ) {

                if( !empty( $message_data['message'] ) ) {
                    

                    if($i == 0) {
                        $message_merge = $message_data['message'];
                    } else {
                        $message_merge .= ' ' . $message_data['message'];
                    }
                    
                }

                $i++;
            }
        }

        return $message_merge;

    }

    public function get_verification_icon( $verification_data ) {

        $icon = '';
        if( isset( $verification_data['status'] ) ) {

            $status = $verification_data['status'];

            switch ( $status ) {
                case 'verified':
                    $icon = 'home';
                    break;
                case 'unverified':
                    $icon = 'warning';
                    break;
                case 'warning':
                    $icon = 'warning';
                    break;
                default:
                    $icon = 'error';
            }

        }

        return $icon;

    }  


    public function generate_shipping_html( $post_id ) {
        ?>

        <div class="waav-post-container">
            <?php $this->auto_correct_shipping_checkbox( $post_id = 0 ); ?>
        </div>

        <?php 
    }

    public function auto_correct_shipping_checkbox( $post_id = 0 ) {

        global $post;

        if( isset( $post ) ) {
            $post_id = $post->ID;
        }

        $verification_data = $this->get_verification_data( $post_id );
        $message_merge = $this->get_verification_message_merge( $verification_data );
        $icon = $this->get_verification_icon( $verification_data );

        $address_validator = new Address_Validator();

        ?>

        

        <div class="waav-after-address">

            <p class="form-field form-field auto-correct-field">
                <label for="excerpt"><?php _e( 'Auto-correct', 'woocommerce' ); ?>:</label>
                <input type="checkbox" name="auto_correct_shipping" checked>
            </p>

        </div>

        <div class="waav-after-address-details">

            <span><strong>Address Validation:</strong></span>

            <div class="waav-address-message">

                <div class="waav-validation-issue">
                    
                    <?php if( $message_merge ) { ?>

                        <div class="waav-address-icon">
                            <img class="waav-icon waav-icon-<?php echo $icon; ?> color-<?php echo $icon; ?>" src=<?php echo plugin_dir_url( dirname( __FILE__ ) ) . "/images/" . $icon . '.svg'; ?>></img>
                        </div>

                        <div class="waav-message color-<?php echo $icon; ?>">
                            <?php echo $message_merge; ?>
                        </div>

                    <?php } ?>
                    
                    <?php if( $address_validator->is_address_modified( $post_id ) ) { ?>
                        <button class="waav-button-link waav-button-revert button-link" type="button">Revert</button>
                        <?php } else { ?>
                        <button class="waav-button-link waav-button-validate button-link" type="button">Validate Address</button>
                    <?php } ?>

                </div>

                
            </div>
            
        </div>

        <?php
        
    }

}