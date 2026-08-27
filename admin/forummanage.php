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

require_once "include/html_functions.php";
require_once "include/user_functions.php";

security_session_start();
$forum_csrf = security_csrf_token('admin-forums');

    $lang = array_merge( $lang, load_language('ad_forummanage') );
    
    if (get_user_class() < UC_MODERATOR || (int) $CURUSER['id'] !== 1) //sysop id check
    stderr("{$lang['stderr_error']}", "{$lang['text_permission']}");

    $mode = isset($_GET['mode']) && is_string($_GET['mode']) ? $_GET['mode'] : ''; //if not goto default!


    switch($mode) {
					case 'edit': 
					editForum();
					break;
					
					case 'takeedit':
					takeeditForum();
					break;
					
					case 'delete':
					deleteForum();
					break;
					
					case 'takedelete':
					takedeleteForum();
					break;
					
					case 'add':
					addForum();
					break;
					
					case 'takeadd':
					takeaddForum();
					break;
					
					default:
					showForums();
	
	}



function forum_require_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'admin-forums'))
    {
      http_response_code(400);
      exit('Invalid forum administration request.');
    }
}

function forum_validate_int($value, $min, $max)
{
    if (!is_scalar($value) || !preg_match('/\A\d+\z/', (string) $value))
      return false;
    $value = (int) $value;
    return $value >= $min && $value <= $max ? $value : false;
}

function forum_validate_text($value, $max_length)
{
    if (!is_string($value))
      return false;
    $value = trim($value);
    if ($value === '' || strlen($value) > $max_length || preg_match('/[\\x00-\\x1F\\x7F]/', $value))
      return false;
    return $value;
}

function showForums() {

    global $lang;

    $HTMLOUT = '';

    $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>Forum Manage<span class='btn' style='float:right;'><a href='admin.php?action=forummanage&amp;mode=add'>{$lang['btn_addnew']}</a></span></div>
                         <div class='cblock-content'>";
    $HTMLOUT .=              begin_main_frame();
    $HTMLOUT .= "            <table width='700' border='0' style='text-align:center;' cellpadding='2' cellspacing='0'>";
    $HTMLOUT .= "                  <tr>
                                      <td class='colhead' style='text-align:left;'>{$lang['header_name']}</td>
                                      <td class='colhead'>{$lang['header_topics']}</td>
                                      <td class='colhead'>{$lang['header_posts']}</td>
                                      <td class='colhead'>{$lang['header_read']}</td>
                                      <td class='colhead'>{$lang['header_write']}</td>
                                      <td class='colhead'>{$lang['header_createtopic']}</td>
                                      <td class='colhead'>{$lang['header_modify']}</td>
                                   </tr>";
    $result = mysql_query ("SELECT  * FROM forums ORDER BY sort ASC");
    if ( mysql_num_rows($result) > 0) {

      while($row = mysql_fetch_assoc($result)){

      $HTMLOUT .= "                <tr>
                                      <td><a href='forums.php?action=viewforum&amp;forumid={$row["id"]}'><b>".htmlsafechars($row["name"])."</b></a><br />".htmlsafechars($row["description"])."</td>";
      $HTMLOUT .= "                   <td>{$row["topiccount"]}</td><td>{$row["postcount"]}</td><td>{$lang['text_minimal']} " . get_user_class_name($row["minclassread"]) . "</td>
                                      <td>{$lang['text_minimal']} " . get_user_class_name($row["minclasswrite"]) . "</td><td>{$lang['text_minimal']} " . get_user_class_name($row["minclasscreate"]) . "</td>
                                      <td style='text-align:center;white-space: nowrap;'><div style='color:red; font-weight:bold;'><a href='admin.php?action=forummanage&amp;mode=edit&amp;id={$row["id"]}'>{$lang['text_edit']}</a>&nbsp;|&nbsp;<a href='admin.php?action=forummanage&amp;mode=delete&amp;id={$row["id"]}'>{$lang['text_delete']}</a></div></td>
                                   </tr>";

    }
    }
    else
    {
      $HTMLOUT .= "                <tr>
                                     <td colspan='7'>{$lang['text_sorry']}</td>
                                   </tr>";
    }
    $HTMLOUT .= "            </table>";

    $HTMLOUT .=              end_main_frame();

    $HTMLOUT .= "        </div>
                     </div>";
    
    print stdhead("{$lang['stdhead_forummanagetools']}") . $HTMLOUT . stdfoot();
}

