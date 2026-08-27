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
$csrf = security_csrf_token('email-gateway');
dbconn();

loggedinorreturn();

    $lang = array_merge( load_language('global'), load_language('email-gateway') );
    
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    
    if (!is_valid_id($id))
      stderr("{$lang['email_error']}", "{$lang['email_bad_id']}");

    $res = mysql_query("SELECT username, class, email FROM users WHERE id=$id");
    $arr = mysql_fetch_assoc($res) or stderr("{$lang['email_error']}", "{$lang['email_no_user']}");
    $username = $arr["username"];
    
    if ($arr["class"] < UC_MODERATOR)
      stderr("{$lang['email_error']}", "{$lang['email_email_staff']}");

    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
      if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'email-gateway'))
      {
        http_response_code(400);
        exit('Invalid email request.');
      }
      if (!security_rate_limit('email-gateway', security_client_identity() . '|' . (int) $CURUSER['id'], 5, 3600))
      {
        http_response_code(429);
        exit('Too many email requests. Please try again later.');
      }

      $to = filter_var($arr['email'], FILTER_VALIDATE_EMAIL);
      if ($to === false)
        stderr($lang['email_error'], $lang['email_failed']);

      $from = substr(trim((string) ($_POST['from'] ?? '')), 0, 80);
      if ($from === '') $from = $lang['email_anon'];
      $from = preg_replace('/[\x00-\x1F\x7F]/', '', $from);

      $from_email = substr(trim((string) ($_POST['from_email'] ?? '')), 0, 80);
      if ($from_email === '') $from_email = $TBDEV['site_email'];
      if (preg_match('/[\r\n]/', $from_email) || !filter_var($from_email, FILTER_VALIDATE_EMAIL))
        stderr($lang['email_error'], $lang['email_invalid']);

      $subject = substr(trim((string) ($_POST['subject'] ?? '')), 0, 80);
      $subject = preg_replace('/[\x00-\x1F\x7F]/', '', $subject);
      if ($subject === '') $subject = '(No subject)';
      $subject = 'Fw: ' . $subject;

      $message = trim((string) ($_POST['message'] ?? ''));
      if ($message === '') stderr($lang['email_error'], $lang['email_no_text']);
      if (strlen($message) > 10000) stderr($lang['email_error'], 'Message is too long.');

      $message = "Message submitted by {$from} from " . security_client_identity() . " at " . gmdate('Y-m-d H:i:s') . " GMT.\n" .
        "{$lang['email_note']}\n" .
        "---------------------------------------------------------------------\n\n" .
        $message . "\n\n" .
        "---------------------------------------------------------------------\n".
        "{$TBDEV['site_name']}{$lang['email_gateway']}\n";

      $success = mail($to, $subject, $message, "{$lang['email_from']}{$TBDEV['site_email']}");

      if ($success)
        stderr("{$lang['email_success']}", "{$lang['email_queued']}");
      else
        stderr("{$lang['email_error']}", "{$lang['email_failed']}");
    }

    $HTMLOUT = '';

    $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>{$lang['email_send']}&nbsp;to&nbsp;{$username}</div>
                         <div class='cblock-content'>
                             <form method='post' action='email-gateway.php?id=$id'>
                                  <input type='hidden' name='csrf_token' value='" . htmlsafechars($csrf) . "' />
                                  <table border='1' cellspacing='0' cellpadding='5'>
                                        <tr><td class='rowhead'>{$lang['email_your_name']}</td><td><input type='text' name='from' size='80' maxlength='80' autocomplete='name' /></td></tr>
                                        <tr><td class='rowhead'>{$lang['email_your_email']}</td><td><input type='email' name='from_email' size='80' maxlength='80' autocomplete='email' /></td></tr>
                                        <tr><td class='rowhead'>{$lang['email_subject']}</td><td><input type='text' name='subject' size='80' maxlength='80' required /></td></tr>
                                        <tr><td class='rowhead'>{$lang['email_message']}</td><td><textarea name='message' cols='80' rows='20' maxlength='10000' required></textarea></td></tr>
                                        <tr><td colspan='2' align='center'><input type='submit' value='{$lang['email_send']}' class='btn' /></td></tr>
                                  </table>
                             </form>
                             <div class='small' style='font-weight:bold;'>{$lang['email_note_ip']}{$lang['email_ip']}<br />{$lang['email_valid']}</div>";

    $HTMLOUT .= "                         </div>
                     </div>";

///////////////////////// HTML OUTPUT ////////////////////
    print stdhead("{$lang['email_gateway']}") . $HTMLOUT . stdfoot(); 
?>