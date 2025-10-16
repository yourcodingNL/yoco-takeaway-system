<?php
/**
 * Plugin Name: YoCo - Takeaway System
 * Plugin URI: https://github.com/yourcodingNL/yoco-takeaway-system
 * Description: Complete takeaway ordering system with food products, menu display, and WooCommerce integration.
 * Version: 1.0.1
 * Author: Your Coding - Sebastiaan Kalkman
 * Author URI: https://www.yourcoding.nl
 * Email: info@yourcoding.nl
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yoco-takeaway
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('YOCO_VERSION', '1.0.0');
define('YOCO_PLUGIN_FILE', __FILE__);
define('YOCO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YOCO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YOCO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main YoCo Takeaway System Class
 */
final class YoCo_Takeaway_System {
    
    /**
     * Plugin instance
     * 
     * @var YoCo_Takeaway_System
     */
    private static $instance = null;
    
    /**
     * Admin instance
     * 
     * @var YoCo_Admin
     */
    public $admin;
    
    /**
     * Frontend instance
     * 
     * @var YoCo_Frontend
     */
    public $frontend;
    
    /**
     * Get plugin instance
     * 
     * @return YoCo_Takeaway_System
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->include_files();
        $this->init_classes();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        
        // Activation/Deactivation hooks
        register_activation_hook(YOCO_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(YOCO_PLUGIN_FILE, array($this, 'deactivate'));
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        require_once YOCO_PLUGIN_DIR . 'includes/class-yoco-core.php';
        require_once YOCO_PLUGIN_DIR . 'includes/class-yoco-admin.php';
        require_once YOCO_PLUGIN_DIR . 'includes/class-yoco-frontend.php';
    }
    
    /**
     * Initialize classes
     */
    private function init_classes() {
        // Initialize core
        YoCo_Core::get_instance();
        
        // Initialize admin (only in admin)
        if (is_admin()) {
            $this->admin = YoCo_Admin::get_instance();
        }
        
        // Initialize frontend (only on frontend)
        if (!is_admin() || wp_doing_ajax()) {
            $this->frontend = YoCo_Frontend::get_instance();
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Plugin is ready
        do_action('yoco_loaded');
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'yoco-takeaway',
            false,
            dirname(YOCO_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check minimum requirements
        if (!$this->check_requirements()) {
            return;
        }
        
        // Create placeholder product for WooCommerce integration
        $this->create_placeholder_product();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $this->set_default_options();
        
        // Log activation
        error_log('YoCo Takeaway System activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('YoCo Takeaway System deactivated');
    }
    
    /**
     * Check plugin requirements
     * 
     * @return bool
     */
    private function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Create placeholder product for WooCommerce
     */
    private function create_placeholder_product() {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        $placeholder_id = get_option('yoco_wc_placeholder_product');
        
        if (!$placeholder_id || !get_post($placeholder_id)) {
            $placeholder_id = wp_insert_post(array(
                'post_title' => __('YoCo Food Product Placeholder', 'yoco-takeaway'),
                'post_type' => 'product',
                'post_status' => 'private',
                'post_content' => __('This is a placeholder product for YoCo Takeaway System. Do not delete.', 'yoco-takeaway')
            ));
            
            if ($placeholder_id) {
                update_post_meta($placeholder_id, '_virtual', 'yes');
                update_post_meta($placeholder_id, '_price', '0');
                update_post_meta($placeholder_id, '_regular_price', '0');
                update_post_meta($placeholder_id, '_manage_stock', 'no');
                update_option('yoco_wc_placeholder_product', $placeholder_id);
            }
        }
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        // Default icons (empty initially)
        if (!get_option('yoco_icons')) {
            update_option('yoco_icons', array(
                'halal' => '',
                'vegetarian' => '',
                'vegan' => '',
                'spicy' => '',
            ));
        }
        
        // Default button text
        if (!get_option('yoco_order_button_text')) {
            update_option('yoco_order_button_text', __('Bestellen', 'yoco-takeaway'));
        }
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('YoCo Takeaway System requires WooCommerce to be installed and activated.', 'yoco-takeaway'); ?></p>
        </div>
        <?php
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php printf(__('YoCo Takeaway System requires PHP version 7.4 or higher. You are running version %s.', 'yoco-takeaway'), PHP_VERSION); ?></p>
        </div>
        <?php
    }
    
    /**
     * WordPress version notice
     */
    public function wp_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php printf(__('YoCo Takeaway System requires WordPress version 5.0 or higher. You are running version %s.', 'yoco-takeaway'), get_bloginfo('version')); ?></p>
        </div>
        <?php
    }
    
    /**
     * Get plugin version
     * 
     * @return string
     */
    public function get_version() {
        return YOCO_VERSION;
    }
    
    /**
     * Prevent cloning
     */
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', 'yoco-takeaway'), YOCO_VERSION);
    }
    
    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Unserializing instances is forbidden.', 'yoco-takeaway'), YOCO_VERSION);
    }
}

