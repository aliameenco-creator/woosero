<?php
/**
 * Plugin Name:       WooSEO Optimizer
 * Plugin URI:        https://github.com/AliRemotelyAvailable/wooseo-optimizer
 * Description:       All-in-one WooCommerce SEO plugin with AI-powered meta descriptions, product schema markup, SEO analysis, Google preview, social cards, enhanced breadcrumbs, and XML sitemap optimization. A free alternative to premium WooCommerce SEO plugins.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Ali - RemotelyAvailable
 * Author URI:        https://remotelyavailable.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wooseo-optimizer
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ──────────────────────────────────────────────
// CONSTANTS
// ──────────────────────────────────────────────
define( 'WSEO_VERSION', '1.0.0' );
define( 'WSEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSEO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ──────────────────────────────────────────────
// ACTIVATION / DEACTIVATION
// ──────────────────────────────────────────────
register_activation_hook( __FILE__, 'wseo_activate' );
function wseo_activate() {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            esc_html__( 'WooSEO Optimizer requires WooCommerce to be installed and active.', 'wooseo-optimizer' ),
            'Plugin Activation Error',
            array( 'back_link' => true )
        );
    }

    $defaults = array(
        'enable_ai_meta'        => 0,
        'enable_schema'         => 1,
        'enable_seo_analysis'   => 1,
        'enable_product_fields' => 1,
        'enable_google_preview' => 1,
        'enable_social_cards'   => 1,
        'enable_breadcrumbs'    => 1,
        'enable_sitemap_opt'    => 1,
        'enable_variant_ids'    => 1,
        'openai_api_key'        => '',
        'openai_model'          => 'gpt-4o-mini',
        'breadcrumb_separator'  => '&raquo;',
        'breadcrumb_home_text'  => 'Home',
    );

    add_option( 'wseo_version', WSEO_VERSION );
    add_option( 'wseo_settings', $defaults );
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'wseo_deactivate' );
function wseo_deactivate() {
    flush_rewrite_rules();
}

// ──────────────────────────────────────────────
// GITHUB AUTO-UPDATER
// ──────────────────────────────────────────────
if ( is_admin() ) {
    require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-updater.php';
}

// ──────────────────────────────────────────────
// LOAD TEXT DOMAIN
// ──────────────────────────────────────────────
add_action( 'init', 'wseo_load_textdomain' );
function wseo_load_textdomain() {
    load_plugin_textdomain( 'wooseo-optimizer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// ──────────────────────────────────────────────
// CHECK WOOCOMMERCE DEPENDENCY
// ──────────────────────────────────────────────
add_action( 'admin_init', 'wseo_check_woocommerce' );
function wseo_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'wseo_woocommerce_missing_notice' );
    }
}

function wseo_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( 'WooSEO Optimizer requires WooCommerce to be installed and active.', 'wooseo-optimizer' ); ?></p>
    </div>
    <?php
}

// ──────────────────────────────────────────────
// LOAD PLUGIN MODULES
// ──────────────────────────────────────────────
add_action( 'plugins_loaded', 'wseo_load_modules' );
function wseo_load_modules() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    $settings = get_option( 'wseo_settings', array() );

    // Always load admin settings page
    require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-admin.php';

    // Feature 1: AI Meta Description Generator
    if ( ! empty( $settings['enable_ai_meta'] ) ) {
        require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-ai-meta.php';
    }

    // Feature 2: Product Schema / Structured Data
    if ( ! empty( $settings['enable_schema'] ) ) {
        require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-schema.php';
    }

    // Feature 3: Product SEO Analysis
    if ( ! empty( $settings['enable_seo_analysis'] ) ) {
        require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-analysis.php';
    }

    // Feature 4: GTIN/Brand/Manufacturer Fields
    if ( ! empty( $settings['enable_product_fields'] ) ) {
        require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-product-fields.php';
    }

    // Feature 5: Google Search Preview
    if ( ! empty( $settings['enable_google_preview'] ) ) {
        require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-google-preview.php';
    }

    // Feature 6: Social Media Cards (OpenGraph / Pinterest)
    if ( ! empty( $settings['enable_social_cards'] ) ) {
        require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-social-cards.php';
    }

    // Feature 7: Enhanced Breadcrumbs
    if ( ! empty( $settings['enable_breadcrumbs'] ) ) {
        require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-breadcrumbs.php';
    }

    // Feature 8: XML Sitemap Optimization
    if ( ! empty( $settings['enable_sitemap_opt'] ) ) {
        require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-sitemap.php';
    }

    // Feature 9: Variant Global Identifiers
    if ( ! empty( $settings['enable_variant_ids'] ) ) {
        require_once WSEO_PLUGIN_DIR . 'includes/class-wseo-variant-ids.php';
    }
}

// ──────────────────────────────────────────────
// PLUGIN ACTION LINKS
// ──────────────────────────────────────────────
add_filter( 'plugin_action_links_' . WSEO_PLUGIN_BASENAME, 'wseo_action_links' );
function wseo_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wseo-settings' ) ) . '">' . esc_html__( 'Settings', 'wooseo-optimizer' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
