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
require_once "include/bittorrent.php";
require_once "include/user_functions.php";

security_session_start();
dbconn();

loggedinorreturn();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'torrent-delete'))
{
  http_response_code(400);
  exit('Invalid torrent deletion request.');
}
if (!security_rate_limit('torrent-delete', security_client_identity() . '|' . (int) $CURUSER['id'], 10, 300))
{
  http_response_code(429);
  exit('Too many torrent deletion requests.');
}

    $lang = array_merge( load_language('global'), load_language('delete') );

    if($CURUSER['class'] < UC_MODERATOR)
      stderr($lang['gl_user_error'], $lang['gl_perm_denied']);
    //if( !$CURUSER['group']['g_delete_torrents'] )
        //stderr($lang['gl_user_error'], $lang['gl_perm_denied']);
    
    if (!mkglobal("id") || !is_string($id))
      stderr($lang['delete_failed'], $lang['delete_missing_data']);

    $id = (int) $id;
    if (!is_valid_id($id))
      stderr($lang['delete_failed'], $lang['delete_missing_data']);
      

    $res = mysql_query("SELECT name,owner,seeders FROM torrents WHERE id = $id");
    $row = mysql_fetch_assoc($res);
    if (!$row)
      stderr("{$lang['delete_failed']}", "{$lang['delete_not_exist']}");

    if ( $CURUSER["id"] != $row["owner"] AND $CURUSER['class'] < UC_MODERATOR )
      stderr("{$lang['delete_failed']}", "{$lang['delete_not_owner']}\n");

    $rt = isset($_POST['reasontype']) && !is_array($_POST['reasontype']) ? (int) $_POST['reasontype'] : 0;

    if ($rt < 1 || $rt > 5)
      stderr($lang['delete_failed'], $lang['delete_invalid']);

    $reason = isset($_POST['reason']) && is_array($_POST['reason']) ? $_POST['reason'] : array();
    foreach ($reason as $index => $value)
    {
      if (!is_string($value) || strlen($value) > 500)
        stderr($lang['delete_failed'], $lang['delete_invalid']);
      $reason[$index] = trim($value);
    }

    if ($rt == 1)
      $reasonstr = "{$lang['delete_dead']}";
    elseif ($rt == 2)
      $reasonstr = "{$lang['delete_dupe']}" . ($reason[0] ? (": " . trim($reason[0])) : "!");
    elseif ($rt == 3)
      $reasonstr = "{$lang['delete_nuked']}" . ($reason[1] ? (": " . trim($reason[1])) : "!");
    elseif ($rt == 4)
    {
      if (!$reason[2])
        stderr("{$lang['delete_failed']}", "{$lang['delete_violated']}");
      $reasonstr = $TBDEV['site_name']."{$lang['delete_rules']}" . trim($reason[2]);
    }
    else
    {
      if (!$reason[3])
        stderr("{$lang['delete_failed']}", "{$lang['delete_reason']}");
      $reasonstr = trim($reason[3]);
    }

    deletetorrent($id);

    write_log("{$lang['delete_torrent']} $id ({$row['name']}){$lang['delete_deleted_by']}{$CURUSER['username']} ($reasonstr)\n");



    $returnto = isset($_POST['returnto']) && is_string($_POST['returnto'])
      ? security_validate_return_to($_POST['returnto'], $TBDEV['baseurl'] . '/index.php')
      : $TBDEV['baseurl'] . '/index.php';
    $ret = "<a href='" . htmlsafechars($returnto) . "'>{$lang['delete_go_back']}</a>";

    $HTMLOUT = '';
    $HTMLOUT .= "<h2>{$lang['delete_deleted']}</h2>
    <p>{$ret}</p>";


    print stdhead("{$lang['delete_deleted']}") . $HTMLOUT . stdfoot();




function deletetorrent($id) {
    global $TBDEV;
    $id = (int) $id;
    mysql_query("DELETE FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
    foreach (array('peers', 'files', 'comments', 'ratings') as $table)
        @mysql_query("DELETE FROM $table WHERE torrent = $id");
    @unlink($TBDEV['torrent_dir'] . DIRECTORY_SEPARATOR . $id . '.torrent');
}

?>