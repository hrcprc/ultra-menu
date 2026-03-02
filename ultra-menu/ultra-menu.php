<?php
/**
 * Plugin Name: Ultra Menu
 * Description: Adds mega menu capabilities to WordPress nav menus.
 * Version: 1.0.0
 * Author: Ultra Menu
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Ultra_Menu_Plugin')) {
    class Ultra_Menu_Plugin {
        public function __construct() {
            add_action('wp_nav_menu_item_custom_fields', [$this, 'render_custom_fields'], 10, 5);
            add_action('wp_update_nav_menu_item', [$this, 'save_custom_fields'], 10, 3);

            add_filter('nav_menu_css_class', [$this, 'add_mega_class'], 10, 4);
            add_filter('walker_nav_menu_start_el', [$this, 'inject_submenu_meta_markup'], 10, 4);

            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        }

        public function render_custom_fields($item_id, $item, $depth, $args, $current_object_id) {
            $is_mega = (int) get_post_meta($item_id, '_ultra_is_mega', true);
            $image_id = (int) get_post_meta($item_id, '_ultra_menu_image_id', true);
            $desc = get_post_meta($item_id, '_ultra_menu_desc', true);

            if ((int) $depth === 0) {
                ?>
                <p class="description description-wide ultra-menu-field ultra-menu-field-mega">
                    <label for="edit-menu-item-ultra-is-mega-<?php echo esc_attr($item_id); ?>">
                        <input
                            type="checkbox"
                            id="edit-menu-item-ultra-is-mega-<?php echo esc_attr($item_id); ?>"
                            class="widefat code edit-menu-item-ultra-is-mega"
                            name="menu-item-ultra-is-mega[<?php echo esc_attr($item_id); ?>]"
                            value="1"
                            <?php checked($is_mega, 1); ?>
                        />
                        <?php esc_html_e('Enable Mega Menu for this item', 'ultra-menu'); ?>
                    </label>
                </p>
                <?php
                return;
            }

            $image_src = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
            ?>
            <div class="ultra-menu-field ultra-menu-image-field description description-wide">
                <label><?php esc_html_e('Image', 'ultra-menu'); ?></label>
                <input
                    type="hidden"
                    class="edit-menu-item-ultra-menu-image-id"
                    name="menu-item-ultra-menu-image-id[<?php echo esc_attr($item_id); ?>]"
                    value="<?php echo esc_attr($image_id); ?>"
                />
                <div class="ultra-menu-image-preview" style="margin:8px 0;">
                    <?php if ($image_src) : ?>
                        <img src="<?php echo esc_url($image_src); ?>" alt="" style="max-width:120px;height:auto;display:block;" />
                    <?php endif; ?>
                </div>
                <button type="button" class="button ultra-menu-select-image"><?php esc_html_e('Select image', 'ultra-menu'); ?></button>
                <button type="button" class="button ultra-menu-remove-image"><?php esc_html_e('Remove image', 'ultra-menu'); ?></button>
            </div>

            <p class="description description-wide ultra-menu-field ultra-menu-desc-field">
                <label for="edit-menu-item-ultra-menu-desc-<?php echo esc_attr($item_id); ?>">
                    <?php esc_html_e('Description', 'ultra-menu'); ?><br />
                    <textarea
                        id="edit-menu-item-ultra-menu-desc-<?php echo esc_attr($item_id); ?>"
                        class="widefat edit-menu-item-ultra-menu-desc"
                        rows="3"
                        cols="20"
                        name="menu-item-ultra-menu-desc[<?php echo esc_attr($item_id); ?>]"
                    ><?php echo esc_textarea($desc); ?></textarea>
                </label>
            </p>
            <?php
        }

        public function save_custom_fields($menu_id, $menu_item_db_id, $args) {
            $is_mega = isset($_POST['menu-item-ultra-is-mega'][$menu_item_db_id]) ? 1 : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
            update_post_meta($menu_item_db_id, '_ultra_is_mega', $is_mega);

            if (isset($_POST['menu-item-ultra-menu-image-id'][$menu_item_db_id])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $image_id = absint($_POST['menu-item-ultra-menu-image-id'][$menu_item_db_id]); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                if ($image_id) {
                    update_post_meta($menu_item_db_id, '_ultra_menu_image_id', $image_id);
                } else {
                    delete_post_meta($menu_item_db_id, '_ultra_menu_image_id');
                }
            }

            if (isset($_POST['menu-item-ultra-menu-desc'][$menu_item_db_id])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $desc = sanitize_textarea_field(wp_unslash($_POST['menu-item-ultra-menu-desc'][$menu_item_db_id])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                if ($desc !== '') {
                    update_post_meta($menu_item_db_id, '_ultra_menu_desc', $desc);
                } else {
                    delete_post_meta($menu_item_db_id, '_ultra_menu_desc');
                }
            }
        }

        public function add_mega_class($classes, $item, $args, $depth) {
            if ((int) $depth === 0 && (int) get_post_meta($item->ID, '_ultra_is_mega', true) === 1) {
                $classes[] = 'is-mega';
            }

            return $classes;
        }

        public function inject_submenu_meta_markup($item_output, $item, $depth, $args) {
            if ((int) $depth < 1) {
                return $item_output;
            }

            $image_id = (int) get_post_meta($item->ID, '_ultra_menu_image_id', true);
            $desc = get_post_meta($item->ID, '_ultra_menu_desc', true);

            if (!$image_id && $desc === '') {
                return $item_output;
            }

            $extra_markup = '<span class="ultra-menu-item-extra">';

            if ($image_id) {
                $attachment_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                $alt = $attachment_alt !== '' ? $attachment_alt : $item->title;
                $image = wp_get_attachment_image(
                    $image_id,
                    'thumbnail',
                    false,
                    [
                        'class' => 'ultra-menu-item-thumb',
                        'alt'   => $alt,
                    ]
                );

                if ($image) {
                    $extra_markup .= '<span class="ultra-menu-item-image">' . $image . '</span>';
                }
            }

            if ($desc !== '') {
                $extra_markup .= '<span class="ultra-menu-item-desc">' . esc_html($desc) . '</span>';
            }

            $extra_markup .= '</span>';

            if (strpos($item_output, '</a>') !== false) {
                return preg_replace('/<\/a>/', $extra_markup . '</a>', $item_output, 1);
            }

            return $item_output . $extra_markup;
        }

        public function enqueue_admin_assets($hook_suffix) {
            if ($hook_suffix !== 'nav-menus.php') {
                return;
            }

            wp_enqueue_media();
            wp_enqueue_script(
                'ultra-menu-admin',
                plugin_dir_url(__FILE__) . 'assets/ultra-menu-admin.js',
                ['jquery'],
                '1.0.0',
                true
            );
        }

        public function enqueue_frontend_assets() {
            wp_enqueue_style(
                'ultra-menu-style',
                plugin_dir_url(__FILE__) . 'assets/ultra-menu.css',
                [],
                '1.0.0'
            );
        }
    }
}

new Ultra_Menu_Plugin();

if (!function_exists('ultra_menu')) {
    function ultra_menu(array $args = []) {
        $defaults = [
            'theme_location' => 'primary',
            'menu_class'     => 'ultra-menu',
            'container'      => false,
        ];

        wp_nav_menu(wp_parse_args($args, $defaults));
    }
}
