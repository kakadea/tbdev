<?php
ob_start('ob_gzhandler');

require_once 'include/bittorrent.php';
require_once 'include/user_functions.php';
require_once 'include/cache_functions.php';

dbconn(true);
loggedinorreturn();

$lang = array_merge(load_language('global'), load_language('index'));

$stats = getCache('frontpagestats');
if (!is_array($stats)) {
    $stats = array('seeders' => 0, 'leechers' => 0);
    $peer_result = @mysql_query("SELECT seeder, COUNT(*) AS cnt FROM peers GROUP BY seeder");
    if ($peer_result) {
        while ($row = mysql_fetch_assoc($peer_result)) {
            if (isset($row['seeder']) && $row['seeder'] === 'yes') {
                $stats['seeders'] = (int) $row['cnt'];
            } elseif (isset($row['cnt'])) {
                $stats['leechers'] = (int) $row['cnt'];
            }
        }
    }
    $stats['registered'] = (int) get_row_count('users');
    $stats['torrents'] = (int) get_row_count('torrents');
    $stats['peers'] = $stats['seeders'] + $stats['leechers'];
    $stats['ratio'] = $stats['leechers'] > 0
        ? round(($stats['seeders'] / $stats['leechers']) * 100)
        : 0;
    setCache('frontpagestats', $stats, 10);
}

$news = getCache('news');
if (!is_array($news)) {
    $news = array();
    $news_result = @mysql_query(
        'SELECT * FROM news WHERE added + (3600 * 24 * 45) > ' . TIME_NOW .
        ' ORDER BY added DESC LIMIT 10'
    );
    if ($news_result) {
        while ($row = mysql_fetch_assoc($news_result)) {
            if (isset($row['id'])) {
                $news[(int) $row['id']] = $row;
            }
        }
        setCache('news', $news, 30);
    }
}

$news_html = '';
if (count($news) > 0) {
    require_once 'include/bbcode_functions.php';
    foreach ($news as $item) {
        $headline = htmlsafechars(isset($item['headline']) ? $item['headline'] : 'News');
        $body = isset($item['body']) ? format_comment($item['body']) : '';
        $added = isset($item['added']) ? get_date($item['added'], 'DATE') : '';
        $actions = '';
        if (get_user_class() >= UC_ADMINISTRATOR && isset($item['id'])) {
            $id = (int) $item['id'];
            $actions = "<span class='card-actions'><a href='admin.php?action=news&amp;mode=edit&amp;newsid={$id}'>Edit</a><a href='admin.php?action=news&amp;mode=delete&amp;newsid={$id}'>Delete</a></span>";
        }
        $news_html .= "<article class='news-card'><div class='news-card-head'><h3>{$headline}</h3>{$actions}</div><p class='eyebrow'>{$added}</p><div class='news-card-body'>{$body}</div></article>";
    }
} else {
    $news_html = "<div class='empty-state'><strong>No news yet.</strong><span>Use the admin area to publish the first tracker update.</span></div>";
}

$admin_link = get_user_class() >= UC_ADMINISTRATOR
    ? "<a class='text-link' href='admin.php?action=news'>Manage news</a>"
    : '';

$retired_notice = '';
if (isset($_GET['module_retired'])) {
    $retired_modules = array('chat' => 'Chat', 'forums' => 'Fóruns', 'links' => 'Links', 'faq' => 'FAQ');
    $retired_key = strtolower((string) $_GET['module_retired']);
    if (isset($retired_modules[$retired_key])) {
        $retired_notice = "<div class='notice notice-info'><strong>{$retired_modules[$retired_key]} foi descontinuado.</strong><span>Este laboratório mantém apenas o catálogo, tracker e ferramentas administrativas essenciais.</span></div>";
    }
}

$dashboard = "{$retired_notice}
<section class='dashboard-hero'>
    <div>
        <p class='eyebrow'>BitTorrent Work · laboratório</p>
        <h1>Seu tracker, em uma visão.</h1>
        <p class='hero-copy'>Acompanhe o estado do catálogo, publique torrents e mantenha o ambiente sob controle.</p>
    </div>
    <div class='hero-actions'><a class='btn btn-primary' href='browse.php'>Browse torrents</a><a class='btn btn-secondary' href='upload.php'>Upload torrent</a></div>
</section>
<section class='stat-grid' aria-label='Tracker statistics'>
    <div class='stat-card'><span class='stat-label'>Registered users</span><strong>" . number_format($stats['registered']) . "</strong><span class='stat-note'>contas confirmadas</span></div>
    <div class='stat-card'><span class='stat-label'>Torrents</span><strong>" . number_format($stats['torrents']) . "</strong><span class='stat-note'>no catálogo</span></div>
    <div class='stat-card'><span class='stat-label'>Active peers</span><strong>" . number_format($stats['peers']) . "</strong><span class='stat-note'>" . number_format($stats['seeders']) . " seeders · " . number_format($stats['leechers']) . " leechers</span></div>
    <div class='stat-card'><span class='stat-label'>Seed / leech ratio</span><strong>" . number_format($stats['ratio']) . "%</strong><span class='stat-note'>atividade atual</span></div>
</section>
<section class='dashboard-section'>
    <div class='section-heading'><div><p class='eyebrow'>Atualizações</p><h2>News</h2></div>{$admin_link}</div>
    <div class='news-list'>{$news_html}</div>
</section>
<section class='dashboard-section quick-start'>
    <div class='section-heading'><div><p class='eyebrow'>Próximos passos</p><h2>Comece por aqui</h2></div></div>
    <div class='quick-grid'><a href='browse.php'><strong>Browse</strong><span>Encontre torrents disponíveis.</span></a><a href='upload.php'><strong>Upload</strong><span>Adicione um torrent ao catálogo.</span></a><a href='topten.php'><strong>Top 10</strong><span>Veja os destaques do tracker.</span></a><a href='staff.php'><strong>Staff</strong><span>Conheça a equipe do ambiente.</span></a></div>
</section>";

print stdhead('Home') . $dashboard . stdfoot();
