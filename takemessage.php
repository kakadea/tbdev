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
$message_csrf = security_csrf_token('messages');
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
  stderr('Error', 'Method');

dbconn();

  loggedinorreturn();
  if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'messages'))
  {
    http_response_code(400);
    exit('Invalid message request.');
  }
  if (!security_rate_limit('message-send', security_client_identity() . '|' . (int) $CURUSER['id'], 30, 300))
  {
    http_response_code(429);
    exit('Too many messages. Please try again later.');
  }

  $lang = array_merge(load_language('global'), load_language('takemessage'));
  
  function ratios($up, $down)
  {
  global $lang;
  if ($down > 0)
    {
      $ratio = number_format($up / $down, 3);
      return "<font color='" . get_ratio_color($ratio) . "'>$ratio</font>";
    }
    else
    {
      if ($up > 0)
        return $lang['takemessage_inf'];
      else
        return "---";
    }
    return;
  }
  
  $n_pms = isset($_POST['n_pms']) && !is_array($_POST['n_pms']) ? (int) $_POST['n_pms'] : 0;
  if ($n_pms > 0)
  {  			                                                      //////  MM  ///
    if ($CURUSER['class'] < UC_MODERATOR)
	  stderr($lang['takemessage_error'], $lang['takemessage_denied']);

    $msg = isset($_POST['msg']) && is_string($_POST['msg']) ? trim($_POST['msg']) : '';
    if ($msg === '')
      stderr($lang['takemessage_error'], $lang['takemessage_something']);
    if (strlen($msg) > 50000)
      stderr($lang['takemessage_error'], 'Message is too long.');

    $subject = isset($_POST['subject']) && is_string($_POST['subject']) ? trim(strip_tags($_POST['subject'])) : '';
    if (strlen($subject) > 120)
      stderr($lang['takemessage_error'], 'Subject is too long.');

    $sender_value = isset($_POST['sender']) && is_string($_POST['sender']) ? $_POST['sender'] : 'self';
    if (!in_array($sender_value, array('self', 'system'), true))
      stderr($lang['takemessage_error'], $lang['takemessage_denied']);
    $sender_id = ($sender_value === 'system' ? 0 : (int) $CURUSER['id']);

    $recipient_ids = array();
    $recipient_input = isset($_POST['pmees']) && is_string($_POST['pmees']) ? $_POST['pmees'] : '';
    foreach (explode(':', $recipient_input) as $value)
    {
      if (ctype_digit($value) && is_valid_id((int) $value)
          && !in_array((int) $value, $recipient_ids, true))
        $recipient_ids[] = (int) $value;
    }
    if (!$recipient_ids || count($recipient_ids) > 500)
      stderr($lang['takemessage_error'], $lang['takemessage_id']);
    $from_is = 'FROM users u WHERE u.id IN (' . implode(', ', $recipient_ids) . ')';

    $query = "INSERT INTO messages (sender, receiver, added, msg, subject, location, poster) ".
             "SELECT $sender_id, u.id, " . TIME_NOW . ", " . sqlesc($msg) .
             ", ". sqlesc($subject).", 1, $sender_id " . $from_is;

    mysql_query($query) or sqlerr(__FILE__, __LINE__);
    $n = mysql_affected_rows();

    $comment = isset($_POST['comment']) && is_string($_POST['comment']) ? trim($_POST['comment']) : '';
    if (strlen($comment) > 10000)
      stderr($lang['takemessage_error'], 'Comment is too long.');
    $snapshot = isset($_POST['snap']) && (string) $_POST['snap'] === '1';

    // add a custom text or stats snapshot to comments in profile
    if ($comment || $snapshot)
    {
	    $res = mysql_query("SELECT u.id, u.uploaded, u.downloaded, u.modcomment ".$from_is) or sqlerr(__FILE__, __LINE__);
	    if (mysql_num_rows($res) > 0)
	    {
	      $l = 0;
	      while ($user = mysql_fetch_assoc($res))
	      {
	        unset($new);
	        $new = '';
	        $old = $user['modcomment'];
	        if ($comment)
	          $new .= $comment;
	        if ($snapshot)
	        {
              
            $new .= ($new ? "\n" : '') .
                    "{$lang['takemessage_mmed']}, " . gmdate('Y-m-d') . ", " .
	            "{$lang['takemessage_ul']}: " . mksize($user['uploaded']) . ", " .
	            "{$lang['takemessage_dl']}: " . mksize($user['downloaded']) . ", " .
	            "{$lang['takemessage_r']}: " . ratios($user['uploaded'],$user['downloaded']) . " - " .
                ($sender_value === 'system' ? $lang['takemessage_System'] : $CURUSER['username']);
	        }
	      	$new .= $old?("\n".$old):$old;
		      mysql_query("UPDATE users SET modcomment = " . sqlesc($new) . " WHERE id = " . $user['id'])
		        or sqlerr(__FILE__, __LINE__);
	  	    if (mysql_affected_rows())
	    	    $l++;
	      }
	    }
    }
  }
  else
  {               																							//////  PM  ///
    $receiver = isset($_POST['receiver']) && !is_array($_POST['receiver']) ? (int) $_POST['receiver'] : 0;
			$origmsg = isset($_POST['origmsg']) && !is_array($_POST['origmsg']) ? (int) $_POST['origmsg'] : 0;
			$save = isset($_POST['save']) && is_string($_POST['save']) ? $_POST['save'] : '';
			$returnto = security_validate_return_to(isset($_POST['returnto']) ? $_POST['returnto'] : '', $TBDEV['baseurl'] . '/messages.php');

	  if (!is_valid_id($receiver) || ($origmsg && !is_valid_id($origmsg)))
	  	stderr($lang['takemessage_error'], $lang['takemessage_id']);

	  $msg = isset($_POST['msg']) && is_string($_POST['msg']) ? trim($_POST['msg']) : '';
	  if ($msg === '')
	    stderr($lang['takemessage_error'], $lang['takemessage_something']);
	  if (strlen($msg) > 50000)
	    stderr($lang['takemessage_error'], 'Message is too long.');

	  $save = ($save === 'yes') ? 'yes' : 'no';

	  $res = mysql_query("SELECT acceptpms, email, notifs, last_access as la FROM users WHERE id=$receiver") or sqlerr(__FILE__, __LINE__);
	  $user = mysql_fetch_assoc($res);
	  if (!$user)
	    stderr($lang['takemessage_error'], $lang['takemessage_no_user']);

	  //Make sure recipient wants this message
		if ($CURUSER['class'] < UC_MODERATOR)
		{
    	if ($user["acceptpms"] == "yes")
	    {
	      $res2 = mysql_query("SELECT * FROM blocks WHERE userid=$receiver AND blockid=" . $CURUSER["id"]) or sqlerr(__FILE__, __LINE__);
	      if (mysql_num_rows($res2) == 1)
	        stderr($lang['takemessage_refused'], $lang['takemessage_blocked']);
	    }
	    elseif ($user["acceptpms"] == "friends")
	    {
	      $res2 = mysql_query("SELECT * FROM friends WHERE userid=$receiver AND friendid=" . $CURUSER["id"]) or sqlerr(__FILE__, __LINE__);
	      if (mysql_num_rows($res2) != 1)
	        stderr($lang['takemessage_refused'], $lang['takemessage_friends']);
	    }
	    elseif ($user["acceptpms"] == "no")
	      stderr($lang['takemessage_refused'], $lang['takemessage_no_pms']);
	  }

	  $subject = isset($_POST['subject']) && is_string($_POST['subject']) ? trim(strip_tags($_POST['subject'])) : '';
    if (strlen($subject) > 120)
      stderr($lang['takemessage_error'], 'Subject is too long.');
    
    mysql_query("INSERT INTO messages (poster, sender, receiver, added, msg, subject, saved, location) VALUES(" . $CURUSER["id"] . ", " . $CURUSER["id"] . ", $receiver, " . TIME_NOW . ", " . sqlesc($msg) . ", " . sqlesc($subject) . ", " . sqlesc($save) . ", 1)") or sqlerr(__FILE__, __LINE__);

	  if (strpos($user['notifs'], '[pm]') !== false)
	  {
	    if (TIME_NOW - $user["la"] >= 300)
	    {
	    $username = $CURUSER["username"];
$body = <<<EOD
You have received a PM from $username!

You can use the URL below to view the message (you may have to login).

{$TBDEV['baseurl']}/messages.php

--
{$TBDEV['site_name']}
EOD;
	    @mail($user["email"], "{$lang['takemessage_received']} " . $username . "!",
	    	$body, "{$lang['takemessage_from']} {$TBDEV['site_email']}");
	    }
	  }
	  $delete = isset($_POST['delete']) && is_string($_POST['delete']) ? $_POST['delete'] : '';

	  if ($origmsg)
	  {
      if ($delete == "yes")
      {
	      // Make sure receiver of $origmsg is current user
	      $res = mysql_query("SELECT * FROM messages WHERE id=$origmsg") or sqlerr(__FILE__, __LINE__);
	      if (mysql_num_rows($res) == 1)
	      {
	        $arr = mysql_fetch_assoc($res);
	        if ($arr["receiver"] != $CURUSER["id"])
	          stderr($lang['takemessage_woot'], $lang['takemessage_happen']);
	        if ($arr["saved"] == "no")
            mysql_query("DELETE FROM messages WHERE id=$origmsg") or sqlerr(__FILE__, __LINE__);
          elseif ($arr["saved"] == "yes")
            mysql_query("UPDATE messages SET location = '0' WHERE id=$origmsg") or sqlerr(__FILE__, __LINE__);
	      }
      }
   	  if (!$returnto)
   	  	$returnto = "{$TBDEV['baseurl']}/messages.php";
	  }

    header('Location: ' . security_validate_return_to($returnto, $TBDEV['baseurl'] . '/messages.php'));
    exit;

	 
	} 
	//stdhead();
	$l = (isset($l)?$l:'');
	  stderr($lang['takemessage_succeed'], (($n_pms > 1) ? "$n {$lang['takemessage_out_of']} $n_pms {$lang['takemessage_were']}" : "{$lang['takemessage_msg_was']}").
	    " {$lang['takemessage_sent']}" . ($l ? " $l {$lang['takemessage_comment']}" . (($l>1) ? "{$lang['takemessage_s_were']}" : " {$lang['takemessage_was']}") . " {$lang['takemessage_updated']}" : ""));
	//stdfoot();
	exit;
?>