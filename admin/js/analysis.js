/**
 * WooSEO Optimizer — Real-Time SEO Analysis
 * Watches all relevant fields and recalculates score instantly.
 */
(function($) {
    'use strict';

    var wseo = {
        // Track dynamic values for fields that can't be read from DOM easily
        hasImage: wseoAnalysis.hasImage || false,
        hasAltText: wseoAnalysis.hasAltText || false,

        init: function() {
            // Run initial analysis after a short delay (let WP editor load)
            setTimeout(function() {
                wseo.runAnalysis();
                wseo.bindEvents();
            }, 500);
        },

        bindEvents: function() {
            // WooSEO meta fields (focus keyword, seo title, meta description)
            $(document).on('input keyup change',
                '#wseo_focus_keyword, #wseo_meta_title, #wseo_meta_description',
                wseo.debounce(wseo.runAnalysis, 200)
            );

            // WooSEO product identifier fields
            $(document).on('input keyup change',
                '#_wseo_brand, #_wseo_manufacturer, #_wseo_gtin, #_wseo_ean, #_wseo_upc, #_wseo_mpn, #_wseo_color',
                wseo.debounce(wseo.runAnalysis, 200)
            );

            // Product title
            $(document).on('input keyup', '#title, #post_title, input[name="post_title"]',
                wseo.debounce(wseo.runAnalysis, 200)
            );

            // Short description (check for both TinyMCE and text mode)
            $(document).on('input keyup', '#excerpt, textarea[name="excerpt"]',
                wseo.debounce(wseo.runAnalysis, 300)
            );

            // Product description - handle TinyMCE
            if (typeof tinymce !== 'undefined') {
                // Wait for TinyMCE editors to initialize
                setTimeout(function() {
                    // Main content editor
                    var contentEditor = tinymce.get('content');
                    if (contentEditor) {
                        contentEditor.on('input keyup change', wseo.debounce(wseo.runAnalysis, 300));
                    }

                    // Excerpt / short description editor
                    var excerptEditor = tinymce.get('excerpt');
                    if (excerptEditor) {
                        excerptEditor.on('input keyup change', wseo.debounce(wseo.runAnalysis, 300));
                    }
                }, 1500);
            }

            // Product image changes (WooCommerce fires this event)
            $('body').on('click', '#set-post-thumbnail, #remove-post-thumbnail, .remove_image_button, .set_product_image', function() {
                setTimeout(function() {
                    wseo.hasImage = $('#_thumbnail_id').val() > 0 || $('#set-post-thumbnail img').length > 0;
                    wseo.runAnalysis();
                }, 500);
            });

            // WooCommerce product image
            $(document).on('change', '#_thumbnail_id', function() {
                wseo.hasImage = $(this).val() > 0;
                wseo.runAnalysis();
            });
        },

        getFieldValue: function(selector) {
            var $el = $(selector);
            if (!$el.length) return '';
            return $.trim($el.val() || '');
        },

        getEditorContent: function(editorId) {
            // Try TinyMCE first
            if (typeof tinymce !== 'undefined') {
                var editor = tinymce.get(editorId);
                if (editor && !editor.isHidden()) {
                    return $.trim(editor.getContent({ format: 'text' }) || '');
                }
            }
            // Fallback to textarea
            var $textarea = $('#' + editorId);
            if ($textarea.length) {
                return $.trim($textarea.val() || '').replace(/<[^>]*>/g, '');
            }
            return '';
        },

        runAnalysis: function() {
            var checks = [];

            // Gather all field values
            var focusKw   = wseo.getFieldValue('#wseo_focus_keyword').toLowerCase();
            var seoTitle  = wseo.getFieldValue('#wseo_meta_title');
            var metaDesc  = wseo.getFieldValue('#wseo_meta_description');
            var postTitle = wseo.getFieldValue('#title') || wseo.getFieldValue('input[name="post_title"]');
            var prodDesc  = wseo.getEditorContent('content');
            var shortDesc = wseo.getEditorContent('excerpt');
            var brand     = wseo.getFieldValue('#_wseo_brand');
            var gtin      = wseo.getFieldValue('#_wseo_gtin');
            var ean       = wseo.getFieldValue('#_wseo_ean');
            var upc       = wseo.getFieldValue('#_wseo_upc');

            // Use SEO title or fall back to post title for length checks
            var effectiveTitle = seoTitle || postTitle;

            // ── CHECK 1: Focus keyword set ──
            checks.push({
                pass: focusKw.length > 0,
                message: focusKw.length > 0 ? 'Focus keyword is set.' : 'Focus keyword is not set.',
                weight: 15
            });

            // ── CHECK 2: SEO title set ──
            checks.push({
                pass: seoTitle.length > 0,
                message: seoTitle.length > 0 ? 'SEO title is set.' : 'SEO title is missing.',
                weight: 10
            });

            // ── CHECK 3: SEO title length ──
            var titleLen = effectiveTitle.length;
            var titlePass = titleLen >= 30 && titleLen <= 60;
            var titleMsg;
            if (titleLen === 0) {
                titleMsg = 'SEO title is empty.';
                titlePass = false;
            } else if (titleLen < 30) {
                titleMsg = 'SEO title is too short (' + titleLen + ' chars). Aim for 30-60.';
            } else if (titleLen > 60) {
                titleMsg = 'SEO title is too long (' + titleLen + ' chars). Keep under 60.';
            } else {
                titleMsg = 'SEO title length is good (' + titleLen + ' chars).';
            }
            checks.push({ pass: titlePass, message: titleMsg, weight: 10 });

            // ── CHECK 4: Focus keyword in title ──
            if (focusKw.length > 0) {
                var kwInTitle = effectiveTitle.toLowerCase().indexOf(focusKw) !== -1;
                checks.push({
                    pass: kwInTitle,
                    message: kwInTitle ? 'Focus keyword found in title.' : 'Focus keyword missing from title.',
                    weight: 10
                });
            }

            // ── CHECK 5: Meta description set ──
            checks.push({
                pass: metaDesc.length > 0,
                message: metaDesc.length > 0 ? 'Meta description is set.' : 'Meta description is missing.',
                weight: 10
            });

            // ── CHECK 6: Meta description length ──
            if (metaDesc.length > 0) {
                var descLen = metaDesc.length;
                var descPass = descLen >= 120 && descLen <= 160;
                var descMsg;
                if (descLen < 120) {
                    descMsg = 'Meta description is too short (' + descLen + ' chars). Aim for 120-160.';
                } else if (descLen > 160) {
                    descMsg = 'Meta description is too long (' + descLen + ' chars). Keep under 160.';
                } else {
                    descMsg = 'Meta description length is good (' + descLen + ' chars).';
                }
                checks.push({ pass: descPass, message: descMsg, weight: 5 });
            }

            // ── CHECK 7: Product description length ──
            var prodDescLen = prodDesc.length;
            checks.push({
                pass: prodDescLen >= 150,
                message: prodDescLen >= 150
                    ? 'Product description length is good (' + prodDescLen + ' chars).'
                    : 'Product description is too short (' + prodDescLen + ' chars). Aim for 150+.',
                weight: 10
            });

            // ── CHECK 8: Short description ──
            checks.push({
                pass: shortDesc.length > 0,
                message: shortDesc.length > 0 ? 'Short description is set.' : 'Short description is missing.',
                weight: 5
            });

            // ── CHECK 9: Product image ──
            // Re-check image presence from DOM
            var imagePresent = wseo.hasImage || $('#_thumbnail_id').val() > 0 || $('#set-post-thumbnail img').length > 0 || $('.woocommerce-product-image img').length > 0;
            checks.push({
                pass: imagePresent,
                message: imagePresent ? 'Product image is set.' : 'Product image is missing.',
                weight: 10
            });

            // ── CHECK 10: Image alt text ──
            if (imagePresent) {
                checks.push({
                    pass: wseo.hasAltText,
                    message: wseo.hasAltText ? 'Product image has alt text.' : 'Product image is missing alt text.',
                    weight: 5
                });
            }

            // ── CHECK 11: Brand ──
            checks.push({
                pass: brand.length > 0,
                message: brand.length > 0 ? 'Brand is set.' : 'Brand is not set (important for schema).',
                weight: 5
            });

            // ── CHECK 12: Product identifier ──
            var hasId = gtin.length > 0 || ean.length > 0 || upc.length > 0;
            checks.push({
                pass: hasId,
                message: hasId
                    ? 'Product identifier (GTIN/EAN/UPC) is set.'
                    : 'No product identifier (GTIN/EAN/UPC) set.',
                weight: 5
            });

            // ── CALCULATE SCORE ──
            var totalWeight = 0;
            var earned = 0;
            for (var i = 0; i < checks.length; i++) {
                totalWeight += checks[i].weight;
                if (checks[i].pass) {
                    earned += checks[i].weight;
                }
            }

            var score = totalWeight > 0 ? Math.round((earned / totalWeight) * 100) : 0;

            // ── UPDATE UI ──
            wseo.updateUI(score, checks);
        },

        updateUI: function(score, checks) {
            var color, label;

            if (score >= 80) {
                color = '#46b450';
                label = 'Great';
            } else if (score >= 50) {
                color = '#ffb900';
                label = 'Needs Work';
            } else {
                color = '#dc3232';
                label = 'Poor';
            }

            // Update score display
            $('#wseo-score-number').text(score + '%').css('color', color);
            $('#wseo-score-label').text(label).css('color', color);

            // Build checklist HTML
            var html = '';
            for (var i = 0; i < checks.length; i++) {
                var icon = checks[i].pass ? '✅' : '❌';
                var textColor = checks[i].pass ? '#333' : '#8b0000';
                html += '<li style="padding:6px 0; border-bottom:1px solid #f0f0f0; font-size:12px; line-height:1.5; color:' + textColor + ';">';
                html += '<span>' + icon + '</span> ';
                html += checks[i].message;
                html += '</li>';
            }

            $('#wseo-checklist').html(html);
        },

        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only run on product edit pages
        if ($('#wseo-analysis-container').length) {
            wseo.init();
        }
    });

})(jQuery);
