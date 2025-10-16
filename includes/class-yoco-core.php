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