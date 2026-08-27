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
|   $Date$
|   $Revision$
|   $Author$
|   $URL$
+------------------------------------------------
*/
require_once("include/bittorrent.php");
security_session_start();
dbconn();
loggedinorreturn();
$lang = array_merge( load_language('global'), load_language('takerate') );
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'torrent-rating'))
{
  http_response_code(400);
  exit('Invalid rating request.');
}
if (!security_rate_limit('torrent-rating', security_client_identity() . '|' . (int) $CURUSER['id'], 10, 300))
{
  http_response_code(429);
  exit('Too many rating requests.');
}


if (!isset($CURUSER))
	stderr("{$lang['rate_fail']}", "{$lang['rate_login']}");

$id = isset($_POST['id']) && !is_array($_POST['id']) && preg_match('/\A\d+\z/', (string) $_POST['id']) ? (int) $_POST['id'] : 0;
if (!is_valid_id($id))
	stderr("{$lang['rate_fail']}", "{$lang['rate_invalid_id']}");

$rating = isset($_POST['rating']) && !is_array($_POST['rating']) && preg_match('/\A[1-5]\z/', (string) $_POST['rating']) ? (int) $_POST['rating'] : 0;
if ($rating < 1 || $rating > 5)
	stderr("{$lang['rate_fail']}", "{$lang['rate_invalid']}");

$res = mysql_query("SELECT owner FROM torrents WHERE id = " . (int) $id) or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_assoc($res);
if (!$row)
	stderr("{$lang['rate_fail']}", "{$lang['rate_torrent_not_found']}");

//if ($row["owner"] == $CURUSER["id"])
//	bark("{$lang['rate_not_vote_own_torrent']}");
$time_now = TIME_NOW;
$res = mysql_query("INSERT INTO ratings (torrent, user, rating, added) VALUES ($id, " . $CURUSER["id"] . ", $rating, $time_now)");
if (!$res) {
	if (mysql_errno() == 1062)
		stderr("{$lang['rate_fail']}", "{$lang['rate_already_voted']}");
	else
		stderr("{$lang['rate_fail']}", mysql_error());
}

mysql_query("UPDATE torrents SET numratings = numratings + 1, ratingsum = ratingsum + " . (int) $rating . " WHERE id = " . (int) $id) or sqlerr(__FILE__, __LINE__);

header('Location: details.php?id=' . (int) $id . '&rated=1');
exit;

?>