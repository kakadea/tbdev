
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
|   $Revision: 2 $
|   $Author$ <-- (oh, wonder what that is?)
|   $URL$
+------------------------------------------------
*/
if ( ! defined( 'IN_TBDEV_ADMIN' ) OR ($CURUSER['class'] < UC_SYSOP) )

{
	print "<div class='error'><b>Incorrect access</b>You cannot access this file directly.</div>";
	exit();
}

require_once "include/user_functions.php";

security_session_start();
$cleanup_csrf = security_csrf_token('admin-cleanup');

    $params = array_merge($_GET, $_POST);
    $params['mode'] = isset($params['mode']) && is_string($params['mode']) ? $params['mode'] : '';

    switch($params['mode'])
    {
      case 'unlock':
        cleanup_take_unlock();
        break;

      case 'delete':
        cleanup_take_delete();
        break;

      case 'takenew':
        cleanup_take_new();
        break;

      case 'new':
        cleanup_show_new();
        break;

      case 'takeedit':
        cleanup_take_edit();
        break;

      case 'edit':
        cleanup_show_edit();
        break;

      default:
        cleanup_show_main();
        break;
    }

function cleanup_require_csrf()
{
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'admin-cleanup'))
  {
    http_response_code(400);
    exit('Invalid cleanup administration request.');
  }
}

function cleanup_validate_int($value, $min, $max = null)
{
  if (!is_scalar($value) || !preg_match('/\A\d+\z/', (string) $value))
    return false;
  $value = (int) $value;
  if ($value < $min || ($max !== null && $value > $max))
    return false;
  return $value;
}

function cleanup_validate_text($value, $max_length)
{
  if (!is_string($value))
    return false;
  $value = trim($value);
  if ($value === '' || strlen($value) > $max_length || preg_match('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]/', $value))
    return false;
  return $value;
}

function cleanup_validate_file($value)
{
  if (!is_string($value))
    return false;
  $value = basename(trim($value));
  if ($value === '' || !preg_match('/\A[a-zA-Z0-9_.-]+\.php\z/', $value))
    return false;
  return $value;
}

function cleanup_show_main()
		{
      global $cleanup_csrf;

    $htmlout = '';

    $htmlout = "
                     <div class='cblock'>
                         <div class='cblock-header'>Current Cleanup Tasks<div style='float:right;'><span class='btn'><a href='./admin.php?action=cleanup_manager&amp;mode=new'>Add New</a></span></div></div>
                         <div class='cblock-content'>
                             <table style='background:#cecece; width:80%;' cellpadding='5px'>
                                   <tr>
                                      <td class='colhead'>Cleanup Title&nbsp;&amp;&nbsp;Description</td>
                                      <td class='colhead' style='width:150px;'>Next Clean Time</td>
                                      <td class='colhead' style='width:40px;'>Edit</td>
                                      <td class='colhead' style='width:40px;'>Delete</td>
                                      <td class='colhead' style='width:40px;'>Off/On</td>
                                   </tr>";

		$sql = mysql_query( "SELECT * FROM cleanup ORDER BY clean_time ASC" ) or sqlerr(__FILE__,__LINE__);
		if( !mysql_num_rows($sql) )
      stderr('Error', 'Fucking panic now!');

			while ($row = mysql_fetch_assoc($sql))
			{
        $row['clean_id'] = (int) $row['clean_id'];
        $row['clean_on'] = (int) $row['clean_on'];
        $row['clean_title'] = htmlsafechars($row['clean_title']);
        $row['clean_desc'] = htmlsafechars($row['clean_desc']);
				if (TIME_NOW > $row['clean_time'])
			{
				$row['_image'] = 'task_run_now.gif';
			}
			else
			{
				$row['_image'] = 'task_run.gif';
			}

			$row['_clean_time'] = gmdate( 'j M Y - G:i', $row['clean_time'] );

			$row['_class']    = $row['clean_on'] != 1 ? " style='color:red'" : '';
			$row['_title']    = $row['clean_on'] != 1 ? " (Locked)" : '';
			$row['_clean_time'] = $row['clean_on'] != 1 ? "<span style='color:red'>{$row['_clean_time']}</span>" : $row['_clean_time'];

			$htmlout .= "          <tr>
                                      <td{$row['_class']}><strong>{$row['clean_title']}{$row['_title']}</strong><br />{$row['clean_desc']}</td>
                                      <td>{$row['_clean_time']}</td>
                                      <td align='center'>
                                         <a href='admin.php?action=cleanup_manager&amp;mode=edit&amp;cid={$row['clean_id']}'>
                                         <img src='./pic/aff_tick.gif' alt='Edit Cleanup' title='Edit' height='12' width='12' /></a>
                                      </td>
                                      <td align='center'>
                                         <form method='post' action='admin.php?action=cleanup_manager' style='display:inline;'>
                                         <input type='hidden' name='mode' value='delete' />
                                         <input type='hidden' name='cid' value='" . (int) $row['clean_id'] . "' />
                                         <input type='hidden' name='csrf_token' value='" . htmlsafechars($cleanup_csrf) . "' />
                                         <button type='submit' style='border:0;background:none;padding:0;cursor:pointer;'><img src='./pic/aff_cross.gif' alt='Delete Cleanup' title='Delete' height='12' width='12' /></button>
                                         </form>
                                      </td>
                                      <td align='center'>
                                         <form method='post' action='admin.php?action=cleanup_manager' style='display:inline;'>
                                         <input type='hidden' name='mode' value='unlock' />
                                         <input type='hidden' name='cid' value='" . (int) $row['clean_id'] . "' />
                                         <input type='hidden' name='clean_on' value='" . (int) $row['clean_on'] . "' />
                                         <input type='hidden' name='csrf_token' value='" . htmlsafechars($cleanup_csrf) . "' />
                                         <button type='submit' style='border:0;background:none;padding:0;cursor:pointer;'><img src='./pic/warnedbig.gif' alt='On/Off Cleanup' title='on/off' height='12' width='12' /></button>
                                         </form>
                                      </td>
                                   </tr>";
		}

		$htmlout .= "        </table>";
        $htmlout .= "    </div>
                     </div>";


		print stdhead('Cleanup Manager - View') . $htmlout . stdfoot();
}


