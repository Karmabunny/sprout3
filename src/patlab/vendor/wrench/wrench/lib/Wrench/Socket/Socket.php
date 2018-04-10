<?php

namespace Wrench\Socket;

use Wrench\Resource;
use Wrench\Exception\SocketException;
use Wrench\Util\Configurable;
use \InvalidArgumentException;

/**
 * Socket class
 *
 * Implements low level logic for connecting, serving, reading to, and
 * writing from WebSocket connections using PHP's streams.
 *
 * Unlike in previous versions of this library, a Socket instance now
 * represents a single underlying socket resource. It's designed to be used
 * by aggregation, rather than inheritance.
 */
abstract class Socket extends Configurable implements Resource
{
    /**
     * Default timeout for socket operations (reads, writes)
     *
     * @var int seconds
     */
    const TIMEOUT_SOCKET = 5;

    /**
     * @var int
     */
    const DEFAULT_RECEIVE_LENGTH = '1400';

    /**#@+
     * Socket name parts
     *
     * @var int
     */
    const NAME_PART_IP = 0;
    const NAME_PART_PORT = 1;
    /**#@-*/

    /**
     * @var resource
     */
    protected $socket = null;

    /**
     * Stream context
     */
    protected $context = null;

    /**
     * Whether the socket is connected to a server
     *
     * Note, the connection may not be ready to use, but the socket is
     * connected at least. See $handshaked, and other properties in
     * subclasses.
     *
     * @var boolean
     */
    protected $connected = false;

    /**
     * The socket name according to stream_socket_get_name
     *
     * @var string
     */
    protected $name;

    /**
     * Configure options
     *
     * Options include
     *   - timeout_socket       => int, seconds, default 5
     *
     * @param array $options
     * @return void
     */
    protected function configure(array $options)
    {
        $options = array_merge(array(
            'timeout_socket' => self::TIMEOUT_SOCKET,
        ), $options);

        parent::configure($options);
    }

    /**
     * Gets the name of the socket
     *
     * @return string
     */
    protected function getName()
    {
        if (!isset($this->name) || !$this->name) {
            $this->name = @stream_socket_get_name($this->socket, true);
        }
        return $this->name;
    }

    /**
     * Gets part of the name of the socket
     *
     * PHP seems to return IPV6 address/port combos like this:
     *   ::1:1234, where ::1 is the address and 1234 the port
     * So, the part number here is either the last : delimited section (the port)
     * or all the other sections (the whole initial part, the address).
     *
     * @param string $name (from $this->getName() usually)
     * @param int<0, 1> $part
     * @return string
     * @throws SocketException
     */
    public static function getNamePart($name, $part)
    {
        if (!$name) {
            throw new InvalidArgumentException('Invalid name');
        }

        $parts = explode(':', $name);

        if (count($parts) < 2) {
            throw new SocketException('Could not parse name parts: ' . $name);
        }

        if ($part == self::NAME_PART_PORT) {
            return end($parts);
        } elseif ($part == self::NAME_PART_IP) {
            return implode(':', array_slice($parts, 0, -1));
        } else {
            throw new InvalidArgumentException('Invalid name part');
        }
    }

    /**
     * Gets the IP address of the socket
     *
     * @throws \Wrench\Exception\SocketException If the IP address cannot be obtained
     * @return string
     */
    public function getIp()
    {
        $name = $this->getName();

        if ($name) {
            return self::getNamePart($name, self::NAME_PART_IP);
        } else {
            throw new SocketException('Cannot get socket IP address');
        }
    }

    /**
     * Gets the port of the socket
     *
     * @throws \Wrench\Exception\SocketException If the port cannot be obtained
     * @return string
     */
    public function getPort()
    {
        $name = $this->getName();

        if ($name) {
            return self::getNamePart($name, self::NAME_PART_PORT);
        } else {
            throw new SocketException('Cannot get socket IP address');
        }
    }

    /**
     * Get the last error that occurred on the socket
     *
     * @return int|string
     */
    public function getLastError()
    {
        if ($this->isConnected() && $this->socket) {
            $err = @socket_last_error($this->socket);
            if ($err) {
                $err = socket_strerror($err);
            }
            if (!$err) {
                $err = 'Unknown error';
            }
            return $err;
        } else {
            return 'Not connected';
        }
    }

    /**
     * Whether the socket is currently connected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * Disconnect the socket
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->socket) {
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
        }
        $this->socket = null;
        $this->connected = false;
    }

    /**
     * @see Wrench.Resource::getResource()
     */
    public function getResource()
    {
        return $this->socket;
    }

    /**
     * @see Wrench.Resource::getResourceId()
     */
    public function getResourceId()
    {
        return (int)$this->socket;
    }

    /**
     * @param string $data Binary data to send down the socket
     * @throws SocketException
     * @return boolean|int The number of bytes sent or false on error
     */
    public function send($data)
    {
        if (!$this->isConnected()) {
            throw new SocketException('Socket is not connected');
        }

        $length = strlen($data);

        if ($length == 0) {
            return 0;
        }

        for ($i = $length; $i > 0; $i -= $written) {
            $written = @fwrite($this->socket, substr($data, -1 * $i));

            if ($written === false) {
                return false;
            } elseif ($written === 0) {
                return false;
            }
        }

        return $length;
    }

    /**
     * Receive data from the socket
     *
     * @param int $length
     * @return string
     */
    public function receive($length = self::DEFAULT_RECEIVE_LENGTH)
    {
        $buffer = '';
        $metadata['unread_bytes'] = 0;

        do {
            // feof means socket has been closed
            // also, sometimes in long running processes the system seems to kill the underlying socket
            if (!$this->socket || feof($this->socket)) {
                $this->disconnect();

                return $buffer;
            }

            $result = fread($this->socket, $length);

            // fread FALSE means socket has been closed
            if ($result === false) {
                $this->disconnect();

                return $buffer;
            }

            $buffer .= $result;

            // feof means socket has been closed
            if (feof($this->socket)) {
                $this->disconnect();

                return $buffer;
            }

            $continue = false;

            if (strlen($result) == 1) {
                // Workaround Chrome behavior (still needed?)
                $continue = true;
            }

            if (strlen($result) == $length) {
                $continue = true;
            }

            // Continue if more data to be read
            $metadata = stream_get_meta_data($this->socket);

            if ($metadata && isset($metadata['unread_bytes'])) {
                if (!$metadata['unread_bytes']) {
                    // stop it, if we read a full message in previous time
                    $continue = false;
                } else {
                    $continue = true;
                    // it makes sense only if unread_bytes less than DEFAULT_RECEIVE_LENGTH
                    if ($length > $metadata['unread_bytes']) {
                        // http://php.net/manual/en/function.stream-get-meta-data.php
                        // 'unread_bytes' don't describes real length correctly.
                        //$length = $metadata['unread_bytes'];

                        // Socket is a blocking by default. When we do a blocking read from an empty
                        // queue it will block and the server will hang. https://bugs.php.net/bug.php?id=1739
                        stream_set_blocking($this->socket, false);
                    }
                }
            }
        } while ($continue);

        return $buffer;
    }
}
