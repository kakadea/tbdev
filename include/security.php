<?php
/**
 * Small security primitives used by the legacy transition layer.
 * These helpers are intentionally dependency-free and can be replaced by
 * the final session/rate-limit implementation when the database layer moves.
 */

function security_session_start()
{
    if (session_status() === PHP_SESSION_ACTIVE)
        return;

    ini_set('session.use_trans_sid', '0');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    session_start();
}

function security_csrf_token($scope = 'default')
{
    security_session_start();
    $scope = preg_replace('/[^a-zA-Z0-9_.-]/', '_', (string) $scope);
    $key = 'csrf_' . $scope;

    if (empty($_SESSION[$key]))
        $_SESSION[$key] = bin2hex(random_bytes(32));

    return $_SESSION[$key];
}

function security_csrf_validate($token, $scope = 'default')
{
    $expected = security_csrf_token($scope);
    return is_string($token) && hash_equals($expected, $token);
}

function security_session_regenerate()
{
    security_session_start();
    session_regenerate_id(true);
}

function security_validate_return_to($value, $fallback = '/')
{
    $value = trim((string) $value);
    if ($value === '' || preg_match('/[\r\n]/', $value))
        return $fallback;

    $parsed = parse_url($value);
    if ($parsed === false || isset($parsed['scheme']) || isset($parsed['host']) || isset($parsed['user']) || isset($parsed['pass']))
        return $fallback;
    if (substr($value, 0, 2) === '//')
        return $fallback;
    if ($value[0] !== '/')
        return $fallback;

    return $value;
}

function security_rate_limit($namespace, $identity, $max_attempts, $window_seconds)
{
    $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tbdev-rate-limit';
    if (!is_dir($base) && !@mkdir($base, 0700, true) && !is_dir($base))
        return true;

    $name = hash('sha256', (string) $namespace . '|' . (string) $identity);
    $path = $base . DIRECTORY_SEPARATOR . $name . '.json';
    $handle = @fopen($path, 'c+');
    if (!$handle)
        return true;
    if (!flock($handle, LOCK_EX))
    {
        fclose($handle);
        return true;
    }

    $raw = stream_get_contents($handle);
    $data = json_decode($raw ?: '', true);
    $now = time();
    if (!is_array($data) || !isset($data['started'], $data['attempts']) || $now - (int) $data['started'] >= (int) $window_seconds)
      $data = array('started' => $now, 'attempts' => 0);

    $data['attempts']++;
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data, JSON_THROW_ON_ERROR));
    fflush($handle);
    $allowed = $data['attempts'] <= (int) $max_attempts;
    flock($handle, LOCK_UN);
    fclose($handle);

    return $allowed;
}

function security_client_identity()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}
?>
