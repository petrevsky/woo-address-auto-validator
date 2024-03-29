<?php


class Invalid_Address_Woocommerce_Subscription_Status
{

    /**
     * Initialize Hooks.
     *
     * @access public
     */
    public function run()
    {
        /**
         *  hook: apply_filters( 'wcs_subscription_statuses', $subscription_statuses )
         *  in file `woocommerce-subscriptions/woocommerce-subscriptions.php`
         */
        add_filter('woocommerce_subscriptions_registered_statuses', array($this, 'register_new_post_status'), 100, 1);

        /**
         *  hook: apply_filters( 'wcs_subscription_statuses', $subscription_statuses )
         *  in file `woocommerce-subscriptions/wcs-functions.php`
         */
        add_filter('wcs_subscription_statuses', array($this, 'add_new_subscription_statuses'), 100, 1);

        /**
         *  hook: apply_filters('woocommerce_can_subscription_be_updated_to', $can_be_updated, $new_status, $subscription)
         *  in file `woocommerce-subscriptions/includes/class-wc-subscription.php`
         */
        
        add_filter('woocommerce_can_subscription_be_updated_to', array($this, 'extends_can_be_updated'), 100, 3);
        
        
        add_filter('woocommerce_subscription_date_to_display', array($this, 'display_this_date'), 100, 3);
        /**
         * Alternative hooks available for the above hook :
         * apply_filters('woocommerce_can_subscription_be_updated_to_' . $new_status, $can_be_updated, $subscription );
         */

        /**
         *  hook: do_action('woocommerce_subscription_status_updated', $subscription, $new_status, $old_status)
         *  in file `woocommerce-subscriptions/includes/class-wc-subscription.php`
         */
        //add_action('woocommerce_subscription_status_updated', array($this, 'extends_update_status'), 100, 3);
        /**
         * Alternative hooks available for the above hook :
         * do_action('woocommerce_subscription_status_' . $new_status, $subscription);
         * do_action('woocommerce_subscription_status_' . $old_status . '_to_' . $new_status, $subscription);
         * do_action('woocommerce_subscription_status_updated', $subscription, $new_status, $old_status);
         * do_action('woocommerce_subscription_status_changed', $subscription_id, $old_status, $new_status);
         */

        /**
         *  hook: apply_filters('woocommerce_can_subscription_be_updated_to_' . $new_status, $can_be_updated, $subscription)
         *  in file `woocommerce-subscriptions/includes/class-wc-subscription.php`
         */
        add_filter('woocommerce_can_subscription_be_updated_to_active', array($this, 'enable_active_in_new_statuses'), 100, 2);
        add_filter('woocommerce_can_subscription_be_updated_to_on-hold', array($this, 'enable_on_hold_in_new_statuses'), 100, 2);
        add_filter('woocommerce_can_subscription_be_updated_to_pending-cancel', array($this, 'enable_pending_cancel_in_new_statuses'), 100, 2);

        /**
         *  hook: apply_filters('woocommerce_subscription_bulk_actions', $bulk_actions)
         *  in file `woocommerce-subscriptions/includes/class-wc-subscription.php`
         */
        add_filter('woocommerce_subscription_bulk_actions', array($this, 'add_new_status_bulk_actions'), 100, 1);

        /**
         *  Following is a WordPress core hook. You will find it's woocommerce-subscription implementation
         *  in file `includes/admin/class-wcs-admin-post-types.php`
         */
        add_action('load-edit.php', array($this, 'parse_bulk_actions'));

        /**
         *  WordPress hook for adding styles and scripts to dashboard
         */
        // add_action('admin_enqueue_scripts', array($this, 'custom_subscription_status_style'));
    }

    /**
     * Registered new status by adding `Invalid Address` to $registered_statuses array.
     *
     * @access public
     *
     * @param array $registered_statuses Registered Statuses array.
     * @return array $registered_statuses with the new status added to it.
     */
    public function register_new_post_status($registered_statuses)
    {
        $registered_statuses['wc-invalid-address'] = _nx_noop('Invalid Address <span class="count">(%s)</span>', 'Invalid Address <span class="count">(%s)</span>', 'post status label including post count', 'custom-wcs-status-texts');
        return $registered_statuses;
    }

    



    


    /**
     * Add new status `Invalid Address` to $subscription_statuses array.
     *
     * @access public
     *
     * @param array $subscription_statuses current subscription statuses array.
     * @return array $subscription_statuses with the new status added to it.
     */

