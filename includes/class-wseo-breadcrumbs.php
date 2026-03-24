<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Breadcrumbs {

    public function __construct() {
        // Override WooCommerce breadcrumb defaults
        add_filter( 'woocommerce_breadcrumb_defaults', array( $this, 'customize_breadcrumbs' ) );
        // Add Schema.org BreadcrumbList JSON-LD
        add_action( 'wp_head', array( $this, 'output_breadcrumb_schema' ), 3 );
    }

    public function customize_breadcrumbs( $defaults ) {
        $settings = get_option( 'wseo_settings', array() );
        $sep      = $settings['breadcrumb_separator'] ?? '&raquo;';
        $home     = $settings['breadcrumb_home_text'] ?? 'Home';

        $defaults['delimiter']   = ' <span class="wseo-breadcrumb-sep"> ' . esc_html( $sep ) . ' </span> ';
        $defaults['wrap_before'] = '<nav class="wseo-breadcrumbs woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'wooseo-optimizer' ) . '">';
        $defaults['wrap_after']  = '</nav>';
        $defaults['before']      = '<span class="wseo-breadcrumb-item">';
        $defaults['after']       = '</span>';
        $defaults['home']        = esc_html( $home );

        return $defaults;
    }

    public function output_breadcrumb_schema() {
        if ( ! is_singular( 'product' ) && ! is_product_category() ) {
            return;
        }

        $items    = array();
        $position = 1;

        // Home
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => get_bloginfo( 'name' ),
            'item'     => home_url( '/' ),
        );

        // Shop page
        $shop_page_id = wc_get_page_id( 'shop' );
        if ( $shop_page_id > 0 ) {
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => get_the_title( $shop_page_id ),
                'item'     => get_permalink( $shop_page_id ),
            );
        }

        if ( is_singular( 'product' ) ) {
            global $post;
            $product = wc_get_product( $post->ID );

            // Product categories (primary)
            $terms = get_the_terms( $post->ID, 'product_cat' );
            if ( $terms && ! is_wp_error( $terms ) ) {
                // Get the deepest category (most specific)
                $deepest = $this->get_deepest_term( $terms );
                if ( $deepest ) {
                    // Build category hierarchy
                    $ancestors = get_ancestors( $deepest->term_id, 'product_cat', 'taxonomy' );
                    $ancestors = array_reverse( $ancestors );

                    foreach ( $ancestors as $ancestor_id ) {
                        $ancestor = get_term( $ancestor_id, 'product_cat' );
                        if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                            $items[] = array(
                                '@type'    => 'ListItem',
                                'position' => $position++,
                                'name'     => $ancestor->name,
                                'item'     => get_term_link( $ancestor ),
                            );
                        }
                    }

                    $items[] = array(
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => $deepest->name,
                        'item'     => get_term_link( $deepest ),
                    );
                }
            }

            // Current product (no URL for last item per Google spec)
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $product ? $product->get_name() : get_the_title(),
            );
        }

        if ( is_product_category() ) {
            $term      = get_queried_object();
            $ancestors = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );
            $ancestors = array_reverse( $ancestors );

            foreach ( $ancestors as $ancestor_id ) {
                $ancestor = get_term( $ancestor_id, 'product_cat' );
                if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                    $items[] = array(
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => $ancestor->name,
                        'item'     => get_term_link( $ancestor ),
                    );
                }
            }

            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $term->name,
            );
        }

        if ( count( $items ) < 2 ) {
            return;
        }

        $schema = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        );

        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        echo "\n" . '</script>' . "\n";
    }

    private function get_deepest_term( $terms ) {
        $deepest = null;
        $max     = -1;

        foreach ( $terms as $term ) {
            $depth = count( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
            if ( $depth > $max ) {
                $max     = $depth;
                $deepest = $term;
            }
        }

        return $deepest;
    }
}

new WSEO_Breadcrumbs();
