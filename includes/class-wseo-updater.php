<?php
/**
 * WooSEO Optimizer — GitHub Auto-Updater
 *
 * Checks a GitHub repository for new releases and integrates
 * with WordPress's built-in plugin update system.
 *
 * HOW TO USE:
 * 1. Push your plugin code to a PUBLIC GitHub repo
 * 2. Create a Release on GitHub (e.g. tag: v1.1.0)
 * 3. Attach the plugin .zip file to the release as an asset
 * 4. Update WSEO_GITHUB_USERNAME and WSEO_GITHUB_REPO below
 * 5. Bump the Version in wooseo-optimizer.php header
 * 6. Users will see "Update Available" in wp-admin automatically
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSEO_Updater {

    /**
     * ┌─────────────────────────────────────────────┐
     * │  CONFIGURE THESE TWO VALUES FOR YOUR REPO   │
     * └─────────────────────────────────────────────┘
     */
    private $github_username = 'aliameenco-creator';   // ← Your GitHub username
    private $github_repo     = 'woosero';               // ← Your GitHub repo name

    private $plugin_slug;
    private $plugin_file;
    private $current_version;
    private $github_api_url;
    private $github_response;

    public function __construct() {
        $this->plugin_file    = WSEO_PLUGIN_BASENAME;
        $this->plugin_slug    = dirname( $this->plugin_file );
        $this->current_version = WSEO_VERSION;
        $this->github_api_url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            $this->github_username,
            $this->github_repo
        );

        // Hook into WordPress update system
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );

        // Show "Check for updates" link on plugins page
        add_filter( 'plugin_row_meta', array( $this, 'add_check_update_link' ), 10, 2 );

        // Handle manual update check
        add_action( 'admin_init', array( $this, 'handle_manual_check' ) );
    }

    /**
     * Fetch the latest release info from GitHub API
     */
    private function get_github_release() {
        if ( ! empty( $this->github_response ) ) {
            return $this->github_response;
        }

        // Check cache first (avoid hitting GitHub API too often)
        $cached = get_transient( 'wseo_github_release' );
        if ( false !== $cached ) {
            $this->github_response = $cached;
            return $cached;
        }

        $response = wp_remote_get( $this->github_api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WooSEO-Optimizer-Updater',
            ),
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body ) || ! isset( $body['tag_name'] ) ) {
            return false;
        }

        $release = array(
            'version'      => ltrim( $body['tag_name'], 'vV' ), // Remove 'v' prefix: v1.1.0 → 1.1.0
            'tag_name'     => $body['tag_name'],
            'name'         => $body['name'] ?? '',
            'body'         => $body['body'] ?? '',
            'published_at' => $body['published_at'] ?? '',
            'html_url'     => $body['html_url'] ?? '',
            'zipball_url'  => $body['zipball_url'] ?? '',
        );

        // Check if there's a .zip asset attached to the release (preferred)
        if ( ! empty( $body['assets'] ) ) {
            foreach ( $body['assets'] as $asset ) {
                if ( substr( $asset['name'], -4 ) === '.zip' ) {
                    $release['download_url'] = $asset['browser_download_url'];
                    break;
                }
            }
        }

        // Fallback to zipball if no .zip asset found
        if ( empty( $release['download_url'] ) ) {
            $release['download_url'] = $release['zipball_url'];
        }

        // Cache for 6 hours
        set_transient( 'wseo_github_release', $release, 6 * HOUR_IN_SECONDS );

        $this->github_response = $release;
        return $release;
    }

    /**
     * Check for plugin updates — hooks into WordPress update system
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_github_release();
        if ( ! $release || empty( $release['version'] ) ) {
            return $transient;
        }

        // Compare versions
        if ( version_compare( $release['version'], $this->current_version, '>' ) ) {
            $plugin_data = array(
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_file,
                'new_version' => $release['version'],
                'url'         => sprintf( 'https://github.com/%s/%s', $this->github_username, $this->github_repo ),
                'package'     => $release['download_url'],
                'icons'       => array(),
                'banners'     => array(),
            );

            $transient->response[ $this->plugin_file ] = (object) $plugin_data;
        } else {
            // No update available — add to no_update list
            $transient->no_update[ $this->plugin_file ] = (object) array(
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_file,
                'new_version' => $this->current_version,
                'url'         => sprintf( 'https://github.com/%s/%s', $this->github_username, $this->github_repo ),
            );
        }

        return $transient;
    }

    /**
     * Provide plugin info for the "View Details" popup in WordPress
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
            return $result;
        }

        $release = $this->get_github_release();
        if ( ! $release ) {
            return $result;
        }

        $plugin_info = array(
            'name'              => 'WooSEO Optimizer',
            'slug'              => $this->plugin_slug,
            'version'           => $release['version'],
            'author'            => '<a href="https://remotelyavailable.com/">Ali - RemotelyAvailable</a>',
            'author_profile'    => 'https://remotelyavailable.com/',
            'homepage'          => sprintf( 'https://github.com/%s/%s', $this->github_username, $this->github_repo ),
            'requires'          => '6.0',
            'tested'            => '6.7',
            'requires_php'      => '7.4',
            'downloaded'        => 0,
            'last_updated'      => $release['published_at'] ?? '',
            'download_link'     => $release['download_url'],
            'sections'          => array(
                'description'  => 'All-in-one WooCommerce SEO plugin with AI-powered meta descriptions, product schema markup, SEO analysis, Google preview, social cards, enhanced breadcrumbs, and XML sitemap optimization.',
                'changelog'    => $this->parse_changelog( $release['body'] ?? '' ),
                'installation' => '<ol><li>Upload the plugin zip file via Plugins → Add New → Upload Plugin.</li><li>Activate the plugin.</li><li>Go to WooSEO in the admin sidebar to configure.</li></ol>',
            ),
        );

        return (object) $plugin_info;
    }

    /**
     * After install, ensure the folder name is correct
     * GitHub zipball extracts to "username-repo-hash" instead of "wooseo-optimizer"
     */
    public function after_install( $response, $hook_extra, $result ) {
        global $wp_filesystem;

        // Only run for our plugin
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_file ) {
            return $result;
        }

        $proper_destination = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
        $install_directory  = $result['destination'];

        // If the installed directory name doesn't match, rename it
        if ( $install_directory !== $proper_destination ) {
            $wp_filesystem->move( $install_directory, $proper_destination );
            $result['destination'] = $proper_destination;
        }

        // Re-activate the plugin
        activate_plugin( $this->plugin_file );

        return $result;
    }

    /**
     * Convert GitHub release markdown body to simple HTML for changelog
     */
    private function parse_changelog( $markdown ) {
        if ( empty( $markdown ) ) {
            return '<p>No changelog available for this release.</p>';
        }

        // Basic markdown to HTML conversion
        $html = esc_html( $markdown );
        $html = nl2br( $html );

        // Convert markdown headers
        $html = preg_replace( '/^### (.+)/m', '<h4>$1</h4>', $html );
        $html = preg_replace( '/^## (.+)/m', '<h3>$1</h3>', $html );

        // Convert markdown list items
        $html = preg_replace( '/^[\-\*] (.+)/m', '<li>$1</li>', $html );
        $html = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html );

        return $html;
    }

    /**
     * Add "Check for updates" link on plugins page
     */
    public function add_check_update_link( $links, $file ) {
        if ( $file !== $this->plugin_file ) {
            return $links;
        }

        $check_url = wp_nonce_url(
            admin_url( 'plugins.php?wseo_check_update=1' ),
            'wseo_check_update'
        );

        $links[] = '<a href="' . esc_url( $check_url ) . '">' . esc_html__( 'Check for updates', 'wooseo-optimizer' ) . '</a>';

        return $links;
    }

    /**
     * Handle manual update check
     */
    public function handle_manual_check() {
        if ( ! isset( $_GET['wseo_check_update'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'wseo_check_update' ) ) {
            return;
        }

        if ( ! current_user_can( 'update_plugins' ) ) {
            return;
        }

        // Clear cached release data
        delete_transient( 'wseo_github_release' );

        // Force WordPress to re-check plugin updates
        delete_site_transient( 'update_plugins' );
        wp_update_plugins();

        // Redirect back with a message
        wp_safe_redirect( admin_url( 'plugins.php?wseo_checked=1' ) );
        exit;
    }
}

new WSEO_Updater();
