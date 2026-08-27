<?php

require_once __DIR__ . '/functions.php';

security_session_start();
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$cache_buster = (string) time();
echo '<a href="index.php" onclick="refreshimg(); return false;" title="Click to refresh image">'
    . '<img class="cimage" src="captcha/GD_Security_image.php?' . rawurlencode($cache_buster) . '" alt="Captcha image" />'
    . '</a>';
