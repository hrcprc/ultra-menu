<?php
/**
 * Plugin Name: Speexx Mega Menu (Avada-free)
 * Description: Adds a simple mega menu option to WordPress menus and provides a walker + CSS to render it without Avada.
 * Version: 1.0.0
 * Author: Speexx
 */

if (!defined('ABSPATH')) exit;
add_filter('nav_menu_item_title', function ($title, $item, $args, $depth) {
    // Remove Astra dropdown toggle markup if present in title string
    $title = preg_replace('/<span[^>]*class="[^"]*dropdown-menu-toggle[^"]*"[^>]*>.*?<\/span>/i', '', $title);
    $title = preg_replace('/&lt;span[^&]*dropdown-menu-toggle.*?&gt;.*?&lt;\/span&gt;/i', '', $title);

    return $title;
}, 999, 4);
final class SPEEXX_Mega_Menu_Plugin {
    const META_KEY = '_speexx_is_mega';

    public static function init(): void {
        add_action('wp_nav_menu_item_custom_fields', [__CLASS__, 'add_menu_item_field'], 10, 4);
        add_action('wp_update_nav_menu_item', [__CLASS__, 'save_menu_item_field'], 10, 2);

        add_filter('nav_menu_css_class', [__CLASS__, 'add_mega_class'], 10, 4);

        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('after_setup_theme', [__CLASS__, 'register_menu_location']);

        add_filter('speexx_mega_menu_walker', [__CLASS__, 'default_walker'], 10, 1);
    }

    public static function register_menu_location(): void {
        $enabled = apply_filters('speexx_mega_menu_register_location', true);
        if (!$enabled) return;

        register_nav_menus([
            'primary' => __('Primary Menu', 'speexx-mega-menu'),
        ]);
    }

    public static function add_menu_item_field(int $item_id, $item, int $depth, $args): void {
        if ($depth !== 0) return;

        $value = get_post_meta($item_id, self::META_KEY, true);
        ?>
        <p class="description description-wide">
            <label>
                <input type="checkbox"
                       name="menu-item-speexx-is-mega[<?php echo esc_attr($item_id); ?>]"
                       value="1"
                    <?php checked($value, '1'); ?> />
                <?php esc_html_e('Enable Mega Menu for this item', 'speexx-mega-menu'); ?>
            </label>
        </p>
        <?php
    }

    public static function save_menu_item_field(int $menu_id, int $menu_item_db_id): void {
        $is_mega = isset($_POST['menu-item-speexx-is-mega'][$menu_item_db_id]) ? '1' : '';

        if ($is_mega === '1') {
            update_post_meta($menu_item_db_id, self::META_KEY, '1');
        } else {
            delete_post_meta($menu_item_db_id, self::META_KEY);
        }
    }

    public static function add_mega_class(array $classes, $item, $args, $depth): array {
        $limit_location = apply_filters('speexx_mega_menu_limit_location', 'primary');

        if (!empty($limit_location) && (($args->theme_location ?? '') !== $limit_location)) {
            return $classes;
        }

        if ((int)$depth !== 0) return $classes;

        $is_mega = get_post_meta($item->ID, self::META_KEY, true);
        if ($is_mega === '1') {
            $classes[] = 'is-mega';
        }

        return $classes;
    }

    public static function enqueue_assets(): void {
        $enabled = apply_filters('speexx_mega_menu_enqueue_css', true);
        if (!$enabled) return;

        wp_register_style(
            'speexx-mega-menu',
            plugins_url('assets/speexx-mega-menu.css', __FILE__),
            [],
            '1.0.0'
        );

        wp_enqueue_style('speexx-mega-menu');
    }

    public static function default_walker($walker) {
        if ($walker) return $walker;
        return new SPEEXX_Mega_Menu_Walker();
    }

    public static function render(array $args = []): void {
        $defaults = [
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'speexx-primary-menu',
            'fallback_cb'    => false,
            'walker'         => apply_filters('speexx_mega_menu_walker', null),
        ];

        $args = array_merge($defaults, $args);
        wp_nav_menu($args);
    }
}

class SPEEXX_Mega_Menu_Walker extends Walker_Nav_Menu {

    public function start_lvl(&$output, $depth = 0, $args = null): void {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul class=\"sub-menu\">\n";
    }

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0): void {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';

        // IMPORTANT: apply WP core filters so nav_menu_css_class works
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes = apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth);

        $class_names = $classes ? ' class="' . esc_attr(implode(' ', $classes)) . '"' : '';

        $output .= $indent . '<li' . $class_names . '>';

        $atts = [];
        $atts['href']  = !empty($item->url) ? $item->url : '';
        $atts['class'] = 'menu-link';

        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if ($value !== '' && $value !== null) {
                $attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
            }
        }

        $title = apply_filters('the_title', $item->title, $item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

        $output .= '<a' . $attributes . '>' . esc_html($title) . '</a>';
    }
    public function end_el(&$output, $item, $depth = 0, $args = null): void {
        $output .= "</li>\n";
    }
}

/**
 * Template helper
 */
function speexx_mega_menu(array $args = []): void {
    SPEEXX_Mega_Menu_Plugin::render($args);
}

SPEEXX_Mega_Menu_Plugin::init();