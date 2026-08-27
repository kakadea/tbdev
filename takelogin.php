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

    if (!mkglobal('username:password'))
      die('wibble');

    if( $TBDEV['captcha'] )
    {
      session_start();
      if(!isset($_POST['captcha']) || empty($_POST['captcha']) || $_SESSION['captcha_id'] != strtoupper($_POST['captcha']))
      {
            header('Location: login.php');
            exit();
      }
    }
    
    dbconn();
    
    $lang = array_merge( load_language('global'), load_language('takelogin') );


    $res = mysql_query("SELECT id, passhash, password_hash, secret, enabled FROM users WHERE username = " . sqlesc($username) . " AND status = 'confirmed'");
    $row = mysql_fetch_assoc($res);

    if (!$row)
      stderr($lang['tlogin_failed'], 'Username or password incorrect');
    
    $legacy_valid = hash_equals(
      (string) $row['passhash'],
      make_passhash($row['secret'], md5($password))
    );
    $modern_valid = !empty($row['password_hash'])
      && password_verify($password, $row['password_hash']);

    if (!$modern_valid && !$legacy_valid)
      stderr($lang['tlogin_failed'], 'Username or password incorrect');

    if (empty($row['password_hash']))
    {
      $new_hash = password_hash($password, PASSWORD_DEFAULT);
      if ($new_hash !== false)
      {
        mysql_query("UPDATE users SET password_hash = " . sqlesc($new_hash) . " WHERE id = " . (int) $row['id']);
      }
    }

    if ($row['enabled'] == 'no')
      stderr($lang['tlogin_failed'], $lang['tlogin_disabled']);

    logincookie($row['id'], $row['passhash']);

//$returnto = str_replace('&amp;', '&', htmlsafechars($_POST['returnto']));
//$returnto = $_POST['returnto'];
    //if (!empty($returnto))
      //header("Location: ".$returnto);
    //else
      header("Location: {$TBDEV['baseurl']}/my.php");

?>