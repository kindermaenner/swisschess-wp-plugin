    <?php

    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    }

    if (!defined('OBJECT')) {
        define('OBJECT', 'OBJECT');
    }

    $GLOBALS['wp_actions'] = [];
    $GLOBALS['wp_routes']  = [];
    $GLOBALS['wp_options'] = [];
    $GLOBALS['wp_errors']  = [];

    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($text) {
            return (string)$text; // minimal mock: keine Veränderung
        }
    }

    if (!function_exists('sanitize_title')) {
        function sanitize_title($title) {
            return strtolower((string)$title);
        }
    }

    function maybe_unserialize($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Schneller Check: ist es überhaupt serialisiert?
        // WordPress nutzt wp_is_serialized(), wir bauen eine Light-Version:
        if (!preg_match('/^[aOsib]:/', $value)) {
            return $value;
        }

        // Jetzt ist es sicher, dass es serialisiert ist → kein Warning
        $result = @unserialize($value);

        return ($result !== false || $value === 'b:0;') ? $result : $value;
    }

    if (!function_exists('add_action')) {
        function add_action($hook, $callback) {
            $GLOBALS['wp_actions'][] = ['hook' => $hook, 'callback' => $callback];
            return true;
        }
    }

    if (!function_exists('add_menu_page')) {
        function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url = '', $position = null) {
            $GLOBALS['wp_menu_pages'][] = [
                'page_title' => $page_title,
                'menu_title' => $menu_title,
                'capability' => $capability,
                'menu_slug' => $menu_slug,
                'callback' => $callback,
                'icon_url' => $icon_url,
                'position' => $position,
            ];

            return $menu_slug;
        }
    }

    if (!function_exists('get_option')) {
        function get_option($key, $default = false) {
            return $GLOBALS['wp_options'][$key] ?? $default;
        }
    }

    if (!function_exists('wp_upload_dir')) {
        function wp_upload_dir() {
            return $GLOBALS['wp_upload_dir'] ?? [
                'basedir' => '/tmp/uploads',
            ];
        }
    }

    if (!function_exists('get_users')) {
        function get_users($args = []) {
            return [
                (object) ['ID' => 1, 'user_login' => 'admin'],
                (object) ['ID' => 2, 'user_login' => 'editor'],
            ];
        }
    }

    if (!function_exists('wp_nonce_field')) {
        function wp_nonce_field($action = -1, $name = '_wpnonce') {
            echo '<input type="hidden" name="' . $name . '" value="nonce" />';
        }
    }

    if (!function_exists('esc_attr')) {
        function esc_attr($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('settings_fields')) {
        function settings_fields($option_group) {
            echo '<input type="hidden" name="option_page" value="' . $option_group . '" />';
        }
    }

    if (!function_exists('selected')) {
        function selected($selected, $current = true, $display = true) {
            $result = ((string)$selected === (string)$current) ? 'selected="selected"' : '';
            if ($display) {
                echo $result;
            }
            return $result;
        }
    }

    if (!function_exists('checked')) {
        function checked($checked, $current = true, $echo = true) {
            $result = ($checked == $current) ? 'checked="checked"' : '';

            if ($echo) {
                echo $result;
            }

            return $result;
        }
    }

    if (!function_exists('submit_button')) {
        function submit_button($text = 'Save Changes') {
            echo '<button type="submit">' . $text . '</button>';
        }
    }

    if (!function_exists('check_admin_referer')) {
        function check_admin_referer($action = -1, $query_arg = '_wpnonce') {
            return true;
        }
    }

    if (!function_exists('wp_generate_password')) {
        function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
            return str_repeat('x', $length);
        }
    }

    if (!function_exists('update_option')) {
        function update_option($key, $value) {
            $GLOBALS['wp_options'][$key] = $value;
            $GLOBALS['wp_updated_options'][$key] = $value;
            return true;
        }
    }

    if (!class_exists('WP_Error')) {
        class WP_Error {
            private string $code;
            private string $message;
            private array $data;

            public function __construct($code, $message, $data = []) {
                $this->code    = $code;
                $this->message = $message;
                $this->data    = $data;
            }

            public function get_error_code() {
                return $this->code;
            }

            public function get_error_message() {
                return $this->message;
            }

            public function get_error_data() {
                return $this->data;
            }
        }
    }

    if (!function_exists('register_rest_route')) {
        function register_rest_route($namespace, $route, $args) {
            $GLOBALS['wp_routes'][] = [
                'namespace' => $namespace,
                'route'     => $route,
                'args'      => $args,
            ];
            return true;
        }
    }

    function esc_html($text)
    {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }

    function get_page_by_title($title)
    {
        foreach ($GLOBALS['wp_pages'] as $p) {
            if ($p['post_title'] === $title) {
                return (object)$p;
            }
        }
        return null;
    }

    function get_posts($args)
    {
        $key   = $args['meta_key']   ?? '';
        $value = $args['meta_value'] ?? '';

        $result = [];

        foreach ($GLOBALS['wp_pages'] as $p) {
            $id = $p['ID'];

            if (isset($GLOBALS['wp_meta'][$id][$key])) {
                // WP: meta_value kann mehrere Werte haben
                if (in_array($value, $GLOBALS['wp_meta'][$id][$key], true)) {
                    $result[] = (object)$p;
                }
            }
        }

        return $result;
    }

    function wp_update_post($data)
    {
        $GLOBALS['wp_updated'][] = $data;
    }

    function wp_insert_post($data)
    {
        $newId = rand(200, 999);
        $data['ID'] = $newId;

        $GLOBALS['wp_inserted'][] = $data;

        // Seite speichern
        $GLOBALS['wp_pages'][] = [
            'ID'             => $newId,
            'post_title'     => $data['post_title'],
            'post_name'      => $data['post_name'],
            'post_content'   => $data['post_content'],
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'post_excerpt'   => '',
            'post_parent'    => 0,
            'menu_order'     => 0,
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_date'      => '',
            'post_date_gmt'  => '',
            'post_modified'      => '',
            'post_modified_gmt'  => '',
            // WP-Alias
            'title'          => $data['post_title'],
            'meta'           => [],
        ];

        return $newId;
    }

    function get_post_meta($post_id, $key = '', $single = false)
    {
        // WP-intern: wenn Key angegeben
        if ($key !== '') {
            if (!isset($GLOBALS['wp_meta'][$post_id][$key])) {
                return $single ? '' : [];
            }
            return $single
                ? $GLOBALS['wp_meta'][$post_id][$key][0]
                : $GLOBALS['wp_meta'][$post_id][$key];
        }

        // WP-intern: alle Meta-Daten
        return $GLOBALS['wp_meta'][$post_id] ?? [];
    }

    function update_post_meta($post_id, $key, $value)
    {
        if (!isset($GLOBALS['wp_meta'][$post_id])) {
            $GLOBALS['wp_meta'][$post_id] = [];
        }

        if (!isset($GLOBALS['wp_meta'][$post_id][$key])) {
            $GLOBALS['wp_meta'][$post_id][$key] = [];
        }

        $GLOBALS['wp_meta'][$post_id][$key][] = $value;
    }

    if (!function_exists('wp_get_nav_menus')) {
        function wp_get_nav_menus() {
            return $GLOBALS['wp_nav_menus'] ?? [];
        }
    }

    if (!function_exists('wp_get_nav_menu_items')) {
        function wp_get_nav_menu_items($menu, $args = []) {
            $menuId = is_object($menu) ? ($menu->term_id ?? 0) : (int)$menu;
            return $GLOBALS['wp_nav_menu_items'][$menuId] ?? [];
        }
    }

    if (!function_exists('wp_delete_post')) {
        function wp_delete_post($post_id, $force_delete = false) {
            $GLOBALS['wp_deleted_posts'][] = [
                'post_id' => (int)$post_id,
                'force_delete' => (bool)$force_delete,
            ];
            return true;
        }
    }

    if (!function_exists('wp_get_post_terms')) {
        function wp_get_post_terms($post_id, $taxonomy, $args = []) {
            return $GLOBALS['wp_terms_by_post'][$post_id][$taxonomy] ?? [];
        }
    }

    if (!function_exists('wp_set_post_terms')) {
        function wp_set_post_terms($post_id, $terms, $taxonomy = 'category', $append = false) {
            $terms = array_values(array_map('intval', (array)$terms));

            if (!isset($GLOBALS['wp_set_terms'][$post_id])) {
                $GLOBALS['wp_set_terms'][$post_id] = [];
            }

            if ($append && isset($GLOBALS['wp_set_terms'][$post_id][$taxonomy])) {
                $existing = $GLOBALS['wp_set_terms'][$post_id][$taxonomy];
                $GLOBALS['wp_set_terms'][$post_id][$taxonomy] = array_values(array_unique(array_merge($existing, $terms)));
            } else {
                $GLOBALS['wp_set_terms'][$post_id][$taxonomy] = $terms;
            }

            return $GLOBALS['wp_set_terms'][$post_id][$taxonomy];
        }
    }