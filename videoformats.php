<?php

require_once __DIR__ . '/include/bittorrent.php';

dbconn(false);
$lang = array_merge(load_language('global'), load_language('videoformats'));

$format_sections = array(
    array('videoformats_cam', 'videoformats_cam_body'),
    array('videoformats_telesync', 'videoformats_telesync_body'),
    array('videoformats_telecine', 'videoformats_telecine_body'),
    array('videoformats_screener', 'videoformats_screener_body'),
    array('videoformats_dvdscreener', 'videoformats_dvdscreener_body'),
    array('videoformats_dvdrip', 'videoformats_dvdrip_body'),
    array('videoformats_vhsrip', 'videoformats_vhsrip_body'),
    array('videoformats_tvrip', 'videoformats_tvrip_body'),
    array('videoformats_workprint', 'videoformats_workprint_body'),
    array('videoformats_divx', 'videoformats_divx_body'),
    array('videoformats_watermarks', 'videoformats_watermarks_body'),
    array('videoformats_asian', 'videoformats_asian_body'),
    array('videoformats_scenetag', null),
    array('videoformats_proper', 'videoformats_proper_body'),
    array('videoformats_limited', 'videoformats_limited_body'),
    array('videoformats_internal', 'videoformats_internal_body'),
    array('videoformats_stv', 'videoformats_stv_body'),
    array('videoformats_aspect', 'videoformats_aspect_body'),
    array('videoformats_repack', 'videoformats_repack_body'),
    array('videoformats_nuked', 'videoformats_nuked_body'),
    array('videoformats_dupe', 'videoformats_dupe_body')
);

$HTMLOUT = "<main class='cblock page-content video-formats' aria-labelledby='video-formats-title'>
  <header class='cblock-header'>
    <h1 id='video-formats-title'>{$lang['videoformats_header']}</h1>
    <p>{$lang['videoformats_title']}</p>
  </header>
  <div class='cblock-content'>
    <div class='video-formats-grid'>";

foreach ($format_sections as $section)
{
    $heading = $lang[$section[0]];
    $body = $section[1] === null ? '' : '<div class="format-description">' . $lang[$section[1]] . '</div>';
    $HTMLOUT .= "
      <section class='format-card'>
        <h2>{$heading}</h2>
        {$body}
      </section>";
}

$HTMLOUT .= "
    </div>
  </div>
</main>";

print stdhead($lang['videoformats_header']) . $HTMLOUT . stdfoot();
