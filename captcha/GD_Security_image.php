<?php

require_once __DIR__ . '/functions.php';

function show_gd_img($content = '')
{
    if (!function_exists('imagecreatetruecolor'))
    {
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('CAPTCHA image service is unavailable.');
    }

    $content = '  ' . preg_replace('/(\w)/', '$1 ', (string) $content) . ' ';
    $tmp_x = 140;
    $tmp_y = 20;
    $image_x = 210;
    $image_y = 65;
    $circles = 3;

    header('Content-Type: image/jpeg');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    $tmp = imagecreatetruecolor($tmp_x, $tmp_y);
    $im = imagecreatetruecolor($image_x, $image_y);
    $white = imagecolorallocate($tmp, 255, 255, 255);
    $black = imagecolorallocate($tmp, 0, 0, 0);
    imagefill($tmp, 0, 0, $white);

    for ($i = 1; $i <= $circles; $i++)
    {
        $values = array();
        for ($point = 0; $point < 6; $point++)
        {
            $values[] = random_int(0, $tmp_x - 10);
            $values[] = random_int(0, $tmp_y - 3);
        }
        $random_color = imagecolorallocate($tmp, random_int(100, 255), random_int(100, 255), random_int(100, 255));
        imagefilledpolygon($tmp, $values, $random_color);
    }

    imagestring($tmp, 5, 0, 2, $content, $black);
    imagecopyresized($im, $tmp, 0, 0, 0, 0, $image_x, $image_y, $tmp_x, $tmp_y);
    imagedestroy($tmp);

    $black = imagecolorallocate($im, 0, 0, 0);
    $grey = imagecolorallocate($im, 100, 100, 100);
    $random_pixels = (int) ($image_x * $image_y / 10);
    for ($i = 0; $i < $random_pixels; $i++)
        imagesetpixel($im, random_int(0, $image_x - 1), random_int(0, $image_y - 1), $black);

    $x_step = ($image_x - 1) / 5;
    for ($i = 0; $i <= 5; $i++)
    {
        imageline($im, (int) ($i * $x_step), 0, (int) ($i * $x_step), $image_y - 1, $grey);
        imageline($im, (int) ($i * $x_step), 0, (int) (($i + 1) * $x_step), $image_y - 1, $grey);
    }

    $y_step = ($image_y - 1) / 5;
    for ($i = 0; $i <= 5; $i++)
        imageline($im, 0, (int) ($i * $y_step), $image_x - 1, (int) ($i * $y_step), $grey);

    imagejpeg($im, null, 85);
    imagedestroy($im);
}

captcha_generate_challenge();
show_gd_img($_SESSION['captcha_id']);
