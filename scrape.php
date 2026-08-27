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
|   $Revision: 199 $
|   $Author$
|   $URL$
+------------------------------------------------
*/
require_once("include/config.php");
require_once("include/legacy_db.php");

    if (!@mysql_connect($TBDEV['mysql_host'], $TBDEV['mysql_user'], $TBDEV['mysql_pass']))
    {
      exit();
    }
      
    @mysql_select_db($TBDEV['mysql_db']) or exit();
    @mysql_set_charset('utf8');

    if (!isset($_GET['info_hash']) || !is_string($_GET['info_hash']) || strlen($_GET['info_hash']) !== 20)
      error('Invalid hash');

    $info_hash = bin2hex($_GET['info_hash']);
    $torrent_stmt = tbdev_db_prepare_execute(
      'SELECT info_hash, seeders, leechers, times_completed FROM torrents WHERE info_hash = ? LIMIT 1',
      's',
      array($info_hash)
    );
    if (!$torrent_stmt)
      error('Tracker database error');
    $res = mysqli_stmt_get_result($torrent_stmt);
    if (!$res || mysqli_num_rows($res) === 0)
      error('No torrent with that hash found');
    
    $benc = 'd5:files';

    while ($row = mysqli_fetch_assoc($res))
    {
      $benc .= 'd20:' . pack('H*', $row['info_hash']) . "d8:completei{$row['seeders']}e10:downloadedi{$row['times_completed']}e10:incompletei{$row['leechers']}eeee";
    }
    mysqli_free_result($res);
    mysqli_stmt_close($torrent_stmt);

    //$benc .= 'd5:flagsd20:min_request_intervali1800ee';
    //$benc .= 'd5:flagsd20:min_request_intervali1800eee';
    
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, max-age=0');
    header('Pragma: no-cache');
    print($benc);


function error($err){

    header('Content-Type: text/plain; charset=UTF-8');
    header('Pragma: no-cache');
    $err = (string) $err;
    exit('d14:failure reason' . strlen($err) . ':' . $err . 'ed5:flagsd20:min_request_intervali1800eeee');

}


?>