/**
 * Get main plugin instance
 * 
 * @return YoCo_Takeaway_System
 */
function YoCo() {
    return YoCo_Takeaway_System::get_instance();
}

// Initialize plugin
YoCo();
/**
 * --- GitHub Updater: YoCo Takeaway System ---
 * Werkt met PUBLIC repo releases (tags als v1.0.1)
 */
if ( ! class_exists( 'YoCo_GitHub_Updater' ) ) {
    class YoCo_GitHub_Updater {
        private $plugin_file;
        private $github_user = 'yourcodingNL';
        private $github_repo = 'yoco-takeaway-system';

        public function __construct( $plugin_file ) {
            $this->plugin_file = $plugin_file;

            // Check op updates
            add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
            // Modal met plugin-info
            add_filter( 'plugins_api', [ $this, 'plugin_info' ], 10, 3 );
        }

        private function get_latest_release() {
            $url  = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
            $args = [
                'headers' => [
                    // GitHub wil een User-Agent header zien
                    'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                ],
                'timeout' => 15,
            ];
            $response = wp_remote_get( $url, $args );
            if ( is_wp_error( $response ) ) return false;

            $body = json_decode( wp_remote_retrieve_body( $response ) );
            if ( ! $body || empty( $body->tag_name ) ) return false;

            // Normaliseer: 'v1.2.3' -> '1.2.3'
            $body->normalized_version = ltrim( $body->tag_name, 'v' );
            return $body;
        }

        public function check_update( $transient ) {
            if ( empty( $transient->checked ) ) return $transient;

            $release = $this->get_latest_release();
            if ( ! $release ) return $transient;

            $slug            = plugin_basename( $this->plugin_file );
            $current_version = isset( $transient->checked[ $slug ] ) ? $transient->checked[ $slug ] : null;
            if ( ! $current_version ) return $transient;

            if ( version_compare( $current_version, $release->normalized_version, '<' ) ) {
                $obj              = new stdClass();
                $obj->slug        = $slug;
                $obj->plugin      = $slug;
                $obj->new_version = $release->normalized_version;
                $obj->url         = "https://github.com/{$this->github_user}/{$this->github_repo}";
                $obj->package     = isset( $release->zipball_url ) ? $release->zipball_url : "https://github.com/{$this->github_user}/{$this->github_repo}/archive/refs/tags/{$release->tag_name}.zip";
                $transient->response[ $slug ] = $obj;
            }

            return $transient;
        }

        public function plugin_info( $res, $action, $args ) {
            if ( $action !== 'plugin_information' ) return $res;
            if ( empty( $args->slug ) || $args->slug !== plugin_basename( $this->plugin_file ) ) return $res;

            $release = $this->get_latest_release();
            if ( ! $release ) return $res;

            return (object) [
                'name'          => 'YoCo - Takeaway System',
                'slug'          => plugin_basename( $this->plugin_file ),
                'version'       => $release->normalized_version,
                'author'        => '<a href="https://www.yourcoding.nl">Your Coding - Sebastiaan Kalkman</a>',
                'homepage'      => "https://github.com/{$this->github_user}/{$this->github_repo}",
                'download_link' => isset( $release->zipball_url ) ? $release->zipball_url : "https://github.com/{$this->github_user}/{$this->github_repo}/archive/refs/tags/{$release->tag_name}.zip",
                'sections'      => [
                    'description' => ! empty( $release->body ) ? wp_kses_post( nl2br( $release->body ) ) : 'Complete takeaway ordering system with WooCommerce integration. Updates via GitHub Releases.',
                    'changelog'   => ! empty( $release->body ) ? wp_kses_post( nl2br( $release->body ) ) : '',
                ],
            ];
        }
    }
}

// Instantieren (alleen in admin heeft effect, maar onschuldig overal)
if ( class_exists( 'YoCo_GitHub_Updater' ) ) {
    new YoCo_GitHub_Updater( YOCO_PLUGIN_FILE );
}
