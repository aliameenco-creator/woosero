<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Product_Fields {

    public function __construct() {
        // Add fields to General product data tab
        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_fields' ) );
        // Save fields
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_fields' ) );
    }

    public function add_fields() {
        global $post;

        echo '<div class="options_group wseo-product-fields">';
        echo '<h4 style="padding-left:12px; margin-top:16px; color:#23282d;">' . esc_html__( '🏷️ WooSEO — Product Identifiers', 'wooseo-optimizer' ) . '</h4>';

        woocommerce_wp_text_input( array(
            'id'          => '_wseo_brand',
            'label'       => __( 'Brand', 'wooseo-optimizer' ),
            'placeholder' => __( 'e.g. Nike, Apple, Samsung', 'wooseo-optimizer' ),
            'desc_tip'    => true,
            'description' => __( 'The brand name of the product. Used in Schema.org structured data.', 'wooseo-optimizer' ),
        ) );

        woocommerce_wp_text_input( array(
            'id'          => '_wseo_manufacturer',
            'label'       => __( 'Manufacturer', 'wooseo-optimizer' ),
            'placeholder' => __( 'e.g. Foxconn, Haier', 'wooseo-optimizer' ),
            'desc_tip'    => true,
            'description' => __( 'The manufacturer of the product (if different from brand).', 'wooseo-optimizer' ),
        ) );

        woocommerce_wp_text_input( array(
            'id'          => '_wseo_color',
            'label'       => __( 'Color', 'wooseo-optimizer' ),
            'placeholder' => __( 'e.g. Black, Red, Blue', 'wooseo-optimizer' ),
            'desc_tip'    => true,
            'description' => __( 'The primary color of the product.', 'wooseo-optimizer' ),
        ) );

        woocommerce_wp_text_input( array(
            'id'          => '_wseo_gtin',
            'label'       => __( 'GTIN', 'wooseo-optimizer' ),
            'placeholder' => __( 'Global Trade Item Number', 'wooseo-optimizer' ),
            'desc_tip'    => true,
            'description' => __( 'The Global Trade Item Number (GTIN) for this product.', 'wooseo-optimizer' ),
        ) );

        woocommerce_wp_text_input( array(
            'id'          => '_wseo_ean',
            'label'       => __( 'EAN (GTIN-13)', 'wooseo-optimizer' ),
            'placeholder' => __( 'European Article Number (13 digits)', 'wooseo-optimizer' ),
            'desc_tip'    => true,
            'description' => __( 'The EAN-13 barcode number for this product.', 'wooseo-optimizer' ),
        ) );

        woocommerce_wp_text_input( array(
            'id'          => '_wseo_upc',
            'label'       => __( 'UPC (GTIN-12)', 'wooseo-optimizer' ),
            'placeholder' => __( 'Universal Product Code (12 digits)', 'wooseo-optimizer' ),
            'desc_tip'    => true,
            'description' => __( 'The UPC barcode number for this product.', 'wooseo-optimizer' ),
        ) );

        woocommerce_wp_text_input( array(
            'id'          => '_wseo_mpn',
            'label'       => __( 'MPN', 'wooseo-optimizer' ),
            'placeholder' => __( 'Manufacturer Part Number', 'wooseo-optimizer' ),
            'desc_tip'    => true,
            'description' => __( 'The Manufacturer Part Number for this product.', 'wooseo-optimizer' ),
        ) );

        echo '</div>';
    }

    public function save_fields( $post_id ) {
        $fields = array(
            '_wseo_brand', '_wseo_manufacturer', '_wseo_color',
            '_wseo_gtin', '_wseo_ean', '_wseo_upc', '_wseo_mpn',
        );

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }
}

new WSEO_Product_Fields();
