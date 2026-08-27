(function () {
  'use strict';

  function setPanelState(toggle) {
    var targetId = toggle.getAttribute('data-target');
    var panel = targetId ? document.getElementById(targetId) : null;
    if (!panel) return;

    var expanded = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    panel.hidden = expanded;
    panel.style.display = expanded ? 'none' : '';

    var image = toggle.querySelector('img');
    if (image) image.alt = expanded ? 'Read more' : 'Read less';
  }

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest ? event.target.closest('.panel-toggle') : null;
    if (!toggle) return;
    setPanelState(toggle);
  });
}());
