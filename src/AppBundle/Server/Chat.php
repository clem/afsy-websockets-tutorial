<?php

namespace AppBundle\Server;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Chat Server.
 */
class Chat implements MessageComponentInterface
{
    /**
     * @var \SplObjectStorage
     */
    private $clients;

    /**
     * @var array
     */
    private $users = [];

    /**
     * @var string
     */
    private $botName = 'ChatBot';

    /**
     * @var string
     */
    private $defaultChannel = 'general';

    /**
     * Chat constructor.
     */
    public function __construct()
    {
        // Initialize
        $this->clients = new \SplObjectStorage();
        $this->users = [];
    }

    /**
     * A new websocket connection.
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Initialize: Add connection to clients list
        $this->clients->attach($conn);
        $this->users[$conn->resourceId] = [
            'connection' => $conn,
            'user' => '',
            'channels' => [],
        ];

        // Send hello message
        $conn->send(json_encode([
            'action' => 'message',
            'channel' => $this->defaultChannel,
            'user' => $this->botName,
            'message' => sprintf('Connection established. Welcome #%d!', $conn->resourceId),
            'messageClass' => 'success',
        ]));
    }

    /**
     *
     * @param ConnectionInterface $closedConnection
     * A connection is closed.
     */
    public function onClose(ConnectionInterface $closedConnection)
    {
        // Send Goodbye message
        $this->sendMessageToChannel(
            $closedConnection,
            $this->defaultChannel,
            $this->botName,
            $this->users[$closedConnection->resourceId]['user'].' has disconnected'
        );

        // Remove connection from users
        unset($this->users[$closedConnection->resourceId]);

        // Detach connection and send message (for log purposes)
        $this->clients->detach($closedConnection);
        echo sprintf('Connection #%d has disconnected\n', $closedConnection->resourceId);
    }

    /**
     * Error handling.
     *
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->send(json_encode([
            'action' => 'message',
            'channel' => $this->defaultChannel,
            'user' => $this->botName,
            'message' => 'An error has occurred: '.$e->getMessage(),
        ]));
        $conn->close();
    }

    /**
     * Handle message sending.
     *
     * @param ConnectionInterface $conn
     * @param string $message
     *
     * @return bool - False if message is not a valid JSON or action is invalid
     */
    public function onMessage(ConnectionInterface $conn, $message)
    {
        // Initialize
        $messageData = json_decode($message);

        // Check message data
        if (null === $messageData) {
            return false;
        }

        // Check connection user
        if (empty($this->users[$conn->resourceId]['user']) && $messageData->user) {
            $this->users[$conn->resourceId]['user'] = $messageData->user;
        }

        // Initialize message data
        $action = $messageData->action ?? 'unknown';
        $channel = $messageData->channel ?? $this->defaultChannel;
        $user = $messageData->user ?? $this->botName;
        $message = $messageData->message ?? '';

        // Check action
        switch ($action) {
            case 'subscribe':
                $this->subscribeToChannel($conn, $channel, $user);

                return true;
            case 'unsubscribe':
                $this->unsubscribeFromChannel($conn, $channel, $user);

                return true;
            case 'message':
                return $this->sendMessageToChannel($conn, $channel, $user, $message);
            default:
                echo sprintf('Action "%s" is not supported yet!', $action);
                break;
        }

        // Return error
        return false;
    }

    /**
     * Subscribe connection to a given channel.
     *
     * @param ConnectionInterface $conn - Active connection
     * @param $channel - Channel to subscribe to
     * @param $user - Username of subscribed user
     */
    private function subscribeToChannel(ConnectionInterface $conn, $channel, $user)
    {
        // Add channel to connection channels
        $this->users[$conn->resourceId]['channels'][$channel] = $channel;

        // Send joined message to channel
        $this->sendMessageToChannel(
            $conn,
            $channel,
            $this->botName,
            $user.' joined #'.$channel
        );
    }

    /**
     * Unsubscribe connection to a given channel.
     *
     * @param ConnectionInterface $conn - Active connection
     * @param $channel - Channel to unsubscribe from
     * @param $user - Username of unsubscribed user
     */
    private function unsubscribeFromChannel(ConnectionInterface $conn, $channel, $user)
    {
        // Check connection
        if (\array_key_exists($channel, $this->users[$conn->resourceId]['channels'])) {
            // Delete connection
            unset($this->users[$conn->resourceId]['channels']);
        }

        // Send left message to channel
        $this->sendMessageToChannel(
            $conn,
            $channel,
            $this->botName,
            $user.' left #'.$channel
        );
    }

    /**
     * Send message to all connections of a given channel.
     *
     * @param ConnectionInterface $conn - Active connection
     * @param $channel - Channel to send message to
     * @param $user - User's username
     * @param $message - User's message
     *
     * @return bool - False if channel doesn't exists
     */
    private function sendMessageToChannel(ConnectionInterface $conn, $channel, $user, $message)
    {
        // Check if connection is linked to channel
        if (!isset($this->users[$conn->resourceId]['channels'][$channel])) {
            // Don't send message
            return false;
        }

        // Loop to send message to all users
        foreach ($this->users as $connectionId => $userConnection) {
            // Check if user has subscribe to channel
            if (\array_key_exists($channel, $userConnection['channels'])) {
                $userConnection['connection']->send(json_encode([
                    'action' => 'message',
                    'channel' => $channel,
                    'user' => $user,
                    'message' => $message,
                ]));
            }
        }

        // Return success
        return true;
    }
}
