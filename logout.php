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
require_once('include/bittorrent.php');

security_session_start();
if (session_status() === PHP_SESSION_ACTIVE)
{
    $_SESSION = array();
    if (ini_get('session.use_cookies'))
    {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

dbconn();
logoutcookie();

//header("Refresh: 0; url=./");
Header("Location: {$TBDEV['baseurl']}/");

?>