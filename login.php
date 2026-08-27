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
require_once "include/bittorrent.php" ;

    security_session_start();
    $csrf = security_csrf_token('login');
    $lang = array_merge(load_language('global'), load_language('login'));
    
    $HTMLOUT = '';
    $js = '';

    if ($TBDEV['captcha'])
    {
      if (isset($_SESSION['captcha_time']) && TIME_NOW - (int) $_SESSION['captcha_time'] < 10)
        exit($lang['login_spam']);

      $js = "<script type='text/javascript' src='captcha/captcha.js'></script>";
    }
    
    $returnto = null;
    if (!empty($_GET['returnto'])) {
      $returnto = security_validate_return_to($_GET['returnto'], $TBDEV['baseurl'] . '/my.php');
      if (!isset($_GET['nowarn']))
      {
        $HTMLOUT .= "<div class='info'>{$lang['login_not_logged_in']}</div><br />\n";
        $HTMLOUT .= "<div class='error'>{$lang['login_error']}</div>";
      }
    }


    $HTMLOUT .= "
                <form method='post' action='takelogin.php'>
                     <input type='hidden' name='csrf_token' value='" . htmlsafechars($csrf) . "' />
                     <div class='cblock'>
                         <div class='cblock-header'>Login</div>
                         <div class='cblock-lb'>Note: You need cookies enabled to log in.</div>
                         <div class='cblock-content'>
                             <table border='0' cellpadding='5'>
                                   <tr>
                                      <td class='rowhead'>{$lang['login_username']}</td>
                                      <td align='left'><input type='text' size='40' name='username' autocomplete='username' required /></td>
                                   </tr>
                                   <tr>
                                      <td class='rowhead'>{$lang['login_password']}</td>
                                      <td align='left'><input type='password' size='40' name='password' autocomplete='current-password' required /></td>
                                   </tr>
                               <!--<tr><td class='rowhead'>{$lang['login_duration']}</td><td align='left'><input type='checkbox' name='logout' value='yes' checked='checked' />{$lang['login_15mins']}</td></tr>-->";

    if( $TBDEV['captcha'] )
    {
      $HTMLOUT .= "                <tr>
                                      <td>&nbsp;</td>
                                      <td>
                                         <div id='captchaimage'>
                                             <a href='login.php' onclick=\"refreshimg(); return false;\" title='{$lang['login_refresh']}'>
                                               <img class='cimage' src='captcha/GD_Security_image.php?".TIME_NOW."' alt='{$lang['login_captcha']}' />
                                             </a>
                                         </div>
                                      </td>
                                   </tr>
                                   <tr>
                                      <td class='rowhead'>{$lang['login_pin']}</td>
                                      <td><input type='text' maxlength='6' name='captcha' id='captcha' autocomplete='off' required /></td>
                                   </tr>";
    }

    $HTMLOUT .= "                  <tr>
                                      <td colspan='2' align='center'><input type='submit' value='{$lang['login_login']}' class='btn' /></td>
                                   </tr>
                             </table>";


    if (isset($returnto))
      $HTMLOUT .= "<input type='hidden' name='returnto' value='" . htmlsafechars($returnto) . "' />\n";

    $HTMLOUT .= "        </div>
                     </div>";

    $HTMLOUT .= "</form>";


    print stdhead($lang['login_login_btn'], $js) . $HTMLOUT . stdfoot();

?>