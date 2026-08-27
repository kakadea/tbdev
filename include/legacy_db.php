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
    $port = 3306;
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