function cleanup_show_edit() {

    global $params, $cleanup_csrf;


    if( !isset($params['cid']) OR empty($params['cid']) OR !is_valid_id($params['cid']) )
    {
      cleanup_show_main();
      exit;
    }

    $cid = intval($params['cid']);

    $sql = mysql_query( "SELECT * FROM cleanup WHERE clean_id = $cid" );

    if( !mysql_num_rows( $sql ) )
      stderr('Error', 'Why me?');

    $row = mysql_fetch_assoc( $sql );
    $row['clean_title'] = htmlsafechars($row['clean_title']);
    $row['clean_desc'] = htmlsafechars($row['clean_desc']);
    $row['clean_file'] = htmlsafechars($row['clean_file']);
    //$row['clean_title'] = htmlsafechars($row['clean_title']);
    $logyes = $row['clean_log'] ? 'checked="checked"' : '';
    $logno = !$row['clean_log'] ? 'checked="checked"' : '';
    $cleanon = $row['clean_on'] ? 'checked="checked"' : '';
    $cleanoff = !$row['clean_on'] ? 'checked="checked"' : '';
    $htmlout = '';

    $htmlout = '';

    $htmlout = "
                     <div class='cblock'>
                         <div class='cblock-header'>Editing cleanup: {$row['clean_title']}</div>
                         <div class='cblock-content'>
                             <div style='width: 615px; text-align: left; padding: 10px; margin: 0 auto; border-style: solid; border-color: lightgrey; border-width: 5px 2px;'>
                                 <form name='inputform' method='post' action='admin.php?action=cleanup_manager'>
                                      <input type='hidden' name='mode' value='takeedit' />
                                      <input type='hidden' name='cid' value='" . (int) $row['clean_id'] . "' />
                                      <input type='hidden' name='csrf_token' value='" . htmlsafechars($cleanup_csrf) . "' />
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Title</label>
                                          <input type='text' value='{$row['clean_title']}' name='clean_title' style='width:250px;' />
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Description</label>
                                          <input type='text' value='{$row['clean_desc']}' name='clean_desc' style='width:380px;' />
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Cleanup File Name</label>
                                          <input type='text' value='{$row['clean_file']}' name='clean_file' style='width:380px;' />
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Cleanup Interval</label>
                                          <input type='text' value='{$row['clean_increment']}' name='clean_increment' style='width:380px;' />
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Cleanup Log</label>
                                          Yes &nbsp; <input name='clean_log' value='1' $logyes type='radio' />&nbsp;&nbsp;&nbsp;<input name='clean_log' value='0' $logno type='radio' /> &nbsp; No
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>cleanup On or Off?</label>
                                          Yes &nbsp; <input name='clean_on' value='1' $cleanon type='radio' />&nbsp;&nbsp;&nbsp;<input name='clean_on' value='0' $cleanoff type='radio' /> &nbsp; No
                                      </div>
                                      <div style='text-align:center;'><input type='submit' name='submit' value='Edit' class='button' />&nbsp;<input type='button' value='Cancel' onclick='javascript: history.back()' /></div>
                                 </form>
                             </div>
                         </div>
                     </div>";

    print stdhead('Cleanup Manager - Edit') . $htmlout . stdfoot();
}



