<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ShopOS_Restock_Stock_Monitor {

    public function __construct() {
        add_action( 'woocommerce_product_set_stock_status',   array( $this, 'on_status_change' ), 10, 3 );
        add_action( 'woocommerce_variation_set_stock_status', array( $this, 'on_status_change' ), 10, 3 );
        add_action( 'woocommerce_product_set_stock',          array( $this, 'on_qty_change' ) );
        add_action( 'woocommerce_variation_set_stock',        array( $this, 'on_qty_change' ) );
    }

    public function on_status_change( $product_id, $stock_status, $product ) {
        if ( 'instock' === $stock_status ) $this->notify( $product_id, $product );
    }

    public function on_qty_change( $product ) {
        if ( $product && $product->get_stock_quantity() > 0 && $product->is_in_stock() ) {
            $this->notify( $product->get_id(), $product );
        }
    }

    private function notify( $product_id, $product ) {
        $is_var = $product->is_type( 'variation' );
        $subs   = $is_var
            ? ShopOS_Restock_Database::get_waiting_for_product( $product->get_parent_id(), $product_id )
            : ShopOS_Restock_Database::get_waiting_for_product( $product_id, 0 );

        if ( empty( $subs ) ) return;

        $c = 0;
        foreach ( $subs as $s ) {
            if ( ShopOS_Restock_Email::send_notification( $s ) ) { ShopOS_Restock_Database::mark_notified( $s->id ); $c++; }
        }

        if ( $c ) {
            $log   = get_option( 'shopos_restock_notification_log', array() );
            $log[] = array( 'product_id' => $product_id, 'count' => $c, 'date' => current_time('mysql') );
            if ( count($log) > 100 ) $log = array_slice( $log, -100 );
            update_option( 'shopos_restock_notification_log', $log );
        }
    }

    public static function manual_notify( $product_id, $variation_id = 0 ) {
        $subs = ShopOS_Restock_Database::get_waiting_for_product( $product_id, $variation_id );
        if ( empty($subs) ) return 0;
        $c = 0;
        foreach ( $subs as $s ) {
            if ( ShopOS_Restock_Email::send_notification( $s ) ) { ShopOS_Restock_Database::mark_notified( $s->id ); $c++; }
        }
        return $c;
    }
}
