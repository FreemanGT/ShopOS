<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ShopOS_Restock_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_rsn_subscribe', array( $this, 'handle_subscribe' ) );
        add_action( 'wp_ajax_nopriv_rsn_subscribe', array( $this, 'handle_subscribe' ) );
    }

    public function handle_subscribe() {
        if ( ! check_ajax_referer( 'shopos_restock_subscribe', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'shopos-core' ) ) );
        }

        // Honeypot — real users can't fill a hidden field; bots usually do.
        // Respond with a generic success to avoid signalling detection.
        if ( ! empty( $_POST['_hp'] ) ) {
            wp_send_json_success( array(
                'message' => shopos_restock_get_option( 'form_success_message' ),
            ) );
        }

        // Per-IP rate limit (REMOTE_ADDR only — not spoofable via proxy
        // headers). 5 submissions / hour shared with any other caller that
        // uses the same bucket.
        if ( ! \ShopOS\Core\Core\Security::rate_limit( 'shopos_restock_subscribe', 5, HOUR_IN_SECONDS ) ) {
            wp_send_json_error( array( 'message' => __( 'Too many requests. Please try again later.', 'shopos-core' ) ) );
        }

        $product_id   = absint( $_POST['product_id'] ?? 0 );
        $variation_id = absint( $_POST['variation_id'] ?? 0 );
        $name         = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $email        = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product.', 'shopos-core' ) ) );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'shopos-core' ) ) );
        }

        $product = wc_get_product( $variation_id ? $variation_id : $product_id );
        if ( ! $product ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'shopos-core' ) ) );
        }

        if ( 'yes' === shopos_restock_get_option( 'enable_gdpr' ) ) {
            if ( empty( $_POST['gdpr'] ) || 'yes' !== $_POST['gdpr'] ) {
                wp_send_json_error( array( 'message' => __( 'Please confirm the consent checkbox to continue.', 'shopos-core' ) ) );
            }
        }

        if ( ShopOS_Restock_Database::exists( $email, $product_id, $variation_id ) ) {
            wp_send_json_error( array(
                'message'   => shopos_restock_get_option( 'form_duplicate_message' ),
                'duplicate' => true,
            ) );
        }

        $id = ShopOS_Restock_Database::insert( array(
            'product_id'     => $product_id,
            'variation_id'   => $variation_id,
            'customer_name'  => $name,
            'customer_email' => $email,
            'status'         => 'waiting',
        ) );

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'shopos-core' ) ) );
        }

        if ( 'yes' === shopos_restock_get_option( 'enable_confirmation' ) ) {
            $subscriber = ShopOS_Restock_Database::get( $id );
            ShopOS_Restock_Email::send_confirmation( $subscriber );
        }

        wp_send_json_success( array(
            'message' => shopos_restock_get_option( 'form_success_message' ),
        ) );
    }
}
