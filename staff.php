<?php
require_once 'include/bittorrent.php';
require_once 'include/user_functions.php';

dbconn();
loggedinorreturn();

$lang = array_merge(load_language('global'), load_language('staff'));
$groups = array(
    UC_SYSOP => array('title' => $lang['header_sysops'], 'members' => array()),
    UC_ADMINISTRATOR => array('title' => $lang['header_admins'], 'members' => array()),
    UC_MODERATOR => array('title' => $lang['header_mods'], 'members' => array()),
);

$query = mysql_query(
    "SELECT users.id, username, added, last_access, class, avatar, av_w, av_h, status " .
    "FROM users WHERE class >= " . (int) UC_MODERATOR . " AND status='confirmed' ORDER BY class DESC, username"
) or sqlerr(__FILE__, __LINE__);

while ($member = mysql_fetch_assoc($query)) {
    $class = (int) $member['class'];
    if (isset($groups[$class]))
        $groups[$class]['members'][] = $member;
}

function render_staff_group($group) {
    global $TBDEV, $lang;
    $cards = '';
    $now = TIME_NOW;
    foreach ($group['members'] as $member) {
        $id = (int) $member['id'];
        $username = htmlsafechars($member['username']);
        $avatar = (!empty($member['avatar']) && (int) $member['av_w'] > 5 && (int) $member['av_h'] > 5)
            ? htmlsafechars($member['avatar'])
            : 'images/default_thumb.png';
        $presence = ((int) $member['last_access'] > $now - 180) ? 'online' : 'offline';
        $last_seen = (int) $member['last_access'] > 0 ? get_date($member['last_access'], '') : 'Never';
        $joined = get_date($member['added'], '');
        $cards .= "<article class='staff-card'>
            <div class='staff-card-head'><a href='userdetails.php?id={$id}'><img class='staff-avatar' src='{$avatar}' alt='' /></a><div><h3><a href='userdetails.php?id={$id}'>{$username}</a></h3><span class='presence {$presence}'>" . ucfirst($presence) . "</span></div></div>
            <dl class='staff-meta'><div><dt>Joined</dt><dd>{$joined}</dd></div><div><dt>Last seen</dt><dd>{$last_seen}</dd></div></dl>
            <div class='staff-actions'><a class='icon-action' href='sendmessage.php?receiver={$id}'><img src='{$TBDEV['pic_base_url']}staff/users.png' alt='' />Message</a><a class='icon-action' href='email-gateway.php?id={$id}'><img src='{$TBDEV['pic_base_url']}staff/mail.png' alt='' />Email</a></div>
        </article>";
    }
    if ($cards === '')
        $cards = "<div class='empty-state'><strong>No members in this group.</strong></div>";
    return "<section class='staff-group'><div class='section-heading'><h2>" . htmlsafechars($group['title']) . "</h2></div><div class='staff-grid'>{$cards}</div></section>";
}

$sections = '';
foreach ($groups as $group)
    $sections .= render_staff_group($group);

$html = "<section class='page-intro'><p class='eyebrow'>Equipe</p><h1>Staff</h1><p>Encontre os responsáveis pelo laboratório e entre em contato quando necessário.</p></section><section class='staff-page'>{$sections}</section>";

print stdhead($lang['stdhead_staff']) . $html . stdfoot();
