<?php

if (!defined('PHP74_82_COMPAT_LOADED')) {
    define('PHP74_82_COMPAT_LOADED', true);

    $php74_82_bareword_keys = require __DIR__ . '/php74_82_bareword_keys.php';

    foreach ($php74_82_bareword_keys as $php74_82_bareword_key) {
        if (!defined($php74_82_bareword_key)) {
            define($php74_82_bareword_key, $php74_82_bareword_key);
        }
    }

    unset($php74_82_bareword_key, $php74_82_bareword_keys);

    if (!function_exists('php74_82_posix_pattern')) {
        function php74_82_posix_pattern($pattern, $flags = '')
        {
            return '~' . str_replace('~', '\~', $pattern) . '~' . $flags;
        }
    }

    if (!function_exists('ereg')) {
        function ereg($pattern, $string, &$regs = null)
        {
            $result = preg_match(php74_82_posix_pattern($pattern), $string, $matches);
            if ($result && func_num_args() >= 3) {
                $regs = $matches;
            }
            return $result;
        }
    }

    if (!function_exists('eregi')) {
        function eregi($pattern, $string, &$regs = null)
        {
            $result = preg_match(php74_82_posix_pattern($pattern, 'i'), $string, $matches);
            if ($result && func_num_args() >= 3) {
                $regs = $matches;
            }
            return $result;
        }
    }

    if (!function_exists('ereg_replace')) {
        function ereg_replace($pattern, $replacement, $string)
        {
            return preg_replace(php74_82_posix_pattern($pattern), $replacement, $string);
        }
    }

    if (!function_exists('eregi_replace')) {
        function eregi_replace($pattern, $replacement, $string)
        {
            return preg_replace(php74_82_posix_pattern($pattern, 'i'), $replacement, $string);
        }
    }

    if (!function_exists('get_magic_quotes_runtime')) {
        function get_magic_quotes_runtime() { return false; }
    }

    if (!function_exists('get_magic_quotes_gpc')) {
        function get_magic_quotes_gpc() { return false; }
    }
}
