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
require_once "include/password_functions.php";

security_session_start();
if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'profile'))
{
  http_response_code(400);
  exit('Invalid profile update request.');
}

dbconn();

loggedinorreturn();

    $lang = array_merge( load_language('global'), load_language('takeprofedit') );
    
    if (!mkglobal("email:chpassword:passagain:chmailpass"))
      stderr("Update failed!", $lang['takeprofedit_no_data']);

    // $set = array();

    $updateset = array();
    $changedemail = 0;

    $password_change_requested = ($chpassword !== '');
    $email_change_requested = ($email !== $CURUSER['email']);
    $current_password_valid = false;
    if ($password_change_requested || $email_change_requested)
    {
      if ($chmailpass === '')
        stderr("Update failed!", 'Enter your current password before changing account credentials.');

      if (!empty($CURUSER['password_hash']))
        $current_password_valid = password_verify($chmailpass, $CURUSER['password_hash']);
      else
        $current_password_valid = hash_equals(
          (string) $CURUSER['passhash'],
          make_passhash($CURUSER['secret'], md5($chmailpass))
        );

      if (!$current_password_valid)
        stderr("Update failed!", 'The current password is incorrect.');
    }

    if ($password_change_requested)
    {
      if (strlen($chpassword) < 10 || strlen($chpassword) > 200)
        stderr("Update failed!", 'Password must be between 10 and 200 characters.');
      if (!hash_equals($chpassword, $passagain))
        stderr("Update failed!", $lang['takeprofedit_pass_not_match']);

      $secret = mksecret();
      $passhash = make_passhash($secret, md5($chpassword));
      $modernpasshash = password_hash($chpassword, PASSWORD_DEFAULT);
      if ($modernpasshash === false)
        stderr("Update failed!", $lang['takeprofedit_pass_not_match']);

      $updateset[] = "secret = " . sqlesc($secret);
      $updateset[] = "passhash = " . sqlesc($passhash);
      $updateset[] = "password_hash = " . sqlesc($modernpasshash);
      logincookie($CURUSER['id'], $passhash);
    }

    if ($email_change_requested)
    {
      if (!validemail($email))
        stderr("Update failed!", $lang['takeprofedit_not_valid_email']);
      $r = mysql_query("SELECT id FROM users WHERE email = " . sqlesc($email) . " AND id <> " . (int) $CURUSER['id']) or sqlerr();
      if (mysql_num_rows($r) > 0)
        stderr("Update failed!", $lang['takeprofedit_address_taken']);
      $changedemail = 1;
    }


    $acceptpms = isset($_POST['acceptpms']) && in_array($_POST['acceptpms'], array('yes', 'friends', 'no'), true) ? $_POST['acceptpms'] : 'yes';
    $deletepms = isset($_POST["deletepms"]) ? "yes" : "no";
    $savepms = (isset($_POST['savepms']) && $_POST["savepms"] != "" ? "yes" : "no");
    $pmnotif = isset($_POST["pmnotif"]) ? $_POST["pmnotif"] : '';
    $emailnotif = isset($_POST["emailnotif"]) ? $_POST["emailnotif"] : '';
    $notifs = ($pmnotif == 'yes' ? "[pm]" : "");
    $notifs .= ($emailnotif == 'yes' ? "[email]" : "");
    $r = mysql_query("SELECT id FROM categories") or sqlerr();
    $rows = mysql_num_rows($r);
    for ($i = 0; $i < $rows; ++$i)
    {
      $a = mysql_fetch_assoc($r);
      if (isset($_POST["cat{$a['id']}"]) && $_POST["cat{$a['id']}"] == 'yes')
        $notifs .= "[cat{$a['id']}]";
    }

    /////// avatar policy during the migration
    $avatars = (isset($_POST['avatars']) ? "yes" : "no");
    $avatar = trim(rawurldecode(isset($_POST['avatar']) ? (string) $_POST['avatar'] : ''));
    $existing_avatar = (string) $CURUSER['avatar'];
    if ($avatar !== '' && $avatar !== $existing_avatar)
      stderr($lang['takeprofedit_user_error'], 'Remote avatar URLs are disabled. Use an approved local avatar upload.');
    if ($avatar === '')
    {
      $updateset[] = "av_w = 0";
      $updateset[] = "av_h = 0";
    }
    /////////////// avatar end /////////////////

    // $ircnick = $_POST["ircnick"];
    // $ircpass = $_POST["ircpass"];
    $info = strlen($_POST["info"]) <= 1500 ? $_POST['info'] : stderr('User Error', ':p');
    $stylesheet = $_POST["stylesheet"];
    $country = $_POST["country"];

    if(isset($_POST["user_timezone"]) && preg_match('#^\-?\d{1,2}(?:\.\d{1,2})?$#', $_POST['user_timezone']))
    $updateset[] = "time_offset = " . sqlesc($_POST['user_timezone']);

    $updateset[] = "auto_correct_dst = " .(isset($_POST['checkdst']) ? 1 : 0);
    $updateset[] = "dst_in_use = " .(isset($_POST['manualdst']) ? 1 : 0);

    /*
    if ($privacy != "normal" && $privacy != "low" && $privacy != "strong")
      bark("whoops");

    $updateset[] = "privacy = '$privacy'";
    */

    $updateset[] = "torrentsperpage = " . max(0, min(100, (int) ($_POST['torrentsperpage'] ?? 0)));
    $updateset[] = "topicsperpage = " . max(0, min(100, (int) ($_POST['topicsperpage'] ?? 0)));
    $updateset[] = "postsperpage = " . max(0, min(100, (int) ($_POST['postsperpage'] ?? 0)));

    if (is_valid_id($stylesheet))
      $updateset[] = "stylesheet = '$stylesheet'";
      
    if (is_valid_id($country))
      $updateset[] = "country = $country";


    $updateset[] = "info = " . sqlesc($info);
    $updateset[] = "acceptpms = " . sqlesc($acceptpms);
    $updateset[] = "deletepms = '$deletepms'";
    $updateset[] = "savepms = '$savepms'";
    $updateset[] = "notifs = '$notifs'";
    $updateset[] = "avatar = " . sqlesc($avatar);
    $updateset[] = "avatars = '$avatars'";

    /* ****** */

    $urladd = "";

    if ($changedemail) {
      $sec = mksecret();
      $hash = md5($sec . $email . $sec);
      $obemail = urlencode($email);
      $updateset[] = "editsecret = " . sqlesc($sec);
      //$thishost = $_SERVER["HTTP_HOST"];
      //$thisdomain = preg_replace('/^www\./is', "", $thishost);
      
      $body = str_replace(array('<#USERNAME#>', '<#SITENAME#>', '<#USEREMAIL#>', '<#IP_ADDRESS#>', '<#CHANGE_LINK#>'),
                        array($CURUSER['username'], $TBDEV['site_name'], $email, $_SERVER['REMOTE_ADDR'], "{$TBDEV['baseurl']}/confirmemail.php?uid={$CURUSER['id']}&key=$hash&email=$obemail"),
                        $lang['takeprofedit_email_body']);
      
      
      mail($email, "{$TBDEV['site_name']} {$lang['takeprofedit_confirm']}", $body, "From: {$TBDEV['site_email']}");

      $urladd .= "&mailsent=1";
    }

    @mysql_query("UPDATE users SET " . implode(", ", $updateset) . " WHERE id = " . $CURUSER["id"]) or sqlerr(__FILE__,__LINE__);

    header("Location: {$TBDEV['baseurl']}/my.php?edited=1" . $urladd);

/////////////////////////////////
//worker function
 /////////////////////////////////
function resize_image($in)
    {

        $out = array(
                'img_width'  => $in['cur_width'],
                'img_height' => $in['cur_height']
              );
        
        if ( $in['cur_width'] > $in['max_width'] )
        {
          $out['img_width']  = $in['max_width'];
          $out['img_height'] = ceil( ( $in['cur_height'] * ( ( $in['max_width'] * 100 ) / $in['cur_width'] ) ) / 100 );
          $in['cur_height'] = $out['img_height'];
          $in['cur_width']  = $out['img_width'];
        }
        
        if ( $in['cur_height'] > $in['max_height'] )
        {
          $out['img_height']  = $in['max_height'];
          $out['img_width']   = ceil( ( $in['cur_width'] * ( ( $in['max_height'] * 100 ) / $in['cur_height'] ) ) / 100 );
        }
        
      
        return $out;
    }

?>