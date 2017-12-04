<?php

namespace AppBundle\Server;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Chat Server
 */
class Chat implements MessageComponentInterface
{
    /**
     * @var \SplObjectStorage
     */
    protected $clients;
    
    /**
     * Chat constructor
     */
    function __construct()
    {
        // Initialize
        $this->clients = new \SplObjectStorage();
    }
    
    /**
     * A new websocket connection
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Initialize: Add connection to clients list
        $this->clients->attach($conn);
        
        // Send hello message
        $conn->send(sprintf('New connection: Hello #%d', $conn->resourceId));
    }

    /**
     * A connection is closed
     *
     * @param ConnectionInterface $closedConnection
     */
    public function onClose(ConnectionInterface $closedConnection)
    {
        // Detach connection and send message (for log purposes)
        $this->clients->detach($closedConnection);
        echo sprintf('Connection #%d has disconnected\n', $closedConnection->resourceId);
    }

    /**
     * Error handling
     *
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->send('An error has occurred: '.$e->getMessage());
        $conn->close();
    }

    /**
     * Handle message sending
     *
     * @param ConnectionInterface $from
     * @param string $message
     */
    public function onMessage(ConnectionInterface $from, $message)
    {
        // Initialize
        $totalClients = count($this->clients) - 1;
        echo vsprintf(
            'Connection #%1$d sending message "%2$s" to %3$d other connection%4$s'."\n", [
            $from->resourceId,
            $message,
            $totalClients,
            $totalClients === 1 ? '' : 's'
        ]);
        
        // Loop to send message to all clients... except the sender
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($message);
            }
        }
    }
}