function addForum() {
    global $CURUSER, $lang, $forum_csrf;

    $HTMLOUT = '';

    $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>Add Forum<span class='btn' style='float:right;'><a href='admin.php?action=forummanage'>{$lang['btn_cancel']}</a></span></div>
                         <div class='cblock-content'>";


    $HTMLOUT .=              begin_main_frame();


    $HTMLOUT .= "            <form method='post' action='admin.php?action=forummanage&amp;mode=takeadd'>
                                      <input type='hidden' name='csrf_token' value='{$forum_csrf}' />
                                  <table width='600' border='0' cellspacing='0' cellpadding='3' style='text-align:center;'>
                                        <tr style='text-align:center;'>
                                           <td colspan='2' class='colhead'>{$lang['header_makenew']}</td>
                                         </tr>
                                         <tr>
                                            <td><b>{$lang['table_forumname']}</b></td>
                                            <td><input name='name' type='text' size='30' /></td>
                                         </tr>
                                         <tr>
                                            <td><b>{$lang['table_forumdescr']}</b></td>
                                            <td><textarea name='desc' cols='50' rows='5' size='30'></textarea></td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_minreadperm']}</b></td>
                                           <td>
                                              <select name='readclass'>";

    $maxclass = get_user_class();
      for ($i = 0; $i <= $maxclass; ++$i)
      $HTMLOUT .= "                                  <option value='$i'" . ($CURUSER["class"] == $i ? " selected='selected'" : "") . ">" . get_user_class_name($i) . "</option>\n";

        $HTMLOUT .= "                         </select>
                                           </td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_minwriteperm']}</b></td>
                                           <td>
                                              <select name='writeclass'>";

    $maxclass = get_user_class();
      for ($i = 0; $i <= $maxclass; ++$i)
      $HTMLOUT .= "                                  <option value='$i'" . ($CURUSER["class"] == $i ? " selected='selected'" : "") . ">" . get_user_class_name($i) . "</option>\n";

        $HTMLOUT .= "                         </select>
                                           </td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_mincreatetperm']}</b></td>
                                           <td>
                                              <select name='createclass'>";

    $maxclass = get_user_class();
      for ($i = 0; $i <= $maxclass; ++$i)
      $HTMLOUT .= "                                  <option value='$i'" . ($CURUSER["class"] == $i ? " selected='selected'" : "") . ">" . get_user_class_name($i) . "</option>\n";

        $HTMLOUT .= "                         </select>
                                           </td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_forumrank']}</b></td>
                                           <td>
                                              <select name='sort'>";

    $res = mysql_query ("SELECT sort FROM forums");
    $nr = mysql_num_rows($res);
    $maxclass = $nr + 1;
      for ($i = 0; $i <= $maxclass; ++$i)
      $HTMLOUT .= "                                  <option value='$i'>$i </option>\n";

        $HTMLOUT .= "                         </select>

                                           </td>
                                        </tr>

                                        <tr style='text-align:center;'>
                                           <td colspan='2'>
                                              <!--<input type='hidden' name='action' value='takeadd' /> -->
                                              <input type='submit' name='Submit' value='{$lang['btn_makeforum']}' class='btn' />
                                           </td>
                                        </tr>
                                  </table>
                             </form>";

    //	end_frame();
    $HTMLOUT .= end_main_frame();

    $HTMLOUT .= "      </div>
                   </div>";
   
    print stdhead("{$lang['stdhead_addforum']}") . $HTMLOUT . stdfoot();

}

