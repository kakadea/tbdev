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

    $lang = array_merge( load_language('global'), load_language('confirm') );
    
    $id = isset($_GET['id']) && !is_array($_GET['id']) ? (int) $_GET['id'] : 0;
    $secret = isset($_GET['secret']) && is_string($_GET['secret']) ? $_GET['secret'] : '';

    if (!is_valid_id($id))
      stderr("{$lang['confirm_user_error']}", "{$lang['confirm_invalid_id']}");
    
    if (!preg_match('/\A[A-Za-z0-9]{32}\z/D', $secret))
		{
			stderr("{$lang['confirm_user_error']}", "{$lang['confirm_invalid_key']}");
		}
		
    dbconn();


    $account_stmt = tbdev_db_prepare_execute(
      'SELECT passhash, editsecret, status FROM users WHERE id = ? LIMIT 1',
      'i',
      array($id)
    );
    $res = $account_stmt ? mysqli_stmt_get_result($account_stmt) : false;
    $row = $res ? mysqli_fetch_assoc($res) : false;
    if ($res)
      mysqli_free_result($res);
    if ($account_stmt)
      mysqli_stmt_close($account_stmt);

    if (!$row)
      stderr("{$lang['confirm_user_error']}", "{$lang['confirm_invalid_id']}");

    if ($row['status'] != 'pending') 
    {
      header("Refresh: 0; url={$TBDEV['baseurl']}/ok.php?type=confirmed");
      exit();
    }

    //$sec = hash_pad($row['editsecret']);
    $stored_secret = (string) $row['editsecret'];
    if ($stored_secret === '' || !hash_equals($stored_secret, $secret))
      stderr($lang['confirm_user_error'], $lang['confirm_cannot_confirm']);

    $confirm_stmt = tbdev_db_prepare_execute(
      "UPDATE users SET status='confirmed', editsecret='' WHERE id = ? AND status = 'pending'",
      'i',
      array($id)
    );
    if (!$confirm_stmt)
      stderr($lang['confirm_user_error'], $lang['confirm_cannot_confirm']);
    mysqli_stmt_close($confirm_stmt);

    if (!mysql_affected_rows())
      stderr("{$lang['confirm_user_error']}", "{$lang['confirm_cannot_confirm']}");

    logincookie($id, $row['passhash']);

    header("Refresh: 0; url={$TBDEV['baseurl']}/ok.php?type=confirm");

?>