    public function add_new_subscription_statuses($subscription_statuses)
    {
        $subscription_statuses['wc-invalid-address'] = _x('Invalid Address', 'Subscription status', 'custom-wcs-status-texts');
        return $subscription_statuses;
    }

    /**
     * Extends can_be_updated_to($status) functions of Woocommerce Subscription plugin.
     *
     * @access public
     *
     * @param boolean $can_be_updated default value if the current subscription can be updated to new status or not.
     * @param string $new_status New status to which current subscription it is to be updated.
     * @param object $subscription current subscription object.
     * @return boolean $can_be_updated If the current subscription can be updated to new status or not.
     */



    public function extends_can_be_updated($can_be_updated, $new_status, $subscription)
    {
        if ($new_status == 'invalid-address') {
            if ($subscription->payment_method_supports('subscription_suspension') && $subscription->has_status(array('active', 'pending', 'on-hold'))) {
                $can_be_updated = true;
            } else {
                $can_be_updated = false;
            }
        }
        return $can_be_updated;
    }





    public function display_this_date($date_to_display, $date_type, $subscription)
    {

        $date_type = wcs_normalise_date_type_key( $date_type, true );

		$timestamp_gmt = $subscription->get_time( $date_type, 'gmt' );

		// Don't display next payment date when the subscription is inactive
		if ( 'next_payment' == $date_type && ! ( $subscription->has_status( 'active' ) || $subscription->has_status( 'invalid-address' ) ) ) {
			$timestamp_gmt = 0;
		}

		if ( $timestamp_gmt > 0 ) {

			$time_diff = $timestamp_gmt - current_time( 'timestamp', true );

			if ( $time_diff > 0 && $time_diff < WEEK_IN_SECONDS ) {
				// translators: placeholder is human time diff (e.g. "3 weeks")
				$date_to_display = sprintf( __( 'In %s', 'woocommerce-subscriptions' ), human_time_diff( current_time( 'timestamp', true ), $timestamp_gmt ) );
			} elseif ( $time_diff < 0 && absint( $time_diff ) < WEEK_IN_SECONDS ) {
				// translators: placeholder is human time diff (e.g. "3 weeks")
				$date_to_display = sprintf( __( '%s ago', 'woocommerce-subscriptions' ), human_time_diff( current_time( 'timestamp', true ), $timestamp_gmt ) );
			} else {
				$date_to_display = date_i18n( wc_date_format(), $subscription->get_time( $date_type, 'site' ) );
			}
		} else {
			switch ( $date_type ) {
				case 'end':
					$date_to_display = __( 'Not yet ended', 'woocommerce-subscriptions' );
					break;
				case 'cancelled':
					$date_to_display = __( 'Not cancelled', 'woocommerce-subscriptions' );
					break;
				case 'next_payment':
				case 'trial_end':
				default:
					$date_to_display = _x( '-', 'original denotes there is no date to display', 'woocommerce-subscriptions' );
					break;
			}
		}
        
        return $date_to_display;
    }

    /**
     * Enable `Active` status in the status change dropdown of the subcription with this new status.
     * This function replaces the default code with the new one.
     * This code will also activate `reactivate` link in the list page for the subscription with `Invalid Address` status
     *
     * @access public
     *
     * @param boolean $can_be_updated default value if the current subscription can be updated to new status or not.
     * @param object $subscription current subscription object.
     * @return boolean $can_be_updated If the current Subscription can be updated to new status or not.
     */
    public function enable_active_in_new_statuses($can_be_updated, $subscription)
    {
        if ($subscription->payment_method_supports('subscription_reactivation') && $subscription->has_status(array('on-hold', 'invalid-address'))) {
            $can_be_updated = true;
        } elseif ( $subscription->has_status('pending-cancel') || $subscription->has_status('pending') ) {
            $can_be_updated = true;
        } else {
            $can_be_updated = false;
        }
        return $can_be_updated;
    }

    /**
     * Enable `On Hold` status in the status change dropdown of the subcription with this new status.
     * This function replaces the default code with the new one.
     * This code will also activate `suspend` link in the list page for the subscription with `Invalid Address` status
     *
     * @access public
     *
     * @param boolean $can_be_updated default value if the current subscription can be updated to new status or not.
     * @param object $subscription current subscription object.
     * @return boolean $can_be_updated If the current subscription can be updated to new status or not.
     */
    public function enable_on_hold_in_new_statuses($can_be_updated, $subscription)
    {
        if ($subscription->payment_method_supports('subscription_suspension') && $subscription->has_status(array('active', 'pending-cancel', 'invalid-address'))) {
            $can_be_updated = true;
        } else {
            $can_be_updated = false;
        }
        return $can_be_updated;
    }