function editForum() {

    global $lang, $forum_csrf;
    
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : stderr("Error", "Not Found");

    $HTMLOUT = '';

    $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>Edit Forum<span class='btn' style='float:right;'><a href='admin.php?action=forummanage'>{$lang['btn_cancel']}</a></span></div>
                         <div class='cblock-content'>";

    $HTMLOUT .=              begin_frame("{$lang['frame_editforum']}");

    $result = mysql_query ("SELECT * FROM forums where id = '$id'");
    if (mysql_num_rows($result) > 0) {
      while($row = mysql_fetch_assoc($result)){


      $HTMLOUT .= "          <form method='post' action='admin.php?action=forummanage&amp;mode=takeedit'>
                                      <input type='hidden' name='csrf_token' value='{$forum_csrf}' />
                                  <table width='600'  border='0' cellspacing='0' cellpadding='3' style='text-align:center;'>
                                        <tr style='text-align:center;'>
                                           <td colspan='2' class='colhead'>{$lang['header_editforum']} ".htmlsafechars($row["name"])."</td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_forumname']}</b></td>
                                           <td><input name='name' type='text' size='30' maxlength='60' value='".htmlsafechars($row["name"])."' /></td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_forumdescr']}</b></td>
                                           <td><textarea name='desc' cols='50' rows='5'>".htmlsafechars($row["description"])."</textarea></td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_minreadperm']}</b></td>
                                           <td>
                                              <select name='readclass'>";

    $maxclass = get_user_class();
      for ($i = 0; $i <= $maxclass; ++$i)
      if( get_user_class_name($i) != "" )
      $HTMLOUT .= "                                  <option value='$i'" . ($row["minclassread"] == $i ? " selected='selected'" : "") . ">" . get_user_class_name($i) . "</option>";

        $HTMLOUT .= "                         </select>
                                           </td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_minpostrank']}</b></td>
                                           <td>
                                              <select name='writeclass'>";

    $maxclass = get_user_class();
      for ($i = 0; $i <= $maxclass; ++$i)
      if( get_user_class_name($i) != "" )
      $HTMLOUT .= "                                  <option value='$i'" . ($row["minclasswrite"] == $i ? " selected='selected'" : "") . ">" . get_user_class_name($i)."</option>";

        $HTMLOUT .= "                         </select>
                                           </td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_mincreatetrank']}</b></td>
                                           <td>
                                              <select name='createclass'>";

    $maxclass = get_user_class();
      for ($i = 0; $i <= $maxclass; ++$i)
      if( get_user_class_name($i) != "" )
      $HTMLOUT .= "                                  <option value='$i'" . ($row["minclasscreate"] == $i ? " selected='selected'" : "") . ">" . get_user_class_name($i)."</option>";

        $HTMLOUT .= "                         </select>
                                           </td>
                                        </tr>
                                        <tr>
                                           <td><b>{$lang['table_forumrank']}</b></td>
                                           <td>
                                              <select name='sort'>";

    $res = mysql_query ("SELECT sort FROM forums");
    $nr = mysql_num_rows($res);
    $maxclass = $nr + 1;
      for ($i = 0; $i <= $maxclass; ++$i)
      $HTMLOUT .= "                                  <option value='$i'" . ($row["sort"] == $i ? " selected='selected'" : "") . ">$i</option>";

        $HTMLOUT .= "                         </select>
                                           </td>
                                        </tr>
                                        <tr style='text-align:center;'>
                                           <td colspan='2'>
                                              <input type='hidden' name='id' value='{$row['id']}' />
                                              <input type='submit' name='Submit' value='{$lang['btn_editforum']}' class='btn' />
                                           </td>
                                        </tr>
                                  </table>
                             </form>";
        }
    }
    else
    {$HTMLOUT .= "{$lang['text_sorry']}";}

    //	end_frame();
    $HTMLOUT .= end_main_frame();

    $HTMLOUT .= "        </div>
                     </div>";

    print stdhead("{$lang['stdhead_editforum']}") . $HTMLOUT . stdfoot();
}

