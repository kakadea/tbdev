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
$csrf = security_csrf_token('bitbucket-upload');
dbconn();
loggedinorreturn();

$lang = array_merge( load_language('global'), load_language('bitbucket') );

$HTMLOUT = '';

$TBDEV['bb_upload_size'] = 256 * 1024;


if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	if (!security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'bitbucket-upload'))
	{
		http_response_code(400);
		exit('Invalid upload request.');
	}
	if (!security_rate_limit('bitbucket-upload', security_client_identity() . '|' . (int) $CURUSER['id'], 20, 3600))
	{
		http_response_code(429);
		exit('Too many uploads. Please try again later.');
	}
	if (!isset($_FILES['file']) || !is_array($_FILES['file']))
		stderr($lang['bitbucket_failed'], $lang['bitbucket_not_received']);

	$file = $_FILES['file'];
	if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		stderr($lang['bitbucket_failed'], $lang['bitbucket_not_received']);
	if (!isset($file['size']) || (int) $file['size'] < 1)
		stderr($lang['bitbucket_failed'], $lang['bitbucket_not_received']);
	if ((int) $file['size'] > $TBDEV['bb_upload_size'])
		stderr($lang['bitbucket_failed'], $lang['bitbucket_too_large']);

	$filename = basename((string) $file['name']);
	if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,127}\.(?:gif|jpe?g|png)\z/i', $filename))
		stderr($lang['bitbucket_failed'], $lang['bitbucket_bad_name']);

	$upload_dir = rtrim((string) $TBDEV['uploads_dir'], '/\\');
	if ($upload_dir === '' || !is_dir($upload_dir) || !is_writable($upload_dir))
		stderr($lang['bitbucket_error'], $lang['bitbucket_internal_error2']);
	$tgtfile = $upload_dir . '/' . $filename;
	if (file_exists($tgtfile))
		stderr($lang['bitbucket_failed'], $lang['bitbucket_no_name'] . '<b>' . htmlsafechars($filename) . '</b> ' . $lang['bitbucket_exists']);

	$it = @exif_imagetype($file['tmp_name']);
	if ($it !== IMAGETYPE_GIF && $it !== IMAGETYPE_JPEG && $it !== IMAGETYPE_PNG)
		stderr($lang['bitbucket_failed'], $lang['bitbucket_not_recognized']);

	$ext = strtolower(strrchr($filename, '.'));
	if (($it === IMAGETYPE_GIF && $ext !== '.gif') || ($it === IMAGETYPE_JPEG && !in_array($ext, array('.jpg', '.jpeg'), true)) || ($it === IMAGETYPE_PNG && $ext !== '.png'))
		stderr($lang['bitbucket_error'], $lang['bitbucket_invalid_extension']);

	if (!move_uploaded_file($file['tmp_name'], $tgtfile))
		stderr($lang['bitbucket_error'], $lang['bitbucket_internal_error2']);
	$url = rtrim($TBDEV['baseurl'], '/') . '/bitbucket/' . rawurlencode($filename);
	$safe_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
	stderr($lang['bitbucket_success'], $lang['bitbucket_url'] . '<b><a href="' . $safe_url . '">' . $safe_url . '</a></b><p><a href="bitbucket-upload.php">' . $lang['bitbucket_upload_another'] . '</a>.');
}


    $HTMLOUT .= "
                     <div class='cblock'>
                         <div class='cblock-header'>{$lang['bitbucket_bbupload']}</div>
                         <div class='cblock-lb'><b>{$lang['bitbucket_maximum']}".number_format($TBDEV['bb_upload_size'])."{$lang['bitbucket_bytes']}</b></div>
                         <div class='cblock-content'>
                             <form method='post' action='{$TBDEV['baseurl']}/bitbucket-upload.php' enctype='multipart/form-data'>
                                  <input type='hidden' name='csrf_token' value='" . htmlsafechars($csrf) . "' />
                                  <table border='1' cellspacing='0' cellpadding='5'>
                                        <tr><td class='rowhead'>{$lang['bitbucket_upload_file']}</td><td><input type='file' name='file' size='60' accept='image/gif,image/jpeg,image/png' required /></td></tr>
                                        <tr><td colspan='2' align='center'><input type='submit' value='{$lang['bitbucket_upload']}' class='btn' /></td></tr>
                                  </table>
                             </form>
                             <br />
                             <table class='main' width='410' border='0' cellspacing='0' cellpadding='0'>
                                   <tr>
                                      <td class='embedded'><div class='small'>{$lang['bitbucket_disclaimer']}</div></td>
                                   </tr>
                             </table>
                         </div>
                     </div>";


    print stdhead("{$lang['bitbucket_bbupload']}") . $HTMLOUT .stdfoot();

?>