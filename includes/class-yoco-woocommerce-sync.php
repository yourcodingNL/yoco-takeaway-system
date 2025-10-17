<?php
/**
 * YoCo WooCommerce Sync Class
 * 
 * Handles synchronization between YoCo food products and WooCommerce products
 * 
 * @package YoCo_Takeaway_System
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class YoCo_WooCommerce_Sync {
    
    /**
     * Instance
     * 
     * @var YoCo_WooCommerce_Sync
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return YoCo_WooCommerce_Sync
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
        // Register custom product type
        add_filter('product_type_selector', array($this, 'add_yoco_product_type'));
        add_filter('woocommerce_product_class', array($this, 'get_yoco_product_class'), 10, 4);
        
        // Hide YoCo products from shop/catalog
        add_action('pre_get_posts', array($this, 'hide_yoco_products_from_shop'));
        add_filter('woocommerce_product_is_visible', array($this, 'hide_yoco_products'), 10, 2);
        
        // Prevent direct access to YoCo product pages
        add_action('template_redirect', array($this, 'redirect_yoco_product_pages'));
    }
    
    /**
     * Sync food product to WooCommerce
     * 
     * @param int $food_id Food product ID
     * @return int|false WooCommerce product ID or false on failure
     */
    public function sync_food_to_woocommerce($food_id) {
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        $food_post = get_post($food_id);
        if (!$food_post || $food_post->post_type !== 'yoco_food') {
            return false;
        }
        
        // Check if WooCommerce product already exists
        $wc_product_id = get_post_meta($food_id, '_yoco_wc_product_id', true);
        
        if ($wc_product_id && get_post($wc_product_id)) {
            // Update existing WooCommerce product
            return $this->update_woocommerce_product($food_id, $wc_product_id);
        } else {
            // Create new WooCommerce product
            return $this->create_woocommerce_product($food_id);
        }
    }
    
    /**
     * Create WooCommerce product from food product
     * 
     * @param int $food_id Food product ID
     * @return int|false WooCommerce product ID or false on failure
     */
    private function create_woocommerce_product($food_id) {
        $food_post = get_post($food_id);
        $food_meta = YoCo_Core::get_food_meta($food_id);
        
        if (!$food_post || empty($food_meta['price']) || $food_meta['price'] <= 0) {
            return false;
        }
        
        // Create WooCommerce product
        $wc_product_data = array(
            'post_title' => $food_post->post_title,
            'post_content' => $food_post->post_content,
            'post_excerpt' => $food_post->post_excerpt,
            'post_status' => 'publish',
            'post_type' => 'product',
            'post_author' => $food_post->post_author,
            'menu_order' => $food_post->menu_order,
            'meta_input' => array(
                '_yoco_food_product' => true,
                '_yoco_food_id' => $food_id,
                '_price' => $food_meta['price'],
                '_regular_price' => $food_meta['price'],
                '_sale_price' => '',
                '_sku' => 'yoco-food-' . $food_id,
                '_manage_stock' => 'no',
                '_stock_status' => 'instock',
                '_visibility' => 'hidden',
                '_catalog_visibility' => 'hidden',
                '_virtual' => 'yes',
                '_downloadable' => 'no',
                '_weight' => '',
                '_length' => '',
                '_width' => '',
                '_height' => '',
                '_tax_status' => 'taxable',
                '_tax_class' => '',
                '_purchase_note' => '',
                '_featured' => 'no',
                '_sold_individually' => 'no',
                '_backorders' => 'no',
                '_yoco_created_time' => time(),
            )
        );
        
        $wc_product_id = wp_insert_post($wc_product_data);
        
        if ($wc_product_id && !is_wp_error($wc_product_id)) {
            // Set product type to simple
            wp_set_object_terms($wc_product_id, 'simple', 'product_type');
            
            // Copy featured image if exists, otherwise use default
            $thumbnail_id = get_post_thumbnail_id($food_id);
            if ($thumbnail_id) {
                set_post_thumbnail($wc_product_id, $thumbnail_id);
            } else {
                // Use default image if no thumbnail and default is set
                $default_image = get_option('yoco_default_image', '');
                if ($default_image) {
                    $this->set_default_image_as_thumbnail($wc_product_id, $default_image);
                }
            }
            
            // Copy categories and tags
            $this->sync_taxonomies($food_id, $wc_product_id);
            
            // Store relationship
            update_post_meta($food_id, '_yoco_wc_product_id', $wc_product_id);
            update_post_meta($wc_product_id, '_yoco_food_id', $food_id);
            
            // Clear WooCommerce cache
            if (function_exists('wc_delete_product_transients')) {
                wc_delete_product_transients($wc_product_id);
            }
            
            error_log('YoCo: Created WooCommerce product #' . $wc_product_id . ' for food #' . $food_id);
            return $wc_product_id;
        }
        
        error_log('YoCo: Failed to create WooCommerce product for food #' . $food_id);
        return false;
    }
    
    /**
     * Update existing WooCommerce product
     * 
     * @param int $food_id Food product ID
     * @param int $wc_product_id WooCommerce product ID
     * @return int|false WooCommerce product ID or false on failure
     */
    private function update_woocommerce_product($food_id, $wc_product_id) {
        $food_post = get_post($food_id);
        $food_meta = YoCo_Core::get_food_meta($food_id);
        
        if (!$food_post || !get_post($wc_product_id)) {
            return false;
        }
        
        // Update WooCommerce product
        $wc_product_data = array(
            'ID' => $wc_product_id,
            'post_title' => $food_post->post_title,
            'post_content' => $food_post->post_content,
            'post_excerpt' => $food_post->post_excerpt,
            'post_status' => 'publish',
            'menu_order' => $food_post->menu_order,
        );
        
        $result = wp_update_post($wc_product_data);
        
        if ($result && !is_wp_error($result)) {
            // Update price
            if (!empty($food_meta['price']) && $food_meta['price'] > 0) {
                update_post_meta($wc_product_id, '_price', $food_meta['price']);
                update_post_meta($wc_product_id, '_regular_price', $food_meta['price']);
            }
            
            // Update featured image
            $thumbnail_id = get_post_thumbnail_id($food_id);
            if ($thumbnail_id) {
                set_post_thumbnail($wc_product_id, $thumbnail_id);
            } else {
                // Use default image if no thumbnail and default is set
                $default_image = get_option('yoco_default_image', '');
                if ($default_image) {
                    $this->set_default_image_as_thumbnail($wc_product_id, $default_image);
                } else {
                    delete_post_thumbnail($wc_product_id);
                }
            }
            
            // Update taxonomies
            $this->sync_taxonomies($food_id, $wc_product_id);
            
            // Clear WooCommerce cache
            if (function_exists('wc_delete_product_transients')) {
                wc_delete_product_transients($wc_product_id);
            }
            
            error_log('YoCo: Updated WooCommerce product #' . $wc_product_id . ' for food #' . $food_id);
            return $wc_product_id;
        }
        
        error_log('YoCo: Failed to update WooCommerce product #' . $wc_product_id . ' for food #' . $food_id);
        return false;
    }
    
    /**
     * Sync taxonomies from food product to WooCommerce product
     * 
     * @param int $food_id Food product ID
     * @param int $wc_product_id WooCommerce product ID
     */
    private function sync_taxonomies($food_id, $wc_product_id) {
        // Sync categories
        $food_categories = wp_get_post_terms($food_id, 'yoco_food_cat', array('fields' => 'ids'));
        if (!empty($food_categories) && !is_wp_error($food_categories)) {
            // Map to WooCommerce product categories if needed, for now just clear
            wp_set_object_terms($wc_product_id, array(), 'product_cat');
        }
        
        // Sync tags
        $food_tags = wp_get_post_terms($food_id, 'yoco_food_tag', array('fields' => 'names'));
        if (!empty($food_tags) && !is_wp_error($food_tags)) {
            wp_set_object_terms($wc_product_id, $food_tags, 'product_tag');
        }
    }
    
    /**
     * Delete WooCommerce product when food product is deleted
     * 
     * @param int $food_id Food product ID
     * @return bool Success
     */
    public function delete_woocommerce_product($food_id) {
        $wc_product_id = get_post_meta($food_id, '_yoco_wc_product_id', true);
        
        if ($wc_product_id && get_post($wc_product_id)) {
            $result = wp_delete_post($wc_product_id, true);
            
            if ($result) {
                delete_post_meta($food_id, '_yoco_wc_product_id');
                error_log('YoCo: Deleted WooCommerce product #' . $wc_product_id . ' for food #' . $food_id);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get WooCommerce product ID for food product
     * 
     * @param int $food_id Food product ID
     * @return int|false WooCommerce product ID or false
     */
    public function get_woocommerce_product_id($food_id) {
        $wc_product_id = get_post_meta($food_id, '_yoco_wc_product_id', true);
        
        if ($wc_product_id && get_post($wc_product_id)) {
            return (int) $wc_product_id;
        }
        
        return false;
    }
    
    /**
     * Add YoCo product type to WooCommerce
     * 
     * @param array $types Product types
     * @return array Modified product types
     */
    public function add_yoco_product_type($types) {
        $types['yoco_food'] = __('YoCo Food Item', 'yoco-takeaway');
        return $types;
    }
    
    /**
     * Get product class for YoCo products
     * 
     * @param string $classname Product class name
     * @param string $product_type Product type
     * @param string $post_type Post type
     * @param int $product_id Product ID
     * @return string Product class name
     */
    public function get_yoco_product_class($classname, $product_type, $post_type, $product_id) {
        if ($product_type === 'yoco_food') {
            return 'WC_Product_Simple'; // Use simple product class
        }
        return $classname;
    }
    
    /**
     * Hide YoCo products from shop and catalog
     * 
     * @param WP_Query $query Query object
     */
    public function hide_yoco_products_from_shop($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_shop() || is_product_category() || is_product_tag()) {
                $meta_query = $query->get('meta_query');
                if (!is_array($meta_query)) {
                    $meta_query = array();
                }
                
                $meta_query[] = array(
                    'key' => '_yoco_food_product',
                    'compare' => 'NOT EXISTS'
                );
                
                $query->set('meta_query', $meta_query);
            }
        }
    }
    
    /**
     * Hide YoCo products from visibility
     * 
     * @param bool $visible Product visibility
     * @param int $product_id Product ID
     * @return bool Modified visibility
     */
    public function hide_yoco_products($visible, $product_id) {
        if (get_post_meta($product_id, '_yoco_food_product', true)) {
            return false;
        }
        return $visible;
    }
    
    /**
     * Redirect YoCo product pages to prevent direct access
     */
    public function redirect_yoco_product_pages() {
        if (is_product()) {
            global $post;
            if ($post && get_post_meta($post->ID, '_yoco_food_product', true)) {
                // Redirect to shop or homepage
                wp_redirect(wc_get_page_permalink('shop') ?: home_url());
                exit;
            }
        }
    }
    
    /**
     * Set default image as product thumbnail
     * 
     * @param int $product_id WooCommerce product ID
     * @param string $image_url Default image URL
     */
    private function set_default_image_as_thumbnail($product_id, $image_url) {
        // Check if we already have this default image in media library
        $existing_attachment = $this->get_attachment_by_url($image_url);
        
        if ($existing_attachment) {
            set_post_thumbnail($product_id, $existing_attachment);
        } else {
            // Import image to media library
            $attachment_id = $this->import_image_to_media_library($image_url);
            if ($attachment_id) {
                set_post_thumbnail($product_id, $attachment_id);
            }
        }
    }
    
    /**
     * Get attachment ID by URL
     * 
     * @param string $url Image URL
     * @return int|false Attachment ID or false
     */
    private function get_attachment_by_url($url) {
        global $wpdb;
        
        $attachment = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = %s",
            $url,
            'attachment'
        ));
        
        return !empty($attachment) ? (int) $attachment[0] : false;
    }
    
    /**
     * Import image to media library
     * 
     * @param string $image_url Image URL
     * @return int|false Attachment ID or false
     */
    private function import_image_to_media_library($image_url) {
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        
        // Download file to temp location
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        // Set up the array of arguments for media_handle_sideload
        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );
        
        // Import the image
        $attachment_id = media_handle_sideload($file_array, 0, 'YoCo Default Image');
        
        // Clean up temp file
        if (file_exists($tmp)) {
            unlink($tmp);
        }
        
        return is_wp_error($attachment_id) ? false : $attachment_id;
    }
    
    /**
     * Bulk sync all food products to WooCommerce
     * 
     * @return array Results array with success/error counts
     */
    public function bulk_sync_all_food_products() {
        $food_products = get_posts(array(
            'post_type' => 'yoco_food',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        $results = array(
            'success' => 0,
            'errors' => 0,
            'skipped' => 0
        );
        
        foreach ($food_products as $food_id) {
            $food_meta = YoCo_Core::get_food_meta($food_id);
            
            // Skip if no price
            if (empty($food_meta['price']) || $food_meta['price'] <= 0) {
                $results['skipped']++;
                continue;
            }
            
            $wc_product_id = $this->sync_food_to_woocommerce($food_id);
            
            if ($wc_product_id) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
            
            // Prevent timeout on large datasets
            if (($results['success'] + $results['errors']) % 10 === 0) {
                sleep(1);
            }
        }
        
        return $results;
    }
    
    /**
     * Clean up orphaned WooCommerce products
     * 
     * @return int Number of cleaned up products
     */
    public function cleanup_orphaned_products() {
        $orphaned_products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_yoco_food_product',
                    'value' => true,
                    'compare' => '='
                )
            )
        ));
        
        $cleaned_up = 0;
        
        foreach ($orphaned_products as $wc_product_id) {
            $food_id = get_post_meta($wc_product_id, '_yoco_food_id', true);
            
            // If food product doesn't exist, delete WooCommerce product
            if (!$food_id || !get_post($food_id) || get_post_type($food_id) !== 'yoco_food') {
                wp_delete_post($wc_product_id, true);
                $cleaned_up++;
            }
        }
        
        return $cleaned_up;
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