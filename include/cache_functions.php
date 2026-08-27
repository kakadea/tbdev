<?php

/**
 * Small filesystem cache compatibility layer for the legacy homepage.
 * Cache entries are JSON data stored outside the document root.
 */

function tbdev_cache_key($key)
{
    if (!is_scalar($key)) {
        return '';
    }

    $key = (string) $key;
    return preg_match('/\A[a-z0-9_-]{1,64}\z/i', $key) ? $key : '';
}

function tbdev_cache_file($key)
{
    global $TBDEV;

    $key = tbdev_cache_key($key);
    if ($key === '' || empty($TBDEV['cache_dir']) || !is_string($TBDEV['cache_dir'])) {
        return '';
    }

    return rtrim($TBDEV['cache_dir'], '/\\') . '/' . $key . '.json';
}

/**
 * Kept for compatibility with the old Memcached call site.
 * A false result means the optional external cache is not in use.
 */
function tbdev_cache_connect()
{
    return false;
}

function getCache($key)
{
    $path = tbdev_cache_file($key);
    if ($path === '' || !is_file($path)) {
        return false;
    }

    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return false;
    }

    try {
        $record = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
        return false;
    }

    if (!is_array($record) || !isset($record['expires']) || !array_key_exists('value', $record)) {
        return false;
    }

    if (!is_numeric($record['expires']) || (int) $record['expires'] < time()) {
        @unlink($path);
        return false;
    }

    return $record['value'];
}

function setCache($key, $value, $ttl = 300)
{
    $path = tbdev_cache_file($key);
    $ttl = filter_var($ttl, FILTER_VALIDATE_INT);
    if ($path === '' || $ttl === false || $ttl < 1) {
        return false;
    }

    $directory = dirname($path);
    if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
        return false;
    }

    try {
        $payload = json_encode(
            array('expires' => time() + $ttl, 'value' => $value),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
        $temporary = $path . '.tmp.' . bin2hex(random_bytes(8));
    } catch (Throwable $exception) {
        return false;
    }

    if (@file_put_contents($temporary, $payload, LOCK_EX) === false) {
        @unlink($temporary);
        return false;
    }

    @chmod($temporary, 0660);
    if (!@rename($temporary, $path)) {
        @unlink($temporary);
        return false;
    }

    return true;
}
