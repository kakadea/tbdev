<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/include/bittorrent.php';
require_once dirname(__DIR__) . '/include/bbcode_functions.php';

$TBDEV['pic_base_url'] = 'pic/';

$allowed = format_comment('[img]https://example.com/image.png[/img]');
if (strpos($allowed, '<img class=') === false || strpos($allowed, 'https://example.com/image.png') === false) {
    fwrite(STDERR, "Allowed HTTPS image was not rendered.\n");
    exit(1);
}

$allowed_shorthand = format_comment('[img=https://example.com/image.webp]');
if (strpos($allowed_shorthand, '<img class=') === false) {
    fwrite(STDERR, "Allowed shorthand image was not rendered.\n");
    exit(1);
}

foreach (array(
    '[img]javascript:alert(1)[/img]',
    '[img]data:text/html;base64,abc[/img]',
    '[img]https://example.com/image.svg[/img]',
    '[img]https://example.com/\" onerror=\"alert(1).png[/img]',
) as $unsafe) {
    $rendered = format_comment($unsafe);
    if (strpos($rendered, '<img class=') !== false || strpos($rendered, ' onerror="') !== false || strpos($rendered, " onerror='") !== false) {
        fwrite(STDERR, "Unsafe image markup was rendered.\n");
        exit(1);
    }
}

echo "BBCode image tests passed.\n";
