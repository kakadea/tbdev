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
error_reporting(E_ALL);

define('SQL_DEBUG', 2);

/* Compare php version for date/time stuff etc! */
	if (version_compare(PHP_VERSION, "5.1.0RC1", ">="))
		date_default_timezone_set('Europe/London');


define('TIME_NOW', time());

$TBDEV['time_adjust'] =  0;
$TBDEV['time_offset'] = '0'; 
$TBDEV['time_use_relative'] = 1;
$TBDEV['time_use_relative_format'] = '{--}, h:i A';
$TBDEV['time_joined'] = 'j-F y';
$TBDEV['time_short'] = 'jS F Y - h:i A';
$TBDEV['time_long'] = 'M j Y, h:i A';
$TBDEV['time_tiny'] = '';
$TBDEV['time_date'] = '';


function tbdev_env($key, $default = '')
{
	$value = getenv($key);
	return ($value === false || $value === '') ? $default : $value;
}

// DB setup. Production values must come from the environment, never from Git.
$TBDEV['mysql_host'] = tbdev_env('TBDEV_DB_HOST', '127.0.0.1');
$TBDEV['mysql_user'] = tbdev_env('TBDEV_DB_USER', 'tbdev');
$TBDEV['mysql_pass'] = tbdev_env('TBDEV_DB_PASSWORD', '');
$TBDEV['mysql_db']   = tbdev_env('TBDEV_DB_NAME', 'tbdev');
$TBDEV['mysql_port'] = (int) tbdev_env('TBDEV_DB_PORT', '3306');

// Cookie setup
$TBDEV['cookie_prefix']  = 'tbalpha_'; // This allows you to have multiple trackers, eg for demos, testing etc.
$TBDEV['cookie_path']    = ''; // ATTENTION: You should never need this unless the above applies eg: /tbdev
$TBDEV['cookie_domain']  = ''; // set to eg: .somedomain.com or is subdomain set to: .sub.somedomain.com
$TBDEV['IPcookieCheck'] = 1;
                              
$TBDEV['site_online'] = 1;
$TBDEV['tracker_post_key'] = tbdev_env('TBDEV_TRACKER_POST_KEY', '');
$TBDEV['tracker_cache_key'] = tbdev_env('TBDEV_TRACKER_CACHE_KEY', '');
$TBDEV['max_torrent_size'] = 1000000;
$TBDEV['announce_interval'] = 60 * 30;
$TBDEV['signup_timeout'] = 86400 * 3;
$TBDEV['minvotes'] = 1;
$TBDEV['max_dead_torrent_time'] = 6 * 3600;

// Max users on site
$TBDEV['maxusers'] = 5000; // LoL Who we kiddin' here?


if ( strtoupper( substr(PHP_OS, 0, 3) ) == 'WIN' )
  {
    $file_path = str_replace( "\\", "/", dirname(__FILE__) );
    $file_path = str_replace( "/include", "", $file_path );
  }
  else
  {
    $file_path = dirname(__FILE__);
    $file_path = str_replace( "/include", "", $file_path );
  }
  
define('ROOT_PATH', $file_path);
$TBDEV['app_env'] = strtolower(trim(tbdev_env('APP_ENV', 'production')));

$torrent_dir_default = $TBDEV['app_env'] === 'production' ? '' : ROOT_PATH . '/torrents';
$TBDEV['torrent_dir'] = rtrim(tbdev_env('TBDEV_TORRENT_DIR', $torrent_dir_default), '/\\');
$TBDEV['cache_dir'] = rtrim(tbdev_env('TBDEV_CACHE_DIR', $TBDEV['app_env'] === 'production' ? '' : ROOT_PATH . '/cache'), '/\\');
$TBDEV['uploads_dir'] = rtrim(tbdev_env('TBDEV_UPLOAD_DIR', $TBDEV['app_env'] === 'production' ? '' : ROOT_PATH . '/bitbucket'), '/\\');
if ($TBDEV['torrent_dir'] === '' || $TBDEV['torrent_dir'][0] !== '/')
  die('TBDEV_TORRENT_DIR must be an absolute writable data directory.');
if ($TBDEV['cache_dir'] === '' || $TBDEV['cache_dir'][0] !== '/')
  die('TBDEV_CACHE_DIR must be an absolute writable data directory.');
if ($TBDEV['uploads_dir'] === '' || $TBDEV['uploads_dir'][0] !== '/')
  die('TBDEV_UPLOAD_DIR must be an absolute writable data directory.');

# Production must use an explicit canonical HTTPS URL; never trust Host headers for links.
$configured_baseurl = rtrim(tbdev_env('TBDEV_BASE_URL', ''), '/');
if ($configured_baseurl === '')
{
  if ($TBDEV['app_env'] === 'production')
    die('TBDEV_BASE_URL must be configured in production.');

  $default_host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '' ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
  $default_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $configured_baseurl = $default_scheme . '://' . $default_host;
}

