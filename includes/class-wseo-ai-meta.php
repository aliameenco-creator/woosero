<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_AI_Meta {

    public function __construct() {
        // Add meta box to product editor
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        // Save meta data
        add_action( 'save_post_product', array( $this, 'save_meta' ), 10, 2 );
        // Output meta tags on frontend
        add_action( 'wp_head', array( $this, 'output_meta_tags' ), 1 );
        // AJAX handler for AI generation
        add_action( 'wp_ajax_wseo_generate_ai_meta', array( $this, 'ajax_generate_meta' ) );
    }

    public function add_meta_box() {
        add_meta_box(
            'wseo_ai_meta_box',
            __( 'WooSEO — AI Meta Description & Title', 'wooseo-optimizer' ),
            array( $this, 'render_meta_box' ),
            'product',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'wseo_save_ai_meta', 'wseo_ai_meta_nonce' );

        $meta_title = get_post_meta( $post->ID, '_wseo_meta_title', true );
        $meta_desc  = get_post_meta( $post->ID, '_wseo_meta_description', true );
        $focus_kw   = get_post_meta( $post->ID, '_wseo_focus_keyword', true );
        $settings   = get_option( 'wseo_settings', array() );
        $has_key    = ! empty( $settings['openai_api_key'] );
        ?>
        <div class="wseo-ai-meta-wrap">
            <p>
                <label for="wseo_focus_keyword"><strong><?php esc_html_e( 'Focus Keyword', 'wooseo-optimizer' ); ?></strong></label>
                <input type="text" id="wseo_focus_keyword" name="wseo_focus_keyword" value="<?php echo esc_attr( $focus_kw ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. wireless bluetooth headphones', 'wooseo-optimizer' ); ?>" />
            </p>

            <p>
                <label for="wseo_meta_title"><strong><?php esc_html_e( 'SEO Title', 'wooseo-optimizer' ); ?></strong></label>
                <input type="text" id="wseo_meta_title" name="wseo_meta_title" value="<?php echo esc_attr( $meta_title ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Custom SEO title for this product', 'wooseo-optimizer' ); ?>" />
                <span class="wseo-char-count" data-target="wseo_meta_title" data-max="60">
                    <?php echo esc_html( strlen( $meta_title ) ); ?>/60 <?php esc_html_e( 'characters', 'wooseo-optimizer' ); ?>
                </span>
            </p>

            <p>
                <label for="wseo_meta_description"><strong><?php esc_html_e( 'Meta Description', 'wooseo-optimizer' ); ?></strong></label>
                <textarea id="wseo_meta_description" name="wseo_meta_description" class="widefat" rows="3" placeholder="<?php esc_attr_e( 'Custom meta description for this product', 'wooseo-optimizer' ); ?>"><?php echo esc_textarea( $meta_desc ); ?></textarea>
                <span class="wseo-char-count" data-target="wseo_meta_description" data-max="160">
                    <?php echo esc_html( strlen( $meta_desc ) ); ?>/160 <?php esc_html_e( 'characters', 'wooseo-optimizer' ); ?>
                </span>
            </p>

            <?php if ( $has_key ) : ?>
                <p>
                    <button type="button" id="wseo-ai-generate-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                        <?php esc_html_e( '🤖 Generate with AI', 'wooseo-optimizer' ); ?>
                    </button>
                    <span id="wseo-ai-spinner" class="spinner" style="float:none;"></span>
                    <span id="wseo-ai-status"></span>
                </p>
            <?php else : ?>
                <p class="wseo-notice-inline">
                    <?php esc_html_e( '⚠️ Add your OpenAI API key in WooSEO Settings to enable AI generation.', 'wooseo-optimizer' ); ?>
                </p>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Character counter
            $('input[id^="wseo_meta_"], textarea[id^="wseo_meta_"]').on('input', function() {
                var len = $(this).val().length;
                var max = $(this).attr('id') === 'wseo_meta_title' ? 60 : 160;
                var $counter = $('[data-target="' + $(this).attr('id') + '"]');
                $counter.text(len + '/' + max + ' characters');
                $counter.css('color', len > max ? '#dc3232' : '#666');
            });

            // AI Generate button
            $('#wseo-ai-generate-btn').on('click', function() {
                var $btn = $(this);
                var $spinner = $('#wseo-ai-spinner');
                var $status = $('#wseo-ai-status');

                $btn.prop('disabled', true);
                $spinner.addClass('is-active');
                $status.text('');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wseo_generate_ai_meta',
                        nonce: '<?php echo esc_js( wp_create_nonce( 'wseo_ai_generate' ) ); ?>',
                        post_id: $btn.data('post-id'),
                        focus_keyword: $('#wseo_focus_keyword').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#wseo_meta_title').val(response.data.title).trigger('input');
                            $('#wseo_meta_description').val(response.data.description).trigger('input');
                            $status.text('✅ Generated!').css('color', '#46b450');
                        } else {
                            $status.text('❌ ' + response.data.message).css('color', '#dc3232');
                        }
                    },
                    error: function() {
                        $status.text('❌ Request failed.').css('color', '#dc3232');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['wseo_ai_meta_nonce'] ) || ! wp_verify_nonce( $_POST['wseo_ai_meta_nonce'], 'wseo_save_ai_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['wseo_meta_title'] ) ) {
            update_post_meta( $post_id, '_wseo_meta_title', sanitize_text_field( $_POST['wseo_meta_title'] ) );
        }
        if ( isset( $_POST['wseo_meta_description'] ) ) {
            update_post_meta( $post_id, '_wseo_meta_description', sanitize_textarea_field( $_POST['wseo_meta_description'] ) );
        }
        if ( isset( $_POST['wseo_focus_keyword'] ) ) {
            update_post_meta( $post_id, '_wseo_focus_keyword', sanitize_text_field( $_POST['wseo_focus_keyword'] ) );
        }
    }

    public function output_meta_tags() {
        if ( ! is_singular( 'product' ) ) {
            return;
        }

        global $post;
        $meta_title = get_post_meta( $post->ID, '_wseo_meta_title', true );
        $meta_desc  = get_post_meta( $post->ID, '_wseo_meta_description', true );

        if ( ! empty( $meta_desc ) ) {
            echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
        }

        if ( ! empty( $meta_title ) ) {
            add_filter( 'document_title_parts', function( $title_parts ) use ( $meta_title ) {
                $title_parts['title'] = $meta_title;
                return $title_parts;
            });
        }
    }

    public function ajax_generate_meta() {
        check_ajax_referer( 'wseo_ai_generate', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wooseo-optimizer' ) ) );
        }

        $post_id       = absint( $_POST['post_id'] ?? 0 );
        $focus_keyword = sanitize_text_field( $_POST['focus_keyword'] ?? '' );

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product.', 'wooseo-optimizer' ) ) );
        }

        $product = wc_get_product( $post_id );
        if ( ! $product ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'wooseo-optimizer' ) ) );
        }

        $settings = get_option( 'wseo_settings', array() );
        $api_key  = $settings['openai_api_key'] ?? '';
        $model    = $settings['openai_model'] ?? 'gpt-4o-mini';

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'OpenAI API key not configured.', 'wooseo-optimizer' ) ) );
        }

        $product_title = $product->get_name();
        $product_desc  = wp_strip_all_tags( $product->get_description() );
        $product_short = wp_strip_all_tags( $product->get_short_description() );
        $product_price = $product->get_price();
        $product_cats  = wp_strip_all_tags( wc_get_product_category_list( $post_id ) );

        $prompt = "You are an expert eCommerce SEO copywriter. Generate an SEO-optimized title and meta description for this WooCommerce product.\n\n";
        $prompt .= "Product Name: {$product_title}\n";
        $prompt .= "Categories: {$product_cats}\n";
        $prompt .= "Price: {$product_price}\n";
        if ( $focus_keyword ) {
            $prompt .= "Focus Keyword: {$focus_keyword}\n";
        }
        if ( $product_short ) {
            $prompt .= "Short Description: " . substr( $product_short, 0, 500 ) . "\n";
        }
        if ( $product_desc ) {
            $prompt .= "Full Description: " . substr( $product_desc, 0, 1000 ) . "\n";
        }
        $prompt .= "\nRules:\n";
        $prompt .= "- SEO Title: max 60 characters, include the focus keyword naturally\n";
        $prompt .= "- Meta Description: 120-155 characters, compelling, include focus keyword, end with a call to action\n";
        $prompt .= "- Do NOT use quotes around the title or description\n";
        $prompt .= "\nRespond in this EXACT format (no extra text):\n";
        $prompt .= "TITLE: your seo title here\n";
        $prompt .= "DESCRIPTION: your meta description here";

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => wp_json_encode( array(
                'model'       => $model,
                'messages'    => array(
                    array( 'role' => 'user', 'content' => $prompt ),
                ),
                'max_tokens'  => 200,
                'temperature' => 0.7,
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
            $error_msg = $body['error']['message'] ?? __( 'Unknown API error.', 'wooseo-optimizer' );
            wp_send_json_error( array( 'message' => $error_msg ) );
        }

        $ai_text = trim( $body['choices'][0]['message']['content'] );

        // Parse response
        $title = '';
        $desc  = '';

        if ( preg_match( '/TITLE:\s*(.+)/i', $ai_text, $m ) ) {
            $title = trim( $m[1] );
        }
        if ( preg_match( '/DESCRIPTION:\s*(.+)/i', $ai_text, $m ) ) {
            $desc = trim( $m[1] );
        }

        if ( empty( $title ) && empty( $desc ) ) {
            wp_send_json_error( array( 'message' => __( 'Could not parse AI response.', 'wooseo-optimizer' ) ) );
        }

        wp_send_json_success( array(
            'title'       => sanitize_text_field( $title ),
            'description' => sanitize_text_field( $desc ),
        ) );
    }
}

new WSEO_AI_Meta();
