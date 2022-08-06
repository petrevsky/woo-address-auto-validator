<?php

class Invalid_Address_Woocommerce_Status_For_Subscription
{

    /**
     * Initialize Hooks.
     *
     * @access public
     */
    public function run()
    {
        // a woocommerce function to register new woocommerce status
        add_action('init', array($this, 'register_invalid_address_order_statuses'), 100);

        /**
         * Following hooks are from woocommerce. You can find its implementation for on-hold status
         * in file `woocommerce-subscriptions/includes/class-wc-subscriptions-manager.php`
         */
        add_filter('wc_order_statuses', array($this, 'invalid_address_wc_order_statuses'), 100, 1);
        add_action('woocommerce_order_status_invalid-address', array($this, 'put_subscription_on_invalid_address_for_order'), 100);


        //add_action('admin_notices', 'misha_custom_order_status_notices');

        //add_action( 'admin_action_mark_invalid-address', 'misha_bulk_process_custom_status' ); // admin_action_{action name}
        
        //add_action( 'init', 'register_shipped_status' );
        //add_filter( 'wc_order_statuses', 'add_invalid_address_to_order_statuses' );
        //add_filter( 'bulk_actions-edit-shop_order', 'custom_dropdown_bulk_actions_shop_order', 100, 1 );

    }

    /**
     * Registered new woocommerce status for `Invalid Address`.
     *
     * @access public
     *
     */
    public function register_invalid_address_order_statuses()
    {
        register_post_status('wc-invalid-address', array(
            'label' => _x('Invalid Address', 'Order status', 'custom-wcs-status-texts'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Invalid Address <span class="count">(%s)</span>', 'Invalid Address<span class="count">(%s)</span>', 'woocommerce'),
        ));
    }

    /**
     * Add new status `Invalid Address` to $order_statuses array.
     *
     * @access public
     *
     * @param array $order_statuses current order statuses array.
     * @return array $order_statuses with the new status added to it.
     */
    public function invalid_address_wc_order_statuses($order_statuses)
    {
        $order_statuses['wc-invalid-address'] = _x('Invalid Address', 'Order status', 'custom-wcs-status-texts');
        return $order_statuses;
    }

    /**
     * Change status of all the subscription in an order to `Invalid Address` when order status is changed to `Invalid Address`.
     *
     * @param object $order woocommerce order.
     * @access public
     */
    public function put_subscription_on_invalid_address_for_order($order)
    {
        $subscriptions = wcs_get_subscriptions_for_order($order, array('order_type' => 'parent'));

        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                try {
                    if (!$subscription->has_status(wcs_get_subscription_ended_statuses())) {
                        $subscription->update_status('invalid-address');
                    }
                } catch (Exception $e) {
                    // translators: $1: order number, $2: error message
                    $subscription->add_order_note(sprintf(__('Failed to update subscription status after order #%1$s was put to invalid-address: %2$s', 'woocommerce-subscriptions'), is_object($order) ? $order->get_order_number() : $order, $e->getMessage()));
                }
            }

            // Added a new action the same way subscription plugin has added for on-hold
            do_action('subscriptions_put_to_invalid_address_for_order', $order);
        }
    }
    

    // Adding custom status to admin order list bulk actions dropdown
    public function custom_dropdown_bulk_actions_shop_order( $actions ) {
        $new_actions = array();
    
        // Add new custom order status after processing
        foreach ($actions as $key => $action) {
            $new_actions[$key] = $action;
            if ('mark_processing' === $key) {
                $new_actions['mark_invalid-address'] = __( 'Mark Invalid Address', 'woocommerce' );
            }
        }
    
        return $new_actions;
    }



}
