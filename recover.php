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
  require_once __DIR__ . '/captcha/functions.php';

  ini_set('session.use_trans_sid', '0');
  if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

  function recover_csrf_token()
  {
    if (empty($_SESSION['recover_csrf']))
      $_SESSION['recover_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['recover_csrf'];
  }

  function recover_h($value)
  {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
  }

  function recover_token($id, $editsecret, $email)
  {
    $key = (string) ($GLOBALS['TBDEV']['site_key'] ?? '');
    if ($key === '')
      stderr('Recovery unavailable', 'The recovery signing key is not configured.');

    return hash_hmac('sha256', (int) $id . '|' . (string) $editsecret . '|' . (string) $email, $key);
  }

  function recover_validate_token($id, $token, $row)
  {
    if (!is_string($token) || !preg_match('/\A[a-f-f0-9]{64}\z/i', $token))
      return false;
    if (empty($row['editsecret']) || (int) $row['recovery_expires'] < TIME_NOW)
      return false;

    return hash_equals(recover_token($id, $row['editsecret'], $row['email']), strtolower($token));
  }
    
  dbconn();

  $lang = array_merge(load_language('global'), load_language('recover'));
  $csrf = recover_csrf_token();
  $reset_id = 0;
  $reset_token = '';
  $reset_row = null;

  if ($_SERVER['REQUEST_METHOD'] === 'POST')
  {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : 'request';
    $posted_csrf = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    if (!hash_equals($csrf, $posted_csrf))
      stderr($lang['stderr_errorhead'], 'Invalid or expired request. Please try again.');

    if ($action === 'reset')
    {
      $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
      $token = isset($_POST['secret']) ? trim((string) $_POST['secret']) : '';
      $newpassword = isset($_POST['password']) ? (string) $_POST['password'] : '';
      $password_confirm = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';

      if (!is_valid_id($id))
        httperr();

      $res = mysql_query("SELECT email, editsecret, recovery_expires FROM users WHERE id = " . $id . " LIMIT 1") or sqlerr();
      $token_row = mysql_fetch_assoc($res);
      if (!$token_row || !recover_validate_token($id, $token, $token_row))
        httperr();

      if (strlen($newpassword) < 10 || strlen($newpassword) > 200)
        stderr($lang['stderr_errorhead'], 'Choose a password between 10 and 200 characters.');
      if (!hash_equals($newpassword, $password_confirm))
        stderr($lang['stderr_errorhead'], 'The passwords do not match.');

      $modernpasshash = password_hash($newpassword, PASSWORD_DEFAULT);
      if ($modernpasshash === false)
        stderr($lang['stderr_errorhead'], 'Unable to create a secure password hash.');

      $new_secret = mksecret();
      $legacy_passhash = make_passhash($new_secret, md5($newpassword));
      $update = mysql_query(
        "UPDATE users SET secret = " . sqlesc($new_secret) .
        ", editsecret = '', recovery_expires = 0, passhash = " . sqlesc($legacy_passhash) .
        ", password_hash = " . sqlesc($modernpasshash) .
        " WHERE id = " . $id . " AND editsecret = " . sqlesc($token_row['editsecret']) .
        " AND recovery_expires = " . (int) $token_row['recovery_expires']
      ) or sqlerr();

      if (!mysql_affected_rows())
        stderr($lang['stderr_errorhead'], $lang['stderr_noupdate']);

      unset($_SESSION['recover_csrf']);
      stderr($lang['stderr_successhead'], 'Your password was reset. You can now sign in with the new password.');
    }

    if ($TBDEV['captcha'])
    {
      if (!captcha_validate_answer(isset($_POST['captcha']) ? $_POST['captcha'] : null) || !isset($_SESSION['captcha_time']) || TIME_NOW - (int) $_SESSION['captcha_time'] < 10)
        stderr($lang['stderr_errorhead'], 'Invalid captcha. Please try again.');
      unset($_SESSION['captcha_id'], $_SESSION['captcha_time']);
    }

    $email = trim(isset($_POST['email']) ? (string) $_POST['email'] : '');
    if (!validemail($email))
      stderr($lang['stderr_errorhead'], $lang['stderr_invalidemail']);

    $res = mysql_query("SELECT id, username, email FROM users WHERE email = " . sqlesc($email) . " LIMIT 1") or sqlerr();
    $arr = mysql_fetch_assoc($res) or stderr($lang['stderr_errorhead'], $lang['stderr_notfound']);

    $sec = bin2hex(random_bytes(16));
    $expires = TIME_NOW + 1800;
    mysql_query(
      "UPDATE users SET editsecret = " . sqlesc($sec) . ", recovery_expires = " . $expires . " WHERE id = " . (int) $arr['id']
    ) or sqlerr();

    if (!mysql_affected_rows())
      stderr($lang['stderr_errorhead'], $lang['stderr_dberror']);

    $reset_url = rtrim($TBDEV['baseurl'], '/') . '/recover.php?id=' . (int) $arr['id'] . '&secret=' . recover_token($arr['id'], $sec, $arr['email']);
    $body = "A password reset was requested for the account associated with this email address (" . $arr['email'] . ").\n\n" .
      "If you made this request, open the following link within 30 minutes to choose a new password:\n\n" .
      $reset_url . "\n\n" .
      "If you did not request this, ignore this email. No password has been changed.\n\n--";
    $headers = 'From: ' . $TBDEV['site_email'] . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';

    @mail($arr['email'], $TBDEV['site_name'] . ' ' . $lang['email_subjreset'], $body, $headers) or stderr($lang['stderr_errorhead'], $lang['stderr_nomail']);
    stderr($lang['stderr_successhead'], $lang['stderr_confmailsent']);
  }
  elseif (isset($_GET['id']) && isset($_GET['secret']))
  {
    $reset_id = (int) $_GET['id'];
    $reset_token = trim((string) $_GET['secret']);

    if (!is_valid_id($reset_id))
      httperr();

    $res = mysql_query("SELECT username, email, editsecret, recovery_expires FROM users WHERE id = " . $reset_id . " LIMIT 1") or sqlerr();
    $reset_row = mysql_fetch_assoc($res) or httperr();
    if (!recover_validate_token($reset_id, $reset_token, $reset_row))
      httperr();
  }
  else
  {
    if (isset($_SESSION['captcha_time']) && TIME_NOW - (int) $_SESSION['captcha_time'] < 10)
      exit($lang['captcha_spam']);
  }

  $HTMLOUT = '';
  $js = '';

  if ($reset_row)
  {
    $HTMLOUT .= "
                   <div class='cblock'>
                       <div class='cblock-header'>Reset password</div>
                       <div class='cblock-content'>
                           <div class='inner_header'>Choose a new password for " . recover_h($reset_row['username']) . "</div>
                           <form method='post' action='recover.php'>
                               <input type='hidden' name='action' value='reset' />
                               <input type='hidden' name='csrf_token' value='" . recover_h($csrf) . "' />
                               <input type='hidden' name='id' value='" . (int) $reset_id . "' />
                               <input type='hidden' name='secret' value='" . recover_h($reset_token) . "' />
                               <table border='1' cellspacing='0' cellpadding='10'>
                                   <tr>
                                       <td class='rowhead'>New password</td>
                                       <td><input type='password' minlength='10' maxlength='200' name='password' autocomplete='new-password' required /></td>
                                   </tr>
                                   <tr>
                                       <td class='rowhead'>Confirm password</td>
                                       <td><input type='password' minlength='10' maxlength='200' name='password_confirm' autocomplete='new-password' required /></td>
                                   </tr>
                                   <tr>
                                       <td colspan='2' align='center'><input type='submit' value='Reset password' class='btn' /></td>
                                   </tr>
                               </table>
                           </form>
                       </div>
                   </div>";
  }
  else
  {
    $HTMLOUT .= "
                   <div class='cblock'>
                       <div class='cblock-header'>{$lang['recover_unamepass']}</div>
                       <div class='cblock-content'>
                           <div class='inner_header'>{$lang['recover_form']}</div>
                           <form method='post' action='recover.php'>
                               <input type='hidden' name='action' value='request' />
                               <input type='hidden' name='csrf_token' value='" . recover_h($csrf) . "' />
                               <table border='1' cellspacing='0' cellpadding='10'>";

    if ($TBDEV['captcha'])
    {
      $js = "<script type='text/javascript' src='captcha/captcha.js'></script>";
      $HTMLOUT .= "                   <tr>
                                         <td>&nbsp;</td>
                                         <td>
                                            <div id='captchaimage'>
                                                <a href='recover.php' onclick=\"refreshimg(); return false;\" title='" . recover_h($lang['captcha_refresh']) . "'>
                                                  <img class='cimage' src='captcha/GD_Security_image.php?" . TIME_NOW . "' alt='" . recover_h($lang['captcha_imagealt']) . "' />
                                                </a>
                                            </div>
                                         </td>
                                      </tr>
                                      <tr>
                                         <td class='rowhead'>" . recover_h($lang['captcha_pin']) . "</td>
                                         <td><input type='text' maxlength='6' name='captcha' id='captcha' autocomplete='off' required /></td>
                                      </tr>";
    }

    $HTMLOUT .= "
                                      <tr>
                                         <td class='rowhead'>" . recover_h($lang['recover_regdemail']) . "</td>
                                         <td><input type='email' size='40' name='email' autocomplete='email' required /></td>
                                      </tr>
                                      <tr>
                                         <td colspan='2' align='center'><input type='submit' value='" . recover_h($lang['recover_btn']) . "' class='btn' /></td>
                                      </tr>
                                </table>
                           </form>
                       </div>
                   </div>";
  }

  print stdhead($lang['head_recover'], $js) . $HTMLOUT . stdfoot();
?>