function cleanup_take_edit() {

      global $params;
      cleanup_require_csrf();

      $cid = cleanup_validate_int(isset($_POST['cid']) ? $_POST['cid'] : null, 1);
      $increment = cleanup_validate_int(isset($_POST['clean_increment']) ? $_POST['clean_increment'] : null, 1, 31536000);
      $log = cleanup_validate_int(isset($_POST['clean_log']) ? $_POST['clean_log'] : null, 0, 1);
      $clean_on = cleanup_validate_int(isset($_POST['clean_on']) ? $_POST['clean_on'] : null, 0, 1);
      $title = cleanup_validate_text(isset($_POST['clean_title']) ? $_POST['clean_title'] : null, 100);
      $description = cleanup_validate_text(isset($_POST['clean_desc']) ? $_POST['clean_desc'] : null, 10000);
      $file = cleanup_validate_file(isset($_POST['clean_file']) ? $_POST['clean_file'] : null);
      if ($cid === false || $increment === false || $log === false || $clean_on === false || $title === false || $description === false || $file === false)
        stderr('Error', 'Invalid cleanup task data');
      if (!is_file(ROOT_PATH . '/include/cleanup/' . $file))
        stderr('Error', 'You need to upload the cleanup file first!');

      $clean_time = TIME_NOW + $increment;
      $sql = 'UPDATE cleanup SET clean_title = ' . sqlesc($title)
        . ', clean_desc = ' . sqlesc($description)
        . ', clean_file = ' . sqlesc($file)
        . ', clean_time = ' . (int) $clean_time
        . ', clean_increment = ' . (int) $increment
        . ', clean_log = ' . (int) $log
        . ', clean_on = ' . (int) $clean_on
        . ' WHERE clean_id = ' . (int) $cid;
      mysql_query($sql) or sqlerr(__FILE__, __LINE__);

      cleanup_show_main();
      exit;
	}




function cleanup_show_new() {

    global $cleanup_csrf;
    $htmlout = '';

    $htmlout .= "
                     <div class='cblock'>
                         <div class='cblock-header'>Add a new cleanup task</div>
                         <div class='cblock-content'>
                             <div style='width: 615px; text-align: left; padding: 10px; margin: 0 auto;border-style: solid; border-color: lightgrey; border-width: 5px 2px;'>
                                 <form name='inputform' method='post' action='admin.php?action=cleanup_manager'>
                                      <input type='hidden' name='mode' value='takenew' />
                                      <input type='hidden' name='csrf_token' value='" . htmlsafechars($cleanup_csrf) . "' />
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Title</label>
                                          <input type='text' value='' name='clean_title' style='width:350px;' />
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Description</label>
                                          <input type='text' value='' name='clean_desc' style='width:350px;' />
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Cleanup File Name</label>
                                          <input type='text' value='' name='clean_file' style='width:350px;' />
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Cleanup Interval</label>
                                          <input type='text' value='' name='clean_increment' style='width:350px;' />
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>Cleanup Log</label>
                                          Yes &nbsp; <input name='clean_log' value='1' type='radio' />&nbsp;&nbsp;&nbsp;<input name='clean_log' value='0' checked='checked' type='radio' /> &nbsp; No
                                      </div>
                                      <div style='margin-bottom:5px;'>
                                          <label style='float:left;width:200px;'>cleanup On or Off?</label>
                                          Yes &nbsp; <input name='clean_on' value='1' type='radio' />&nbsp;&nbsp;&nbsp;<input name='clean_on' value='0' checked='checked' type='radio' /> &nbsp; No
                                      </div>
                                      <div style='text-align:center;'><input type='submit' name='submit' value='Add' class='button' />&nbsp;<input type='button' value='Cancel' onclick='javascript: history.back()' /></div>
                                 </form>
                             </div>
                         </div>
                     </div>";

    print stdhead('Cleanup Manager - Add New') . $htmlout . stdfoot();
}