function takeaddForum() {

    global $lang, $CURUSER;
    forum_require_csrf();

    $name = forum_validate_text(isset($_POST['name']) ? $_POST['name'] : null, 150);
    $description = forum_validate_text(isset($_POST['desc']) ? $_POST['desc'] : null, 350);
    $sort = forum_validate_int(isset($_POST['sort']) ? $_POST['sort'] : null, 0, 100000);
    $read_class = forum_validate_int(isset($_POST['readclass']) ? $_POST['readclass'] : null, 0, (int) $CURUSER['class']);
    $write_class = forum_validate_int(isset($_POST['writeclass']) ? $_POST['writeclass'] : null, 0, (int) $CURUSER['class']);
    $create_class = forum_validate_int(isset($_POST['createclass']) ? $_POST['createclass'] : null, 0, (int) $CURUSER['class']);
    if ($name === false || $description === false || $sort === false || $read_class === false || $write_class === false || $create_class === false)
      stderr($lang['stderr_error'], $lang['text_error']);

    $sql = 'INSERT INTO forums (sort, name, description, minclassread, minclasswrite, minclasscreate) VALUES ('
      . (int) $sort . ', ' . sqlesc($name) . ', ' . sqlesc($description) . ', '
      . (int) $read_class . ', ' . (int) $write_class . ', ' . (int) $create_class . ')';
    mysql_query($sql) or sqlerr(__FILE__, __LINE__);
    if (mysql_affected_rows() === 1)
      stderr($lang['stderr_success'], $lang['text_added'] . ". <a href='admin.php?action=forummanage'>{$lang['text_return']}</a>");
    stderr($lang['stderr_error'], $lang['text_error'] . ". <a href='admin.php?action=forummanage'>{$lang['text_return']}</a>");
}

function takeeditForum() {

    global $lang, $CURUSER;
    forum_require_csrf();

    $id = forum_validate_int(isset($_POST['id']) ? $_POST['id'] : null, 1, 2147483647);
    $name = forum_validate_text(isset($_POST['name']) ? $_POST['name'] : null, 150);
    $description = forum_validate_text(isset($_POST['desc']) ? $_POST['desc'] : null, 350);
    $sort = forum_validate_int(isset($_POST['sort']) ? $_POST['sort'] : null, 0, 100000);
    $read_class = forum_validate_int(isset($_POST['readclass']) ? $_POST['readclass'] : null, 0, (int) $CURUSER['class']);
    $write_class = forum_validate_int(isset($_POST['writeclass']) ? $_POST['writeclass'] : null, 0, (int) $CURUSER['class']);
    $create_class = forum_validate_int(isset($_POST['createclass']) ? $_POST['createclass'] : null, 0, (int) $CURUSER['class']);
    if ($id === false || $name === false || $description === false || $sort === false || $read_class === false || $write_class === false || $create_class === false)
      stderr($lang['stderr_error'], $lang['text_error']);

    $sql = 'UPDATE forums SET sort = ' . (int) $sort . ', name = ' . sqlesc($name)
      . ', description = ' . sqlesc($description) . ', minclassread = ' . (int) $read_class
      . ', minclasswrite = ' . (int) $write_class . ', minclasscreate = ' . (int) $create_class
      . ' WHERE id = ' . (int) $id;
    mysql_query($sql) or sqlerr(__FILE__, __LINE__);
    if (mysql_affected_rows() === 1)
      stderr($lang['stderr_success'], $lang['text_edited'] . ". <a href='admin.php?action=forummanage'>{$lang['text_return']}</a>");
    stderr($lang['stderr_error'], $lang['text_error'] . ". <a href='admin.php?action=forummanage'>{$lang['text_return']}</a>");
}

function deleteForum() {

    global $lang;

    $id = isset($_GET['id']) && !is_array($_GET['id']) ? (int) $_GET['id'] : 0;
    if (!is_valid_id($id))
      stderr($lang['stderr_error'], $lang['text_noid']);

    $res = mysql_query('SELECT id FROM topics WHERE forumid = ' . (int) $id) or sqlerr(__FILE__, __LINE__);

    if (mysql_num_rows($res) >= 1) 
    {
      print stdhead() . forum_select($id) . stdfoot();
      exit();
    }
    else
    {
      global $forum_csrf;
      $link = "{$lang['text_warning']}
        <form method='post' action='admin.php?action=forummanage&amp;mode=takedelete&amp;id=" . (int) $id . "'>
        <input type='hidden' name='csrf_token' value='{$forum_csrf}' />
        <input type='submit' value='{$lang['text_warning_cont']}' class='btn' />
        </form>";
      stderr($lang['stderr_error'], $link);
		}
	
}


