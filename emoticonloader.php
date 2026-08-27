<?php

require_once __DIR__ . '/include/emoticons.php';

function emoticon_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?><!doctype html>
<html lang='en'>
<head>
  <meta charset='utf-8' />
  <meta name='viewport' content='width=device-width, initial-scale=1' />
  <meta name='referrer' content='same-origin' />
  <title>Emoticons</title>
  <link rel='stylesheet' href='1.css' media='all' />
  <style>
    body { margin: 0; padding: .75rem; }
    main { width: 100%; max-width: 24rem; margin: 0 auto; }
    h1 { font-size: 1.15rem; margin: 0 0 .75rem; }
    .emoticon-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; list-style: none; margin: 0; padding: 0; }
    .emoticon-list a { display: flex; min-height: 3rem; align-items: center; justify-content: center; gap: .5rem; padding: .5rem; border: 1px solid #d5dde5; border-radius: 6px; background: #fff; }
    .emoticon-list img { max-width: 2rem; max-height: 2rem; }
  </style>
</head>
<body>
  <main aria-labelledby='emoticon-title'>
    <h1 id='emoticon-title'>More emoticons</h1>
    <ul class='emoticon-list'>
<?php foreach ($smilies as $code => $filename):
    $safe_code = emoticon_escape($code);
    $safe_filename = basename((string) $filename);
    $image_url = 'pic/smilies/' . rawurlencode($safe_filename);
?>
      <li>
        <a href='#' class='emoticon-choice' data-smilie='<?php echo $safe_code; ?>' aria-label='Insert <?php echo $safe_code; ?>'>
          <span><?php echo $safe_code; ?></span>
          <img src='<?php echo emoticon_escape($image_url); ?>' alt='' loading='lazy' />
        </a>
      </li>
<?php endforeach; ?>
    </ul>
  </main>
  <script>
    (function () {
      'use strict';
      document.querySelectorAll('.emoticon-choice').forEach(function (choice) {
        choice.addEventListener('click', function (event) {
          event.preventDefault();
          var opener = window.opener;
          var form = opener && opener.document && opener.document.forms.namedItem('bbcode2text');
          var editor = form && form.elements.namedItem('body');
          if (!editor) return;
          editor.value += ' ' + choice.dataset.smilie + ' ';
          editor.focus();
        });
      });
    }());
  </script>
</body>
</html>
