<?php
if (!defined('MBX_BOOTSTRAP_LOADED')) {
    define('MBX_BOOTSTRAP_LOADED', true);

    function mbx_plugin_dir()
    {
        return dirname(__DIR__);
    }

    function mbx_admin_dir()
    {
        static $adminDir = null;
        if ($adminDir !== null) {
            return $adminDir;
        }

        $pluginDir = mbx_plugin_dir();
        $candidates = array(
            dirname($pluginDir),
            dirname($pluginDir) . '/admin',
        );
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $candidates[] = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') . '/admin';
        }

        foreach ($candidates as $candidate) {
            $candidate = str_replace('\\', '/', $candidate);
            if (file_exists($candidate . '/include/inc_base.php') && file_exists($candidate . '/include/header.php')) {
                return $adminDir = $candidate;
            }
        }

        throw new RuntimeException('ERP admin 디렉터리를 찾을 수 없습니다.');
    }

    function mbx_admin_path($relative)
    {
        return mbx_admin_dir() . '/' . ltrim(str_replace('\\', '/', $relative), '/');
    }

    function mbx_require_admin_file($relative)
    {
        $path = mbx_admin_path($relative);
        if (!file_exists($path)) {
            throw new RuntimeException('필수 ERP 파일을 찾을 수 없습니다: ' . $relative);
        }
        require_once $path;
    }

    function mbx_include_admin_file($relative)
    {
        $path = mbx_admin_path($relative);
        if (!file_exists($path)) {
            throw new RuntimeException('필수 ERP 파일을 찾을 수 없습니다: ' . $relative);
        }
        include $path;
    }

    function mbx_root_mode()
    {
        $parent = rtrim(str_replace('\\', '/', dirname(mbx_plugin_dir())), '/');
        return basename($parent) === 'admin' ? 'admin' : 'document';
    }

    function mbx_plugin_web_root()
    {
        if (php_sapi_name() === 'cli') {
            return mbx_root_mode() === 'admin' ? '/admin/mailbox' : '/mailbox';
        }
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
        if (strpos($script, '/admin/mailbox/') !== false) {
            return '/admin/mailbox';
        }
        if (strpos($script, '/mailbox/') !== false) {
            return '/mailbox';
        }
        return mbx_root_mode() === 'admin' ? '/admin/mailbox' : '/mailbox';
    }

    function mbx_plugin_url($path = '')
    {
        return rtrim(mbx_plugin_web_root(), '/') . '/' . ltrim((string)$path, '/');
    }
}
?>
