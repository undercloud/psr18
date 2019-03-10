<?php
namespace Undercloud\Psr18;

use Psr\Http\Message\RequestInterface;
use Undercloud\Psr18\Streams\SocketStream;

/**
 * Class Transport
 *
 * @category Psr18HttpClient
 * @package  Undercloud\Psr18
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/psr18
 */
class Transport
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var object
     */
    private $options;

    /**
     * @var resource
     */
    private $connection;

    /**
     * Transport constructor.
     * @param RequestInterface $request
     * @param $options
     */
    public function __construct(RequestInterface $request, $options)
    {
        if (!isset($options->timeout)) {
            $options->timeout = 30;
        }

        $this->request = $request;
        $this->options = $options;

        $this->createConnection();
    }

    private function createContext()
    {
        return stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'verify_host' => true,
                'allow_self_signed' => true
            ],
            'http' => [
                'follow_location' => 1,
                'ignore_errors' => 1,
                'timeout' => $this->options->timeout,
                'protocol_version' => $this->request->getProtocolVersion()
            ]
        ]);
    }

    private function createConnection()
    {
        $transport = $this->request->getUri()->getScheme() === 'https' ? 'ssl' : 'tcp';

        $port = $this->request->getUri()->getPort();
        if (!$port) {
            $port = $this->request->getUri()->getScheme() === 'https' ? 443 : 80;
        }

        $host = $this->request->getUri()->getHost();
        $host = gethostbyname($host);
        $host = $transport . '://' . $host . ':' . $port;

        $timeout = $this->options->timeout;
        $flags   = STREAM_CLIENT_CONNECT;
        $context = $this->createContext();

        if (false === ($this->connection = @stream_socket_client($host, $errno, $errstr, $timeout, $flags, $context))) {
            throw NetworkException::factory($this->request, $errstr . ' (' . (int) $errno . ')');
        }

        stream_set_chunk_size($this->connection, HttpClient::BUFFER_SIZE);
        stream_set_blocking($this->connection, true);
    }

    public function send($data)
    {
        if (-1 === @stream_socket_sendto($this->connection, $data)) {
            throw NetworkException::factory($this->request, error_get_last()['message']);
        }
    }

    public function readMessage()
    {
        $message = '';
        while (!stream_get_meta_data($this->connection)['eof']) {
            $symbol = stream_get_contents($this->connection, 1);

            $message .= $symbol;
            if (HttpClient::CRLF . HttpClient::CRLF === substr($message, -4)) {
                break;
            }
        }

        return rtrim($message, HttpClient::CRLF);
    }

    public function createBodyStream(array $options = [])
    {
        return new SocketStream($this->connection, $options);
    }

    public function __destruct()
    {
        if ($this->connection) {
            stream_socket_shutdown($this->connection, STREAM_SHUT_WR);
        }
    }
}
