(function () {
  'use strict';

  function getEditor(elementName) {
    var form = document.forms.namedItem('bbcode2text');
    if (!form) return null;
    var element = form.elements.namedItem(String(elementName));
    return element && typeof element.value === 'string' ? element : null;
  }

  function safeUrl(value) {
    try {
      var url = new URL(String(value).trim(), window.location.href);
      return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : '';
    } catch (error) {
      return '';
    }
  }

  window.addText = function (elementName, prefix, suffix) {
    var editor = getEditor(elementName);
    if (!editor) return false;

    var start = typeof editor.selectionStart === 'number' ? editor.selectionStart : editor.value.length;
    var end = typeof editor.selectionEnd === 'number' ? editor.selectionEnd : start;
    var before = editor.value.slice(0, start);
    var selected = editor.value.slice(start, end);
    var after = editor.value.slice(end);
    var inserted = String(prefix) + selected + String(suffix);

    editor.value = before + inserted + after;
    editor.focus();
    if (typeof editor.setSelectionRange === 'function') {
      var cursorStart = before.length + String(prefix).length;
      editor.setSelectionRange(cursorStart, cursorStart + selected.length);
    }
    return false;
  };

  window.insertText = function (elementName, text) {
    return window.addText(elementName, '', String(text));
  };

  window.tag_url = function () {
    var url = safeUrl(window.prompt('Please enter the URL', 'https://'));
    var title = window.prompt('Please enter the URL name', 'My Webpage');
    if (!url || !title || !String(title).trim()) {
      window.alert('A valid HTTP(S) URL and a name are required.');
      return false;
    }
    return window.addText('body', '[url=' + url + ']' + String(title).trim(), '[/url]');
  };

  window.tag_image = function () {
    var url = safeUrl(window.prompt('Please enter the image URL', 'https://'));
    if (!url) {
      window.alert('A valid HTTP(S) image URL is required.');
      return false;
    }
    return window.addText('body', '[img]' + url, '[/img]');
  };

  window.tag_list = function () {
    var items = [];
    var value;
    do {
      value = window.prompt('Enter a list item. Leave blank to finish.', '');
      if (value !== null && String(value).trim() !== '') items.push('[*]' + String(value).trim());
    } while (value !== null && String(value).trim() !== '');

    if (items.length) return window.addText('body', '[list]\n' + items.join('\n') + '\n', '[/list]\n');
    return false;
  };

  window.alterfont = function (value, tag) {
    if (!value || !tag) return false;
    var form = document.forms.namedItem('bbcode2text');
    if (form) {
      ['ffont', 'fsize', 'fcolor'].forEach(function (name) {
        var field = form.elements.namedItem(name);
        if (field) field.selectedIndex = 0;
      });
    }
    return window.addText('body', '[' + String(tag) + '=' + String(value) + ']', '[/' + String(tag) + ']');
  };

  window.more_emoticons = function () {
    return window.open('emoticonloader.php', 'Emoticons', 'width=300,height=500,resizable=yes,scrollbars=yes,noopener,noreferrer');
  };
}());
