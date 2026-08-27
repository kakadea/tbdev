<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/include/legacy_db.php';

$_SERVER = array(
    'REMOTE_ADDR' => '172.23.0.1',
    'HTTP_CF_CONNECTING_IP' => '1.1.1.1',
    'HTTP_X_FORWARDED_FOR' => '1.1.1.1, 172.23.0.1',
);
if (tbdev_client_ip() !== '1.1.1.1') {
    fwrite(STDERR, "Trusted local proxy did not resolve the public client IP.\n");
    exit(1);
}

$_SERVER = array(
    'REMOTE_ADDR' => '8.8.8.8',
    'HTTP_CF_CONNECTING_IP' => '1.1.1.1',
);
if (tbdev_client_ip() !== '8.8.8.8') {
    fwrite(STDERR, "Public immediate peer must not trust spoofable forwarding headers.\n");
    exit(1);
}

$_SERVER = array('REMOTE_ADDR' => '172.23.0.1');
if (tbdev_client_ip() !== '172.23.0.1') {
    fwrite(STDERR, "Local direct client fallback was not preserved.\n");
    exit(1);
}

echo "Client IP tests passed.\n";
