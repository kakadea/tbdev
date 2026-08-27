<?php

require_once __DIR__ . '/../captcha/functions.php';

$failures = 0;
function captcha_check($condition, $message)
{
    global $failures;
    if ($condition)
        echo "PASS: $message\n";
    else
    {
        echo "FAIL: $message\n";
        $failures++;
    }
}

session_save_path(sys_get_temp_dir());
security_session_start();
$_SESSION = array();
$challenge = captcha_generate_challenge();

captcha_check(is_string($challenge) && preg_match('/\A[A-Z]{6}\z/', $challenge) === 1, 'captcha challenge has six uppercase letters');
captcha_check(captcha_validate_answer($challenge), 'correct captcha answer is accepted');
captcha_check(captcha_validate_answer(strtolower($challenge)), 'lowercase captcha answer is accepted case-insensitively');
captcha_check(!captcha_validate_answer('AAAAAA'), 'wrong captcha answer is rejected');
captcha_check(!captcha_validate_answer(array('invalid')), 'array captcha answer is rejected');
$_SESSION['captcha_time'] = time() - 901;
captcha_check(!captcha_validate_answer($challenge), 'expired captcha answer is rejected');

if ($failures > 0)
{
    echo "\n$failures test(s) failed.\n";
    exit(1);
}

echo "\nAll CAPTCHA tests passed.\n";
