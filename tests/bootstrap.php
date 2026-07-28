<?php
// ABOUTME: Test bootstrap for the WooCommerce Sample plugin — stubs the minimal
// ABOUTME: WordPress surface needed to load the plugin file outside WordPress.

define('ABSPATH', dirname(__DIR__) . '/');
define('WOOCOMMERCE_VERSION', '10.0.0');

$GLOBALS['ws_registered_hooks'] = [];

function get_option($name, $default = false) {
    if ($name === 'active_plugins') {
        return ['woocommerce/woocommerce.php'];
    }
    return $default;
}

function apply_filters($tag, $value) {
    return $value;
}

function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['ws_registered_hooks'][] = [
        'tag' => $tag,
        'callback' => $callback,
        'priority' => $priority,
    ];
    return true;
}

function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
    return add_filter($tag, $callback, $priority, $accepted_args);
}

function plugin_basename($file) {
    return basename(dirname($file)) . '/' . basename($file);
}

function ws_assert_same($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        throw new Exception(
            ($message !== '' ? $message . ' — ' : '')
            . 'expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true)
        );
    }
}

require dirname(__DIR__) . '/woocommerce-sample.php';