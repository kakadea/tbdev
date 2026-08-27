(function () {
  'use strict';

  window.PopUp = function (url, name, width, height, center, resize, scroll, posleft, postop) {
    var popupWidth = Math.max(240, Number(width) || 400);
    var popupHeight = Math.max(160, Number(height) || 300);
    var left = Number(posleft) || 0;
    var top = Number(postop) || 0;

    if (center && window.screen) {
      left = Math.max(0, Math.round((window.screen.availWidth - popupWidth) / 2));
      top = Math.max(0, Math.round((window.screen.availHeight - popupHeight) / 2));
    }

    var features = [
      'width=' + popupWidth,
      'height=' + popupHeight,
      'left=' + left,
      'top=' + top,
      'resizable=' + (resize ? 'yes' : 'no'),
      'scrollbars=' + (scroll ? 'yes' : 'no'),
      'noopener,noreferrer'
    ].join(',');

    var popup = window.open(String(url), String(name || '_blank'), features);
    if (popup && typeof popup.focus === 'function') popup.focus();
    return popup;
  };
}());
