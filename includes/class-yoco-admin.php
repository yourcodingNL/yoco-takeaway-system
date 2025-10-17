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
     * @var YoCo_Admin|null
     */
    private static $instance = null;

    /**
     * Get instance (Singleton)
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

        // Admin columns for yoco_food post type
        add_filter('manage_yoco_food_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_yoco_food_posts_custom_column', array($this, 'show_admin_columns'), 10, 2);
        add_filter('manage_edit-yoco_food_sortable_columns', array($this, 'make_columns_sortable'));

        // Admin styles
        add_action('admin_head', array($this, 'admin_styles'));

        // Category order fields (taxonomy yoco_food_cat)
        add_action('yoco_food_cat_add_form_fields', array($this, 'add_category_order_field'));
        add_action('yoco_food_cat_edit_form_fields', array($this, 'edit_category_order_field'));
        add_action('created_yoco_food_cat', array($this, 'save_category_order_field'));
        add_action('edited_yoco_food_cat', array($this, 'save_category_order_field'));

        // Add order column to categories list
        add_filter('manage_edit-yoco_food_cat_columns', array($this, 'add_category_columns'));
        add_filter('manage_yoco_food_cat_custom_column', array($this, 'show_category_columns'), 10, 3);
        add_filter('manage_edit-yoco_food_cat_sortable_columns', array($this, 'make_category_columns_sortable'));

        // Quick edit support for category order
        add_action('quick_edit_custom_box', array($this, 'add_quick_edit_fields'), 10, 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_quick_edit_script'));
        
        // WooCommerce sync hooks
        add_action('save_post_yoco_food', array($this, 'trigger_woocommerce_sync'), 20); // After meta save
        add_action('before_delete_post', array($this, 'cleanup_woocommerce_product'));
        add_action('wp_trash_post', array($this, 'cleanup_woocommerce_product'));
    }

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
     *
     * @param WP_Post $post
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
                <small class="yoco-price-note"><?php _e('Vereist voor winkelwagen functionaliteit', 'yoco-takeaway'); ?></small>
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
                            <?php
                            if ($level === 0) {
                                echo esc_html($label);
                            } else {
                                echo str_repeat('🌶️', (int) $level) . ' ' . esc_html($label);
                            }
                            ?>
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
                    <input type="checkbox" id="allergen_<?php echo esc_attr($key); ?>" name="yoco_food_allergens[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, (array) $meta['allergens'], true)); ?>>
                    <label for="allergen_<?php echo esc_attr($key); ?>" style="margin: 0;"><?php echo esc_html($label); ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <style>
        .yoco-price-note {
            color: #666;
            font-style: italic;
            display: block;
            margin-top: 5px;
        }
        </style>
        <?php
    }

    /**
     * Save food meta data
     *
     * @param int $post_id
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
        $old_price = get_post_meta($post_id, '_yoco_food_price', true);
        $new_price = isset($_POST['yoco_food_price']) ? sanitize_text_field($_POST['yoco_food_price']) : '';

        if ($new_price !== $old_price) {
            update_post_meta($post_id, '_yoco_food_price', $new_price);
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
        $allergens = isset($_POST['yoco_food_allergens']) ? array_map('sanitize_text_field', (array) $_POST['yoco_food_allergens']) : array();
        update_post_meta($post_id, '_yoco_food_allergens', $allergens);
    }

    /**
     * Trigger WooCommerce sync after food product save
     * 
     * @param int $post_id
     */
    public function trigger_woocommerce_sync($post_id) {
        // Skip for autosave or revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check if sync class exists
        if (class_exists('YoCo_WooCommerce_Sync')) {
            YoCo_WooCommerce_Sync::get_instance()->sync_food_to_woocommerce($post_id);
        }
    }
    
    /**
     * Cleanup WooCommerce product when food product is deleted
     * 
     * @param int $post_id
     */
    public function cleanup_woocommerce_product($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'yoco_food') {
            return;
        }
        
        // Check if sync class exists
        if (class_exists('YoCo_WooCommerce_Sync')) {
            YoCo_WooCommerce_Sync::get_instance()->delete_woocommerce_product($post_id);
        }
    }

    /**
     * Add order field to new category form (taxonomy: yoco_food_cat)
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
     * Add order field to edit category form (taxonomy: yoco_food_cat)
     *
     * @param WP_Term $term
     */
    public function edit_category_order_field($term) {
        $order = get_term_meta($term->term_id, 'yoco_order', true);
        if ($order === '' || $order === null) {
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
     * Save category order field (taxonomy: yoco_food_cat)
     *
     * @param int $term_id
     */
    public function save_category_order_field($term_id) {
        if (isset($_POST['yoco_category_order'])) {
            $order = intval($_POST['yoco_category_order']);
            update_term_meta($term_id, 'yoco_order', $order);
        }
    }

    /**
     * Add order column to categories list (taxonomy table columns)
     *
     * @param array $columns
     * @return array
     */
    public function add_category_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ('name' === $key) {
                $new_columns['yoco_order'] = __('Volgorde', 'yoco-takeaway');
            }
        }
        return $new_columns;
    }

    /**
     * Show order column content (taxonomy: yoco_food_cat)
     *
     * @param string $content
     * @param string $column_name
     * @param int    $term_id
     * @return string
     */
    public function show_category_columns($content, $column_name, $term_id) {
        if ($column_name === 'yoco_order') {
            $order = get_term_meta($term_id, 'yoco_order', true);
            return ($order !== '' && $order !== null) ? $order : '10';
        }
        return $content;
    }

    /**
     * Make taxonomy columns sortable (yoco_food_cat)
     *
     * @param array $columns
     * @return array
     */
    public function make_category_columns_sortable($columns) {
        $columns['yoco_order'] = 'yoco_order';
        return $columns;
    }

    /**
     * Add quick edit fields (taxonomy quick edit)
     *
     * @param string $column_name
     * @param string $screen
     * @param string $taxonomy
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
     * Enqueue quick edit script (taxonomy screen)
     *
     * @param string $hook
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
     * Add settings page under the yoco_food post type menu
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
                'halal'      => isset($_POST['icon_halal']) ? esc_url_raw($_POST['icon_halal']) : '',
                'vegetarian' => isset($_POST['icon_vegetarian']) ? esc_url_raw($_POST['icon_vegetarian']) : '',
                'vegan'      => isset($_POST['icon_vegan']) ? esc_url_raw($_POST['icon_vegan']) : '',
                'spicy'      => isset($_POST['icon_spicy']) ? esc_url_raw($_POST['icon_spicy']) : '',
            );

            update_option('yoco_icons', $icons);
            update_option('yoco_order_button_text', isset($_POST['order_button_text']) ? sanitize_text_field($_POST['order_button_text']) : __('Bestellen', 'yoco-takeaway'));
            update_option('yoco_default_image', isset($_POST['default_image']) ? esc_url_raw($_POST['default_image']) : '');

            echo '<div class="notice notice-success"><p>' . esc_html__('Instellingen opgeslagen!', 'yoco-takeaway') . '</p></div>';
        }

        $icons = get_option('yoco_icons', array(
            'halal'      => '',
            'vegetarian' => '',
            'vegan'      => '',
            'spicy'      => '',
        ));

        $order_button_text = get_option('yoco_order_button_text', __('Bestellen', 'yoco-takeaway'));
        $default_image = get_option('yoco_default_image', '');
        ?>
        <div class="wrap">
            <h1><?php _e('YoCo Takeaway System Instellingen', 'yoco-takeaway'); ?></h1>

            <!-- Virtual Cart System Status Card -->
            <div class="yoco-status-card">
                <h2><?php _e('Virtual Cart System Status', 'yoco-takeaway'); ?></h2>
                <?php $this->render_cart_system_status(); ?>
            </div>

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
                                <br><img src="<?php echo esc_url($icons['halal']); ?>" style="max-width: 50px; margin-top: 10px;" alt="Halal icon">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Vegetarisch Icoon', 'yoco-takeaway'); ?></th>
                        <td>
                            <input type="text" name="icon_vegetarian" id="icon_vegetarian" value="<?php echo esc_attr($icons['vegetarian']); ?>" class="regular-text">
                            <button type="button" class="button yoco-upload-icon-button" data-target="icon_vegetarian"><?php _e('Upload Icoon', 'yoco-takeaway'); ?></button>
                            <?php if (!empty($icons['vegetarian'])): ?>
                                <br><img src="<?php echo esc_url($icons['vegetarian']); ?>" style="max-width: 50px; margin-top: 10px;" alt="Vegetarian icon">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Vegan Icoon', 'yoco-takeaway'); ?></th>
                        <td>
                            <input type="text" name="icon_vegan" id="icon_vegan" value="<?php echo esc_attr($icons['vegan']); ?>" class="regular-text">
                            <button type="button" class="button yoco-upload-icon-button" data-target="icon_vegan"><?php _e('Upload Icoon', 'yoco-takeaway'); ?></button>
                            <?php if (!empty($icons['vegan'])): ?>
                                <br><img src="<?php echo esc_url($icons['vegan']); ?>" style="max-width: 50px; margin-top: 10px;" alt="Vegan icon">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Pittig Icoon', 'yoco-takeaway'); ?></th>
                        <td>
                            <input type="text" name="icon_spicy" id="icon_spicy" value="<?php echo esc_attr($icons['spicy']); ?>" class="regular-text">
                            <button type="button" class="button yoco-upload-icon-button" data-target="icon_spicy"><?php _e('Upload Icoon', 'yoco-takeaway'); ?></button>
                            <?php if (!empty($icons['spicy'])): ?>
                                <br><img src="<?php echo esc_url($icons['spicy']); ?>" style="max-width: 50px; margin-top: 10px;" alt="Spicy icon">
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <h2><?php _e('Standaard Afbeelding', 'yoco-takeaway'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Standaard Product Afbeelding', 'yoco-takeaway'); ?></th>
                        <td>
                            <input type="text" name="default_image" id="default_image" value="<?php echo esc_attr($default_image); ?>" class="regular-text">
                            <button type="button" class="button yoco-upload-icon-button" data-target="default_image"><?php _e('Upload Afbeelding', 'yoco-takeaway'); ?></button>
                            <?php if (!empty($default_image)): ?>
                                <br><img src="<?php echo esc_url($default_image); ?>" style="max-width: 100px; margin-top: 10px;" alt="Default image">
                            <?php endif; ?>
                            <p class="description"><?php _e('Deze afbeelding wordt gebruikt voor producten zonder eigen afbeelding.', 'yoco-takeaway'); ?></p>
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

        <style>
        .yoco-status-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .yoco-status-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 4px;
        }
        .yoco-status-success { background: #d4edda; border-left: 4px solid #28a745; }
        .yoco-status-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .yoco-status-error { background: #f8d7da; border-left: 4px solid #dc3545; }
        </style>
        <?php
    }

    /**
     * Render cart system status section
     */
    private function render_cart_system_status() {
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="yoco-status-item yoco-status-error">
                <span class="dashicons dashicons-dismiss"></span>
                <div>
                    <strong><?php _e('WooCommerce niet actief', 'yoco-takeaway'); ?></strong>
                    <p><?php _e('Installeer en activeer WooCommerce om de winkelwagen functionaliteit te gebruiken.', 'yoco-takeaway'); ?></p>
                </div>
            </div>
            <?php
            return;
        }

        // Check virtual product
        $virtual_product_id = get_option('yoco_virtual_product_id');
        $virtual_product = $virtual_product_id ? get_post($virtual_product_id) : false;

        // Count food products
        $counts = wp_count_posts('yoco_food');
        $total_food_products = isset($counts->publish) ? (int) $counts->publish : 0;
        $food_with_price = (int) $this->count_food_with_price();

        ?>
        <div class="yoco-status-item yoco-status-success">
            <span class="dashicons dashicons-yes-alt"></span>
            <div>
                <strong><?php _e('WooCommerce is actief', 'yoco-takeaway'); ?></strong>
                <p><?php _e('YoCo gebruikt een virtual cart systeem voor optimale prestaties.', 'yoco-takeaway'); ?></p>
            </div>
        </div>

        <div class="yoco-status-item <?php echo $virtual_product ? 'yoco-status-success' : 'yoco-status-error'; ?>">
            <span class="dashicons dashicons-<?php echo $virtual_product ? 'yes-alt' : 'dismiss'; ?>"></span>
            <div>
                <strong><?php _e('Virtual Product', 'yoco-takeaway'); ?></strong>
                <?php if ($virtual_product): ?>
                    <p><?php _e('Virtual product is aanwezig en functioneel.', 'yoco-takeaway'); ?></p>
                    <small>ID: <?php echo (int) $virtual_product_id; ?> | Status: <?php echo esc_html(ucfirst($virtual_product->post_status)); ?></small>
                <?php else: ?>
                    <p><?php _e('Virtual product ontbreekt. Deactiveer en heractiveer de plugin.', 'yoco-takeaway'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="yoco-status-item yoco-status-success">
            <span class="dashicons dashicons-cart"></span>
            <div>
                <strong><?php _e('Food Products', 'yoco-takeaway'); ?></strong>
                <p>
                    <?php
                    printf(
                        __('%d van %d food products hebben een prijs en kunnen besteld worden.', 'yoco-takeaway'),
                        $food_with_price,
                        $total_food_products
                    );
                    ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Count food products with price > 0
     *
     * @return int
     */
    private function count_food_with_price() {
        global $wpdb;

        $sql = $wpdb->prepare("
            SELECT COUNT(1)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
              AND p.post_status = %s
              AND pm.meta_key = %s
              AND CAST(pm.meta_value AS DECIMAL(10,2)) > 0
        ", 'yoco_food', 'publish', '_yoco_food_price');

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Enqueue admin scripts/styles
     *
     * @param string $hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only on settings page and food product pages
        if ($hook === 'yoco_food_page_yoco-settings' || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_media();
            wp_enqueue_script(
                'yoco-admin',
                YOCO_PLUGIN_URL . 'assets/js/yoco-admin.js',
                array('jquery'),
                YOCO_VERSION,
                true
            );

            wp_localize_script('yoco-admin', 'yoco_admin', array(
                'ajax_url'     => admin_url('admin-ajax.php'),
                'nonce'        => wp_create_nonce('yoco_admin_action'),
                'media_title'  => __('Kies een icoon', 'yoco-takeaway'),
                'media_button' => __('Gebruik dit icoon', 'yoco-takeaway'),
            ));
        }

        // Admin styles on all YoCo pages
        $post_type = get_post_type();
        if (strpos($hook, 'yoco') !== false || $post_type === 'yoco_food') {
            wp_enqueue_style(
                'yoco-admin',
                YOCO_PLUGIN_URL . 'assets/css/yoco-admin.css',
                array(),
                YOCO_VERSION
            );
        }
    }

    /**
     * Add admin columns to yoco_food list table
     *
     * @param array $columns
     * @return array
     */
    public function add_admin_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
        }
        $new_columns['yoco_price'] = __('Prijs', 'yoco-takeaway');
        $new_columns['yoco_attributes'] = __('Kenmerken', 'yoco-takeaway');
        return $new_columns;
    }

    /**
     * Show admin columns content
     *
     * @param string $column
     * @param int    $post_id
     */
    public function show_admin_columns($column, $post_id) {
        if ($column === 'yoco_price') {
            $price = get_post_meta($post_id, '_yoco_food_price', true);
            echo $price !== '' && $price !== null
                ? '€' . number_format((float) $price, 2, ',', '.')
                : '<span style="color: #dc3232;">—</span>';
        }

        if ($column === 'yoco_attributes') {
            $icons = get_option('yoco_icons', array());
            $attributes = array();

            if (get_post_meta($post_id, '_yoco_food_halal', true) === '1') {
                $attributes[] = !empty($icons['halal'])
                    ? '<img src="' . esc_url($icons['halal']) . '" style="width:20px;height:20px;" title="Halal" alt="Halal">'
                    : '🕌';
            }
            if (get_post_meta($post_id, '_yoco_food_vegetarian', true) === '1') {
                $attributes[] = !empty($icons['vegetarian'])
                    ? '<img src="' . esc_url($icons['vegetarian']) . '" style="width:20px;height:20px;" title="Vegetarisch" alt="Vegetarian">'
                    : '🥕';
            }
            if (get_post_meta($post_id, '_yoco_food_vegan', true) === '1') {
                $attributes[] = !empty($icons['vegan'])
                    ? '<img src="' . esc_url($icons['vegan']) . '" style="width:20px;height:20px;" title="Vegan" alt="Vegan">'
                    : '🌱';
            }

            $spicy = (int) get_post_meta($post_id, '_yoco_food_spicy', true);
            if ($spicy > 0) {
                $spicy_icon = !empty($icons['spicy'])
                    ? '<img src="' . esc_url($icons['spicy']) . '" style="width:20px;height:20px;" title="Pittig" alt="Spicy">'
                    : '🌶️';
                $attributes[] = str_repeat($spicy_icon, $spicy);
            }

            echo !empty($attributes) ? implode(' ', $attributes) : '—';
        }
    }

    /**
     * Make columns sortable (yoco_food list table)
     *
     * @param array $columns
     * @return array
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
                .column-yoco_price { width: 80px; }
                .column-yoco_attributes { width: 120px; }
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