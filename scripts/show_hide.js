(function () {
  'use strict';

  window.showhide = function (id) {
    var element = document.getElementById(String(id));
    if (!element) return false;

    var isHidden = window.getComputedStyle(element).display === 'none';
    element.hidden = !isHidden;
    element.style.display = isHidden ? '' : 'none';
    return isHidden;
  };
}());
