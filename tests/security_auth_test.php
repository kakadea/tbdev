<?php
ob_start();
require_once __DIR__ . '/../include/password_functions.php';
require_once __DIR__ . '/../include/security.php';

$failures = 0;
function check_test($condition, $message)
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

$modern_hash = password_hash('correct horse battery staple', PASSWORD_DEFAULT);
check_test($modern_hash !== false, 'modern password hash is generated');
check_test(password_verify('correct horse battery staple', $modern_hash), 'modern password verifies');
check_test(!password_verify('wrong password', $modern_hash), 'wrong modern password is rejected');

$legacy_salt = 'abcde';
$legacy_hash = make_passhash($legacy_salt, md5('legacy-password'));
check_test(hash_equals($legacy_hash, make_passhash($legacy_salt, md5('legacy-password'))), 'legacy password verifies with compatibility formula');
check_test(!hash_equals($legacy_hash, make_passhash($legacy_salt, md5('wrong-password'))), 'wrong legacy password is rejected');

check_test(security_validate_return_to('/my.php?edited=1', '/fallback') === '/my.php?edited=1', 'relative return URL is accepted');
check_test(security_validate_return_to('https://evil.example/', '/fallback') === '/fallback', 'absolute external return URL is rejected');
check_test(security_validate_return_to('//evil.example/', '/fallback') === '/fallback', 'scheme-relative return URL is rejected');
check_test(security_validate_return_to("/ok\r\nLocation: https://evil.example/", '/fallback') === '/fallback', 'header injection in return URL is rejected');

session_save_path(sys_get_temp_dir());
security_session_start();
$csrf = security_csrf_token('test');
check_test(security_csrf_validate($csrf, 'test'), 'CSRF token validates in its scope');
check_test(!security_csrf_validate('invalid', 'test'), 'invalid CSRF token is rejected');

$identity = 'test-' . bin2hex(random_bytes(8));
check_test(security_rate_limit('unit-test', $identity, 2, 60), 'first rate-limited request is allowed');
check_test(security_rate_limit('unit-test', $identity, 2, 60), 'second rate-limited request is allowed');
check_test(!security_rate_limit('unit-test', $identity, 2, 60), 'third rate-limited request is rejected');

if ($failures > 0)
{
    echo "\n$failures test(s) failed.\n";
    exit(1);
}

echo "\nAll security/auth tests passed.\n";
ob_end_flush();
?>
