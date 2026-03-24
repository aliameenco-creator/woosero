<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Sitemap {

    public function __construct() {
        // Remove product_variation from WordPress core sitemaps
        add_filter( 'wp_sitemaps_post_types', array( $this, 'remove_variations_from_sitemap' ) );

        // Also filter Yoast/RankMath sitemaps if they exist
        add_filter( 'wpseo_sitemap_exclude_post_type', array( $this, 'yoast_exclude_variations' ), 10, 2 );
        add_filter( 'rank_math/sitemap/exclude_post_type', array( $this, 'rankmath_exclude_variations' ), 10, 2 );

        // Remove attachment pages from sitemap (they have no SEO value)
        add_filter( 'wp_sitemaps_post_types', array( $this, 'remove_attachments_from_sitemap' ) );
    }

    /**
     * Remove product variations from WordPress core XML sitemap
     */
    public function remove_variations_from_sitemap( $post_types ) {
        // Remove product variations — these are duplicate content
        unset( $post_types['product_variation'] );

        return $post_types;
    }

    /**
     * Remove attachment pages from sitemap
     */
    public function remove_attachments_from_sitemap( $post_types ) {
        unset( $post_types['attachment'] );

        return $post_types;
    }

    /**
     * Exclude variations from Yoast SEO sitemap (if installed)
     */
    public function yoast_exclude_variations( $excluded, $post_type ) {
        if ( 'product_variation' === $post_type ) {
            return true;
        }
        return $excluded;
    }

    /**
     * Exclude variations from RankMath sitemap (if installed)
     */
    public function rankmath_exclude_variations( $excluded, $post_type ) {
        if ( 'product_variation' === $post_type ) {
            return true;
        }
        return $excluded;
    }
}

new WSEO_Sitemap();