$baseurl_parts = parse_url($configured_baseurl);
if ($baseurl_parts === false || empty($baseurl_parts['scheme']) || empty($baseurl_parts['host']) ||
    !in_array(strtolower($baseurl_parts['scheme']), array('http', 'https'), true) ||
    isset($baseurl_parts['user']) || isset($baseurl_parts['pass']) || isset($baseurl_parts['query']) || isset($baseurl_parts['fragment']))
  die('TBDEV_BASE_URL must be an absolute HTTP(S) URL without credentials or query parameters.');
if ($TBDEV['app_env'] === 'production' && strtolower($baseurl_parts['scheme']) !== 'https')
  die('TBDEV_BASE_URL must use HTTPS in production.');
$TBDEV['baseurl'] = $configured_baseurl;

$announce_url = rtrim(tbdev_env('TBDEV_ANNOUNCE_URL', $TBDEV['baseurl'] . '/announce.php'), '/');
$announce_parts = parse_url($announce_url);
if ($announce_parts === false || empty($announce_parts['scheme']) || empty($announce_parts['host']) ||
    !in_array(strtolower($announce_parts['scheme']), array('http', 'https'), true) ||
    isset($announce_parts['user']) || isset($announce_parts['pass']) || isset($announce_parts['fragment']))
  die('TBDEV_ANNOUNCE_URL must be an absolute HTTP(S) URL.');
$TBDEV['announce_urls'] = array($announce_url);

/*
## DO NOT UNCOMMENT THIS: IT'S FOR LATER USE!
$host = getenv( 'SERVER_NAME' );
$script = getenv( 'SCRIPT_NAME' );
$script = str_replace( "\\", "/", $script );

  if( $host AND $script )
  {
    $script = str_replace( '/index.php', '', $script );

    $TBDEV['baseurl'] = "http://{$host}{$script}";
  }
*/

//set this to true to make this a tracker that only registered users may use
//$TBDEV['membersonly'] = 1; //deprecated no longer needed

//maximum number of peers (seeders+leechers) allowed before torrents starts to be deleted to make room...
//set this to something high if you don't require this feature
//$TBDEV['peerlimit'] = 50000; //deprecated. no longer used.

// Email for sender/return path.
$TBDEV['site_email'] = tbdev_env('TBDEV_SITE_EMAIL', '');
$TBDEV['site_name'] = tbdev_env('TBDEV_SITE_NAME', 'TBDev');
$TBDEV['site_key'] = tbdev_env('TBDEV_SITE_KEY', '');

if ($TBDEV['app_env'] === 'production')
{
  if (strlen($TBDEV['site_key']) < 32 || strlen($TBDEV['tracker_post_key']) < 32 || strlen($TBDEV['tracker_cache_key']) < 32)
    die('TBDEV_SITE_KEY and tracker keys must be configured with random values in production.');
  if (!filter_var($TBDEV['site_email'], FILTER_VALIDATE_EMAIL))
    die('TBDEV_SITE_EMAIL must be configured in production.');
}

$TBDEV['language'] = 'en';
//charset
$TBDEV['char_set'] = 'UTF-8'; //also to be used site wide in meta tags
if (ini_get('default_charset') != $TBDEV['char_set']) {
ini_set('default_charset',$TBDEV['char_set']);
}
$TBDEV['msg_alert'] = 1; // saves a query when off
$TBDEV['captcha'] = 0; // turns captcha on/off

$TBDEV['autoclean_interval'] = 900;
$TBDEV['log_dir'] = tbdev_env('TBDEV_LOG_DIR', ROOT_PATH . '/logs');
if ($TBDEV['log_dir'] === '' || $TBDEV['log_dir'][0] !== '/')
  die('TBDEV_LOG_DIR must be an absolute path.');
$TBDEV['log_dir'] = rtrim($TBDEV['log_dir'], '/');
$TBDEV['sql_error_log'] = $TBDEV['log_dir'] . '/sql_err_' . date('M_D_Y') . '.log';
$TBDEV['pic_base_url'] = "./pic/";
$TBDEV['stylesheet'] = "./1.css";
$TBDEV['readpost_expiry'] = 14*86400; // 14 days
$TBDEV['last_10_posts'] = 0;
//set this to size of user avatars
$TBDEV['av_img_height'] = 100;
$TBDEV['av_img_width'] = 100;
$TBDEV['allowed_ext'] = array('image/gif', 'image/png', 'image/jpeg');
// Set this to the line break character sequence of your system
//$TBDEV['linebreak'] = "\r\n"; // not used at present.

define ('UC_USER', 0);
define ('UC_POWER_USER', 1);
define ('UC_VIP', 2);
define ('UC_UPLOADER', 3);
define ('UC_MODERATOR', 4);
define ('UC_ADMINISTRATOR', 5);
define ('UC_SYSOP', 6);

//Do not modify -- versioning system
//This will help identify code for support issues at tbdev.net
define ('TBVERSION','TBDev.Lite.v1.0');

?>