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
if ( ! defined( 'IN_TBDEV_FORUM' ) )
{
	print "{$lang['forum_mod_options_access']}";
	exit();
}

security_session_start();
$forum_mod_csrf = security_csrf_token('forum-mod');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'forum-mod'))
{
  http_response_code(400);
  exit('Invalid forum moderation request.');
}

  //-------- Action: Lock topic

  if ($action == "locktopic")
  {
    $forumid = isset($_GET['forumid']) && !is_array($_GET['forumid']) ? (int) $_GET['forumid'] : 0;
    $topicid = isset($_GET['topicid']) && !is_array($_GET['topicid']) ? (int) $_GET['topicid'] : 0;
    $page = isset($_GET['page']) && !is_array($_GET['page']) ? (int) $_GET['page'] : 0;

    if (!is_valid_id($topicid) || get_user_class() < UC_MODERATOR)
      stderr("{$lang['forum_mod_options_user_error']}", "{$lang['forum_mod_options_incorrect']}");

    @mysql_query("UPDATE topics SET locked='yes' WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    header("Location: {$TBDEV['baseurl']}/forums.php?action=viewforum&forumid=$forumid&page=$page");

    die;
  }

  //-------- Action: Unlock topic

  if ($action == "unlocktopic")
  {
    $forumid = isset($_GET['forumid']) && !is_array($_GET['forumid']) ? (int) $_GET['forumid'] : 0;
    $topicid = isset($_GET['topicid']) && !is_array($_GET['topicid']) ? (int) $_GET['topicid'] : 0;
    $page = isset($_GET['page']) && !is_array($_GET['page']) ? (int) $_GET['page'] : 0;

    if (!is_valid_id($topicid) || get_user_class() < UC_MODERATOR)
      stderr("{$lang['forum_mod_options_user_error']}", "{$lang['forum_mod_options_incorrect']}");

    @mysql_query("UPDATE topics SET locked='no' WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    header("Location: {$TBDEV['baseurl']}/forums.php?action=viewforum&forumid=$forumid&page=$page");

    die;
  }

  //-------- Action: Set locked on/off

  if ($action == "setlocked")
  {
    $topicid = isset($_POST['topicid']) && !is_array($_POST['topicid']) ? (int) $_POST['topicid'] : 0;

    if (!is_valid_id($topicid) || get_user_class() < UC_MODERATOR)
      stderr($lang['forum_mod_options_user_error'], $lang['forum_mod_options_incorrect']);

    $locked_value = isset($_POST['locked']) && is_string($_POST['locked']) ? $_POST['locked'] : '';
    if (!in_array($locked_value, array('yes', 'no'), true))
      stderr($lang['forum_mod_options_user_error'], $lang['forum_mod_options_incorrect']);
    $locked = sqlesc($locked_value);
    
    @mysql_query("UPDATE topics SET locked=$locked WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    header("Location: {$TBDEV['baseurl']}/forums.php?action=viewtopic&topicid=$topicid");

    die;
  }

  //-------- Action: Set sticky on/off

  if ($action == "setsticky")
  {
    $topicid = isset($_POST['topicid']) && !is_array($_POST['topicid']) ? (int) $_POST['topicid'] : 0;

    if (!is_valid_id($topicid) || get_user_class() < UC_MODERATOR)
      stderr($lang['forum_mod_options_user_error'], $lang['forum_mod_options_incorrect']);

    $sticky_value = isset($_POST['sticky']) && is_string($_POST['sticky']) ? $_POST['sticky'] : '';
    if (!in_array($sticky_value, array('yes', 'no'), true))
      stderr($lang['forum_mod_options_user_error'], $lang['forum_mod_options_incorrect']);
    $sticky = sqlesc($sticky_value);
    
    @mysql_query("UPDATE topics SET sticky=$sticky WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    header("Location: {$TBDEV['baseurl']}/forums.php?action=viewtopic&topicid=$topicid");

    die;
  }

  //-------- Action: Rename topic

  if ($action == 'renametopic')
  {
  	if (get_user_class() < UC_MODERATOR)
  	  stderr("{$lang['forum_mod_options_user_error']}", "{$lang['forum_mod_options_incorrect']}");

	$topicid = isset($_POST['topicid']) && !is_array($_POST['topicid']) ? (int) $_POST['topicid'] : 0;

	if (!is_valid_id($topicid))
  	  stderr("{$lang['forum_mod_options_user_error']}", "{$lang['forum_mod_options_incorrect']}");

	$subject = isset($_POST['subject']) && is_string($_POST['subject']) ? trim(strip_tags($_POST['subject'])) : '';

	if ($subject === '')
	  stderr($lang['forum_mod_options_error'], $lang['forum_mod_options_new_title']);
	if (strlen($subject) > 120)
	  stderr($lang['forum_mod_options_error'], $lang['forum_mod_options_new_title']);

	$subject = sqlesc($subject);

  	@mysql_query("UPDATE topics SET subject=$subject WHERE id=$topicid") or sqlerr();

	header("Location: {$TBDEV['baseurl']}/forums.php?action=viewtopic&topicid=$topicid");
		exit;
  }

  //-------- Action: Delete topic

  if ($action == "deletetopic")
  {
    $topicid = isset($_POST['topicid']) && !is_array($_POST['topicid']) ? (int) $_POST['topicid'] : 0;
    $forumid = isset($_POST['forumid']) && !is_array($_POST['forumid']) ? (int) $_POST['forumid'] : 0;

    if (!is_valid_id($topicid) || get_user_class() < UC_MODERATOR)
      stderr("{$lang['forum_mod_options_user_error']}", "{$lang['forum_mod_options_incorrect']}");

    $sure = isset($_POST['sure']) && (string) $_POST['sure'] === '1';

    if (!$sure)
    {
      
      $HTMLOUT = "<table>
      <tr>
        <td align='right'>{$lang['forum_mod_options_sanity']}</td>
      </tr>
      <tr>
        <td>
          <form method='post' action='forums.php?action=deletetopic'>
          <input type='hidden' name='action' value='deletetopic' />
          <input type='hidden' name='topicid' value='$topicid' />
          <input type='hidden' name='forumid' value='$forumid' />
          <input type='checkbox' name='sure' value='1' />{$lang['forum_mod_options_sure']}
          <input type='submit' value={$lang['forum_mod_options_ok']} />
          </form>
        </td>
      </tr>
	    </table>\n";
	    
      print stdhead("{$lang['forum_mod_options_delete']}") . $HTMLOUT . stdfoot();
      exit();
    }

    @mysql_query("DELETE FROM topics WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    @mysql_query("DELETE FROM posts WHERE topicid=$topicid") or sqlerr(__FILE__, __LINE__);

    header("Location: {$TBDEV['baseurl']}/forums.php?action=viewforum&forumid=$forumid");

    die;
  }


  //-------- Action: Move topic

  if ($action == "movetopic")
  {
    $forumid = isset($_POST['forumid']) && !is_array($_POST['forumid']) ? (int) $_POST['forumid'] : 0;
    $topicid = isset($_POST['topicid']) && !is_array($_POST['topicid']) ? (int) $_POST['topicid'] : 0;

    if (!is_valid_id($forumid) || !is_valid_id($topicid) || get_user_class() < UC_MODERATOR)
      stderr("{$lang['forum_mod_options_user_error']}", "{$lang['forum_mod_options_incorrect']}");

    // Make sure topic and forum is valid

    $res = @mysql_query("SELECT minclasswrite FROM forums WHERE id=$forumid") or sqlerr(__FILE__, __LINE__);

    if (mysql_num_rows($res) != 1)
      stderr("{$lang['forum_mod_options_error']}", "{$lang['forum_mod_options_notfound']}");

    $arr = mysql_fetch_row($res);

    if (get_user_class() < $arr[0])
      stderr("{$lang['forum_mod_options_user_error']}", "{$lang['forum_mod_options_incorrect']}");

    $res = @mysql_query("SELECT subject,forumid FROM topics WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    if (mysql_num_rows($res) != 1)
      stderr("{$lang['forum_mod_options_error']}", "{$lang['forum_mod_options_topic_notfound']}");

    $arr = mysql_fetch_assoc($res);

    if ($arr["forumid"] != $forumid)
      @mysql_query("UPDATE topics SET forumid=$forumid WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    // Redirect to forum page

    header("Location: {$TBDEV['baseurl']}/forums.php?action=viewforum&forumid=$forumid");

    die;
  }

?>