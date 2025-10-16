<?php
/**
 * YoCo Frontend Class
 * 
 * Handles all frontend functionality including shortcodes and AJAX
 * 
 * @package YoCo_Takeaway_System
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class YoCo_Frontend {
    
    /**
     * Instance
     * 
     * @var YoCo_Frontend
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return YoCo_Frontend
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
        add_shortcode('yoco_menu', array($this, 'menu_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_yoco_add_to_cart', array($this, 'add_to_cart_ajax'));
        add_action('wp_ajax_nopriv_yoco_add_to_cart', array($this, 'add_to_cart_ajax'));
        
        // WooCommerce cart hooks
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_data'), 10, 4);
        add_filter('woocommerce_cart_item_name', array($this, 'cart_item_name'), 10, 3);
        add_filter('woocommerce_cart_item_price', array($this, 'cart_item_price'), 10, 3);
    }
    
    /**
     * Menu shortcode
     * 
     * @param array $atts
     * @return string
     */
    public function menu_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
        ), $atts);
        
        // Get template path
        $template_path = YOCO_PLUGIN_DIR . 'templates/takeaway-menu.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return $this->render_menu_fallback($atts);
    }
    
    /**
     * Fallback menu render if template doesn't exist
     */
    private function render_menu_fallback($atts) {
        ob_start();
        
        // Get categories and tags
        $categories = get_terms(array(
            'taxonomy' => 'yoco_food_cat',
            'hide_empty' => false,
        ));
        
        $tags = get_terms(array(
            'taxonomy' => 'yoco_food_tag',
            'hide_empty' => false,
        ));
        
        // Query food items
        $args = array(
            'post_type' => 'yoco_food',
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
        );
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'yoco_food_cat',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ),
            );
        }
        
        $food_items = new WP_Query($args);
        
        if (!$food_items->have_posts()) {
            return '<p>' . __('Geen food producten gevonden.', 'yoco-takeaway') . '</p>';
        }
        
        $icons = get_option('yoco_icons', array());
        $button_text = get_option('yoco_order_button_text', __('Bestellen', 'yoco-takeaway'));
        $allergen_labels = YoCo_Core::get_allergen_labels();
        
        ?>
        <div class="yoco-menu" id="yoco-menu">
            <div class="yoco-search-wrapper">
                <input type="text" id="yoco-search-input" class="yoco-search-input" placeholder="<?php _e('Zoek op naam, ingrediënt of dieetwens...', 'yoco-takeaway'); ?>">
                <button type="button" id="yoco-search-btn" class="yoco-search-btn"><?php _e('Zoeken', 'yoco-takeaway'); ?></button>
            </div>
            
            <div class="yoco-filters-toggle" id="yoco-filters-toggle">
                <span class="yoco-filters-toggle-text">⚙️ <?php _e('Uitgebreid zoeken', 'yoco-takeaway'); ?></span>
                <span class="yoco-filters-toggle-icon">+</span>
            </div>
            
            <div class="yoco-filters" id="yoco-filters">
                <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                <div class="yoco-filter-section">
                    <h3><?php _e('Gerechten', 'yoco-takeaway'); ?></h3>
                    <div class="yoco-filter-buttons" id="category-filters">
                        <?php foreach ($categories as $category): ?>
                            <button type="button" class="yoco-filter-btn category-btn" data-value="<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($tags) && !is_wp_error($tags)): ?>
                <div class="yoco-filter-section">
                    <h3><?php _e('Kenmerken', 'yoco-takeaway'); ?></h3>
                    <div class="yoco-filter-buttons" id="tag-filters">
                        <?php foreach ($tags as $tag): ?>
                            <button type="button" class="yoco-filter-btn tag-btn" data-value="<?php echo esc_attr($tag->slug); ?>">
                                <?php echo esc_html($tag->name); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <button type="button" class="yoco-filter-reset" id="yoco-filter-reset">
                    ✕ <?php _e('Reset alle filters', 'yoco-takeaway'); ?>
                </button>
            </div>
            
            <div id="yoco-items-container">
                <?php 
                $spicy_labels = YoCo_Core::get_spicy_labels();
                while ($food_items->have_posts()) : $food_items->the_post(); 
                    $post_id = get_the_ID();
                    $meta = YoCo_Core::get_food_meta($post_id);
                    $thumbnail = get_the_post_thumbnail_url($post_id, 'thumbnail');
                    
                    $item_categories = wp_get_post_terms($post_id, 'yoco_food_cat', array('fields' => 'slugs'));
                    $item_tags = wp_get_post_terms($post_id, 'yoco_food_tag', array('fields' => 'slugs'));
                ?>
                <div class="yoco-item" data-food-id="<?php echo $post_id; ?>" 
                     data-categories="<?php echo esc_attr(implode(',', $item_categories)); ?>" 
                     data-tags="<?php echo esc_attr(implode(',', $item_tags)); ?>">
                    
                    <?php if ($thumbnail): ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title(); ?>" class="yoco-image">
                    <?php endif; ?>
                    
                    <div class="yoco-info">
                        <h3 class="yoco-title"><?php the_title(); ?></h3>
                        <?php if (get_the_excerpt()): ?>
                            <p class="yoco-description"><?php echo get_the_excerpt(); ?></p>
                        <?php endif; ?>
                        
                        <div class="yoco-icons">
                            <?php if ($meta['halal'] == '1'): ?>
                                <div class="yoco-icon-item">
                                    <?php if (!empty($icons['halal'])): ?>
                                        <img src="<?php echo esc_url($icons['halal']); ?>" class="yoco-icon" alt="Halal">
                                    <?php else: ?>
                                        <span class="yoco-emoji-icon">🕌</span>
                                    <?php endif; ?>
                                    <span><?php _e('Halal', 'yoco-takeaway'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($meta['vegetarian'] == '1'): ?>
                                <div class="yoco-icon-item">
                                    <?php if (!empty($icons['vegetarian'])): ?>
                                        <img src="<?php echo esc_url($icons['vegetarian']); ?>" class="yoco-icon" alt="Vegetarisch">
                                    <?php else: ?>
                                        <span class="yoco-emoji-icon">🥕</span>
                                    <?php endif; ?>
                                    <span><?php _e('Vegetarisch', 'yoco-takeaway'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($meta['vegan'] == '1'): ?>
                                <div class="yoco-icon-item">
                                    <?php if (!empty($icons['vegan'])): ?>
                                        <img src="<?php echo esc_url($icons['vegan']); ?>" class="yoco-icon" alt="Vegan">
                                    <?php else: ?>
                                        <span class="yoco-emoji-icon">🌱</span>
                                    <?php endif; ?>
                                    <span><?php _e('Vegan', 'yoco-takeaway'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($meta['spicy'] > 0): ?>
                                <div class="yoco-icon-item">
                                    <?php 
                                    for ($i = 0; $i < $meta['spicy']; $i++) {
                                        if (!empty($icons['spicy'])) {
                                            echo '<img src="' . esc_url($icons['spicy']) . '" class="yoco-icon" alt="Pittig">';
                                        } else {
                                            echo '<span class="yoco-emoji-icon">🌶️</span>';
                                        }
                                    }
                                    ?>
                                    <span><?php echo $spicy_labels[$meta['spicy']]; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($meta['allergens'])): ?>
                            <button type="button" class="yoco-allergens-btn" data-food-id="<?php echo $post_id; ?>">
                                ⚠️ <?php _e('Zie allergenen', 'yoco-takeaway'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="yoco-price-order">
                        <div class="yoco-price">€<?php echo number_format($meta['price'], 2, ',', '.'); ?></div>
                        
                        <div class="yoco-quantity">
                            <button class="yoco-qty-btn yoco-qty-minus" type="button">-</button>
                            <input type="number" class="yoco-qty-input" value="1" min="1" max="99" readonly>
                            <button class="yoco-qty-btn yoco-qty-plus" type="button">+</button>
                        </div>
                        
                        <button class="yoco-order-btn" type="button"><?php echo esc_html($button_text); ?></button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <script>
        // Allergens data for popup
        const yocoAllergens = {
            <?php 
            $food_items->rewind_posts();
            while ($food_items->have_posts()) : $food_items->the_post(); 
                $post_id = get_the_ID();
                $meta = YoCo_Core::get_food_meta($post_id);
                if (!empty($meta['allergens']) && is_array($meta['allergens'])) {
                    $allergen_names = array();
                    foreach ($meta['allergens'] as $allergen) {
                        if (isset($allergen_labels[$allergen])) {
                            $allergen_names[] = $allergen_labels[$allergen];
                        }
                    }
                    if (!empty($allergen_names)) {
                        echo "'" . $post_id . "': " . json_encode($allergen_names) . ",\n";
                    }
                }
            endwhile; 
            ?>
        };
        </script>
        <?php
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for adding to cart
     */
    public function add_to_cart_ajax() {
        check_ajax_referer('yoco_add_to_cart', 'nonce');
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(__('WooCommerce is niet actief', 'yoco-takeaway'));
            return;
        }
        
        $food_id = intval($_POST['food_id']);
        $quantity = intval($_POST['quantity']);
        
        if (!$food_id || !$quantity) {
            wp_send_json_error(__('Ongeldige gegevens', 'yoco-takeaway'));
            return;
        }
        
        $food_post = get_post($food_id);
        if (!$food_post || $food_post->post_type !== 'yoco_food') {
            wp_send_json_error(__('Product niet gevonden', 'yoco-takeaway'));
            return;
        }
        
        $price = get_post_meta($food_id, '_yoco_food_price', true);
        if (!$price) {
            wp_send_json_error(__('Prijs niet gevonden', 'yoco-takeaway'));
            return;
        }
        
        $cart_item_data = array(
            'yoco_food_product' => true,
            'yoco_food_id' => $food_id,
            'yoco_food_title' => $food_post->post_title,
            'yoco_food_price' => $price,
            'yoco_food_thumbnail' => get_the_post_thumbnail_url($food_id, 'thumbnail'),
            'unique_key' => md5($food_id . time())
        );
        
        $placeholder_product_id = get_option('yoco_wc_placeholder_product');
        
        if (!$placeholder_product_id || !get_post($placeholder_product_id)) {
            wp_send_json_error(__('Placeholder product niet gevonden', 'yoco-takeaway'));
            return;
        }
        
        $cart_item_key = WC()->cart->add_to_cart($placeholder_product_id, $quantity, 0, array(), $cart_item_data);
        
        if ($cart_item_key) {
            wp_send_json_success(array(
                'message' => __('Product toegevoegd aan winkelwagen', 'yoco-takeaway'),
                'cart_count' => WC()->cart->get_cart_contents_count()
            ));
        } else {
            wp_send_json_error(__('Kon product niet toevoegen aan winkelwagen', 'yoco-takeaway'));
        }
    }
    
    /**
     * Add cart item data
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        if (isset($cart_item_data['yoco_food_product'])) {
            $cart_item_data['data'] = clone WC()->product_factory->get_product($product_id);
            $cart_item_data['data']->set_name($cart_item_data['yoco_food_title']);
            $cart_item_data['data']->set_price($cart_item_data['yoco_food_price']);
        }
        return $cart_item_data;
    }
    
    /**
     * Display cart item data
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['yoco_food_product'])) {
            $item_data[] = array(
                'name' => __('YoCo Product', 'yoco-takeaway'),
                'value' => $cart_item['yoco_food_title']
            );
        }
        return $item_data;
    }
    
    /**
     * Save order item data
     */
    public function save_order_item_data($item, $cart_item_key, $values, $order) {
        if (isset($values['yoco_food_product'])) {
            $item->add_meta_data(__('YoCo Product ID', 'yoco-takeaway'), $values['yoco_food_id']);
            $item->add_meta_data(__('YoCo Product Title', 'yoco-takeaway'), $values['yoco_food_title']);
        }
    }
    
    /**
     * Cart item name
     */
    public function cart_item_name($name, $cart_item, $cart_item_key) {
        if (isset($cart_item['yoco_food_product'])) {
            return $cart_item['yoco_food_title'];
        }
        return $name;
    }
    
    /**
     * Cart item price
     */
    public function cart_item_price($price, $cart_item, $cart_item_key) {
        if (isset($cart_item['yoco_food_product'])) {
            return wc_price($cart_item['yoco_food_price']);
        }
        return $price;
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