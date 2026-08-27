<?php
if (!defined('IN_TBDEV_ADMIN')) {
    print '<h1>Incorrect access</h1>You cannot access this file directly.';
    exit();
}

require_once 'include/user_functions.php';
$lang = array_merge($lang, load_language('ad_index'));

$groups = array(
    array('title' => 'Users & moderation', 'items' => array(
        array('href' => 'admin.php?action=bans', 'label' => $lang['index_bans'], 'description' => 'Manage blocked addresses and accounts.'),
        array('href' => 'admin.php?action=adduser', 'label' => $lang['index_new_user'], 'description' => 'Create a controlled lab account.'),
        array('href' => 'users.php', 'label' => $lang['index_user_list'], 'description' => 'Search and review confirmed users.'),
        array('href' => 'admin.php?action=usersearch', 'label' => $lang['index_user_search'], 'description' => 'Find a user by account data.'),
        array('href' => 'admin.php?action=delacct', 'label' => $lang['index_delacct'], 'description' => 'Remove an account after confirmation.'),
        array('href' => 'admin.php?action=log', 'label' => $lang['index_log'], 'description' => 'Review administrative events.'),
    )),
    array('title' => 'Catalog & tracker', 'items' => array(
        array('href' => 'admin.php?action=categories', 'label' => $lang['index_categories'], 'description' => 'Create categories required for uploads.'),
        array('href' => 'admin.php?action=stats', 'label' => $lang['index_stats'], 'description' => 'View tracker counters and activity.'),
        array('href' => 'admin.php?action=testip', 'label' => $lang['index_testip'], 'description' => 'Inspect an IP against tracker rules.'),
        array('href' => 'admin.php?action=docleanup', 'label' => $lang['index_mcleanup'], 'description' => 'Run only deliberately reviewed cleanup jobs.'),
        array('href' => 'admin.php?action=cleanup_manager', 'label' => 'Cleanup Manager', 'description' => 'Configure maintenance tasks safely.'),
        array('href' => 'tags.php', 'label' => $lang['index_tags'], 'description' => 'Manage catalog tags.'),
    )),
    array('title' => 'Content & presentation', 'items' => array(
        array('href' => 'admin.php?action=news', 'label' => $lang['index_news'], 'description' => 'Publish updates on the home page.'),
        array('href' => 'admin.php?action=rules', 'label' => $lang['index_rules'], 'description' => 'Edit the active tracker rules.'),
        array('href' => 'smilies.php', 'label' => $lang['index_emoticons'], 'description' => 'Review the available emoticons.'),
        array('href' => 'reputation_ad.php', 'label' => $lang['index_rep_system'], 'description' => 'Manage reputation thresholds.'),
        array('href' => 'reputation_settings.php', 'label' => $lang['index_rep_settings'], 'description' => 'Adjust reputation settings.'),
    )),
    array('title' => 'Database diagnostics', 'items' => array(
        array('href' => 'admin.php?action=mysql_overview', 'label' => $lang['index_mysql_overview'], 'description' => 'Inspect the isolated database overview.'),
        array('href' => 'admin.php?action=mysql_stats', 'label' => $lang['index_mysql_stats'], 'description' => 'Inspect database statistics.'),
    )),
);

$html = "<section class='page-intro admin-intro'><p class='eyebrow'>Controle</p><h1>Staff tools</h1><p>Ferramentas administrativas do BitTorrent Work. Ações destrutivas exigem revisão e confirmação explícita.</p></section><div class='admin-dashboard'>";
foreach ($groups as $group) {
    $html .= "<section class='admin-group'><div class='section-heading'><h2>" . htmlsafechars($group['title']) . "</h2></div><div class='admin-grid'>";
    foreach ($group['items'] as $item) {
        $href = htmlsafechars($item['href']);
        $label = htmlsafechars($item['label']);
        $description = htmlsafechars($item['description']);
        $html .= "<a class='admin-card' href='{$href}'><strong>{$label}</strong><span>{$description}</span><em>Open →</em></a>";
    }
    $html .= '</div></section>';
}
$html .= '</div>';

print stdhead('Staff tools') . $html . stdfoot();
?>
