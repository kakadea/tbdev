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
require_once "include/password_functions.php";

security_session_start();
$adduser_csrf = security_csrf_token('admin-adduser');

    $lang = array_merge( $lang, load_language('ad_adduser') );
    
    if (get_user_class() < UC_ADMINISTRATOR)
      stderr("{$lang['stderr_error']}", "{$lang['text_denied']}");
      
      
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
      if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'admin-adduser'))
      {
        http_response_code(400);
        exit('Invalid account creation request.');
      }
      if (!security_rate_limit('admin-adduser', security_client_identity() . '|' . (int) $CURUSER['id'], 10, 900))
      {
        http_response_code(429);
        exit('Too many account creation requests.');
      }

      $username_raw = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
      $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
      $password2 = isset($_POST['password2']) && is_string($_POST['password2']) ? $_POST['password2'] : '';
      $email_raw = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
      if ($username_raw === '' || strlen($username_raw) < 3 || strlen($username_raw) > 32 || !preg_match('/\A[A-Za-z0-9_.-]+\z/', $username_raw) || $password === '' || $email_raw === '')
        stderr($lang['stderr_error'], $lang['text_missing']);
      if (!hash_equals(hash('sha256', $password), hash('sha256', $password2)))
        stderr($lang['stderr_error'], $lang['text_passwd']);
      if (strlen($password) < 10 || strlen($password) > 200)
        stderr($lang['stderr_error'], 'Password must contain between 10 and 200 characters.');
      if (!validemail($email_raw))
        stderr($lang['stderr_error'], $lang['text_email']);

      $username = sqlesc($username_raw);
      $email = sqlesc($email_raw);
      $secret_raw = mksecret();
      $secret = sqlesc($secret_raw);
      $passhash = sqlesc(make_passhash($secret_raw, md5($password)));
      $modern_hash = sqlesc(password_hash($password, PASSWORD_DEFAULT));
      $time_now = TIME_NOW;

      mysql_query("INSERT INTO users (added, last_access, secret, username, passhash, password_hash, status, email) VALUES($time_now, $time_now, $secret, $username, $passhash, $modern_hash, 'confirmed', $email)") or sqlerr(__FILE__, __LINE__);
      $res = mysql_query("SELECT id FROM users WHERE username=$username LIMIT 1") or sqlerr(__FILE__, __LINE__);
      $arr = mysql_fetch_row($res);
      if (!$arr)
        stderr("{$lang['stderr_error']}", "{$lang['text_username']}");
      header("Location: {$TBDEV['baseurl']}/userdetails.php?id=$arr[0]");
      die;
    }
    

    $HTMLOUT = '';
    
    $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>{$lang['text_adduser']}</div>
                         <div class='cblock-content'>
                             <form method='post' action='admin.php?action=adduser'>
                                  <input type='hidden' name='csrf_token' value='{$adduser_csrf}' />
                                  <table border='1' cellspacing='0' cellpadding='5'>
                                        <tr><td class='rowhead'>{$lang['table_username']}</td><td><input type='text' name='username' size='40' /></td></tr>
                                        <tr><td class='rowhead'>{$lang['table_password']}</td><td><input type='password' name='password' size='40' /></td></tr>
                                        <tr><td class='rowhead'>{$lang['table_repasswd']}</td><td><input type='password' name='password2' size='40' /></td></tr>
                                        <tr><td class='rowhead'>{$lang['table_email']}</td><td><input type='text' name='email' size='40' /></td></tr>
                                        <tr><td colspan='2' align='center'><input type='submit' value='{$lang['btn_okay']}' class='btn' /></td></tr>
                                  </table>
                             </form>
                         </div>
                     </div>";


    print stdhead("{$lang['stdhead_adduser']}") . $HTMLOUT . stdfoot(); 
?>