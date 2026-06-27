<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

$GLOBALS['test_actions'] = [];
$GLOBALS['test_routes']  = [];
$GLOBALS['test_options'] = [];
$GLOBALS['test_errors']  = [];

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
        $GLOBALS['test_actions'][] = ['hook' => $hook, 'callback' => $callback];
        return true;
    }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url = '', $position = null) {
        $GLOBALS['test_menu_pages'][] = [
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
        return $GLOBALS['test_options'][$key] ?? $default;
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
        return (string) $value;
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
        $GLOBALS['test_options'][$key] = $value;
        $GLOBALS['test_updated_options'][$key] = $value;
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
        $GLOBALS['test_routes'][] = [
            'namespace' => $namespace,
            'route'     => $route,
            'args'      => $args,
        ];
        return true;
    }
}

function esc_html($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

