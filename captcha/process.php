<?php

require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo captcha_validate_answer(isset($_POST['captcha']) ? $_POST['captcha'] : null) ? '1' : '0';
