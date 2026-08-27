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
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

//require_once "include/bittorrent.php";
require_once "include/user_functions.php";
require_once "include/bbcode_functions.php";
require_once "include/html_functions.php";

security_session_start();
$news_csrf = security_csrf_token('admin-news');

    $lang = array_merge( $lang, load_language('ad_news') );
    
    $input = array_merge( $_GET, $_POST);

    $mode = isset($input['mode']) ? $input['mode'] : '';

    $warning = '';
    
    $HTMLOUT = '';
    
        // Update NEws dates to rejuvenate /////////////////////////////

    if ('update' === $mode)
    {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'admin-news'))
      {
        http_response_code(400);
        exit('Invalid news update request.');
      }
      if (!isset($_POST['news_update']) || !is_array($_POST['news_update']) || count($_POST['news_update']) > 100)
        stderr('Error', 'No data!');

      $newsIDS = array();
      foreach ($_POST['news_update'] as $value)
      {
        if (is_array($value) || !is_valid_id($value))
          stderr('Error', 'No news ID');
        $newsIDS[] = (int) $value;
      }
      $news = implode(',', array_unique($newsIDS));
      mysql_query('UPDATE news SET added = ' . TIME_NOW . ' WHERE id IN (' . $news . ')') or sqlerr(__FILE__, __LINE__);
      header("Location: {$TBDEV['baseurl']}/admin.php?action=news");
      exit;
    }
	
    
    //   Delete News Item    //////////////////////////////////////////////////////
    if ($mode === 'delete')
    {
      $newsid = isset($input['newsid']) && !is_array($input['newsid']) ? (int) $input['newsid'] : 0;
      if (!is_valid_id($newsid))
        stderr($lang['news_error'], sprintf($lang['news_gen_error'], 1));

      $sure = isset($_POST['sure']) && !is_array($_POST['sure']) ? (int) $_POST['sure'] : 0;
      if (!$sure)
      {
        $confirm = sprintf($lang['news_delete_text'], $newsid);
        $confirm .= "<form method='post' action='admin.php?action=news'>
          <input type='hidden' name='mode' value='delete' />
          <input type='hidden' name='newsid' value='" . (int) $newsid . "' />
          <input type='hidden' name='sure' value='1' />
          <input type='hidden' name='csrf_token' value='" . htmlsafechars($news_csrf) . "' />
          <input type='submit' value='Confirm' class='btn' />
        </form>";
        stderr($lang['news_delete_notice'], $confirm);
      }
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'admin-news'))
      {
        http_response_code(400);
        exit('Invalid news deletion request.');
      }

      mysql_query('DELETE FROM news WHERE id = ' . (int) $newsid) or sqlerr(__FILE__, __LINE__);
      header("Location: {$TBDEV['baseurl']}/admin.php?action=news");
      exit;
    }


    //   Add News Item    /////////////////////////////////////////////////////////
    if ($mode === 'add')
    {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'admin-news'))
      {
        http_response_code(400);
        exit('Invalid news creation request.');
      }
      $body = isset($_POST['body']) && is_string($_POST['body']) ? trim($_POST['body']) : '';
      if ($body === '' || strlen($body) < 4 || strlen($body) > 200000)
        stderr($lang['news_error'], $lang['news_add_body']);
      $subject = isset($_POST['subject']) && is_string($_POST['subject']) ? trim($_POST['subject']) : '';
      if (strlen($subject) > 255)
        stderr($lang['news_error'], $lang['news_add_body']);
      $headline = $subject !== '' ? $subject : 'TBDev.net News';
      $added = isset($_POST['added']) && is_scalar($_POST['added']) && preg_match('/\A\d{1,10}\z/', (string) $_POST['added'])
        ? (int) $_POST['added'] : TIME_NOW;
      mysql_query('INSERT INTO news (userid, added, body, headline) VALUES (' . (int) $CURUSER['id'] . ', ' . $added . ', ' . sqlesc($body) . ', ' . sqlesc($headline) . ')') or sqlerr(__FILE__, __LINE__);
      if (mysql_affected_rows() == 1)
        $warning = $lang['news_add_ok'];
      else
        stderr($lang['news_error'], $lang['news_add_err']);
    }

    
    //   Edit News Item    ////////////////////////////////////////////////////////
    if ($mode == 'edit')
    {

      $newsid = isset($input["newsid"]) ? (int)$input["newsid"] : 0;

      if (!is_valid_id($newsid))
        stderr($lang['news_error'], sprintf($lang['news_gen_error'],2));

      $res = @mysql_query("SELECT * FROM news WHERE id=$newsid") or sqlerr(__FILE__, __LINE__);

      if (mysql_num_rows($res) != 1)
        stderr($lang['news_error'], $lang['news_edit_nonewsid']);

      $arr = mysql_fetch_assoc($res);

      if ($_SERVER['REQUEST_METHOD'] === 'POST')
      {
        if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'admin-news'))
        {
          http_response_code(400);
          exit('Invalid news edit request.');
        }
        $body = isset($_POST['body']) && is_string($_POST['body']) ? trim($_POST['body']) : '';
        if ($body === '' || strlen($body) < 4 || strlen($body) > 200000)
          stderr($lang['news_error'], $lang['news_add_body']);
        $subject = isset($_POST['subject']) && is_string($_POST['subject']) ? trim($_POST['subject']) : '';
        if (strlen($subject) > 255)
          stderr($lang['news_error'], $lang['news_add_body']);
        $headline = $subject !== '' ? $subject : 'TBDev.net News';
        mysql_query('UPDATE news SET body=' . sqlesc($body) . ', headline=' . sqlesc($headline) . ' WHERE id=' . (int) $newsid) or sqlerr(__FILE__, __LINE__);
        header("Location: {$TBDEV['baseurl']}/admin.php?action=news");
        exit;
      }
      else
      {
        //$returnto = isset($_POST['returnto']) ? htmlsafechars($_POST['returnto']) : $TBDEV['baseurl'].'/news.php';
        $js = "<script type='text/javascript' src='scripts/bbcode2text.js'></script>";
        $title = htmlsafechars($arr['headline']);
    $HTMLOUT .= "
                 <div class='cblock'>
                 <div class='cblock-header'>{$lang['news_edit_title']}</div>
                 <div class='cblock-lb'></div>
                 <div class='cblock-content'>

        <form id='bbcode2text' method='post' action='admin.php?action=news'>
        
        <input type='hidden' name='newsid' value='" . (int) $newsid . "' />
        <input type='hidden' name='csrf_token' value='" . htmlsafechars($news_csrf) . "' />
        <input type='hidden' name='mode' value='edit' />
        <div align='center'>
           <input style='width:615px;' type='text' name='subject' size='50' value='{$title}' />
        </div>";
        
        $HTMLOUT .= bbcode2textarea( 'body', $arr['body'] );
          
        
        $HTMLOUT .= "<div align='center'>
                <input type='submit' name='newsedit' value='Edit' class='' />
             </div></form>
      </div></div>";

      print  stdhead($lang['news_edit_title'], $js) . $HTMLOUT . stdfoot();
        exit();
      }
    }

    
    
    //   Other Actions and followup    ////////////////////////////////////////////
    $HTMLOUT .= "
                 <div class='cblock'>
                     <div class='cblock-header'>{$lang['news_submit_title']}</div>";
    $HTMLOUT .= "    <div class='cblock-lb'>";
    if (!empty($warning))
    {
      $HTMLOUT .= "<p style='font-size:-3px;'>($warning)</p>";
    }
    $HTMLOUT .= "    </div>
                     <div class='cblock-content'>";

    $HTMLOUT .= "<form id='bbcode2text' method='post' action='admin.php?action=news'>
                      <input type='hidden' name='mode' value='add' />
                      <input type='hidden' name='csrf_token' value='" . htmlsafechars($news_csrf) . "' />";
    
    $js = "<script type='text/javascript' src='scripts/bbcode2text.js'></script>";
        
    $HTMLOUT .= "<div align='center'>
                <input style='width:615px;' type='text' name='subject' size='50' value='' />
             </div>";
    $HTMLOUT .= bbcode2textarea( 'body' );
    $HTMLOUT .= "<div align='center'>
                <input type='submit' name='postquickreply' value='Add' class='' />
             </div>
    </form><br /><br />";

    $res = @mysql_query("SELECT * FROM news ORDER BY added DESC") or sqlerr(__FILE__, __LINE__);

    if (mysql_num_rows($res) > 0)
    {
      $HTMLOUT .= begin_main_frame();
      $HTMLOUT .= "      <form method='post' action='admin.php?action=news'>
      <input type='hidden' name='mode' value='update' />
      <input type='hidden' name='csrf_token' value='" . htmlsafechars($news_csrf) . "' />";

      while ($arr = mysql_fetch_assoc($res))
      {
        $newsid = (int) $arr['id'];
        $body = format_comment($arr['body']);
        $headline = htmlsafechars($arr['headline']);
        $userid = (int) $arr['userid'];
        $added = get_date( $arr['added'],'');

        $res2 = @mysql_query("SELECT username, donor FROM users WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
        $arr2 = mysql_fetch_assoc($res2);

          $postername = isset($arr2['username']) ? htmlsafechars($arr2['username']) : '';

        if ($postername == "")
          $by = "unknown[$userid]";
        else
          $by = "<a href='userdetails.php?id=$userid'><b>$postername</b></a>" .
            ($arr2["donor"] == "yes" ? "<img src=\"{$TBDEV['pic_base_url']}star.gif\" alt='Donor' />" : "");
            
        $HTMLOUT .= begin_frame();    
        $HTMLOUT .= begin_table(true);
        $HTMLOUT .= "
        <tr>
          <td class='colhead'>$headline<span style='float:right;'><input type='checkbox' name='news_update[]' value='$newsid' /></span></td>
        </tr>
        <tr>
          <td>{$added}&nbsp;&nbsp;by&nbsp;$by
            <div style='float:right;'><a href='admin.php?action=news&amp;mode=edit&amp;newsid=$newsid'><span class='btn'>{$lang['news_act_edit']}</span></a>&nbsp;<a href='admin.php?action=news&amp;mode=delete&amp;newsid=$newsid'><span class='btn'>{$lang['news_act_delete']}</span></a>
            </div>
          </td>
        </tr>
        <tr valign='top'>
          <td class='comment'>$body</td>
        </tr>\n";
        
        $HTMLOUT .= end_table();
        $HTMLOUT .= end_frame();
        $HTMLOUT .= "<div class='clear'>&nbsp;</div>";
      }
      
       $HTMLOUT .= "<div style='text-align:right;'><input name='submit' type='submit' value='Update' class='btn' /></div></form>";
       $HTMLOUT .= end_main_frame();


    }
    else
      stdmsg($lang['news_sorry'], $lang['news_nonews']);

      $HTMLOUT .= "
      </div></div>";

    print stdhead($lang['news_window_title'], $js) . $HTMLOUT . stdfoot();
    die;
?>