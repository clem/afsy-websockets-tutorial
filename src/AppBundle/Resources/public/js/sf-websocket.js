/* globals wsUrl: true */
(function () {
  'use strict';

  // Initialize Chat
  var defaultChannel = 'general';
  var botName = 'ChatBot';
  var userName = '';
  var ws;

  // Initialize channels
  var _channelsList = document.getElementById('ws-channels-list');

  // Initialize user
  var _connectionStatus = document.getElementById('ws-connection-status');
  var _userName = document.getElementById('ws-username');

  // Initialize form
  var _textInput = document.getElementById('ws-content-to-send');
  var _textSender = document.getElementById('ws-send-content');
  var _receiver = document.getElementById('ws-content-receiver');
  var enterKeyCode = 13;

  /**
   * Send _textInput content to WebSocket
   *
   * @param {object} e - Event
   *
   * @return {boolean} False anyway
   */
  var sendTextInputContent = function (e) {
    // Get text input content
    var content = _textInput.value;

    // Check content
    if (content.length < 1) {
      // Don't send content
      e.preventDefault();
      return false;
    }

    // Send it to WS
    ws.send(JSON.stringify({
      action: 'message',
      user: userName,
      message: content,
      channel: 'general'
    }));

    // Reset input
    _textInput.value = '';

    // Prevent default behavior
    e.preventDefault();
    return false;
  };

  /**
   * Add channel in channels list
   *
   * @param {string} channel - Channel name
   */
  var addChannel = function addChannel(channel) {
    // Initialize
    var _channel = '#' + channel;

    // Add channel to list
    _channelsList.innerHTML += '<li>' +
        '<a href="' + _channel + '">' + _channel + '</a>' +
      '</li>';
  };

  /**
   * Activate a given channel
   *
   * @param {string} channel - Channel name
   */
  var activateChannel = function activateChannel(channel) {
    // Initialize
    var _channel = '#' + channel;

    // Get channel
    var _chanLink = document.querySelectorAll('a[href="' + _channel + '"]');

    // Activate parent list item
    _chanLink[0].parentElement.classList.add('active');
  };

  /**
   * Add message to current channel
   *
   * @param {string} message - Message to display
   */
  var addMessageToChannel = function(message) {
    // Initialize
    var _message = JSON.parse(message);
    var liClass = 'message' + (_message.user === userName ? ' mine' : '');

    // Check message class
    if (_message.hasOwnProperty('messageClass')) {
      liClass += ' ' + _message.messageClass;
    }

    // Update messages list
    _receiver.innerHTML += '<li class="' + liClass + '">' +
        _message.user + ': ' + _message.message +
      '</li>';
  };

  /**
   * Send a message from ChatBot to #general channel
   *
   * @param {string} message - Message to display
   * @param {string} messageClass - CSS class to add to message
   */
  var botMessageToGeneral = function (message, messageClass) {
    return addMessageToChannel(JSON.stringify({
      action: 'message',
      channel: defaultChannel,
      user: botName,
      message: message,
      messageClass: messageClass
    }));
  };

  /**
   * "Disable" form input and button
   */
  var disableFormAndWsConnection = function disableFormAndWsConnection () {
    // Disable form input and button
    _textInput.classList.add('disabled');
    _textSender.classList.add('disabled');

    // Mark connection has offline
    _connectionStatus.classList.remove('active');
  };

  // Ask user's name
  while (userName.length === 0) {
    userName = prompt('Hi! I need your name for the Chat please :)');
  }

  // Update user's name
  _userName.innerHTML = userName;

  // Initialize WebSocket
  ws = new WebSocket('ws://' + wsUrl);
  botMessageToGeneral('Connecting...', '');

  /**
   * On connection open
   */
  ws.onopen = function () {
    // Initialize: activate connection status and enable form
    _connectionStatus.classList.add('active');
    _textInput.classList.remove('disabled');
    _textSender.classList.remove('disabled');

    // Add default channel in channels list
    addChannel(defaultChannel);
    activateChannel(defaultChannel);

    // Subscribe to default channel
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
    botMessageToGeneral('Connection closed', 'disabled');
    disableFormAndWsConnection();
  };

  /**
   * On connection error
   */
  ws.onerror = function () {
    botMessageToGeneral('An error occured!', 'error');
    disableFormAndWsConnection();
  };

  // Bind form events
  _textSender.onclick = sendTextInputContent;
  _textInput.onkeyup = function(e) {
    // Check for Enter key
    if (e.keyCode === enterKeyCode) {
      sendTextInputContent(e);
    }
  };

})();
