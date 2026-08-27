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

    $lang = array_merge( load_language('global'), load_language('confirmemail') );
    
    if (!isset($_GET['uid'], $_GET['key'], $_GET['email']))
      stderr($lang['confirmmail_user_error'], $lang['confirmmail_idiot']);

    if (!is_string($_GET['uid']) || !preg_match('/\A\d{1,10}\z/D', $_GET['uid']))
      stderr($lang['confirmmail_user_error'], $lang['confirmmail_no_id']);
    if (!is_string($_GET['key']) || !preg_match('/\A[A-Za-z0-9]{32}\z/D', $_GET['key']))
		{
			stderr( "{$lang['confirmmail_user_error']}", "{$lang['confirmmail_no_key']}" );
		}
		
    $id = (int) $_GET['uid'];
    $key = (string) $_GET['key'];
    $email = is_string($_GET['email']) ? urldecode($_GET['email']) : '';
    
    if( !validemail($email) )
      stderr("{$lang['confirmmail_user_error']}", "{$lang['confirmmail_false_email']}");

dbconn();
loggedinorreturn();

    $account_stmt = tbdev_db_prepare_execute(
      'SELECT editsecret FROM users WHERE id = ? LIMIT 1',
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
      stderr("{$lang['confirmmail_user_error']}", "{$lang['confirmmail_not_complete']}");

    //$sec = hash_pad($row["editsecret"]);
    $sec = $row['editsecret'];
    if (preg_match('/^ *$/s', $sec))
      stderr("{$lang['confirmmail_user_error']}", "{$lang['confirmmail_not_complete']}");
      
    if (!hash_equals(md5($sec . $email . $sec), $key))
      stderr($lang['confirmmail_user_error'], $lang['confirmmail_not_complete']);

    $confirm_stmt = tbdev_db_prepare_execute(
      "UPDATE users SET editsecret='', email=? WHERE id=? AND editsecret=?",
      'sis',
      array($email, $id, (string) $row['editsecret'])
    );
    if (!$confirm_stmt)
      stderr($lang['confirmmail_user_error'], $lang['confirmmail_not_complete']);
    mysqli_stmt_close($confirm_stmt);

    if (!mysql_affected_rows())
      stderr("{$lang['confirmmail_user_error']}", "{$lang['confirmmail_not_complete']}");

    header("Refresh: 0; url={$TBDEV['baseurl']}/my.php?emailch=1");


?>