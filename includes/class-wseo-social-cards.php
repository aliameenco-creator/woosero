<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Social_Cards {

    public function __construct() {
        add_action( 'wp_head', array( $this, 'output_social_meta' ), 2 );
    }

    public function output_social_meta() {
        if ( ! is_singular( 'product' ) ) {
            return;
        }

        global $post;
        $product = wc_get_product( $post->ID );
        if ( ! $product ) {
            return;
        }

        $meta_title = get_post_meta( $post->ID, '_wseo_meta_title', true );
        $meta_desc  = get_post_meta( $post->ID, '_wseo_meta_description', true );

        $title       = ! empty( $meta_title ) ? $meta_title : $product->get_name();
        $description = ! empty( $meta_desc ) ? $meta_desc : wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
        $description = wp_trim_words( $description, 30, '...' );
        $url         = get_permalink( $post->ID );
        $site_name   = get_bloginfo( 'name' );
        $currency    = get_woocommerce_currency();
        $price       = $product->get_price();
        $in_stock    = $product->is_in_stock();

        // Get product image
        $image_url = '';
        $image_id  = $product->get_image_id();
        if ( $image_id ) {
            $image_data = wp_get_attachment_image_src( $image_id, 'large' );
            if ( $image_data ) {
                $image_url = $image_data[0];
            }
        }

        // ── OpenGraph Tags ──
        echo "\n<!-- WooSEO Optimizer: Social Cards -->\n";
        echo '<meta property="og:type" content="product" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";

        if ( $image_url ) {
            echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
            echo '<meta property="og:image:alt" content="' . esc_attr( $title ) . '" />' . "\n";
        }

        // ── Product-specific OpenGraph (Facebook / Pinterest) ──
        echo '<meta property="product:price:amount" content="' . esc_attr( $price ) . '" />' . "\n";
        echo '<meta property="product:price:currency" content="' . esc_attr( $currency ) . '" />' . "\n";

        if ( $in_stock ) {
            echo '<meta property="product:availability" content="in stock" />' . "\n";
        } else {
            echo '<meta property="product:availability" content="out of stock" />' . "\n";
        }

        // Sale price
        if ( $product->is_on_sale() && $product->get_sale_price() ) {
            echo '<meta property="product:sale_price:amount" content="' . esc_attr( $product->get_sale_price() ) . '" />' . "\n";
            echo '<meta property="product:sale_price:currency" content="' . esc_attr( $currency ) . '" />' . "\n";
        }

        // Brand
        $brand = get_post_meta( $post->ID, '_wseo_brand', true );
        if ( $brand ) {
            echo '<meta property="product:brand" content="' . esc_attr( $brand ) . '" />' . "\n";
        }

        // Condition
        echo '<meta property="product:condition" content="new" />' . "\n";

        // SKU as retailer item ID
        $sku = $product->get_sku();
        if ( $sku ) {
            echo '<meta property="product:retailer_item_id" content="' . esc_attr( $sku ) . '" />' . "\n";
        }

        // ── Twitter Card Tags ──
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";

        if ( $image_url ) {
            echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '" />' . "\n";
        }

        // ── Pinterest Rich Pin Tags ──
        echo '<meta property="og:price:amount" content="' . esc_attr( $price ) . '" />' . "\n";
        echo '<meta property="og:price:currency" content="' . esc_attr( $currency ) . '" />' . "\n";

        echo "<!-- /WooSEO Optimizer: Social Cards -->\n\n";
    }
}

new WSEO_Social_Cards();
