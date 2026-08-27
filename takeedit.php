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
require_once 'include/user_functions.php';

security_session_start();
$torrent_edit_csrf = security_csrf_token('torrent-edit');
dbconn();

loggedinorreturn();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'torrent-edit'))
{
  http_response_code(400);
  exit('Invalid torrent edit request.');
}
if (!security_rate_limit('torrent-edit', security_client_identity() . '|' . (int) $CURUSER['id'], 30, 300))
{
  http_response_code(429);
  exit('Too many torrent edit requests.');
}

    $lang = array_merge( load_language('global'), load_language('takeedit') );


    if (!mkglobal('name:descr:type') || !is_string($name) || !is_string($descr) || !is_string($type))
      stderr($lang['takedit_failed'], $lang['takedit_no_data']);
    $name = trim($name);
    $descr = trim($descr);
    $type = (int) $type;
    if ($name === '' || strlen($name) > 255 || strlen($descr) > 200000 || $type < 1)
      stderr($lang['takedit_failed'], $lang['takedit_no_data']);

    $id = isset($_POST['id']) && !is_array($_POST['id']) ? (int) $_POST['id'] : 0;
    if ( !is_valid_id($id) )
      stderr($lang['takedit_failed'], $lang['takedit_no_data']);


    $res = mysql_query("SELECT owner, filename, save_as FROM torrents WHERE id = " . (int) $id);

    if (false == mysql_num_rows($res))
      stderr($lang['takedit_failed'], $lang['takedit_no_data']);

    $row = mysql_fetch_assoc($res);

    $category_res = mysql_query("SELECT id FROM categories WHERE id = " . (int) $type);
    if (!$category_res || mysql_num_rows($category_res) !== 1)
      stderr($lang['takedit_failed'], $lang['takedit_no_data']);

    if ($CURUSER['id'] != $row['owner'] && $CURUSER['class'] < UC_MODERATOR)
      stderr($lang['takedit_failed'], $lang['takedit_not_owner']);

    $updateset = array();

    $fname = isset($row['filename']) && is_string($row['filename']) ? $row['filename'] : '';
    if (!preg_match('/\A(.+)\.torrent\z/si', $fname, $matches))
      stderr($lang['takedit_failed'], $lang['takedit_no_data']);
    $shortfname = $matches[1];
    $dname = isset($row['save_as']) && is_string($row['save_as']) ? $row['save_as'] : '';

    $nfoaction = isset($_POST['nfoaction']) && is_string($_POST['nfoaction']) ? $_POST['nfoaction'] : 'keep';
    if (!in_array($nfoaction, array('keep', 'update', 'remove'), true))
      stderr($lang['takedit_failed'], $lang['takedit_no_data']);
    if ($nfoaction === 'update')
    {
      $nfofile = isset($_FILES['nfo']) && is_array($_FILES['nfo']) ? $_FILES['nfo'] : array();
      if (($nfofile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !isset($nfofile['tmp_name'], $nfofile['size']) || (int) $nfofile['size'] > 65535)
        stderr($lang['takedit_failed'], $lang['takedit_nfo_error']);
      $nfofilename = $nfofile['tmp_name'];
      if (!is_uploaded_file($nfofilename) || @filesize($nfofilename) <= 0)
        stderr($lang['takedit_failed'], $lang['takedit_nfo_error']);
      $nfo_contents = @file_get_contents($nfofilename);
      if ($nfo_contents === false)
        stderr($lang['takedit_failed'], $lang['takedit_nfo_error']);
      $updateset[] = "nfo = " . sqlesc(str_replace("\x0d\x0d\x0a", "\x0d\x0a", $nfo_contents));
    }
    elseif ($nfoaction === 'remove')
      $updateset[] = 'nfo = ""';

    $updateset[] = "name = " . sqlesc($name);
    $updateset[] = "search_text = " . sqlesc(searchfield("$shortfname $dname $name"));
    $updateset[] = "descr = " . sqlesc($descr);
    $updateset[] = "ori_descr = " . sqlesc($descr);
    $updateset[] = "category = " . (int) $type;
    //if ($CURUSER["admin"] == "yes") {
    if ($CURUSER['class'] > UC_MODERATOR) {
      if (isset($_POST['banned']) && is_scalar($_POST['banned'])) {
        $updateset[] = 'banned = "yes"';
        $_POST['visible'] = 0;
      }
      else
        $updateset[] = 'banned = "no"';
    }
    $visible = isset($_POST['visible']) && is_scalar($_POST['visible']) ? 'yes' : 'no';
    $updateset[] = "visible = '" . $visible . "'";

    mysql_query("UPDATE torrents SET " . join(',', $updateset) . " WHERE id = " . (int) $id) or sqlerr(__FILE__, __LINE__);

    write_log(sprintf($lang['takedit_log'], $id, $name, $CURUSER['username']));

    header('Location: ' . $TBDEV['baseurl'] . '/details.php?id=' . (int) $id . '&edited=1');
    exit;


?>