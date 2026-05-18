<?php

if (!function_exists('mysql_connect')) {
    if (!defined('MYSQL_ASSOC')) {
        define('MYSQL_ASSOC', MYSQLI_ASSOC);
    }

    if (!defined('MYSQL_NUM')) {
        define('MYSQL_NUM', MYSQLI_NUM);
    }

    if (!defined('MYSQL_BOTH')) {
        define('MYSQL_BOTH', MYSQLI_BOTH);
    }

    function mysql_compat_default_link($link = null)
    {
        if ($link instanceof mysqli) {
            return $link;
        }

        return isset($GLOBALS['mysql_compat_default_link']) ? $GLOBALS['mysql_compat_default_link'] : null;
    }

    function mysql_compat_parse_host($server)
    {
        $host = (string) $server;
        $port = null;

        if (substr_count($host, ':') === 1 && strpos($host, ']') === false) {
            list($hostPart, $portPart) = explode(':', $host, 2);
            if ($portPart !== '' && ctype_digit($portPart)) {
                $host = $hostPart;
                $port = (int) $portPart;
            }
        }

        return array($host, $port);
    }

    function mysql_connect($server = null, $username = null, $password = null, $new_link = false, $client_flags = 0)
    {
        if (!extension_loaded('mysqli')) {
            trigger_error('mysqli extension is required for mysql_* compatibility.', E_USER_ERROR);
        }

        list($host, $port) = mysql_compat_parse_host($server);
        $link = mysqli_connect($host, $username, $password, '', $port ?: ini_get('mysqli.default_port'));

        if ($link instanceof mysqli) {
            $GLOBALS['mysql_compat_default_link'] = $link;
        }

        return $link;
    }

    function mysql_pconnect($server = null, $username = null, $password = null, $client_flags = 0)
    {
        return mysql_connect($server, $username, $password, true, $client_flags);
    }

    function mysql_close($link = null)
    {
        $link = mysql_compat_default_link($link);

        if (!$link) {
            return false;
        }

        if (isset($GLOBALS['mysql_compat_default_link']) && $GLOBALS['mysql_compat_default_link'] === $link) {
            unset($GLOBALS['mysql_compat_default_link']);
        }

        return mysqli_close($link);
    }

    function mysql_select_db($database_name, $link = null)
    {
        $link = mysql_compat_default_link($link);

        if (!$link) {
            return false;
        }

        return mysqli_select_db($link, $database_name);
    }

    function mysql_query($query, $link = null)
    {
        if ($query instanceof mysqli && is_string($link)) {
            $swap = $query;
            $query = $link;
            $link = $swap;
        }

        $link = mysql_compat_default_link($link);

        if (!$link) {
            return false;
        }

        return mysqli_query($link, $query);
    }

    function mysql_error($link = null)
    {
        $link = mysql_compat_default_link($link);

        if ($link instanceof mysqli) {
            return mysqli_error($link);
        }

        return mysqli_connect_error();
    }

    function mysql_errno($link = null)
    {
        $link = mysql_compat_default_link($link);

        if ($link instanceof mysqli) {
            return mysqli_errno($link);
        }

        return mysqli_connect_errno();
    }

    function mysql_real_escape_string($string, $link = null)
    {
        $link = mysql_compat_default_link($link);

        if (!$link) {
            return addslashes($string);
        }

        return mysqli_real_escape_string($link, $string);
    }

    function mysql_fetch_assoc($result)
    {
        return $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : false;
    }

    function mysql_fetch_array($result, $result_type = MYSQL_BOTH)
    {
        return $result instanceof mysqli_result ? mysqli_fetch_array($result, $result_type) : false;
    }

    function mysql_fetch_row($result)
    {
        return $result instanceof mysqli_result ? mysqli_fetch_row($result) : false;
    }

    function mysql_fetch_object($result, $class_name = 'stdClass', $params = array())
    {
        if (!($result instanceof mysqli_result)) {
            return false;
        }

        if ($class_name === 'stdClass' && !$params) {
            return mysqli_fetch_object($result);
        }

        return mysqli_fetch_object($result, $class_name, $params);
    }

    function mysql_num_rows($result)
    {
        return $result instanceof mysqli_result ? mysqli_num_rows($result) : false;
    }

    function mysql_affected_rows($link = null)
    {
        $link = mysql_compat_default_link($link);

        return $link instanceof mysqli ? mysqli_affected_rows($link) : false;
    }

    function mysql_insert_id($link = null)
    {
        $link = mysql_compat_default_link($link);

        return $link instanceof mysqli ? mysqli_insert_id($link) : false;
    }

    function mysql_free_result($result)
    {
        return $result instanceof mysqli_result ? mysqli_free_result($result) : false;
    }

    function mysql_data_seek($result, $row_number)
    {
        return $result instanceof mysqli_result ? mysqli_data_seek($result, $row_number) : false;
    }

    function mysql_result($result, $row = 0, $field = 0)
    {
        if (!($result instanceof mysqli_result)) {
            return false;
        }

        if (!mysqli_data_seek($result, $row)) {
            return false;
        }

        $data = mysqli_fetch_array($result, MYSQLI_BOTH);

        if ($data === null || $data === false) {
            return false;
        }

        return isset($data[$field]) ? $data[$field] : false;
    }
}
