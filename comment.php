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
require_once "include/html_functions.php";

$action = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : '';

security_session_start();
$comment_csrf = security_csrf_token('comment');
dbconn(false);


loggedinorreturn();

    $lang = array_merge( load_language('global'), load_language('comment') );
    
    if ($action == "add")
    {
      if ($_SERVER['REQUEST_METHOD'] === 'POST')
      {
        if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'comment'))
        {
          http_response_code(400);
          exit('Invalid comment request.');
        }
        if (!security_rate_limit('comment-add', security_client_identity() . '|' . (int) $CURUSER['id'], 20, 300))
        {
          http_response_code(429);
          exit('Too many comments. Please try again later.');
        }
        $torrentid = isset($_POST['tid']) && !is_array($_POST['tid']) ? (int) $_POST['tid'] : 0;
        if (!is_valid_id($torrentid))
          stderr("{$lang['comment_error']}", "{$lang['comment_invalid_id']}");

        $res = @mysql_query("SELECT name FROM torrents WHERE id = $torrentid") or sqlerr(__FILE__,__LINE__);
        $arr = mysql_fetch_array($res,MYSQL_NUM);
        if (!$arr)
          stderr("{$lang['comment_error']}", "{$lang['comment_invalid_torrent']}");

        $text = isset($_POST['body']) && is_string($_POST['body']) ? trim($_POST['body']) : '';
        if ($text === '')
          stderr($lang['comment_error'], $lang['comment_body']);
        if (strlen($text) > 20000)
          stderr($lang['comment_error'], 'Comment is too long.');

        @mysql_query("INSERT INTO comments (user, torrent, added, text, ori_text) VALUES (" .
            $CURUSER["id"] . ",$torrentid, " . TIME_NOW . ", " . sqlesc($text) .
             "," . sqlesc($text) . ")");

        $newid = mysql_insert_id();

        @mysql_query("UPDATE torrents SET comments = comments + 1 WHERE id = $torrentid");

        header("Refresh: 0; url=details.php?id=$torrentid&viewcomm=$newid#comm$newid");
        die;
      }

      $torrentid = isset($_GET['tid']) && !is_array($_GET['tid']) ? (int) $_GET['tid'] : 0;
      if (!is_valid_id($torrentid))
        stderr("{$lang['comment_error']}", "{$lang['comment_invalid_id']}");

      $res = mysql_query("SELECT name FROM torrents WHERE id = $torrentid") or sqlerr(__FILE__,__LINE__);
      $arr = mysql_fetch_assoc($res);
      if (!$arr)
        stderr("{$lang['comment_error']}", "{$lang['comment_invalid_torrent']}");
      
      $HTMLOUT = '';
      $js = "<script type='text/javascript' src='scripts/bbcode2text.js'></script>";
      
      $HTMLOUT .= "<div class='cblock'>
                    <div class='cblock-header'>{$lang['comment_add']}\"" . htmlsafechars($arr["name"]) . "\"</div>
                    <div class='cblock-content'>
                    <form name='bbcode2text' method='post' action='comment.php?action=add'>
                    <input type='hidden' name='csrf_token' value='" . htmlsafechars($comment_csrf) . "' />
                    <input type='hidden' name='tid' value='" . (int) $torrentid . "' />";
      $HTMLOUT .=   bbcode2textarea(  );
      $HTMLOUT .= " <div align='center'>
                    <input type='submit' name='comment' value='{$lang['comment_doit']}' class='' />
                    </div>
                    </form>
                    </div>
                    </div>";




      $res = mysql_query("SELECT comments.id, text, comments.added, comments.editedby, comments.editedat, username, users.id as user, users.title, users.avatar, users.av_w, users.av_h, users.class, users.donor, users.warned FROM comments LEFT JOIN users ON comments.user = users.id WHERE torrent = $torrentid ORDER BY comments.id DESC LIMIT 5");

      $allrows = array();
      while ($row = mysql_fetch_assoc($res))
        $allrows[] = $row;

      if (count($allrows)) {
              require_once "include/comment_functions.php";
              //require_once "include/html_functions.php";
              require_once "include/bbcode_functions.php";
          $HTMLOUT .= "<h2>{$lang['comment_recent']}</h2>\n";
          $HTMLOUT .= commenttable($allrows);
        }

      print stdhead("{$lang['comment_add']}\"{$arr["name"]}\"", $js) . $HTMLOUT . stdfoot();
      die;
    }
    elseif ($action == "edit")
    {
      $commentid = isset($_GET['cid']) && !is_array($_GET['cid']) ? (int) $_GET['cid'] : 0;
      if (!is_valid_id($commentid))
        stderr("{$lang['comment_error']}", "{$lang['comment_invalid_id']}");

      $res = mysql_query("SELECT c.*, t.name FROM comments AS c LEFT JOIN torrents AS t ON c.torrent = t.id WHERE c.id=$commentid") or sqlerr(__FILE__,__LINE__);
      $arr = mysql_fetch_assoc($res);
      if (!$arr)
        stderr("{$lang['comment_error']}", "{$lang['comment_invalid_id']}.");

      if ($arr["user"] != $CURUSER["id"] && get_user_class() < UC_MODERATOR)
        stderr("{$lang['comment_error']}", "{$lang['comment_denied']}");

      if ($_SERVER['REQUEST_METHOD'] === 'POST')
      {
        if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'comment'))
        {
          http_response_code(400);
          exit('Invalid comment edit request.');
        }
        $text = isset($_POST['body']) && is_string($_POST['body']) ? trim($_POST['body']) : '';
        $returnto = security_validate_return_to(isset($_POST['returnto']) ? $_POST['returnto'] : '', '/details.php?id=' . (int) $arr['torrent']);

        if ($text === '')
          stderr($lang['comment_error'], $lang['comment_body']);
        if (strlen($text) > 20000)
          stderr($lang['comment_error'], 'Comment is too long.');

        $text = sqlesc($text);

        $editedat = TIME_NOW;

        mysql_query("UPDATE comments SET text=$text, editedat=$editedat, editedby={$CURUSER['id']} WHERE id=$commentid") or sqlerr(__FILE__, __LINE__);

        header('Location: ' . $returnto);
        exit;
      }
      
      $returnto = '/details.php?id=' . (int) $arr['torrent'];
      $js = "<script type='text/javascript' src='scripts/bbcode2text.js'></script>";
      $HTMLOUT = '';

      $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>{$lang['comment_edit']}\"" . htmlsafechars($arr["name"]) . "\"</div>
                         <div class='cblock-content'>
                             <form name='bbcode2text' method='post' action='comment.php?action=edit&amp;cid=$commentid'>
                                  <input type='hidden' name='csrf_token' value='" . htmlsafechars($comment_csrf) . "' />
                                  <input type='hidden' name='returnto' value='" . htmlsafechars($returnto) . "' />
                                  <input type='hidden' name='cid' value='" . (int) $commentid . "' />";
      $HTMLOUT .=                 bbcode2textarea( 'body', htmlsafechars($arr["text"]) );
      $HTMLOUT .= "       <div align='center'>
                          <input type='submit' name='comment' value='{$lang['comment_doit']}' class='' />
                          </div>
                          </form>
                         </div>
                     </div>";

      print stdhead("{$lang['comment_edit']}\"{$arr["name"]}\"", $js) . $HTMLOUT . stdfoot();
      die;
    }
    elseif ($action == "delete")
    {
      if (get_user_class() < UC_MODERATOR)
        stderr("{$lang['comment_error']}", "{$lang['comment_denied']}");

      $commentid = isset($_GET['cid']) && !is_array($_GET['cid']) ? (int) $_GET['cid'] : (isset($_POST['cid']) && !is_array($_POST['cid']) ? (int) $_POST['cid'] : 0);

      if (!is_valid_id($commentid))
        stderr($lang['comment_error'], $lang['comment_invalid_id']);

      $sure = isset($_POST['sure']) && (string) $_POST['sure'] === '1';

      if (!$sure || $_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'comment'))
      {
        $confirm = "<form method='post' action='comment.php?action=delete'>
          <input type='hidden' name='csrf_token' value='" . htmlsafechars($comment_csrf) . "' />
          <input type='hidden' name='cid' value='" . (int) $commentid . "' />
          <input type='hidden' name='sure' value='1' />
          <button type='submit'>here</button>
        </form>";
        stderr($lang['comment_delete'], $lang['comment_about_delete'] . $confirm . ' ' . $lang['comment_delete_sure']);
      }


      $res = mysql_query("SELECT torrent FROM comments WHERE id=$commentid")  or sqlerr(__FILE__,__LINE__);
      $arr = mysql_fetch_assoc($res);
      if ($arr)
        $torrentid = $arr["torrent"];

      @mysql_query("DELETE FROM comments WHERE id=$commentid") or sqlerr(__FILE__,__LINE__);
      if ($torrentid && mysql_affected_rows() > 0)
        mysql_query("UPDATE torrents SET comments = comments - 1 WHERE id = $torrentid");

      $returnto = security_validate_return_to(isset($_POST['returnto']) ? $_POST['returnto'] : '', '/');
      header('Location: ' . $returnto);
      exit;
    }
    elseif ($action == "vieworiginal")
    {
      if (get_user_class() < UC_MODERATOR)
        stderr("{$lang['comment_error']}", "{$lang['comment_denied']}");

      $commentid = isset($_GET['cid']) && !is_array($_GET['cid']) ? (int) $_GET['cid'] : 0;

      if (!is_valid_id($commentid))
        stderr($lang['comment_error'], $lang['comment_invalid_id']);

      $res = mysql_query("SELECT c.*, t.name FROM comments AS c LEFT JOIN torrents AS t ON c.torrent = t.id WHERE c.id=$commentid") or sqlerr(__FILE__,__LINE__);
      $arr = mysql_fetch_assoc($res);
      if (!$arr)
        stderr("{$lang['comment_error']}", "{$lang['comment_invalid_id']} $commentid.");

      
      $HTMLOUT = '';

      $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>{$lang['comment_original_contents']}#$commentid</div>
                         <div class='cblock-content'>
                             <table width='500' border='1' cellspacing='0' cellpadding='5'>
                                   <tr>
                                      <td class='comment'>".htmlsafechars($arr["ori_text"])."</td>
                                   </tr>
                             </table><br />
                         </div>
                     </div>";

      $returnto = '/details.php?id=' . (int) $arr['torrent'];

      $HTMLOUT .= "<span class='btn'><a href='" . htmlsafechars($returnto) . "'>{$lang['comment_back']}</a></span>\n";

      print stdhead("{$lang['comment_original']}") . $HTMLOUT . stdfoot();
      die;
    }
    else
      stderr("{$lang['comment_error']}", "{$lang['comment_unknown']}");

    die;
?>