<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Analysis {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function add_meta_box() {
        add_meta_box(
            'wseo_seo_analysis_box',
            __( 'WooSEO — SEO Analysis', 'wooseo-optimizer' ),
            array( $this, 'render_meta_box' ),
            'product',
            'side',
            'high'
        );
    }

    public function enqueue_scripts( $hook_suffix ) {
        global $post;

        if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        if ( ! $post || 'product' !== $post->post_type ) {
            return;
        }

        $product  = wc_get_product( $post->ID );
        $image_id = $product ? $product->get_image_id() : 0;
        $alt_text = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';

        wp_enqueue_script(
            'wseo-analysis-js',
            WSEO_PLUGIN_URL . 'admin/js/analysis.js',
            array( 'jquery' ),
            WSEO_VERSION,
            true
        );

        wp_localize_script( 'wseo-analysis-js', 'wseoAnalysis', array(
            'hasImage'   => ! empty( $image_id ),
            'hasAltText' => ! empty( $alt_text ),
        ) );
    }

    public function render_meta_box( $post ) {
        ?>
        <div class="wseo-analysis" id="wseo-analysis-container">
            <div class="wseo-score" style="text-align:center; margin-bottom:14px;">
                <div id="wseo-score-number" style="font-size:42px; font-weight:bold; color:#dc3232; transition:color 0.3s;">
                    0%
                </div>
                <div id="wseo-score-label" style="font-size:14px; font-weight:600; color:#dc3232; transition:color 0.3s;">
                    <?php esc_html_e( 'Analyzing...', 'wooseo-optimizer' ); ?>
                </div>
            </div>

            <ul id="wseo-checklist" style="list-style:none; padding:0; margin:0;">
                <!-- Populated by JavaScript in real-time -->
            </ul>

            <p style="text-align:center; margin-top:12px; font-size:11px; color:#aaa; font-style:italic;">
                <?php esc_html_e( 'Updates in real-time as you edit fields.', 'wooseo-optimizer' ); ?>
            </p>
        </div>
        <?php
    }
}

new WSEO_Analysis();
