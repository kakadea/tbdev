<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/include/bittorrent.php';

$valid = array(
    'The.Highers.Test.2026.torrent',
    'Linux ISO — edição de teste (arm64).torrent',
    'arquivo com espaços e acentos áéíóú.torrent',
    'C:\\fake\\browser\\path\\fixture.torrent',
);
foreach ($valid as $filename) {
    $normalized = tbdev_upload_filename($filename);
    if ($normalized === '' || !validfilename($filename)) {
        fwrite(STDERR, "Valid torrent filename rejected: {$filename}\n");
        exit(1);
    }
}

$expected = tbdev_upload_filename('C:\\fake\\browser\\path\\fixture.torrent');
if ($expected !== 'fixture.torrent') {
    fwrite(STDERR, "Browser path was not reduced to basename.\n");
    exit(1);
}

$invalid = array(
    '',
    '.',
    '..',
    "bad\nname.torrent",
    'bad:name.torrent',
    'bad|name.torrent',
    str_repeat('a', 256) . '.torrent',
);
foreach ($invalid as $filename) {
    if (tbdev_upload_filename($filename) !== '' || validfilename($filename)) {
        fwrite(STDERR, "Invalid torrent filename accepted.\n");
        exit(1);
    }
}

echo "Upload filename tests passed.\n";
