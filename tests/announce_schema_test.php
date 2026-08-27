<?php

declare(strict_types=1);

$source = file_get_contents(dirname(__DIR__) . '/announce.php');
if ($source === false) {
    fwrite(STDERR, "Could not read announce.php.\n");
    exit(1);
}

if (preg_match('/(?:SELECT|INSERT INTO|UPDATE)[^;\n]*\bcompact\b/i', $source)) {
    fwrite(STDERR, "announce.php still references peers.compact in SQL.\n");
    exit(1);
}

if (strpos($source, "pack('Nn'") === false) {
    fwrite(STDERR, "announce.php no longer builds compact peers in memory.\n");
    exit(1);
}

if (strpos($source, 'FILTER_FLAG_IPV4') === false) {
    fwrite(STDERR, "announce.php must enforce the legacy IPv4 storage contract.\n");
    exit(1);
}

echo "Announce schema coverage passed.\n";