    public function enable_pending_cancel_in_new_statuses($can_be_updated, $subscription)
    {
        if ($subscription->payment_method_supports('subscription_suspension') && $subscription->has_status(array('active', 'pending', 'invalid-address', 'on-hold'))) {
            $can_be_updated = true;
        } else {
            $can_be_updated = false;
        }
        return $can_be_updated;
    }
    

    /**
     * Actions to be performed while the status is updated should be handled here
     * For this example, I am simply copying the `On Hold` actions as it is.
     *
     * @access public
     *
     * @param object $subscription current subscription object.
     * @param string $new_status New status to which current subscription it is to be updated.
     * @param string $old_status Current status of current subscription.
     * @return boolean $can_be_updated If the current subscription can be updated to new status or not.
     */
    public function extends_update_status($subscription, $new_status, $old_status)
    {
        /*if ($new_status == 'cicaj_kurac') {
            $subscription->update_suspension_count($subscription->suspension_count + 1);
            wcs_maybe_make_user_inactive($subscription->customer_user);
        }*/
    }

    /**
     * Add the new status on the bulk actions drop down of the link
     *
     * @access public
     *
     * @param array $bulk_actions current bulk action array.
     * @return array $bulk_actions with the new status added to it.
     */
    public function add_new_status_bulk_actions($bulk_actions)
    {
        $bulk_actions['invalid-address'] = _x('Mark Invalid Address', 'an action on a subscription', 'custom-wcs-status-texts');
        return $bulk_actions;
    }

    /**
     * Deals with bulk actions. The style is similar to what WooCommerce is doing. Extensions will have to define their
     * own logic by copying the concept behind this method.
     *
     * @access public
     *
     */
    public function parse_bulk_actions()
    {

        // We only want to deal with shop_subscriptions. In case any other CPTs have an 'active' action
        if (!isset($_REQUEST['post_type']) || 'shop_subscription' !== $_REQUEST['post_type'] || !isset($_REQUEST['post'])) {
            return;
        }

        $action = '';

        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action']) {
            $action = $_REQUEST['action'];
        } elseif (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2']) {
            $action = $_REQUEST['action2'];
        }

        switch ($action) {
            case 'active':
            case 'on-hold':
            case 'cancelled':
            case 'invalid-address':
                $new_status = $action;
                break;
            default:
                return;
        }

        $report_action = 'marked_' . $new_status;

        $changed = 0;

        $subscription_ids = array_map('absint', (array) $_REQUEST['post']);

        $sendback_args = array(
            'post_type' => 'shop_subscription',
            $report_action => true,
            'ids' => join(',', $subscription_ids),
            'error_count' => 0,
        );

        foreach ($subscription_ids as $subscription_id) {
            $subscription = wcs_get_subscription($subscription_id);
            $order_note = _x('Subscription status changed by bulk edit:', 'Used in order note. Reason why status changed.', 'woocommerce-subscriptions');

            try {
                if ('cancelled' == $action) {
                    $subscription->cancel_order($order_note);
                } else {
                    $subscription->update_status($new_status, $order_note, true);
                }

                // Fire the action hooks
                switch ($action) {
                    case 'active':
                    case 'on-hold':
                    case 'cancelled':
                    case 'invalid-address':
                    case 'trash':
                        do_action('woocommerce_admin_changed_subscription_to_' . $action, $subscription_id);
                        break;
                }

                $changed++;
            } catch (Exception $e) {
                $sendback_args['error'] = urlencode($e->getMessage());
                $sendback_args['error_count']++;
            }
        }

        $sendback_args['changed'] = $changed;
        $sendback = add_query_arg($sendback_args, wp_get_referer() ? wp_get_referer() : '');
        wp_safe_redirect(esc_url_raw($sendback));

        exit();
    }


    /**
     * Add css to admin dashboard
     *
     * @access public
     *
     * @param string top level hook for current page
     *
     */
    public function custom_subscription_status_style($hook)
    {
        wp_register_style('subscription-status-style', plugin_dir_url(__FILE__) . 'assets/css/subscription-status.css');
        if ('edit.php' == $hook && ( $_GET['post_type']=='shop_subscription' || $_GET['post_type']== 'shop_order' )) {
            wp_enqueue_style('subscription-status-style');
        }
    }
    
}
