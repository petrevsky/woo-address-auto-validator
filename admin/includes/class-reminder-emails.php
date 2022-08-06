<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Reminder_Emails {


    public function __construct() {
        add_action( 'init', array($this, 'subscription_auto_login' ) );
        add_action( 'waav_send_scheduled_reminder', array( $this, 'maybe_send_scheduled_reminder' ), 10, 1 );
        add_action( 'template_redirect', array( $this, 'address_confirm_notice') );
    }

    public function address_confirm_notice() {

        if( isset( $_GET['waav_confirm'] ) ) {
            if( $_GET['waav_confirm'] == 'true' ) {
                wc_add_notice( __( 'Thank you, your address has been confirmed - we will ship your issues to the provided address.', 'woocommerce' ), 'success' );
            } else {
                wc_add_notice( __( 'There was error confirming your address. Please contact support.', 'woocommerce' ), 'error' );
            }
        }
    }

    public function subscription_auto_login() {

        $parmas_required = array( 'sub_id', 'user_id', 'key' );
        $url_params = array();
        $pass = 0;

        foreach( $parmas_required as $param ) {
            if( isset( $_GET[$param] ) ) {
                $url_params[$param] = $_GET[$param];
                $pass = 1;
            } else {
                $pass = 0;
                break;
            }
        }

        if( $pass ) {

            $subscription = wcs_get_subscription( $url_params['sub_id'] );
            $order = method_exists( $subscription, 'get_parent' ) ? $subscription->get_parent() : $subscription->order;
            $chk_user = get_user_by( 'id', $url_params['user_id'] );

            if( isset( $_GET['action'] ) ) {
                $action = $_GET['action'];
            }

            if ( $chk_user->ID == $url_params['user_id'] && $chk_user->waav_auto_log_key == $url_params['key'] ) {

                wp_set_auth_cookie( $chk_user->ID );

                if( $action == "change" ) {
                    wp_redirect( '/my-account/edit-address/shipping?subscription=' . $url_params['sub_id'] );
                } else if ( $action == "confirm" ) {

                    $note = "Customer has confirmed address - changing status back to active.";
                    $address_validator = new Address_Validator;
                    $address_validator->maybe_change_post_status( $subscription, $note );

                    wp_redirect( '/my-account/view-subscription/' . $url_params['sub_id'] . "?waav_confirm=true" );
                } else {
                    wp_redirect( '/my-account/view-subscription/' . $url_params['sub_id'] );
                }

                exit;
            }
            
        }
    }

    public function generate_auto_login_url( $sub_id, $user, $action = "" ) {

        $hash_key = md5( microtime() . rand() );
        update_user_meta( $user->ID, 'waav_auto_log_key', $hash_key );

        $q_string = '?sub_id=' .  $sub_id . '&user_id=' . $user->ID . '&key=' . $user->waav_auto_log_key;
        $url = get_permalink( get_option('woocommerce_myaccount_page_id') ) . $q_string;

        return $url;
    }

    public function action_auto_login_url( $url, $action = "" ) {
        $url .= '&action=' . $action;
        return $url;
    }

    public function maybe_modify_email_body( $subscription, $body ) {

        $url = $this->generate_auto_login_url( $subscription->get_id(), $subscription->get_user() );

        $parameters = array(
            'subscription_id' => $subscription->get_id(),
            'subscription_first_name' => $subscription->get_shipping_first_name(),
            'subscription_last_name' => $subscription->get_shipping_last_name(),
            'subscription_address' => str_replace( '<br/>', ', ', $subscription->get_formatted_shipping_address() ),
            'subscription_address_confirm' => $this->action_auto_login_url( $url, $action = "confirm" ),
            'subscription_address_change' => $this->action_auto_login_url( $url, $action = "change" ) 
        );

        foreach( $parameters as $key => $value ) {
            $param = '[' . $key . ']';
            $body = str_replace( $param, $value, $body );
        }

        error_log( $body );
        

        return $body;

    }


    public function maybe_schedule_email_reminder( $post_id ) {


        $enable_schedule = wpsf_get_setting( 'waav', 'tab_2_email_reminder', 'reocurring-enable' );
        
        if( $enable_schedule ) {
            
            $reocurring_days = wpsf_get_setting( 'waav', 'tab_2_email_reminder', 'reocurring-days' );

            if( $reocurring_days > 0 ) {

                $time = time() + ( $reocurring_days * 86400 );
                // $time = time() + (10 * $reocurring_days);
            
                wp_schedule_single_event(
                    $time, 
                    'waav_send_scheduled_reminder',
                    array( $post_id )
                );
                
            }

        }
        
        
        
        
    }

    public function maybe_send_scheduled_reminder( $post_id ) {

        $reminder_count = get_post_meta( $post_id, '_waav_reminder_count', 1 );
        $subject_addition = 'Reminder ' . $reminder_count . ': ';
        $this->maybe_send_reminder_email( $post_id, $subject_addition );

    }

    public function increase_reminders_sent( $post_id ) {
        if( $count = get_post_meta( $post_id, '_waav_reminder_count', 1 ) ) {
            update_post_meta( $post_id, '_waav_reminder_count', $count + 1 );
        } else {
            update_post_meta( $post_id, '_waav_reminder_count', 1 );
        }

        update_post_meta( $post_id, '_waav_last_reminder', time() );
    }


    public function maybe_send_reminder_email( $post_id, $subject_addition = 0 ) {

        $enable_sending = wpsf_get_setting( 'waav', 'tab_2_email_reminder', 'send-reminder-emails' );

        if( get_post_type( $post_id ) == "shop_subscription" && $enable_sending ) {
            
            $subscription = new WC_Subscription( $post_id );

            if( $subscription->get_status() == "invalid-address" ) {
                
                $to = $subscription->get_billing_email();

                $subject = wpsf_get_setting( 'waav', 'tab_2_email_reminder', 'subject' );

                if( $subject_addition ) {
                    $subject = $subject_addition . ' ' . $subject;
                }


                $body_html = wpautop( wpsf_get_setting( 'waav', 'tab_2_email_reminder', 'body' ) );

                $body = $this->maybe_modify_email_body( $subscription, $body_html );

                $headers = array('Content-Type: text/html; charset=UTF-8');
        
                if( !empty( $subject ) && !empty( $body ) && !empty( $to ) ) {
                    wp_mail( $to, $subject, $body, $headers );
                    $this->increase_reminders_sent( $post_id );
                    $this->maybe_schedule_email_reminder( $post_id );
                }

            }

        }

    }

}



?>