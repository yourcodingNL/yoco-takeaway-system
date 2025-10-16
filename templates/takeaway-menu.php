<?php
/**
 * YoCo Takeaway Menu Template
 * 
 * Template for displaying the food menu with search, filters, and ordering
 * 
 * @package YoCo_Takeaway_System
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get all categories first (moved up to fix PHP error)
$all_categories = get_terms(array(
    'taxonomy' => 'yoco_food_cat',
    'hide_empty' => true,
    'orderby' => 'meta_value_num',
    'meta_key' => 'yoco_order',
    'order' => 'ASC'
));

// If no custom order exists, fall back to name ordering
if (empty($all_categories) || is_wp_error($all_categories)) {
    $all_categories = get_terms(array(
        'taxonomy' => 'yoco_food_cat',
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
}

// Get categories and tags for filters
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
    'post_status' => 'publish'
);

// Category filter from shortcode
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
    echo '<p class="yoco-no-products">' . __('Geen food producten gevonden.', 'yoco-takeaway') . '</p>';
    return;
}

// Get plugin options
$icons = get_option('yoco_icons', array());
$button_text = get_option('yoco_order_button_text', __('Bestellen', 'yoco-takeaway'));
$allergen_labels = YoCo_Core::get_allergen_labels();
$spicy_labels = YoCo_Core::get_spicy_labels();

?>

<div class="yoco-menu" id="yoco-menu">
    
    <!-- Search Section -->
    <div class="yoco-search-wrapper">
        <input type="text" 
               id="yoco-search-input" 
               class="yoco-search-input" 
               placeholder="<?php esc_attr_e('Zoek op naam, ingrediënt of dieetwens...', 'yoco-takeaway'); ?>"
               aria-label="<?php esc_attr_e('Zoeken in menu', 'yoco-takeaway'); ?>">
        <button type="button" 
                id="yoco-search-btn" 
                class="yoco-search-btn"
                aria-label="<?php esc_attr_e('Zoeken', 'yoco-takeaway'); ?>">
            <?php _e('Zoeken', 'yoco-takeaway'); ?>
        </button>
    </div>
    
    <!-- Filter Toggle -->
    <div class="yoco-filters-toggle" id="yoco-filters-toggle" role="button" tabindex="0" aria-expanded="false">
        <span class="yoco-filters-toggle-text"><?php _e('Uitgebreid zoeken', 'yoco-takeaway'); ?></span>
        <span class="yoco-filters-toggle-icon">+</span>
    </div>
    
    <!-- Filters Section -->
    <div class="yoco-filters" id="yoco-filters" aria-hidden="true">
        
        <?php if (!empty($categories) && !is_wp_error($categories)): ?>
        <div class="yoco-filter-section">
            <h3><?php _e('Gerechten', 'yoco-takeaway'); ?></h3>
            <div class="yoco-filter-buttons" id="category-filters" role="group" aria-label="<?php esc_attr_e('Filter op categorie', 'yoco-takeaway'); ?>">
                <?php foreach ($categories as $category): ?>
                    <button type="button" 
                            class="yoco-filter-btn category-btn" 
                            data-value="<?php echo esc_attr($category->slug); ?>"
                            aria-pressed="false">
                        <?php echo esc_html($category->name); ?>
                        <?php if ($category->count > 0): ?>
                            <span class="yoco-filter-count">(<?php echo $category->count; ?>)</span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($tags) && !is_wp_error($tags)): ?>
        <div class="yoco-filter-section">
            <h3><?php _e('Kenmerken', 'yoco-takeaway'); ?></h3>
            <div class="yoco-filter-buttons" id="tag-filters" role="group" aria-label="<?php esc_attr_e('Filter op kenmerken', 'yoco-takeaway'); ?>">
                <?php foreach ($tags as $tag): ?>
                    <button type="button" 
                            class="yoco-filter-btn tag-btn" 
                            data-value="<?php echo esc_attr($tag->slug); ?>"
                            aria-pressed="false">
                        <?php echo esc_html($tag->name); ?>
                        <?php if ($tag->count > 0): ?>
                            <span class="yoco-filter-count">(<?php echo $tag->count; ?>)</span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="yoco-filter-actions">
            <button type="button" class="yoco-filter-reset" id="yoco-filter-reset">
                ✕ <?php _e('Reset alle filters', 'yoco-takeaway'); ?>
            </button>
        </div>
    </div>
    
    <!-- Menu Items Container -->
    <div id="yoco-items-container" class="yoco-items-container">
        
        <?php 
        // Loop through each category
        foreach ($all_categories as $category) :
            
            // Query items for this specific category, ordered alphabetically
            $category_args = array(
                'post_type' => 'yoco_food',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'yoco_food_cat',
                        'field' => 'term_id',
                        'terms' => $category->term_id,
                    ),
                ),
            );
            
            $category_items = new WP_Query($category_args);
            
            // Skip category if no items
            if (!$category_items->have_posts()) {
                continue;
            }
        ?>
        
        <div class="yoco-category-section" data-category="<?php echo esc_attr($category->slug); ?>">
            <h2 class="yoco-category-title"><?php echo esc_html($category->name); ?></h2>
            
            <?php if ($category->description): ?>
                <div class="yoco-category-description">
                    <?php echo wp_kses_post($category->description); ?>
                </div>
            <?php endif; ?>
            
            <div class="yoco-category-items">
                
                <?php while ($category_items->have_posts()) : $category_items->the_post(); 
                    $post_id = get_the_ID();
                    $meta = YoCo_Core::get_food_meta($post_id);
                    $thumbnail = get_the_post_thumbnail_url($post_id, 'medium');
                    $thumbnail_alt = get_the_post_thumbnail_caption($post_id) ?: get_the_title();
                    
                    $item_categories = wp_get_post_terms($post_id, 'yoco_food_cat', array('fields' => 'slugs'));
                    $item_tags = wp_get_post_terms($post_id, 'yoco_food_tag', array('fields' => 'slugs'));
                    
                    // Skip if no price set
                    if (empty($meta['price']) || $meta['price'] <= 0) {
                        continue;
                    }
                ?>
                
                <article class="yoco-item" 
                         data-food-id="<?php echo esc_attr($post_id); ?>" 
                         data-categories="<?php echo esc_attr(implode(',', $item_categories)); ?>" 
                         data-tags="<?php echo esc_attr(implode(',', $item_tags)); ?>"
                         itemscope 
                         itemtype="https://schema.org/FoodEstablishmentReservation">
                    
                    <!-- Product Image -->
                    <?php if ($thumbnail): ?>
                        <div class="yoco-image-wrapper">
                            <img src="<?php echo esc_url($thumbnail); ?>" 
                                 alt="<?php echo esc_attr($thumbnail_alt); ?>" 
                                 class="yoco-image"
                                 itemprop="image"
                                 loading="lazy">
                            
                            <?php if ($meta['is_menu'] == '1'): ?>
                                <span class="yoco-menu-badge" title="<?php esc_attr_e('Menu optie beschikbaar', 'yoco-takeaway'); ?>">
                                    <?php _e('Menu', 'yoco-takeaway'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Product Info -->
                    <div class="yoco-info">
                        <header class="yoco-item-header">
                            <h3 class="yoco-title" itemprop="name"><?php the_title(); ?></h3>
                        </header>
                        
                        <?php if (get_the_excerpt()): ?>
                            <div class="yoco-description" itemprop="description">
                                <?php echo wp_kses_post(get_the_excerpt()); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Diet and Feature Icons -->
                        <div class="yoco-icons" role="list" aria-label="<?php esc_attr_e('Product kenmerken', 'yoco-takeaway'); ?>">
                            
                            <?php if ($meta['halal'] == '1'): ?>
                                <div class="yoco-icon-item" role="listitem" title="<?php esc_attr_e('Halal gecertificeerd', 'yoco-takeaway'); ?>">
                                    <?php if (!empty($icons['halal'])): ?>
                                        <img src="<?php echo esc_url($icons['halal']); ?>" 
                                             class="yoco-icon" 
                                             alt="<?php esc_attr_e('Halal', 'yoco-takeaway'); ?>">
                                    <?php else: ?>
                                        <span class="yoco-emoji-icon" aria-label="<?php esc_attr_e('Halal', 'yoco-takeaway'); ?>">🕌</span>
                                    <?php endif; ?>
                                    <span><?php _e('Halal', 'yoco-takeaway'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($meta['vegetarian'] == '1'): ?>
                                <div class="yoco-icon-item" role="listitem" title="<?php esc_attr_e('Geschikt voor vegetariërs', 'yoco-takeaway'); ?>">
                                    <?php if (!empty($icons['vegetarian'])): ?>
                                        <img src="<?php echo esc_url($icons['vegetarian']); ?>" 
                                             class="yoco-icon" 
                                             alt="<?php esc_attr_e('Vegetarisch', 'yoco-takeaway'); ?>">
                                    <?php else: ?>
                                        <span class="yoco-emoji-icon" aria-label="<?php esc_attr_e('Vegetarisch', 'yoco-takeaway'); ?>">🥕</span>
                                    <?php endif; ?>
                                    <span><?php _e('Vegetarisch', 'yoco-takeaway'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($meta['vegan'] == '1'): ?>
                                <div class="yoco-icon-item" role="listitem" title="<?php esc_attr_e('100% plantaardig', 'yoco-takeaway'); ?>">
                                    <?php if (!empty($icons['vegan'])): ?>
                                        <img src="<?php echo esc_url($icons['vegan']); ?>" 
                                             class="yoco-icon" 
                                             alt="<?php esc_attr_e('Vegan', 'yoco-takeaway'); ?>">
                                    <?php else: ?>
                                        <span class="yoco-emoji-icon" aria-label="<?php esc_attr_e('Vegan', 'yoco-takeaway'); ?>">🌱</span>
                                    <?php endif; ?>
                                    <span><?php _e('Vegan', 'yoco-takeaway'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($meta['spicy'] > 0): ?>
                                <div class="yoco-icon-item" role="listitem" title="<?php echo esc_attr(sprintf(__('Pittigheid: %s', 'yoco-takeaway'), $spicy_labels[$meta['spicy']])); ?>">
                                    <?php 
                                    $spicy_level = intval($meta['spicy']);
                                    for ($i = 0; $i < $spicy_level; $i++) {
                                        if (!empty($icons['spicy'])) {
                                            echo '<img src="' . esc_url($icons['spicy']) . '" class="yoco-icon yoco-spicy-icon" alt="' . esc_attr__('Pittig', 'yoco-takeaway') . '">';
                                        } else {
                                            echo '<span class="yoco-emoji-icon" aria-label="' . esc_attr__('Pittig', 'yoco-takeaway') . '">🌶️</span>';
                                        }
                                    }
                                    ?>
                                    <span><?php echo esc_html($spicy_labels[$meta['spicy']]); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Allergen Information -->
                        <?php if (!empty($meta['allergens']) && is_array($meta['allergens'])): ?>
                            <div class="yoco-allergen-info">
                                <button type="button" 
                                        class="yoco-allergens-btn" 
                                        data-food-id="<?php echo esc_attr($post_id); ?>"
                                        aria-label="<?php esc_attr_e('Bekijk allergenen informatie', 'yoco-takeaway'); ?>">
                                    ⚠️ <?php _e('Zie allergenen', 'yoco-takeaway'); ?>
                                    <span class="yoco-allergen-count">(<?php echo count($meta['allergens']); ?>)</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Tags -->
                        <?php 
                        $tags_list = wp_get_post_terms($post_id, 'yoco_food_tag');
                        if (!empty($tags_list) && !is_wp_error($tags_list)): 
                        ?>
                            <div class="yoco-tags">
                                <?php foreach ($tags_list as $tag): ?>
                                    <span class="yoco-tag">#<?php echo esc_html($tag->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Price and Order Section -->
                    <div class="yoco-price-order">
                        <div class="yoco-price-info">
                            <div class="yoco-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                                <span class="yoco-currency">€</span>
                                <span class="yoco-amount" itemprop="price"><?php echo number_format($meta['price'], 2, ',', '.'); ?></span>
                                <meta itemprop="priceCurrency" content="EUR">
                            </div>
                            
                            <?php if ($meta['is_menu'] == '1'): ?>
                                <div class="yoco-menu-note">
                                    <small><?php _e('Menu mogelijk', 'yoco-takeaway'); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Quantity Selector -->
                        <div class="yoco-quantity" role="group" aria-label="<?php esc_attr_e('Aantal selecteren', 'yoco-takeaway'); ?>">
                            <button class="yoco-qty-btn yoco-qty-minus" 
                                    type="button" 
                                    aria-label="<?php esc_attr_e('Aantal verminderen', 'yoco-takeaway'); ?>">-</button>
                            <input type="number" 
                                   class="yoco-qty-input" 
                                   value="1" 
                                   min="1" 
                                   max="99" 
                                   readonly
                                   aria-label="<?php esc_attr_e('Aantal', 'yoco-takeaway'); ?>">
                            <button class="yoco-qty-btn yoco-qty-plus" 
                                    type="button" 
                                    aria-label="<?php esc_attr_e('Aantal verhogen', 'yoco-takeaway'); ?>">+</button>
                        </div>
                        
                        <!-- Order Button -->
                        <button class="yoco-order-btn" 
                                type="button"
                                aria-describedby="yoco-order-desc-<?php echo $post_id; ?>">
                            <span class="yoco-order-text"><?php echo esc_html($button_text); ?></span>
                            <span class="yoco-order-loading" style="display: none;">
                                <span class="yoco-spinner"></span>
                                <?php _e('Laden...', 'yoco-takeaway'); ?>
                            </span>
                        </button>
                        
                        <div id="yoco-order-desc-<?php echo $post_id; ?>" class="screen-reader-text">
                            <?php printf(__('Voeg %s toe aan winkelwagen', 'yoco-takeaway'), get_the_title()); ?>
                        </div>
                    </div>
                </article>
                
                <?php endwhile; ?>
                
            </div> <!-- .yoco-category-items -->
        </div> <!-- .yoco-category-section -->
        
        <?php 
            wp_reset_postdata();
        endforeach; // End categories loop
        ?>
    </div>
    
    <!-- No Results Message Template (hidden by default) -->
    <div class="yoco-no-results" style="display: none;">
        <div class="yoco-no-results-content">
            <h3><?php _e('Geen resultaten gevonden', 'yoco-takeaway'); ?></h3>
            <p><?php _e('Geen producten gevonden die voldoen aan je zoekcriteria. Probeer andere filters of zoektermen.', 'yoco-takeaway'); ?></p>
            <button type="button" class="yoco-clear-search"><?php _e('Wis zoekopdracht', 'yoco-takeaway'); ?></button>
        </div>
    </div>
</div>

<!-- JavaScript Data for Frontend -->
<script type="text/javascript">
window.yocoAllergens = {
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
                echo "'" . $post_id . "': " . json_encode($allergen_names, JSON_UNESCAPED_UNICODE) . ",\n";
            }
        }
    endwhile; 
    ?>
};

// Configuration for frontend
window.yocoConfig = {
    currency: '<?php echo get_woocommerce_currency_symbol(); ?>',
    buttonText: <?php echo json_encode($button_text, JSON_UNESCAPED_UNICODE); ?>,
    i18n: {
        noResults: <?php echo json_encode(__('Geen resultaten gevonden', 'yoco-takeaway'), JSON_UNESCAPED_UNICODE); ?>,
        loading: <?php echo json_encode(__('Laden...', 'yoco-takeaway'), JSON_UNESCAPED_UNICODE); ?>,
        error: <?php echo json_encode(__('Er is een fout opgetreden', 'yoco-takeaway'), JSON_UNESCAPED_UNICODE); ?>,
        addedToCart: <?php echo json_encode(__('Toegevoegd aan winkelwagen', 'yoco-takeaway'), JSON_UNESCAPED_UNICODE); ?>
    }
};
</script>

<?php 
wp_reset_postdata();

// Preload critical resources
?>
<link rel="preload" as="style" href="<?php echo YOCO_PLUGIN_URL; ?>assets/css/yoco-frontend.css">
<link rel="preload" as="script" href="<?php echo YOCO_PLUGIN_URL; ?>assets/js/yoco-frontend.js">