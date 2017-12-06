/* globals wsUrl: true */
(function () {
  'use strict';

  // Initialize Chat
  var defaultChannel = 'general';
  var botName = 'ChatBot';

  // Initialize form
  var _textInput = document.getElementById('ws-content-to-send');
  var _textSender = document.getElementById('ws-send-content');
  var _receiver = document.getElementById('ws-content-receiver');
  var enterKeyCode = 13;

  // Ask user's name
  var userName = prompt('Hi! I need your name for the Chat please :)');

  /**
   * Send _textInput content to WebSocket
   */
  var sendTextInputContent = function () {
    // Get text input content
    var content = _textInput.value;

    // Send it to WS
    ws.send(JSON.stringify({
      action: 'message',
      user: userName,
      message: content,
      channel: 'general'
    }));

    // Reset input
    _textInput.value = '';
  };

  /**
   * Add message to current channel
   *
   * @param {string} message - Message to display
   */
  var addMessageToChannel = function(message) {
    _receiver.innerHTML += '<div class="message">' + message + '</div>';
  };

  /**
   * Send a message from ChatBot to #general channel
   *
   * @param {string} message - Message to display
   */
  var botMessageToGeneral = function (message) {
    return addMessageToChannel(JSON.stringify({
      action: 'message',
      channel: defaultChannel,
      user: botName,
      message: message
    }));
  };

  // Initialize WebSocket
  var ws = new WebSocket('ws://' + wsUrl);
  botMessageToGeneral('Connecting...');

  /**
   * On connection open
   */
  ws.onopen = function () {
    ws.send(JSON.stringify({
      action: 'subscribe',
      channel: defaultChannel,
      user: userName
    }));
  };

  /**
   * On message reception
   *
   * @param event
   */
  ws.onmessage = function (event) {
    addMessageToChannel(event.data);
  };

  /**
   * On connection close
   */
  ws.onclose = function () {
    botMessageToGeneral('Connection closed');
  };

  /**
   * On connection error
   */
  ws.onerror = function () {
    botMessageToGeneral('An error occured!');
  };

  // Bind form events
  _textSender.onclick = sendTextInputContent;
  _textInput.onkeyup = function(e) {
    // Check for Enter key
    if (e.keyCode === enterKeyCode) {
      sendTextInputContent();
    }
  };

})();