function cleanup_take_new() {

      cleanup_require_csrf();
      $increment = cleanup_validate_int(isset($_POST['clean_increment']) ? $_POST['clean_increment'] : null, 1, 31536000);
      $log = cleanup_validate_int(isset($_POST['clean_log']) ? $_POST['clean_log'] : null, 0, 1);
      $clean_on = cleanup_validate_int(isset($_POST['clean_on']) ? $_POST['clean_on'] : null, 0, 1);
      $title = cleanup_validate_text(isset($_POST['clean_title']) ? $_POST['clean_title'] : null, 100);
      $description = cleanup_validate_text(isset($_POST['clean_desc']) ? $_POST['clean_desc'] : null, 10000);
      $file = cleanup_validate_file(isset($_POST['clean_file']) ? $_POST['clean_file'] : null);
      if ($increment === false || $log === false || $clean_on === false || $title === false || $description === false || $file === false)
        stderr('Error', 'Invalid cleanup task data');
      if (!is_file(ROOT_PATH . '/include/cleanup/' . $file))
        stderr('Error', 'You need to upload the cleanup file first!');

      $clean_time = TIME_NOW + $increment;
      $cron_key = bin2hex(random_bytes(16));
      $sql = 'INSERT INTO cleanup (clean_title, clean_desc, clean_file, clean_time, clean_increment, clean_cron_key, clean_log, clean_on) VALUES ('
        . sqlesc($title) . ', ' . sqlesc($description) . ', ' . sqlesc($file) . ', ' . (int) $clean_time . ', '
        . (int) $increment . ', ' . sqlesc($cron_key) . ', ' . (int) $log . ', ' . (int) $clean_on . ')';
      mysql_query($sql) or sqlerr(__FILE__, __LINE__);
      if (!mysql_insert_id())
        stderr('Error', 'Something went horridly wrong');
      stderr('Info', 'Success, new cleanup task added!');
      exit;
	}




function cleanup_take_delete() {

      cleanup_require_csrf();
      $cid = cleanup_validate_int(isset($_POST['cid']) ? $_POST['cid'] : null, 1);
      if ($cid === false)
        stderr('Error', 'Bad cleanup task ID');

      mysql_query('DELETE FROM cleanup WHERE clean_id = ' . (int) $cid) or sqlerr(__FILE__, __LINE__);
      if (mysql_affected_rows() !== 1)
        stderr('Error', 'Something went horridly wrong');
      stderr('Info', 'Success, cleanup task deleted!');
      exit;
	}




function cleanup_take_unlock() {

      cleanup_require_csrf();
      $cid = cleanup_validate_int(isset($_POST['cid']) ? $_POST['cid'] : null, 1);
      if ($cid === false)
        stderr('Error', 'Bad cleanup task ID');

      $res = mysql_query('SELECT clean_on FROM cleanup WHERE clean_id = ' . (int) $cid . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
      $row = mysql_fetch_assoc($res);
      if (!$row)
        stderr('Error', 'Cleanup task not found');
      $clean_on = ((int) $row['clean_on'] === 1) ? 0 : 1;
      mysql_query('UPDATE cleanup SET clean_on = ' . $clean_on . ' WHERE clean_id = ' . (int) $cid . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
      if (mysql_affected_rows() !== 1)
        stderr('Error', 'Something went horridly wrong');
      cleanup_show_main();
      exit;
	}
?>