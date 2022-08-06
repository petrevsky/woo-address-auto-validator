<?php 

class Invalid_Address_Woocommerce_Status_For_Order {

    public function __construct() {
        add_filter( 'woocommerce_register_shop_order_post_statuses', array( $this, 'register_invalid_address_order_status' ) );
        add_filter( 'wc_order_statuses', array( $this, 'show_invalid_address_order_status' ) );
        add_filter( 'bulk_actions-edit-shop_order', array( $this, 'invalid_address_order_status_bulk' ) );
    }

    public function register_invalid_address_order_status( $order_statuses ){
    
        $order_statuses['invalid-address'] = array(                                 
          'label'                     => _x( 'Invalid Address Status', 'Order status', 'woocommerce' ),
          'public'                    => true,
          'exclude_from_search'       => false,
          'show_in_admin_all_list'    => true,
          'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Invalid Address Status <span class="count">(%s)</span>', 'Invalid Address Status <span class="count">(%s)</span>', 'woocommerce' ),                              
        );      
        return $order_statuses;
    }
    
    public function show_invalid_address_order_status( $order_statuses ) {      
        $order_statuses['invalid-address'] = _x( 'Invalid Address Status', 'Order status', 'woocommerce' );       
        return $order_statuses;
    }
     
     public function invalid_address_order_status_bulk( $bulk_actions ) {
   
        $bulk_actions['mark_invalid-address'] = 'Change status to invalid address status';
        return $bulk_actions;
    }
    
}


?>