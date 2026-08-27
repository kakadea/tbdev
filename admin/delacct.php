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

if ( ! defined( 'IN_TBDEV_ADMIN' ) )
{
	print "<h1>{$lang['text_incorrect']}</h1>{$lang['text_cannot']}";
	exit();
}

require_once "include/user_functions.php";

security_session_start();
$delacct_csrf = security_csrf_token('admin-delacct');

    $lang = array_merge( $lang, load_language('ad_delacct') );
    
    if( $CURUSER['class'] < UC_ADMINISTRATOR )
      stderr($lang['text_error'], $lang['text_unable']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
      if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'admin-delacct'))
      {
        http_response_code(400);
        exit('Invalid account deletion request.');
      }
      if (!security_rate_limit('admin-delacct', security_client_identity() . '|' . (int) $CURUSER['id'], 5, 900))
      {
        http_response_code(429);
        exit('Too many account deletion requests.');
      }
      $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
      if ($username === '' || strlen($username) > 32)
        stderr($lang['text_error'], $lang['text_please']);
        
      $res = @mysql_query("SELECT * FROM users WHERE username=" . sqlesc($username) ) or sqlerr();
      if (mysql_num_rows($res) != 1)
        stderr("{$lang['text_error']}", "{$lang['text_bad']}");
      $arr = mysql_fetch_assoc($res);

      $id = (int) $arr['id'];
      if ($id === (int) $CURUSER['id'])
        stderr($lang['text_error'], 'The current administrator cannot delete their own account.');
      $res = mysql_query('DELETE FROM users WHERE id = ' . $id . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
      if (mysql_affected_rows() != 1)
        stderr("{$lang['text_error']}", "{$lang['text_unable']}");
        
      stderr("{$lang['stderr_success']}", "{$lang['text_success']}");
    }

    $HTMLOUT = '';

    $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>{$lang['text_delete']}</div>
                         <div class='cblock-content'>
                             <form method='post' action='admin.php?action=delacct'>
                                  <input type='hidden' name='csrf_token' value='{$delacct_csrf}' />
                                  <table border='1' cellspacing='0' cellpadding='5'>
                                        <tr>
                                           <td class='rowhead'>{$lang['table_username']}</td>
                                           <td><input size='40' name='username' /></td>
                                        </tr>
                                        <tr>
                                           <td colspan='2'><input type='submit' class='btn' value='{$lang['btn_delete']}' /></td>
                                        </tr>
                                  </table>
                             </form>
                         </div>
                     </div>";

    print stdhead("{$lang['stdhead_delete']}") . $HTMLOUT . stdfoot();
?>