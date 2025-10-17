<?php
/**
 * YoCo Core Class
 * 
 * Handles core functionality like post types, taxonomies, and basic setup
 * 
 * @package YoCo_Takeaway_System
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class YoCo_Core {
    
    /**
     * Instance
     * 
     * @var YoCo_Core
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return YoCo_Core
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
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Register Food Product post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __('Food Products', 'yoco-takeaway'),
            'singular_name'      => __('Food Product', 'yoco-takeaway'),
            'menu_name'          => __('Food Products', 'yoco-takeaway'),
            'add_new'            => __('Voeg Nieuw Toe', 'yoco-takeaway'),
            'add_new_item'       => __('Voeg Nieuw Food Product Toe', 'yoco-takeaway'),
            'edit_item'          => __('Bewerk Food Product', 'yoco-takeaway'),
            'new_item'           => __('Nieuw Food Product', 'yoco-takeaway'),
            'view_item'          => __('Bekijk Food Product', 'yoco-takeaway'),
            'search_items'       => __('Zoek Food Products', 'yoco-takeaway'),
            'not_found'          => __('Geen food products gevonden', 'yoco-takeaway'),
            'not_found_in_trash' => __('Geen food products in prullenbak', 'yoco-takeaway'),
            'all_items'          => __('Alle Food Products', 'yoco-takeaway'),
            'archives'           => __('Food Product Archieven', 'yoco-takeaway'),
            'attributes'         => __('Food Product Attributen', 'yoco-takeaway'),
            'insert_into_item'   => __('Invoegen in food product', 'yoco-takeaway'),
            'uploaded_to_this_item' => __('Geüpload naar dit food product', 'yoco-takeaway'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => true,
            'query_var'           => true,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-food',
            'supports'            => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'show_in_rest'        => true,
            'can_export'          => true,
            'delete_with_user'    => false,
        );

        register_post_type('yoco_food', $args);
    }
    
    /**
     * Register taxonomies for Food Products
     */
    public function register_taxonomies() {
        // Food Categories
        $cat_labels = array(
            'name'                       => __('Food Categorieën', 'yoco-takeaway'),
            'singular_name'              => __('Food Categorie', 'yoco-takeaway'),
            'search_items'               => __('Zoek Categorieën', 'yoco-takeaway'),
            'popular_items'              => __('Populaire Categorieën', 'yoco-takeaway'),
            'all_items'                  => __('Alle Categorieën', 'yoco-takeaway'),
            'parent_item'                => __('Parent Categorie', 'yoco-takeaway'),
            'parent_item_colon'          => __('Parent Categorie:', 'yoco-takeaway'),
            'edit_item'                  => __('Bewerk Categorie', 'yoco-takeaway'),
            'update_item'                => __('Update Categorie', 'yoco-takeaway'),
            'add_new_item'               => __('Voeg Nieuwe Categorie Toe', 'yoco-takeaway'),
            'new_item_name'              => __('Nieuwe Categorie Naam', 'yoco-takeaway'),
            'separate_items_with_commas' => __('Scheid categorieën met komma\'s', 'yoco-takeaway'),
            'add_or_remove_items'        => __('Categorieën toevoegen of verwijderen', 'yoco-takeaway'),
            'choose_from_most_used'      => __('Kies uit meest gebruikte categorieën', 'yoco-takeaway'),
            'not_found'                  => __('Geen categorieën gevonden', 'yoco-takeaway'),
            'menu_name'                  => __('Categorieën', 'yoco-takeaway'),
            'view_item'                  => __('Bekijk Categorie', 'yoco-takeaway'),
            'back_to_items'              => __('← Terug naar Food Products', 'yoco-takeaway'),
        );

        register_taxonomy('yoco_food_cat', array('yoco_food'), array(
            'hierarchical'      => true,
            'labels'            => $cat_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'food-categorie'),
            'show_in_rest'      => true,
            'show_tagcloud'     => false,
        ));

        // Food Tags
        $tag_labels = array(
            'name'                       => __('Food Tags', 'yoco-takeaway'),
            'singular_name'              => __('Food Tag', 'yoco-takeaway'),
            'search_items'               => __('Zoek Tags', 'yoco-takeaway'),
            'popular_items'              => __('Populaire Tags', 'yoco-takeaway'),
            'all_items'                  => __('Alle Tags', 'yoco-takeaway'),
            'edit_item'                  => __('Bewerk Tag', 'yoco-takeaway'),
            'update_item'                => __('Update Tag', 'yoco-takeaway'),
            'add_new_item'               => __('Voeg Nieuwe Tag Toe', 'yoco-takeaway'),
            'new_item_name'              => __('Nieuwe Tag Naam', 'yoco-takeaway'),
            'separate_items_with_commas' => __('Scheid tags met komma\'s', 'yoco-takeaway'),
            'add_or_remove_items'        => __('Tags toevoegen of verwijderen', 'yoco-takeaway'),
            'choose_from_most_used'      => __('Kies uit meest gebruikte tags', 'yoco-takeaway'),
            'not_found'                  => __('Geen tags gevonden', 'yoco-takeaway'),
            'menu_name'                  => __('Tags', 'yoco-takeaway'),
            'view_item'                  => __('Bekijk Tag', 'yoco-takeaway'),
            'back_to_items'              => __('← Terug naar Food Products', 'yoco-takeaway'),
        );

        register_taxonomy('yoco_food_tag', array('yoco_food'), array(
            'hierarchical'      => false,
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'food-tag'),
            'show_in_rest'      => true,
            'show_tagcloud'     => true,
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only enqueue on pages with our shortcode
        if (is_singular() || is_page()) {
            global $post;
            if ($post && has_shortcode($post->post_content, 'yoco_menu')) {
                wp_enqueue_script('jquery');
                
                wp_enqueue_style(
                    'yoco-frontend',
                    YOCO_PLUGIN_URL . 'assets/css/yoco-frontend.css',
                    array(),
                    YOCO_VERSION
                );
                
                wp_enqueue_script(
                    'yoco-frontend',
                    YOCO_PLUGIN_URL . 'assets/js/yoco-frontend.js',
                    array('jquery'),
                    YOCO_VERSION,
                    true
                );
                
                // Localize script for AJAX
                wp_localize_script('yoco-frontend', 'yoco_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('yoco_add_to_cart'),
                    'i18n' => array(
                        'adding' => __('Even geduld...', 'yoco-takeaway'),
                        'added_title' => __('Toegevoegd aan winkelwagen!', 'yoco-takeaway'),
                        'added_message' => __('%dx %s toegevoegd', 'yoco-takeaway'),
                        'error_title' => __('Fout', 'yoco-takeaway'),
                        'error_message' => __('Er is iets misgegaan', 'yoco-takeaway'),
                        'network_error' => __('Er is een fout opgetreden. Probeer het opnieuw.', 'yoco-takeaway'),
                        'order_button' => get_option('yoco_order_button_text', __('Bestellen', 'yoco-takeaway')),
                    )
                ));
            }
        }
    }
    
    /**
     * Ensure virtual product exists and is valid
     * 
     * @return int Virtual product ID
     */
    public static function ensure_virtual_product() {
        $virtual_product_id = get_option('yoco_virtual_product_id');
        
        // Check if product exists and is valid
        if ($virtual_product_id && get_post($virtual_product_id)) {
            $product = get_post($virtual_product_id);
            // Verify it's still a product and has our meta
            if ($product->post_type === 'product' && get_post_meta($virtual_product_id, '_yoco_virtual_master', true)) {
                return $virtual_product_id;
            }
        }
        
        // Create or recreate virtual product
        return self::create_virtual_product();
    }
    
    /**
     * Create virtual product with safety features
     * 
     * @return int|false Virtual product ID or false on failure
     */
    private static function create_virtual_product() {
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        // Try to use a high ID for safety
        $desired_id = 999999;
        
        // Create virtual product
        $virtual_product_data = array(
            'post_title' => __('YoCo Virtual Product', 'yoco-takeaway'),
            'post_content' => __('Virtual product for YoCo Takeaway System. Do not delete.', 'yoco-takeaway'),
            'post_status' => 'publish',
            'post_type' => 'product',
            'post_name' => 'yoco-virtual-container-v1', // Unique slug
            'meta_input' => array(
                '_virtual' => 'yes',
                '_price' => '0',
                '_regular_price' => '0',
                '_manage_stock' => 'no',
                '_stock_status' => 'instock',
                '_visibility' => 'hidden',
                '_catalog_visibility' => 'hidden',
                '_yoco_virtual_master' => true,
                '_yoco_created_time' => time(),
            )
        );
        
        // Try with desired ID first
        if (!get_post($desired_id)) {
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$wpdb->posts} (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count) VALUES (%d, %d, %s, %s, %s, %s, '', %s, 'closed', 'closed', '', %s, '', '', %s, %s, '', 0, '', 0, %s, '', 0)",
                $desired_id,
                1, // Admin user
                current_time('mysql'),
                current_time('mysql', 1),
                $virtual_product_data['post_content'],
                $virtual_product_data['post_title'],
                $virtual_product_data['post_status'],
                $virtual_product_data['post_name'],
                current_time('mysql'),
                current_time('mysql', 1),
                $virtual_product_data['post_type']
            ));
            
            if ($wpdb->insert_id || get_post($desired_id)) {
                $virtual_product_id = $desired_id;
            } else {
                // Fallback to normal insertion
                $virtual_product_id = wp_insert_post($virtual_product_data);
            }
        } else {
            // Fallback to normal insertion
            $virtual_product_id = wp_insert_post($virtual_product_data);
        }
        
        if ($virtual_product_id && !is_wp_error($virtual_product_id)) {
            // Add meta data
            foreach ($virtual_product_data['meta_input'] as $key => $value) {
                update_post_meta($virtual_product_id, $key, $value);
            }
            
            // Set WooCommerce product type
            wp_set_object_terms($virtual_product_id, 'simple', 'product_type');
            
            update_option('yoco_virtual_product_id', $virtual_product_id);
            
            error_log('YoCo: Created virtual product #' . $virtual_product_id);
            return $virtual_product_id;
        }
        
        error_log('YoCo: Failed to create virtual product');
        return false;
    }
    
    /**
     * Get all allergen labels
     * 
     * @return array
     */
    public static function get_allergen_labels() {
        return array(
            'gluten' => __('Gluten (tarwe, rogge, gerst, haver, spelt)', 'yoco-takeaway'),
            'crustaceans' => __('Schaaldieren', 'yoco-takeaway'),
            'eggs' => __('Eieren', 'yoco-takeaway'),
            'fish' => __('Vis', 'yoco-takeaway'),
            'peanuts' => __('Pinda\'s', 'yoco-takeaway'),
            'soy' => __('Soja', 'yoco-takeaway'),
            'milk' => __('Melk (inclusief lactose)', 'yoco-takeaway'),
            'nuts' => __('Noten (amandel, hazelnoot, walnoot, etc.)', 'yoco-takeaway'),
            'celery' => __('Selderij', 'yoco-takeaway'),
            'mustard' => __('Mosterd', 'yoco-takeaway'),
            'sesame' => __('Sesamzaad', 'yoco-takeaway'),
            'sulfites' => __('Sulfiet (E220-E228)', 'yoco-takeaway'),
            'lupin' => __('Lupine', 'yoco-takeaway'),
            'molluscs' => __('Weekdieren (oesters, mosselen, etc.)', 'yoco-takeaway')
        );
    }
    
    /**
     * Get spicy level labels
     * 
     * @return array
     */
    public static function get_spicy_labels() {
        return array(
            0 => __('Niet pittig', 'yoco-takeaway'),
            1 => __('Mild', 'yoco-takeaway'),
            2 => __('Pittig', 'yoco-takeaway'),
            3 => __('Extra pittig', 'yoco-takeaway'),
            4 => __('Vuurpittig', 'yoco-takeaway')
        );
    }
    
    /**
     * Get food product meta
     * 
     * @param int $post_id
     * @return array
     */
    public static function get_food_meta($post_id) {
        return array(
            'price' => get_post_meta($post_id, '_yoco_food_price', true),
            'halal' => get_post_meta($post_id, '_yoco_food_halal', true),
            'vegetarian' => get_post_meta($post_id, '_yoco_food_vegetarian', true),
            'vegan' => get_post_meta($post_id, '_yoco_food_vegan', true),
            'spicy' => get_post_meta($post_id, '_yoco_food_spicy', true),
            'is_menu' => get_post_meta($post_id, '_yoco_food_is_menu', true),
            'allergens' => get_post_meta($post_id, '_yoco_food_allergens', true) ?: array(),
        );
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