<?php
/**
 * Plugin Name: Speexaax Mega Menu (Avada-free)
 * Description: Adds a simple mega menu option to WordPress menus and provides a walker + CSS to render it without Avada.
 * Version: 1.1.0
 * Author: Speexx
 */

if (!defined('ABSPATH')) exit;

add_filter('nav_menu_item_title', function ($title, $item, $args, $depth) {
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
        if (!apply_filters('speexx_mega_menu_register_location', true)) {
            return;
        }

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

        if ((int) $depth !== 0) return $classes;

        if (get_post_meta($item->ID, self::META_KEY, true) === '1') {
            $classes[] = 'is-mega';
        }

        return $classes;
    }

    public static function enqueue_assets(): void {
        if (!apply_filters('speexx_mega_menu_enqueue_css', true)) {
            return;
        }

        wp_enqueue_style(
            'speexx-mega-menu',
            plugins_url('assets/speexx-mega-menu.css', __FILE__),
            [],
            '1.1.0'
        );

        wp_enqueue_script(
            'speexx-mega-menu',
            plugins_url('assets/speexx-mega-menu.js', __FILE__),
            [],
            '1.1.0',
            true
        );
    }

    public static function default_walker($walker) {
        return $walker ?: new SPEEXX_Mega_Menu_Walker();
    }

    public static function render(array $args = []): void {
        $defaults = [
            'theme_location' => 'primary',
            'container'      => 'nav',
            'container_class'=> 'speexx-primary-nav',
            'menu_class'     => 'speexx-primary-menu',
            'fallback_cb'    => false,
            'walker'         => apply_filters('speexx_mega_menu_walker', null),
        ];

        if (!isset($args['items_wrap'])) {
            $custom_logo_id = get_theme_mod('custom_logo');
            $logo = $custom_logo_id ? wp_get_attachment_image($custom_logo_id, 'full', false, ['class' => 'speexx-site-logo']) : '';

            if (!$logo) {
                $logo = '<span class="speexx-site-name">' . esc_html(get_bloginfo('name')) . '</span>';
            }

            $home_url = esc_url(home_url('/'));
            $logo_item = '<li class="menu-item speexx-site-brand"><a class="menu-link" href="' . $home_url . '">' . $logo . '</a></li>';

            $defaults['items_wrap'] = '<ul id="%1$s" class="%2$s">' . $logo_item . '%3$s</ul>';
        }

        wp_nav_menu(array_merge($defaults, $args));
    }
}

class SPEEXX_Mega_Menu_Walker extends Walker_Nav_Menu {

    private array $mega_branch_stack = [];

    public function start_lvl(&$output, $depth = 0, $args = null): void {
        $indent = str_repeat("\t", $depth);
        $is_mega_branch = !empty($this->mega_branch_stack[$depth]);

        if ($depth === 0 && $is_mega_branch) {
            $output .= "\n$indent<div class=\"sub-menu speexx-mega-sub-menu\">\n";
            $output .= "$indent\t<div class=\"speexx-mega-panel__inner\">\n";
            $output .= "$indent\t\t<ul class=\"speexx-mega-products\">\n";
            return;
        }

        $output .= "\n$indent<ul class=\"sub-menu\">\n";
    }

    public function end_lvl(&$output, $depth = 0, $args = null): void {
        $indent = str_repeat("\t", $depth);
        $is_mega_branch = !empty($this->mega_branch_stack[$depth]);

        if ($depth === 0 && $is_mega_branch) {
            $output .= "$indent\t\t</ul>\n";
            $output .= "$indent\t\t<div class=\"speexx-mega-description\" aria-live=\"polite\"></div>\n";
            $output .= "$indent\t</div>\n";
            $output .= "$indent</div>\n";
            return;
        }

        $output .= "$indent</ul>\n";
    }

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0): void {
        $indent = $depth ? str_repeat("\t", $depth) : '';

        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes = apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth);

        $is_mega_item = $depth === 0 && in_array('is-mega', $classes, true);
        if ($depth === 0) {
            $this->mega_branch_stack[0] = $is_mega_item;
        }

        $class_names = $classes ? ' class="' . esc_attr(implode(' ', $classes)) . '"' : '';

        $output .= $indent . '<li' . $class_names . '>';

        $atts = [
            'href'  => !empty($item->url) ? $item->url : '',
            'class' => 'menu-link',
        ];

        if ($depth === 1 && !empty($this->mega_branch_stack[0])) {
            $atts['data-mega-description'] = wp_strip_all_tags((string) $item->description);
        }

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

        if ($depth === 0) {
            unset($this->mega_branch_stack[0]);
        }
    }
}

function speexx_mega_menu(array $args = []): void {
    SPEEXX_Mega_Menu_Plugin::render($args);
}

SPEEXX_Mega_Menu_Plugin::init();
