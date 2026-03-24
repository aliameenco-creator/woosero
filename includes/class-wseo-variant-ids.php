<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Variant_IDs {

    public function __construct() {
        // Add fields to each variation
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_variation_fields' ), 10, 3 );
        // Save variation fields
        add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ), 10, 2 );
        // Add variation data to JS
        add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_data' ), 10, 3 );
        // Output per-variation schema
        add_action( 'wp_head', array( $this, 'output_variation_schema' ), 6 );
    }

    public function add_variation_fields( $loop, $variation_data, $variation ) {
        echo '<div class="wseo-variation-fields" style="border-top:1px solid #eee; padding-top:10px; margin-top:10px;">';
        echo '<p style="font-weight:600; font-size:12px; color:#23282d; margin-bottom:8px;">🏷️ ' . esc_html__( 'WooSEO Identifiers', 'wooseo-optimizer' ) . '</p>';

        woocommerce_wp_text_input( array(
            'id'            => "_wseo_var_gtin_{$loop}",
            'name'          => "wseo_var_gtin[{$loop}]",
            'value'         => get_post_meta( $variation->ID, '_wseo_var_gtin', true ),
            'label'         => __( 'GTIN', 'wooseo-optimizer' ),
            'placeholder'   => __( 'GTIN for this variation', 'wooseo-optimizer' ),
            'wrapper_class' => 'form-row form-row-first',
        ) );

        woocommerce_wp_text_input( array(
            'id'            => "_wseo_var_ean_{$loop}",
            'name'          => "wseo_var_ean[{$loop}]",
            'value'         => get_post_meta( $variation->ID, '_wseo_var_ean', true ),
            'label'         => __( 'EAN', 'wooseo-optimizer' ),
            'placeholder'   => __( 'EAN-13 for this variation', 'wooseo-optimizer' ),
            'wrapper_class' => 'form-row form-row-last',
        ) );

        woocommerce_wp_text_input( array(
            'id'            => "_wseo_var_upc_{$loop}",
            'name'          => "wseo_var_upc[{$loop}]",
            'value'         => get_post_meta( $variation->ID, '_wseo_var_upc', true ),
            'label'         => __( 'UPC', 'wooseo-optimizer' ),
            'placeholder'   => __( 'UPC for this variation', 'wooseo-optimizer' ),
            'wrapper_class' => 'form-row form-row-first',
        ) );

        woocommerce_wp_text_input( array(
            'id'            => "_wseo_var_mpn_{$loop}",
            'name'          => "wseo_var_mpn[{$loop}]",
            'value'         => get_post_meta( $variation->ID, '_wseo_var_mpn', true ),
            'label'         => __( 'MPN', 'wooseo-optimizer' ),
            'placeholder'   => __( 'MPN for this variation', 'wooseo-optimizer' ),
            'wrapper_class' => 'form-row form-row-last',
        ) );

        echo '</div>';
    }

    public function save_variation_fields( $variation_id, $loop ) {
        $fields = array( 'wseo_var_gtin', 'wseo_var_ean', 'wseo_var_upc', 'wseo_var_mpn' );

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ][ $loop ] ) ) {
                update_post_meta( $variation_id, '_' . $field, sanitize_text_field( $_POST[ $field ][ $loop ] ) );
            }
        }
    }

    public function add_variation_data( $data, $product, $variation ) {
        $data['wseo_gtin'] = get_post_meta( $variation->get_id(), '_wseo_var_gtin', true );
        $data['wseo_ean']  = get_post_meta( $variation->get_id(), '_wseo_var_ean', true );
        $data['wseo_upc']  = get_post_meta( $variation->get_id(), '_wseo_var_upc', true );
        $data['wseo_mpn']  = get_post_meta( $variation->get_id(), '_wseo_var_mpn', true );
        return $data;
    }

    public function output_variation_schema() {
        if ( ! is_singular( 'product' ) ) {
            return;
        }

        global $post;
        $product = wc_get_product( $post->ID );

        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return;
        }

        $variations = $product->get_available_variations();
        if ( empty( $variations ) ) {
            return;
        }

        $brand = get_post_meta( $post->ID, '_wseo_brand', true );
        $offers = array();

        foreach ( $variations as $variation ) {
            $var_id    = $variation['variation_id'];
            $var_price = $variation['display_price'];
            $in_stock  = $variation['is_in_stock'];

            $offer = array(
                '@type'         => 'Offer',
                'price'         => $var_price,
                'priceCurrency' => get_woocommerce_currency(),
                'availability'  => $in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url'           => get_permalink( $post->ID ),
            );

            // Variation-specific identifiers
            $gtin = get_post_meta( $var_id, '_wseo_var_gtin', true );
            $ean  = get_post_meta( $var_id, '_wseo_var_ean', true );
            $upc  = get_post_meta( $var_id, '_wseo_var_upc', true );
            $mpn  = get_post_meta( $var_id, '_wseo_var_mpn', true );

            if ( $gtin ) {
                $offer['gtin'] = $gtin;
            }
            if ( $ean ) {
                $offer['gtin13'] = $ean;
            }
            if ( $upc ) {
                $offer['gtin12'] = $upc;
            }
            if ( $mpn ) {
                $offer['mpn'] = $mpn;
            }

            // Variation attributes as name
            $attr_summary = array();
            foreach ( $variation['attributes'] as $attr_key => $attr_val ) {
                if ( $attr_val ) {
                    $attr_summary[] = $attr_val;
                }
            }
            if ( ! empty( $attr_summary ) ) {
                $offer['name'] = $product->get_name() . ' - ' . implode( ', ', $attr_summary );
            }

            $offers[] = $offer;
        }

        if ( empty( $offers ) ) {
            return;
        }

        $schema = array(
            '@context' => 'https://schema.org/',
            '@type'    => 'Product',
            'name'     => $product->get_name(),
            'url'      => get_permalink( $post->ID ),
            'offers'   => $offers,
        );

        if ( $brand ) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name'  => $brand,
            );
        }

        echo "\n" . '<!-- WooSEO: Variation Schema -->' . "\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        echo "\n" . '</script>' . "\n";
    }
}

new WSEO_Variant_IDs();
