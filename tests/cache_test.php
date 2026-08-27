<?php

$cache_dir = sys_get_temp_dir() . '/tbdev-cache-test-' . bin2hex(random_bytes(6));
mkdir($cache_dir, 0700, true);
$TBDEV = array('cache_dir' => $cache_dir);
require_once __DIR__ . '/../include/cache_functions.php';

function cache_test($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

register_shutdown_function(function () use ($cache_dir) {
    foreach (glob($cache_dir . '/*') ?: array() as $file) {
        @unlink($file);
    }
    @rmdir($cache_dir);
});

$value = array('registered' => 3, 'label' => 'áudio');
cache_test(setCache('frontpage', $value, 60) === true, 'cache value is written');
cache_test(getCache('frontpage') === $value, 'cache value round-trips as JSON data');
cache_test(is_file($cache_dir . '/frontpage.json'), 'cache file uses data-only JSON extension');
cache_test(setCache('../escape', $value, 60) === false, 'path traversal cache key is rejected');
cache_test(getCache(array('invalid')) === false, 'array cache key is rejected');

file_put_contents(
    $cache_dir . '/expired.json',
    json_encode(array('expires' => time() - 1, 'value' => 'expired'))
);
cache_test(getCache('expired') === false, 'expired cache value is rejected');
cache_test(!is_file($cache_dir . '/expired.json'), 'expired cache file is removed');

echo "All cache tests passed.\n";
