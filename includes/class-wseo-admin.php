<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function add_menu() {
        add_menu_page(
            __( 'WooSEO Optimizer', 'wooseo-optimizer' ),
            __( 'WooSEO', 'wooseo-optimizer' ),
            'manage_options',
            'wseo-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-chart-area',
            56
        );
    }

    public function register_settings() {
        register_setting( 'wseo_settings_group', 'wseo_settings', array(
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
        ) );
    }

    public function sanitize_settings( $input ) {
        $sanitized = array();

        // Toggle features
        $toggles = array(
            'enable_ai_meta', 'enable_schema', 'enable_seo_analysis',
            'enable_product_fields', 'enable_google_preview', 'enable_social_cards',
            'enable_breadcrumbs', 'enable_sitemap_opt', 'enable_variant_ids',
        );

        foreach ( $toggles as $toggle ) {
            $sanitized[ $toggle ] = isset( $input[ $toggle ] ) ? 1 : 0;
        }

        // API settings
        $sanitized['openai_api_key']       = sanitize_text_field( $input['openai_api_key'] ?? '' );
        $sanitized['openai_model']         = sanitize_key( $input['openai_model'] ?? 'gpt-4o-mini' );
        $sanitized['breadcrumb_separator'] = sanitize_text_field( $input['breadcrumb_separator'] ?? '&raquo;' );
        $sanitized['breadcrumb_home_text'] = sanitize_text_field( $input['breadcrumb_home_text'] ?? 'Home' );

        return $sanitized;
    }

    public function enqueue_admin_assets( $hook_suffix ) {
        // Load on settings page AND product edit pages
        $allowed = array( 'toplevel_page_wseo-settings', 'post.php', 'post-new.php' );
        if ( ! in_array( $hook_suffix, $allowed, true ) ) {
            return;
        }

        // On post pages, only load for products
        if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
            global $post;
            if ( ! $post || 'product' !== $post->post_type ) {
                return;
            }
        }

        wp_enqueue_style( 'wseo-admin-style', WSEO_PLUGIN_URL . 'admin/css/admin.css', array(), WSEO_VERSION );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = get_option( 'wseo_settings', array() );
        ?>
        <div class="wrap wseo-wrap">
            <h1><?php esc_html_e( 'WooSEO Optimizer Settings', 'wooseo-optimizer' ); ?></h1>
            <p class="wseo-subtitle"><?php esc_html_e( 'Your free WooCommerce SEO powerhouse. Toggle features on/off and configure your AI settings below.', 'wooseo-optimizer' ); ?></p>

            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'wseo_settings_group' ); ?>

                <!-- AI CONFIGURATION -->
                <div class="wseo-card">
                    <h2><?php esc_html_e( '🤖 AI Configuration', 'wooseo-optimizer' ); ?></h2>
                    <p class="wseo-card-desc"><?php esc_html_e( 'Connect your OpenAI API key to enable AI-powered meta description and title generation for your products.', 'wooseo-optimizer' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="wseo_openai_key"><?php esc_html_e( 'OpenAI API Key', 'wooseo-optimizer' ); ?></label></th>
                            <td>
                                <input type="password" id="wseo_openai_key" name="wseo_settings[openai_api_key]" value="<?php echo esc_attr( $settings['openai_api_key'] ?? '' ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Get your API key from platform.openai.com', 'wooseo-optimizer' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="wseo_openai_model"><?php esc_html_e( 'AI Model', 'wooseo-optimizer' ); ?></label></th>
                            <td>
                                <select id="wseo_openai_model" name="wseo_settings[openai_model]">
                                    <option value="gpt-4o-mini" <?php selected( $settings['openai_model'] ?? '', 'gpt-4o-mini' ); ?>>GPT-4o Mini (Fast & Cheap)</option>
                                    <option value="gpt-4o" <?php selected( $settings['openai_model'] ?? '', 'gpt-4o' ); ?>>GPT-4o (Best Quality)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- FEATURE TOGGLES -->
                <div class="wseo-card">
                    <h2><?php esc_html_e( '⚡ Feature Toggles', 'wooseo-optimizer' ); ?></h2>
                    <p class="wseo-card-desc"><?php esc_html_e( 'Enable or disable individual features. Only active features load on your site — zero bloat.', 'wooseo-optimizer' ); ?></p>

                    <table class="form-table wseo-toggles">
                        <?php
                        $features = array(
                            'enable_ai_meta'        => array(
                                'label' => __( 'AI Meta Descriptions & Titles', 'wooseo-optimizer' ),
                                'desc'  => __( 'Auto-generate SEO titles and meta descriptions for products using AI. Requires OpenAI API key.', 'wooseo-optimizer' ),
                            ),
                            'enable_schema'         => array(
                                'label' => __( 'Product Schema / Rich Snippets', 'wooseo-optimizer' ),
                                'desc'  => __( 'Output JSON-LD structured data for products — price, availability, ratings, brand, GTIN — so Google shows rich results.', 'wooseo-optimizer' ),
                            ),
                            'enable_seo_analysis'   => array(
                                'label' => __( 'Product SEO Analysis', 'wooseo-optimizer' ),
                                'desc'  => __( 'Get a real-time SEO score in the product editor with checks for title, description, images, and keywords.', 'wooseo-optimizer' ),
                            ),
                            'enable_product_fields' => array(
                                'label' => __( 'GTIN / Brand / Manufacturer Fields', 'wooseo-optimizer' ),
                                'desc'  => __( 'Add GTIN, EAN, UPC, MPN, brand, manufacturer, and color fields to your product editor.', 'wooseo-optimizer' ),
                            ),
                            'enable_google_preview' => array(
                                'label' => __( 'Google Search Preview', 'wooseo-optimizer' ),
                                'desc'  => __( 'See a live preview of how your product will appear in Google search results, including rating, price, and availability.', 'wooseo-optimizer' ),
                            ),
                            'enable_social_cards'   => array(
                                'label' => __( 'Social Media Cards (OpenGraph / Pinterest)', 'wooseo-optimizer' ),
                                'desc'  => __( 'Output rich OpenGraph and Pinterest meta tags with product price, availability, and images.', 'wooseo-optimizer' ),
                            ),
                            'enable_breadcrumbs'    => array(
                                'label' => __( 'Enhanced Breadcrumbs', 'wooseo-optimizer' ),
                                'desc'  => __( 'Upgrade your WooCommerce breadcrumbs with customizable separator, home text, and proper Schema.org markup.', 'wooseo-optimizer' ),
                            ),
                            'enable_sitemap_opt'    => array(
                                'label' => __( 'XML Sitemap Optimization', 'wooseo-optimizer' ),
                                'desc'  => __( 'Automatically hide product variations from your XML sitemap so Google focuses on your main product pages.', 'wooseo-optimizer' ),
                            ),
                            'enable_variant_ids'    => array(
                                'label' => __( 'Variant Global Identifiers', 'wooseo-optimizer' ),
                                'desc'  => __( 'Add GTIN/EAN/UPC fields to individual product variations for accurate per-variant schema output.', 'wooseo-optimizer' ),
                            ),
                        );

                        foreach ( $features as $key => $feature ) :
                            $checked = ! empty( $settings[ $key ] ) ? 'checked' : '';
                            ?>
                            <tr>
                                <th scope="row"><?php echo esc_html( $feature['label'] ); ?></th>
                                <td>
                                    <label class="wseo-switch">
                                        <input type="checkbox" name="wseo_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php echo esc_attr( $checked ); ?>>
                                        <span class="wseo-slider"></span>
                                    </label>
                                    <p class="description"><?php echo esc_html( $feature['desc'] ); ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- BREADCRUMB SETTINGS -->
                <div class="wseo-card">
                    <h2><?php esc_html_e( '🍞 Breadcrumb Settings', 'wooseo-optimizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="wseo_breadcrumb_sep"><?php esc_html_e( 'Separator', 'wooseo-optimizer' ); ?></label></th>
                            <td>
                                <input type="text" id="wseo_breadcrumb_sep" name="wseo_settings[breadcrumb_separator]" value="<?php echo esc_attr( $settings['breadcrumb_separator'] ?? '&raquo;' ); ?>" class="small-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="wseo_breadcrumb_home"><?php esc_html_e( 'Home Text', 'wooseo-optimizer' ); ?></label></th>
                            <td>
                                <input type="text" id="wseo_breadcrumb_home" name="wseo_settings[breadcrumb_home_text]" value="<?php echo esc_attr( $settings['breadcrumb_home_text'] ?? 'Home' ); ?>" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( __( 'Save All Settings', 'wooseo-optimizer' ) ); ?>
            </form>
        </div>
        <?php
    }
}

new WSEO_Admin();
