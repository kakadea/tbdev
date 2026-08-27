<?php

require_once 'include/bittorrent.php';
require_once 'include/user_functions.php';

dbconn();
loggedinorreturn();
$lang = array_merge(load_language('global'), load_language('chat'));
$site_name = htmlsafechars($TBDEV['site_name']);

$HTMLOUT = "
  <div class='cblock'>
    <div class='cblock-header'>{$site_name} — {$lang['chat_chat']}</div>
    <div class='cblock-content'>
      <p>{$lang['chat_unavailable']}</p>
    </div>
  </div>";

print stdhead($lang['chat_chat']) . $HTMLOUT . stdfoot();
