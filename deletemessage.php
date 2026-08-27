<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/security.php';

security_session_start();
dbconn(false);
loggedinorreturn();
$lang = array_merge(load_language('global'), load_language('deletemessage'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'messages'))
{
    http_response_code(400);
    exit('Invalid message deletion request.');
}
if (!security_rate_limit('message-delete-legacy', security_client_identity() . '|' . (int) $CURUSER['id'], 30, 300))
{
    http_response_code(429);
    exit('Too many message deletion requests.');
}

$id = isset($_POST['id']) && !is_array($_POST['id']) && preg_match('/\A\d+\z/', (string) $_POST['id']) ? (int) $_POST['id'] : 0;
$type = isset($_POST['type']) && is_string($_POST['type']) ? $_POST['type'] : '';
if (!is_valid_id($id) || !in_array($type, array('in', 'out'), true))
{
    http_response_code(400);
    exit($lang['deletemessage_unknown']);
}

$res = mysql_query('SELECT receiver, sender, location FROM messages WHERE id = ' . (int) $id . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
$message = mysql_fetch_assoc($res);
if (!$message)
    exit($lang['deletemessage_bad_id']);

$owner_field = $type === 'in' ? 'receiver' : 'sender';
if ((int) $message[$owner_field] !== (int) $CURUSER['id'])
    exit($lang['deletemessage_dont_do']);

$location = (string) $message['location'];
if ($type === 'in' && $location === 'in')
    mysql_query('DELETE FROM messages WHERE id = ' . (int) $id . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
elseif ($type === 'out' && $location === 'out')
    mysql_query('DELETE FROM messages WHERE id = ' . (int) $id . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
elseif ($location === 'both')
    mysql_query("UPDATE messages SET location = '" . ($type === 'in' ? 'out' : 'in') . "' WHERE id = " . (int) $id . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
else
    exit($type === 'in' ? $lang['deletemessage_not_inbox'] : $lang['deletemessage_sentbox']);

header('Location: ' . $TBDEV['baseurl'] . '/messages.php' . ($type === 'out' ? '?out=1' : ''));
exit;
