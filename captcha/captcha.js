(function () {
  'use strict';

  function request(url, options) {
    return fetch(url, Object.assign({
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Cache-Control': 'no-cache' }
    }, options || {}));
  }

  window.refreshimg = function () {
    var imageContainer = document.getElementById('captchaimage');
    if (!imageContainer) return;

    request('captcha/newsession.php', { method: 'POST' })
      .then(function (response) {
        if (!response.ok && response.status !== 204) throw new Error('challenge');
        return request('captcha/image_req.php', { method: 'GET' });
      })
      .then(function (response) {
        if (!response.ok) throw new Error('image');
        return response.text();
      })
      .then(function (html) {
        imageContainer.innerHTML = html;
      })
      .catch(function () {
        imageContainer.textContent = 'Unable to refresh CAPTCHA.';
      });
  };

  window.check = function () {
    var input = document.getElementById('captcha');
    if (!input) return;

    var body = new URLSearchParams();
    body.set('captcha', input.value);
    request('captcha/process.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: body.toString()
    })
      .then(function (response) { return response.text(); })
      .then(function (result) {
        var valid = result === '1';
        input.style.border = valid ? '1px solid #49c24f' : '1px solid #c24949';
        input.style.background = valid ? '#bcffbf' : '#ffbcbc';
      })
      .catch(function () {
        input.style.border = '1px solid #c24949';
        input.style.background = '#ffbcbc';
      });
  };
}());
