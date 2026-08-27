<?php

require_once __DIR__ . '/../include/security.php';

function captcha_generate_challenge()
{
    security_session_start();
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $challenge = '';
    for ($i = 0; $i < 6; $i++)
        $challenge .= $alphabet[random_int(0, strlen($alphabet) - 1)];

    $_SESSION['captcha_id'] = $challenge;
    $_SESSION['captcha_time'] = time();
    return $challenge;
}

function captcha_validate_answer($value)
{
    security_session_start();
    if (!is_string($value))
        return false;

    $value = strtoupper(trim($value));
    if (!preg_match('/\A[A-Z]{6}\z/', $value))
        return false;
    if (empty($_SESSION['captcha_id']) || !is_string($_SESSION['captcha_id']))
        return false;
    if (!isset($_SESSION['captcha_time']) || time() - (int) $_SESSION['captcha_time'] > 900)
        return false;

    return hash_equals($_SESSION['captcha_id'], $value);
}
