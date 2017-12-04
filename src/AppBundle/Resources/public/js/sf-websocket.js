/* globals wsUrl: true */
(function () {
  'use strict';

  // Initialize
  var _receiver = document.getElementById('ws-content-receiver');
  var ws = new WebSocket('ws://' + wsUrl);

  /**
   * On connection open
   */
  ws.onopen = function () {
    ws.send('Hello');
    _receiver.innerHTML = 'Connected !';
  };

  ws.onmessage = function (event) {
    _receiver.innerHTML = event.data;
  };

  /**
   * On connection close
   */
  ws.onclose = function () {
    _receiver.innerHTML = 'Connection closed';
  };

  /**
   * On connection error
   */
  ws.onerror = function () {
    _receiver.innerHTML = 'An error occured!';
  };

})();
