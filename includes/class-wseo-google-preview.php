<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Google_Preview {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
    }

    public function add_meta_box() {
        add_meta_box(
            'wseo_google_preview_box',
            __( 'WooSEO — Google Search Preview', 'wooseo-optimizer' ),
            array( $this, 'render_meta_box' ),
            'product',
            'normal',
            'default'
        );
    }

    public function render_meta_box( $post ) {
        $product = wc_get_product( $post->ID );
        if ( ! $product ) {
            echo '<p>' . esc_html__( 'Save the product first to see preview.', 'wooseo-optimizer' ) . '</p>';
            return;
        }

        $meta_title = get_post_meta( $post->ID, '_wseo_meta_title', true );
        $meta_desc  = get_post_meta( $post->ID, '_wseo_meta_description', true );
        $title      = ! empty( $meta_title ) ? $meta_title : $product->get_name();
        $desc       = ! empty( $meta_desc ) ? $meta_desc : wp_trim_words( wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ), 25, '...' );
        $url        = get_permalink( $post->ID );
        $price      = $product->get_price_html();
        $avg_rating = floatval( $product->get_average_rating() );
        $rev_count  = $product->get_rating_count();
        $in_stock   = $product->is_in_stock();
        $currency   = get_woocommerce_currency_symbol();
        $raw_price  = $product->get_price();

        ?>
        <div class="wseo-google-preview" style="font-family: Arial, sans-serif; max-width: 600px; padding: 16px; background: #fff; border: 1px solid #ddd; border-radius: 8px;">
            <!-- URL breadcrumb -->
            <div style="font-size: 12px; color: #202124; margin-bottom: 4px; line-height: 18px;">
                <span style="color: #4d5156;">
                    <?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?>
                </span>
                <span style="color: #70757a;"> › products › <?php echo esc_html( $post->post_name ); ?></span>
            </div>

            <!-- Title -->
            <div id="wseo-preview-title" style="font-size: 20px; color: #1a0dab; line-height: 26px; margin-bottom: 4px; cursor: pointer; text-decoration: none;">
                <?php echo esc_html( $title ); ?>
            </div>

            <!-- Rich snippet data -->
            <?php if ( $avg_rating > 0 && $rev_count > 0 ) : ?>
                <div style="font-size: 13px; color: #70757a; margin-bottom: 4px; line-height: 20px;">
                    <!-- Stars -->
                    <span style="color: #fbbc04; font-size: 14px; letter-spacing: -1px;">
                        <?php
                        for ( $i = 1; $i <= 5; $i++ ) {
                            echo $i <= round( $avg_rating ) ? '★' : '☆';
                        }
                        ?>
                    </span>
                    <span style="margin-left: 4px;">
                        <?php echo esc_html( $avg_rating ); ?>/5
                    </span>
                    <span style="margin-left: 2px;">
                        (<?php echo esc_html( $rev_count ); ?> <?php echo esc_html( _n( 'review', 'reviews', $rev_count, 'wooseo-optimizer' ) ); ?>)
                    </span>
                    <span style="margin-left: 8px; color: #202124; font-weight: 500;">
                        <?php echo esc_html( $currency . $raw_price ); ?>
                    </span>
                    <span style="margin-left: 8px; color: <?php echo $in_stock ? '#188038' : '#dc3232'; ?>;">
                        <?php echo $in_stock ? esc_html__( 'In stock', 'wooseo-optimizer' ) : esc_html__( 'Out of stock', 'wooseo-optimizer' ); ?>
                    </span>
                </div>
            <?php else : ?>
                <div style="font-size: 13px; color: #70757a; margin-bottom: 4px;">
                    <span style="color: #202124; font-weight: 500;"><?php echo esc_html( $currency . $raw_price ); ?></span>
                    <span style="margin-left: 8px; color: <?php echo $in_stock ? '#188038' : '#dc3232'; ?>;">
                        <?php echo $in_stock ? esc_html__( 'In stock', 'wooseo-optimizer' ) : esc_html__( 'Out of stock', 'wooseo-optimizer' ); ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Description -->
            <div id="wseo-preview-desc" style="font-size: 14px; color: #4d5156; line-height: 22px;">
                <?php echo esc_html( $desc ); ?>
            </div>
        </div>

        <p class="description" style="margin-top: 8px;">
            <?php esc_html_e( 'This is an approximation of how your product may appear in Google search results. Actual results may vary.', 'wooseo-optimizer' ); ?>
        </p>

        <script>
        jQuery(document).ready(function($) {
            // Live update preview when meta fields change
            $(document).on('input', '#wseo_meta_title', function() {
                var val = $(this).val();
                $('#wseo-preview-title').text(val || '<?php echo esc_js( $product->get_name() ); ?>');
            });
            $(document).on('input', '#wseo_meta_description', function() {
                var val = $(this).val();
                $('#wseo-preview-desc').text(val || '<?php echo esc_js( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 25, '...' ) ); ?>');
            });
        });
        </script>
        <?php
    }
}

new WSEO_Google_Preview();
