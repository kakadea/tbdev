<?php
/*
+------------------------------------------------
|   TBDev.net BitTorrent Tracker PHP
|   =============================================
|   by CoLdFuSiOn
|   (c) 2003 - 2011 TBDev.Net
|   http://www.tbdev.net
|   =============================================
|   svn: http://sourceforge.net/projects/tbdevnet/
|   Licence Info: GPL
+------------------------------------------------
*/
require_once "include/bittorrent.php";
dbconn(false);

if (!$CURUSER)
  exit;

$raw_url = isset($_GET['url']) ? trim((string) $_GET['url']) : '';
if ($raw_url === '' || preg_match('/[\x00-\x1F\x7F]/', $raw_url))
  httperr();

if (stripos($raw_url, 'www.') === 0)
  $raw_url = 'http://' . $raw_url;

$parts = parse_url($raw_url);
if ($parts === false || empty($parts['scheme']) || empty($parts['host']) ||
    !in_array(strtolower($parts['scheme']), array('http', 'https'), true) ||
    isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']))
  httperr();

$url = filter_var($raw_url, FILTER_VALIDATE_URL);
if ($url === false)
  httperr();

$safe_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
header('Referrer-Policy: no-referrer');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'');
header('X-Content-Type-Options: nosniff');
print("<html><head><meta http-equiv='refresh' content='3;url={$safe_url}'></head><body>\n");
print("<div style='width:100%;text-align:center;background:#E9D58F;border:1px solid #CEAA49;margin:5px 0;padding:0 5px;font-weight:bold;'>Redirecting you to:<br />\n");
print(htmlsafechars($url) . "</div></body></html>\n");
?>
