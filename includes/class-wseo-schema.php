<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Schema {

    public function __construct() {
        add_action( 'wp_head', array( $this, 'output_product_schema' ), 5 );
    }

    public function output_product_schema() {
        if ( ! is_singular( 'product' ) ) {
            return;
        }

        global $post;
        $product = wc_get_product( $post->ID );
        if ( ! $product ) {
            return;
        }

        $schema = array(
            '@context'    => 'https://schema.org/',
            '@type'       => 'Product',
            'name'        => $product->get_name(),
            'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
            'url'         => get_permalink( $post->ID ),
        );

        // Product image
        $image_id = $product->get_image_id();
        if ( $image_id ) {
            $schema['image'] = wp_get_attachment_url( $image_id );
        }

        // SKU
        $sku = $product->get_sku();
        if ( $sku ) {
            $schema['sku'] = $sku;
        }

        // Brand
        $brand = get_post_meta( $post->ID, '_wseo_brand', true );
        if ( $brand ) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name'  => $brand,
            );
        }

        // Manufacturer
        $manufacturer = get_post_meta( $post->ID, '_wseo_manufacturer', true );
        if ( $manufacturer ) {
            $schema['manufacturer'] = array(
                '@type' => 'Organization',
                'name'  => $manufacturer,
            );
        }

        // Color
        $color = get_post_meta( $post->ID, '_wseo_color', true );
        if ( $color ) {
            $schema['color'] = $color;
        }

        // GTIN / EAN / UPC / MPN
        $gtin = get_post_meta( $post->ID, '_wseo_gtin', true );
        if ( $gtin ) {
            $schema['gtin'] = $gtin;
        }

        $ean = get_post_meta( $post->ID, '_wseo_ean', true );
        if ( $ean ) {
            $schema['gtin13'] = $ean;
        }

        $upc = get_post_meta( $post->ID, '_wseo_upc', true );
        if ( $upc ) {
            $schema['gtin12'] = $upc;
        }

        $mpn = get_post_meta( $post->ID, '_wseo_mpn', true );
        if ( $mpn ) {
            $schema['mpn'] = $mpn;
        }

        // Offers
        $offers = array(
            '@type'           => 'Offer',
            'url'             => get_permalink( $post->ID ),
            'priceCurrency'   => get_woocommerce_currency(),
            'price'           => $product->get_price(),
            'priceValidUntil' => gmdate( 'Y-12-31' ),
            'availability'    => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'seller'          => array(
                '@type' => 'Organization',
                'name'  => get_bloginfo( 'name' ),
            ),
        );

        // Sale price
        if ( $product->is_on_sale() && $product->get_sale_price() ) {
            $offers['price'] = $product->get_sale_price();
        }

        // Item condition
        $offers['itemCondition'] = 'https://schema.org/NewCondition';

        $schema['offers'] = $offers;

        // Aggregate rating
        $rating_count = $product->get_rating_count();
        $avg_rating   = $product->get_average_rating();

        if ( $rating_count > 0 && $avg_rating > 0 ) {
            $schema['aggregateRating'] = array(
                '@type'       => 'AggregateRating',
                'ratingValue' => $avg_rating,
                'reviewCount' => $rating_count,
                'bestRating'  => '5',
                'worstRating' => '1',
            );
        }

        // Individual reviews
        $reviews = get_comments( array(
            'post_id' => $post->ID,
            'status'  => 'approve',
            'type'    => 'review',
            'number'  => 10,
        ) );

        if ( ! empty( $reviews ) ) {
            $schema['review'] = array();
            foreach ( $reviews as $review ) {
                $review_rating = get_comment_meta( $review->comment_ID, 'rating', true );
                $review_schema = array(
                    '@type'         => 'Review',
                    'author'        => array(
                        '@type' => 'Person',
                        'name'  => $review->comment_author,
                    ),
                    'datePublished' => gmdate( 'Y-m-d', strtotime( $review->comment_date ) ),
                    'reviewBody'    => wp_strip_all_tags( $review->comment_content ),
                );

                if ( $review_rating ) {
                    $review_schema['reviewRating'] = array(
                        '@type'       => 'Rating',
                        'ratingValue' => $review_rating,
                        'bestRating'  => '5',
                        'worstRating' => '1',
                    );
                }

                $schema['review'][] = $review_schema;
            }
        }

        // Output JSON-LD
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        echo "\n" . '</script>' . "\n";
    }
}

new WSEO_Schema();
