<?php

require_once __DIR__ . '/functions.php';

captcha_generate_challenge();
http_response_code(204);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
