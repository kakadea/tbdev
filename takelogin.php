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
require_once 'include/bittorrent.php';
require_once "include/password_functions.php";

    security_session_start();
    if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'login'))
    {
      http_response_code(400);
      exit('Invalid login request.');
    }

    if (!mkglobal('username:password'))
      die('Invalid login request.');

    $login_identity = security_client_identity() . '|' . strtolower(trim((string) $username));
    if (!security_rate_limit('login', $login_identity, 8, 900))
    {
      http_response_code(429);
      exit('Too many login attempts. Please try again later.');
    }

    if ($TBDEV['captcha'])
    {
      if (!isset($_POST['captcha']) || empty($_POST['captcha']) || empty($_SESSION['captcha_id']) || $_SESSION['captcha_id'] !== strtoupper((string) $_POST['captcha']))
      {
        header('Location: login.php');
        exit();
      }
      unset($_SESSION['captcha_id'], $_SESSION['captcha_time']);
    }

    dbconn();

    $lang = array_merge(load_language('global'), load_language('takelogin'));


    $res = mysql_query("SELECT id, passhash, password_hash, secret, enabled FROM users WHERE username = " . sqlesc($username) . " AND status = 'confirmed'");
    $row = mysql_fetch_assoc($res);

    if (!$row)
      stderr($lang['tlogin_failed'], 'Username or password incorrect');
    
    if (!empty($row['password_hash']))
    {
      if (!password_verify($password, $row['password_hash']))
        stderr($lang['tlogin_failed'], 'Username or password incorrect');

      if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT))
      {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        if ($new_hash !== false)
          mysql_query("UPDATE users SET password_hash = " . sqlesc($new_hash) . " WHERE id = " . (int) $row['id']);
      }
    }
    else
    {
      $legacy_valid = hash_equals(
        (string) $row['passhash'],
        make_passhash($row['secret'], md5($password))
      );
      if (!$legacy_valid)
        stderr($lang['tlogin_failed'], 'Username or password incorrect');

      $new_hash = password_hash($password, PASSWORD_DEFAULT);
      if ($new_hash !== false)
        mysql_query("UPDATE users SET password_hash = " . sqlesc($new_hash) . " WHERE id = " . (int) $row['id']);
    }

    if ($row['enabled'] == 'no')
      stderr($lang['tlogin_failed'], $lang['tlogin_disabled']);

    security_session_regenerate();
    logincookie($row['id'], $row['passhash']);

    $returnto = security_validate_return_to(
      isset($_POST['returnto']) ? $_POST['returnto'] : '',
      $TBDEV['baseurl'] . '/my.php'
    );
    header('Location: ' . $returnto);

?>