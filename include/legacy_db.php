<?php
/**
 * Transitional MySQL compatibility layer for TBDev.
 *
 * The original application uses the removed ext/mysql API in hundreds of
 * places. This layer keeps the old call contract while using mysqli under
 * PHP 8.2. It is intentionally a bridge, not the final data-access design.
 */

defined('TBDEV_LEGACY_DB_ADAPTER') || define('TBDEV_LEGACY_DB_ADAPTER', true);

if (!defined('MYSQL_ASSOC')) {
    define('MYSQL_ASSOC', MYSQLI_ASSOC);
}
if (!defined('MYSQL_NUM')) {
    define('MYSQL_NUM', MYSQLI_NUM);
}
if (!defined('MYSQL_BOTH')) {
    define('MYSQL_BOTH', MYSQLI_BOTH);
}

$GLOBALS['TBDEV_DB_LINK'] = null;

/**
 * Resolve the client IPv4 behind the trusted local reverse proxy.
 * Public proxy headers are ignored when the immediate peer is public.
 */
function tbdev_client_ip()
{
    $remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $remote_ip = filter_var($remote, FILTER_VALIDATE_IP);
    $remote_is_public = $remote_ip !== false && filter_var($remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

    if (!$remote_is_public) {
        $candidates = array();
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
            $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        if (isset($_SERVER['HTTP_X_REAL_IP']))
            $candidates[] = $_SERVER['HTTP_X_REAL_IP'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $candidates = array_merge($candidates, explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']));

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false)
                return $candidate;
        }
    }

    return filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? $remote : '';
}

function tbdev_db_link()
{
    return $GLOBALS['TBDEV_DB_LINK'];
}

function mysql_connect($server = null, $username = null, $password = null, $new_link = false, $client_flags = 0)
{
    global $TBDEV;

    $server = $server ?? ($TBDEV['mysql_host'] ?? '127.0.0.1');
    $username = $username ?? ($TBDEV['mysql_user'] ?? '');
    $password = $password ?? ($TBDEV['mysql_pass'] ?? '');

    $host = $server;
    $port = isset($TBDEV['mysql_port']) ? (int) $TBDEV['mysql_port'] : 3306;
    $socket = null;

    if (str_contains($server, ':')) {
        [$host, $port_part] = explode(':', $server, 2);
        if (ctype_digit($port_part)) {
            $port = (int) $port_part;
        } else {
            $socket = $port_part;
            $port = 0;
        }
    }

    $link = mysqli_init();
    if (!$link) {
        $GLOBALS['TBDEV_DB_LINK'] = null;
        return false;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    if (!@mysqli_real_connect($link, $host, $username, $password, null, $port, $socket)) {
        $GLOBALS['TBDEV_DB_LINK'] = $link;
        return false;
    }

    $GLOBALS['TBDEV_DB_LINK'] = $link;
    return $link;
}

function mysql_select_db($database, $link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    return $link instanceof mysqli ? @mysqli_select_db($link, $database) : false;
}

function mysql_set_charset($charset, $link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    return $link instanceof mysqli ? @mysqli_set_charset($link, $charset) : false;
}

function mysql_query($query, $link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    return $link instanceof mysqli ? @mysqli_query($link, $query) : false;
}

/**
 * Execute one prepared statement and return the mysqli_stmt on success.
 * Callers may use mysqli_stmt_get_result() for SELECTs and close the
 * statement with mysqli_stmt_close() after consuming it.
 */
function tbdev_db_prepare_execute($query, $types = '', array $params = array(), $link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    if (!$link instanceof mysqli)
        return false;
    if ($types !== '' && strlen($types) !== count($params))
        return false;

    $stmt = @mysqli_prepare($link, $query);
    if (!$stmt)
        return false;

    if ($types !== '')
    {
        $bind = array($types);
        foreach ($params as $index => $value)
        {
            $params[$index] = $value;
            $bind[] = &$params[$index];
        }
        if (!@call_user_func_array(array($stmt, 'bind_param'), $bind))
        {
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    if (!@mysqli_stmt_execute($stmt))
    {
        mysqli_stmt_close($stmt);
        return false;
    }

    return $stmt;
}

function mysql_fetch_assoc($result)
{
    return $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : false;
}

function mysql_fetch_row($result)
{
    return $result instanceof mysqli_result ? mysqli_fetch_row($result) : false;
}

function mysql_fetch_array($result, $result_type = MYSQL_BOTH)
{
    return $result instanceof mysqli_result ? mysqli_fetch_array($result, $result_type) : false;
}

function mysql_num_rows($result)
{
    return $result instanceof mysqli_result ? mysqli_num_rows($result) : false;
}

function mysql_affected_rows($link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    return $link instanceof mysqli ? mysqli_affected_rows($link) : false;
}

function mysql_insert_id($link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    return $link instanceof mysqli ? mysqli_insert_id($link) : false;
}

function mysql_errno($link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    return $link instanceof mysqli ? mysqli_errno($link) : 0;
}

function mysql_error($link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    return $link instanceof mysqli ? mysqli_error($link) : 'Database connection is not initialized';
}

function mysql_real_escape_string($unescaped_string, $link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    if (!$link instanceof mysqli) {
        throw new RuntimeException('TBDev database connection is not initialized');
    }
    return mysqli_real_escape_string($link, (string) $unescaped_string);
}

function mysql_free_result($result)
{
    return $result instanceof mysqli_result ? mysqli_free_result($result) : false;
}

function mysql_close($link_identifier = null)
{
    $link = $link_identifier ?: tbdev_db_link();
    if (!$link instanceof mysqli) {
        return false;
    }
    $closed = mysqli_close($link);
    $GLOBALS['TBDEV_DB_LINK'] = null;
    return $closed;
}
