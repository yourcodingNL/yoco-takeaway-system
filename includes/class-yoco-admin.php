<?php
/**
 * YoCo Admin Class
 * 
 * Handles all admin/backend functionality
 * 
 * @package YoCo_Takeaway_System
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class YoCo_Admin {
    
    /**
     * Instance
     * 
     * @var YoCo_Admin
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return YoCo_Admin
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
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_yoco_food', array($this, 'save_food_meta'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Admin columns
        add_filter('manage_yoco_food_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_yoco_food_posts_custom_column', array($this, 'show_admin_columns'), 10, 2);
        add_filter('manage_edit-yoco_food_sortable_columns', array($this, 'make_columns_sortable'));
        
        // Admin styles
        add_action('admin_head', array($this, 'admin_styles'));
        
        // Category order fields
        add_action('yoco_food_cat_add_form_fields', array($this, 'add_category_order_field'));
        add_action('yoco_food_cat_edit_form_fields', array($this, 'edit_category_order_field'));
        add_action('created_yoco_food_cat', array($this, 'save_category_order_field'));
        add_action('edited_yoco_food_cat', array($this, 'save_category_order_field'));
        
        // Add order column to categories list
        add_filter('manage_edit-yoco_food_cat_columns', array($this, 'add_category_columns'));
        add_filter('manage_yoco_food_cat_custom_column', array($this, 'show_category_columns'), 10, 3);
        add_filter('manage_edit-yoco_food_cat_sortable_columns', array($this, 'make_category_columns_sortable'));
        
        // Quick edit support
        add_action('quick_edit_custom_box', array($this, 'add_quick_edit_fields'), 10, 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_quick_edit_script'));
    }
    
    /**
     * Add order field to new category form
     */
    public function add_category_order_field() {
        ?>
        <div class="form-field">
            <label for="yoco_category_order"><?php _e('Volgorde', 'yoco-takeaway'); ?></label>
            <input type="number" name="yoco_category_order" id="yoco_category_order" value="10" min="0" step="1">
            <p class="description"><?php _e('Getal voor sortering (lager = eerder weergegeven). Bijv: 10, 20, 30...', 'yoco-takeaway'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add order field to edit category form
     */
    public function edit_category_order_field($term) {
        $order = get_term_meta($term->term_id, 'yoco_order', true);
        if (empty($order)) {
            $order = 10;
        }
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="yoco_category_order"><?php _e('Volgorde', 'yoco-takeaway'); ?></label>
            </th>
            <td>
                <input type="number" name="yoco_category_order" id="yoco_category_order" value="<?php echo esc_attr($order); ?>" min="0" step="1">
                <p class="description"><?php _e('Getal voor sortering (lager = eerder weergegeven). Bijv: 10, 20, 30...', 'yoco-takeaway'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Make category columns sortable
     */
    public function make_category_columns_sortable($columns) {
        $columns['yoco_order'] = 'yoco_order';
        return $columns;
    }
    
    /**
     * Add quick edit fields
     */
    public function add_quick_edit_fields($column_name, $screen, $taxonomy) {
        if ($column_name !== 'yoco_order' || $taxonomy !== 'yoco_food_cat') {
            return;
        }
        ?>
        <fieldset>
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php _e('Volgorde', 'yoco-takeaway'); ?></span>
                    <span class="input-text-wrap">
                        <input type="number" name="yoco_category_order" class="ptitle" value="" min="0" step="1">
                    </span>
                </label>
                <p class="howto"><?php _e('Getal voor sortering (lager = eerder weergegeven)', 'yoco-takeaway'); ?></p>
            </div>
        </fieldset>
        <?php
    }
    
    /**
     * Enqueue quick edit script
     */
    public function enqueue_quick_edit_script($hook) {
        if ($hook === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'yoco_food_cat') {
            wp_enqueue_script(
                'yoco-quick-edit',
                YOCO_PLUGIN_URL . 'assets/js/yoco-quick-edit.js',
                array('jquery'),
                YOCO_VERSION,
                true
            );
        }
    }
    
    /**
     * Save category order field
     */
    public function save_category_order_field($term_id) {
        if (isset($_POST['yoco_category_order'])) {
            $order = intval($_POST['yoco_category_order']);
            update_term_meta($term_id, 'yoco_order', $order);
        }
    }
    
    /**
     * Add order column to categories list
     */
    public function add_category_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'name') {
                $new_columns['yoco_order'] = __('Volgorde', 'yoco-takeaway');
            }
        }
        return $new_columns;
    }
    
    /**
     * Show order column content
     */
    public function show_category_columns($content, $column_name, $term_id) {
        if ($column_name === 'yoco_order') {
            $order = get_term_meta($term_id, 'yoco_order', true);
            return !empty($order) ? $order : '10';
        }
        return $content;
    }
    
    /**
     * Remove old drag-and-drop functions (not needed anymore)
     */
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'yoco_food_details',
            __('Food Product Details', 'yoco-takeaway'),
            array($this, 'render_food_details_meta_box'),
            'yoco_food',
            'normal',
            'high'
        );
    }
    
    /**
     * Render food details meta box
     */
    public function render_food_details_meta_box($post) {
        wp_nonce_field('yoco_food_details_nonce', 'yoco_food_details_nonce_field');
        
        $meta = YoCo_Core::get_food_meta($post->ID);
        $allergen_list = YoCo_Core::get_allergen_labels();
        $icons = get_option('yoco_icons', array());
        
        ?>
        <div class="yoco-food-details-grid">
            <div class="yoco-food-detail-field">
                <label for="yoco_food_price"><?php _e('Prijs (€) *', 'yoco-takeaway'); ?></label>
                <input type="number" step="0.01" id="yoco_food_price" name="yoco_food_price" value="<?php echo esc_attr($meta['price']); ?>" required>
            </div>
            
            <div class="yoco-food-detail-field">
                <div class="yoco-food-checkbox-group">
                    <input type="checkbox" id="yoco_food_is_menu" name="yoco_food_is_menu" value="1" <?php checked($meta['is_menu'], '1'); ?>>
                    <label for="yoco_food_is_menu" style="margin: 0;"><?php _e('Maak er een menu van', 'yoco-takeaway'); ?></label>
                </div>
            </div>
        </div>
        
        <hr style="margin: 20px 0;">
        
        <h3><?php _e('Dieetwensen', 'yoco-takeaway'); ?></h3>
        
        <div class="yoco-food-details-grid">
            <div class="yoco-food-detail-field">
                <div class="yoco-food-checkbox-group">
                    <?php if (!empty($icons['halal'])): ?>
                        <img src="<?php echo esc_url($icons['halal']); ?>" class="yoco-food-icon-preview" alt="Halal">
                    <?php endif; ?>
                    <input type="checkbox" id="yoco_food_halal" name="yoco_food_halal" value="1" <?php checked($meta['halal'], '1'); ?>>
                    <label for="yoco_food_halal" style="margin: 0;"><?php _e('Halal', 'yoco-takeaway'); ?></label>
                </div>
            </div>
            
            <div class="yoco-food-detail-field">
                <div class="yoco-food-checkbox-group">
                    <?php if (!empty($icons['vegetarian'])): ?>
                        <img src="<?php echo esc_url($icons['vegetarian']); ?>" class="yoco-food-icon-preview" alt="Vegetarisch">
                    <?php endif; ?>
                    <input type="checkbox" id="yoco_food_vegetarian" name="yoco_food_vegetarian" value="1" <?php checked($meta['vegetarian'], '1'); ?>>
                    <label for="yoco_food_vegetarian" style="margin: 0;"><?php _e('Vegetarisch', 'yoco-takeaway'); ?></label>
                </div>
            </div>
            
            <div class="yoco-food-detail-field">
                <div class="yoco-food-checkbox-group">
                    <?php if (!empty($icons['vegan'])): ?>
                        <img src="<?php echo esc_url($icons['vegan']); ?>" class="yoco-food-icon-preview" alt="Vegan">
                    <?php endif; ?>
                    <input type="checkbox" id="yoco_food_vegan" name="yoco_food_vegan" value="1" <?php checked($meta['vegan'], '1'); ?>>
                    <label for="yoco_food_vegan" style="margin: 0;"><?php _e('Vegan', 'yoco-takeaway'); ?></label>
                </div>
            </div>
            
            <div class="yoco-food-detail-field">
                <label>
                    <?php if (!empty($icons['spicy'])): ?>
                        <img src="<?php echo esc_url($icons['spicy']); ?>" class="yoco-food-icon-preview" alt="Pittig" style="vertical-align: middle; margin-right: 5px;">
                    <?php endif; ?>
                    <?php _e('Pittigheid', 'yoco-takeaway'); ?>
                </label>
                <div class="yoco-spicy-rating">
                    <?php foreach (YoCo_Core::get_spicy_labels() as $level => $label): ?>
                        <label>
                            <input type="radio" name="yoco_food_spicy" value="<?php echo $level; ?>" <?php checked($meta['spicy'], $level); ?>>
                            <?php if ($level === 0): ?>
                                <?php echo $label; ?>
                            <?php else: ?>
                                <?php echo str_repeat('🌶️', $level) . ' ' . $label; ?>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <hr style="margin: 20px 0;">
        
        <h3><?php _e('Allergenen', 'yoco-takeaway'); ?></h3>
        <p style="color: #666; font-size: 13px; margin-bottom: 15px;"><?php _e('Selecteer alle allergenen die in dit product voorkomen:', 'yoco-takeaway'); ?></p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <?php foreach ($allergen_list as $key => $label): ?>
                <div class="yoco-food-checkbox-group">
                    <input type="checkbox" id="allergen_<?php echo $key; ?>" name="yoco_food_allergens[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $meta['allergens'])); ?>>
                    <label for="allergen_<?php echo $key; ?>" style="margin: 0;"><?php echo $label; ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Save food meta data
     */
    public function save_food_meta($post_id) {
        if (!isset($_POST['yoco_food_details_nonce_field']) || !wp_verify_nonce($_POST['yoco_food_details_nonce_field'], 'yoco_food_details_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save price
        if (isset($_POST['yoco_food_price'])) {
            update_post_meta($post_id, '_yoco_food_price', sanitize_text_field($_POST['yoco_food_price']));
        }
        
        // Save checkboxes
        update_post_meta($post_id, '_yoco_food_halal', isset($_POST['yoco_food_halal']) ? '1' : '0');
        update_post_meta($post_id, '_yoco_food_vegetarian', isset($_POST['yoco_food_vegetarian']) ? '1' : '0');
        update_post_meta($post_id, '_yoco_food_vegan', isset($_POST['yoco_food_vegan']) ? '1' : '0');
        update_post_meta($post_id, '_yoco_food_is_menu', isset($_POST['yoco_food_is_menu']) ? '1' : '0');
        
        // Save spicy level
        if (isset($_POST['yoco_food_spicy'])) {
            update_post_meta($post_id, '_yoco_food_spicy', sanitize_text_field($_POST['yoco_food_spicy']));
        }
        
        // Save allergens
        $allergens = isset($_POST['yoco_food_allergens']) ? array_map('sanitize_text_field', $_POST['yoco_food_allergens']) : array();
        update_post_meta($post_id, '_yoco_food_allergens', $allergens);
    }
    
    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=yoco_food',
            __('YoCo Instellingen', 'yoco-takeaway'),
            __('Instellingen', 'yoco-takeaway'),
            'manage_options',
            'yoco-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['yoco_settings_submit'])) {
            check_admin_referer('yoco_settings_action', 'yoco_settings_nonce');
            
            $icons = array(
                'halal' => esc_url_raw($_POST['icon_halal']),
                'vegetarian' => esc_url_raw($_POST['icon_vegetarian']),
                'vegan' => esc_url_raw($_POST['icon_vegan']),
                'spicy' => esc_url_raw($_POST['icon_spicy']),
            );
            
            update_option('yoco_icons', $icons);
            update_option('yoco_order_button_text', sanitize_text_field($_POST['order_button_text']));
            
            echo '<div class="notice notice-success"><p>' . __('Instellingen opgeslagen!', 'yoco-takeaway') . '</p></div>';
        }
        
        $icons = get_option('yoco_icons', array(
            'halal' => '',
            'vegetarian' => '',
            'vegan' => '',
            'spicy' => '',
        ));
        
        $order_button_text = get_option('yoco_order_button_text', __('Bestellen', 'yoco-takeaway'));
        
        ?>
        <div class="wrap">
            <h1><?php _e('YoCo Takeaway System Instellingen', 'yoco-takeaway'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('yoco_settings_action', 'yoco_settings_nonce'); ?>
                
                <h2><?php _e('Iconen', 'yoco-takeaway'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Halal Icoon', 'yoco-takeaway'); ?></th>
                        <td>
                            <input type="text" name="icon_halal" id="icon_halal" value="<?php echo esc_attr($icons['halal']); ?>" class="regular-text">
                            <button type="button" class="button yoco-upload-icon-button" data-target="icon_halal"><?php _e('Upload Icoon', 'yoco-takeaway'); ?></button>
                            <?php if (!empty($icons['halal'])): ?>
                                <br><img src="<?php echo esc_url($icons['halal']); ?>" style="max-width: 50px; margin-top: 10px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Vegetarisch Icoon', 'yoco-takeaway'); ?></th>
                        <td>
                            <input type="text" name="icon_vegetarian" id="icon_vegetarian" value="<?php echo esc_attr($icons['vegetarian']); ?>" class="regular-text">
                            <button type="button" class="button yoco-upload-icon-button" data-target="icon_vegetarian"><?php _e('Upload Icoon', 'yoco-takeaway'); ?></button>
                            <?php if (!empty($icons['vegetarian'])): ?>
                                <br><img src="<?php echo esc_url($icons['vegetarian']); ?>" style="max-width: 50px; margin-top: 10px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Vegan Icoon', 'yoco-takeaway'); ?></th>
                        <td>
                            <input type="text" name="icon_vegan" id="icon_vegan" value="<?php echo esc_attr($icons['vegan']); ?>" class="regular-text">
                            <button type="button" class="button yoco-upload-icon-button" data-target="icon_vegan"><?php _e('Upload Icoon', 'yoco-takeaway'); ?></button>
                            <?php if (!empty($icons['vegan'])): ?>
                                <br><img src="<?php echo esc_url($icons['vegan']); ?>" style="max-width: 50px; margin-top: 10px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Pittig Icoon', 'yoco-takeaway'); ?></th>
                        <td>
                            <input type="text" name="icon_spicy" id="icon_spicy" value="<?php echo esc_attr($icons['spicy']); ?>" class="regular-text">
                            <button type="button" class="button yoco-upload-icon-button" data-target="icon_spicy"><?php _e('Upload Icoon', 'yoco-takeaway'); ?></button>
                            <?php if (!empty($icons['spicy'])): ?>
                                <br><img src="<?php echo esc_url($icons['spicy']); ?>" style="max-width: 50px; margin-top: 10px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Knopteksten', 'yoco-takeaway'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Bestelknop Tekst', 'yoco-takeaway'); ?></th>
                        <td>
                            <input type="text" name="order_button_text" value="<?php echo esc_attr($order_button_text); ?>" class="regular-text">
                            <p class="description"><?php _e('De tekst op de bestelknop (bijv. "Bestellen", "In winkelwagen", etc.)', 'yoco-takeaway'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="yoco_settings_submit" class="button button-primary" value="<?php _e('Opslaan', 'yoco-takeaway'); ?>">
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only on settings page
        if ($hook === 'yoco_food_page_yoco-settings') {
            wp_enqueue_media();
            wp_enqueue_script(
                'yoco-admin',
                YOCO_PLUGIN_URL . 'assets/js/yoco-admin.js',
                array('jquery'),
                YOCO_VERSION,
                true
            );
            
            wp_localize_script('yoco-admin', 'yoco_admin', array(
                'media_title' => __('Kies een icoon', 'yoco-takeaway'),
                'media_button' => __('Gebruik dit icoon', 'yoco-takeaway'),
            ));
        }
        
        // Admin styles
        wp_enqueue_style(
            'yoco-admin',
            YOCO_PLUGIN_URL . 'assets/css/yoco-admin.css',
            array(),
            YOCO_VERSION
        );
    }
    
    /**
     * Add admin columns
     */
    public function add_admin_columns($columns) {
        $columns['yoco_price'] = __('Prijs', 'yoco-takeaway');
        $columns['yoco_attributes'] = __('Kenmerken', 'yoco-takeaway');
        return $columns;
    }
    
    /**
     * Show admin columns content
     */
    public function show_admin_columns($column, $post_id) {
        if ($column == 'yoco_price') {
            $price = get_post_meta($post_id, '_yoco_food_price', true);
            echo $price ? '€' . number_format($price, 2, ',', '.') : '-';
        }
        
        if ($column == 'yoco_attributes') {
            $icons = get_option('yoco_icons', array());
            $attributes = array();
            
            if (get_post_meta($post_id, '_yoco_food_halal', true) == '1') {
                $attributes[] = !empty($icons['halal']) 
                    ? '<img src="' . esc_url($icons['halal']) . '" style="width: 20px; height: 20px;" title="Halal">' 
                    : '🕌';
            }
            if (get_post_meta($post_id, '_yoco_food_vegetarian', true) == '1') {
                $attributes[] = !empty($icons['vegetarian']) 
                    ? '<img src="' . esc_url($icons['vegetarian']) . '" style="width: 20px; height: 20px;" title="Vegetarisch">' 
                    : '🥕';
            }
            if (get_post_meta($post_id, '_yoco_food_vegan', true) == '1') {
                $attributes[] = !empty($icons['vegan']) 
                    ? '<img src="' . esc_url($icons['vegan']) . '" style="width: 20px; height: 20px;" title="Vegan">' 
                    : '🌱';
            }
            
            $spicy = get_post_meta($post_id, '_yoco_food_spicy', true);
            if ($spicy > 0) {
                $spicy_icon = !empty($icons['spicy']) 
                    ? '<img src="' . esc_url($icons['spicy']) . '" style="width: 20px; height: 20px;" title="Pittig">' 
                    : '🌶️';
                $attributes[] = str_repeat($spicy_icon, $spicy);
            }
            
            echo !empty($attributes) ? implode(' ', $attributes) : '-';
        }
    }
    
    /**
     * Make columns sortable
     */
    public function make_columns_sortable($columns) {
        $columns['yoco_price'] = 'yoco_price';
        return $columns;
    }
    
    /**
     * Admin styles
     */
    public function admin_styles() {
        global $post_type;
        if ($post_type === 'yoco_food') {
            ?>
            <style>
                .yoco-food-details-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    margin-top: 15px;
                }
                .yoco-food-detail-field {
                    margin-bottom: 15px;
                }
                .yoco-food-detail-field label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 5px;
                }
                .yoco-food-detail-field input[type="number"],
                .yoco-food-detail-field input[type="text"] {
                    width: 100%;
                    padding: 5px;
                }
                .yoco-food-checkbox-group {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 10px;
                    background: #f9f9f9;
                    border-radius: 4px;
                }
                .yoco-food-checkbox-group input[type="checkbox"] {
                    margin: 0;
                }
                .yoco-food-icon-preview {
                    width: 24px;
                    height: 24px;
                    object-fit: contain;
                }
                .yoco-spicy-rating {
                    display: flex;
                    gap: 5px;
                    flex-direction: column;
                }
                .yoco-spicy-rating input[type="radio"] {
                    margin: 0 5px 0 0;
                }
            </style>
            <?php
        }
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