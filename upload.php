<?php
require_once 'include/bittorrent.php';
require_once 'include/user_functions.php';
require_once 'include/html_functions.php';

security_session_start();
$csrf = security_csrf_token('upload');
dbconn(false);
loggedinorreturn();

$lang = array_merge(load_language('global'), load_language('upload'));

if ($CURUSER['class'] < UC_UPLOADER) {
    stderr($lang['upload_sorry'], $lang['upload_no_auth']);
}

$categories = genrelist();
$category_options = "<option value=''>{$lang['upload_choose_one']}</option>";
foreach ($categories as $category) {
    $category_id = (int) $category['id'];
    $category_name = htmlsafechars($category['name']);
    $category_options .= "<option value='{$category_id}'>{$category_name}</option>";
}

if (count($categories) > 0) {
    $category_control = "<select id='torrent-type' name='type' required>{$category_options}</select>";
    $category_help = "<p class='field-help'>Escolha a categoria que melhor descreve o conteúdo do torrent.</p>";
} else {
    $category_control = "<select id='torrent-type' name='type' disabled><option value=''>Nenhuma categoria disponível</option></select>";
    $category_help = "<p class='field-help field-warning'>Nenhuma categoria foi cadastrada ainda. Um SysOp precisa criar uma em <a href='admin.php?action=categories'>Administração → Categorias</a> antes do upload.</p>";
}

$announce_url = htmlsafechars($TBDEV['announce_urls'][0]);
$max_size = (int) $TBDEV['max_torrent_size'];
$description_editor = bbcode2textarea('body');
$upload_js = "<script type='text/javascript' src='scripts/bbcode2text.js'></script>";

$upload_html = "
<section class='page-intro'>
    <p class='eyebrow'>Catálogo</p>
    <h1>Publicar torrent</h1>
    <p>Envie um arquivo <code>.torrent</code> válido para o laboratório. O tracker usará este announce URL:</p>
    <code class='announce-url'>{$announce_url}</code>
</section>
<section class='form-card'>
    <form name='bbcode2text' id='bbcode2text' class='upload-form' enctype='multipart/form-data' action='takeupload.php' method='post'>
        <input type='hidden' name='csrf_token' value='" . htmlsafechars($csrf) . "' />
        <input type='hidden' name='MAX_FILE_SIZE' value='{$max_size}' />
        <div class='form-grid'>
            <div class='form-field form-field-wide'>
                <label for='torrent-file'>Arquivo torrent <span class='required'>*</span></label>
                <input id='torrent-file' type='file' name='file' accept='.torrent,application/x-bittorrent' required />
                <p class='field-help'>Somente arquivos .torrent até " . mksize($max_size) . ".</p>
            </div>
            <div class='form-field form-field-wide'>
                <label for='torrent-name'>Nome exibido <span class='required'>*</span></label>
                <input id='torrent-name' type='text' name='name' maxlength='255' placeholder='Ex.: Linux ISO — edição de teste' required />
                <p class='field-help'>Use um nome descritivo; ele será exibido no catálogo.</p>
            </div>
            <div class='form-field form-field-wide'>
                <label for='torrent-nfo'>NFO <span class='optional'>opcional</span></label>
                <input id='torrent-nfo' type='file' name='nfo' accept='.nfo,text/plain' />
                <p class='field-help'>Arquivo de informações de texto, limitado pelo servidor.</p>
            </div>
            <div class='form-field form-field-wide'>
                <label for='editor-body'>Descrição <span class='required'>*</span></label>
                {$description_editor}
                <p class='field-help'>Imagens externas são opcionais e aceitam somente URLs HTTP(S) terminadas em JPG, PNG, GIF ou WebP. HTML cru nunca é renderizado.</p>
            </div>
            <div class='form-field'>
                <label for='torrent-type'>Categoria <span class='required'>*</span></label>
                {$category_control}
                {$category_help}
            </div>
        </div>
        <div class='form-actions'><a class='btn btn-secondary' href='browse.php'>Cancelar</a><button class='btn btn-primary' type='submit'" . (count($categories) === 0 ? ' disabled' : '') . ">Publicar torrent</button></div>
    </form>
</section>";

print stdhead($lang['upload_stdhead'], $upload_js) . $upload_html . stdfoot();
