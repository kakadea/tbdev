<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$assets = array(
    'images/branding_bg.png',
    'images/button.png',
    'images/default_thumb.png',
    'images/fb_gradient.png',
    'images/gradient_bg.png',
    'images/gradient_bp.png',
    'images/info.png',
    'images/key.png',
    'images/logo.jpg',
    'images/primarynav_bg.png',
    'images/user_green.png',
    'images/user_off.png',
    'pic/aff_cross.gif',
    'pic/aff_tick.gif',
    'pic/arrowdown.gif',
    'pic/arrowup.gif',
    'pic/flag/uk.gif',
    'pic/forumicons/default_avatar.gif',
    'pic/logo.gif',
    'pic/multipage.gif',
    'pic/new.png',
    'pic/panel_on.gif',
    'pic/readpm.gif',
    'pic/rep/reputation_down.gif',
    'pic/rep/reputation_up.gif',
    'pic/staff/mail.png',
    'pic/staff/users.png',
    'pic/star.gif',
    'pic/tbani22.gif',
    'pic/tbdev_btn_red.png',
    'pic/tile_back.gif',
    'pic/unreadpm.gif',
    'pic/updated.png',
    'pic/warned.gif',
    'pic/warned0.gif',
    'pic/warnedbig.gif',
);

foreach ($assets as $relative) {
    $path = $root . '/' . $relative;
    if (!is_file($path) || !is_readable($path)) {
        fwrite(STDERR, "Missing or unreadable theme asset: {$relative}\n");
        exit(1);
    }
    if (@getimagesize($path) === false) {
        fwrite(STDERR, "Invalid image asset: {$relative}\n");
        exit(1);
    }
}

foreach (array('pic/caticons', 'pic/forumicons', 'pic/rep', 'pic/smilies', 'pic/staff') as $relative) {
    if (!is_dir($root . '/' . $relative)) {
        fwrite(STDERR, "Missing theme directory: {$relative}\n");
        exit(1);
    }
}

echo "Theme assets passed.\n";