function takedeleteForum() {

    global $lang;
    forum_require_csrf();

    $id = isset($_GET['id']) && !is_array($_GET['id']) ? (int) $_GET['id'] : 0;
    if (!is_valid_id($id))
      stderr($lang['stderr_error'], $lang['text_noid']);

    if (!isset($_POST['deleteall']))
    {
      $res = mysql_query('SELECT id FROM topics WHERE forumid = ' . (int) $id) or sqlerr(__FILE__, __LINE__);
      if (mysql_num_rows($res) !== 0)
        stderr($lang['stderr_error'], $lang['text_smthbad']);
      mysql_query('DELETE FROM forums WHERE id = ' . (int) $id . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
      if (mysql_affected_rows() !== 1)
        stderr($lang['stderr_error'], $lang['text_nowheretomove']);
      stderr($lang['stderr_success'], $lang['text_forumdeleted'] . " <a href='admin.php?action=forummanage'>{$lang['text_deleted_text']}</a>");
    }

    $forumid = forum_validate_int(isset($_POST['forumid']) ? $_POST['forumid'] : null, 1, 2147483647);
    if ($forumid === false || $forumid === $id)
      stderr($lang['stderr_error'], $lang['text_smthbad']);
    $destination = mysql_query('SELECT id FROM forums WHERE id = ' . (int) $forumid . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
    if (mysql_num_rows($destination) !== 1)
      stderr($lang['stderr_error'], $lang['text_smthbad']);

    $res = mysql_query('SELECT id FROM topics WHERE forumid = ' . (int) $id) or sqlerr(__FILE__, __LINE__);
    if (mysql_num_rows($res) === 0)
      stderr($lang['stderr_error'], $lang['text_notopic']);
    $topic_ids = array();
    while ($row = mysql_fetch_assoc($res))
      $topic_ids[] = (int) $row['id'];

    mysql_query('UPDATE topics SET forumid = ' . (int) $forumid . ' WHERE id IN (' . implode(',', $topic_ids) . ')') or sqlerr(__FILE__, __LINE__);
    if (mysql_affected_rows() < 1)
      stderr($lang['stderr_error'], $lang['text_smthbad']);
    mysql_query('DELETE FROM forums WHERE id = ' . (int) $id . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
    if (mysql_affected_rows() !== 1)
      stderr($lang['stderr_error'], $lang['text_smthbad']);
    stderr($lang['stderr_success'], $lang['text_forumdeleted']);
}

function forum_select($currentforum = 0) {

    global $lang, $forum_csrf;
    
    $HTMLOUT = '';

    $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>Forum Select</div>
                         <div class='cblock-content'>";


    $HTMLOUT .= "            <div style='text-align:center;'>
                                 <form method='post' action='admin.php?action=forummanage&amp;mode=takedelete&amp;id=$currentforum' name='jump'>
                                      <input type='hidden' name='csrf_token' value='{$forum_csrf}' />
                                      <input type='hidden' name='deleteall' value='true' />
                                      {$lang['text_select']}
                                      <select name='forumid'>";

    $res = mysql_query("SELECT * FROM forums ORDER BY name") or sqlerr(__FILE__, __LINE__);

    while ($arr = mysql_fetch_assoc($res))
    {
      if ($arr["id"] == $currentforum)
    continue;
        $HTMLOUT .= "                        <option value='" . $arr["id"] . ($currentforum == $arr["id"] ? "' selected='selected'>" : "'>") . $arr["name"] . "</option>\n";
    }

    $HTMLOUT .= "                     </select>
                                      <input type='submit' value='{$lang['btn_moveto']}' class='btn' />
                                 </form>\n
                             </div>
                         </div>
                     </div>";
    
    return $HTMLOUT;
}

?>