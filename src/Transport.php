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
     *
     * @param RequestInterface $request instance
     * @param object           $options flags
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

    /**
     * Create stream context
     *
     * @return resource
     */
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

    /**
     * Create socket stream connection
     *
     * @return void
     */
    private function createConnection()
    {
        $errno = $errstr = null;

        $transport = $this->request->getUri()->getScheme() === 'https'
            ? 'ssl'
            : 'tcp';

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

        $arguments = [$host, $errno, $errstr, $timeout, $flags, $context];

        if (false === ($this->connection = @stream_socket_client(...$arguments))) {
            throw NetworkException::factory(
                $this->request,
                $errstr . ' (' . (int) $errno . ')'
            );
        }

        stream_set_chunk_size($this->connection, HttpClient::BUFFER_SIZE);
        stream_set_blocking($this->connection, true);
    }

    /**
     * Send data to socket stream
     *
     * @param mixed $data to send
     *
     * @return void
     */
    public function send($data)
    {
        if (-1 === @stream_socket_sendto($this->connection, (string) $data)) {
            throw NetworkException::factory(
                $this->request,
                error_get_last()['message']
            );
        }
    }

    /**
     * Read header message from socket stream
     *
     * @return string
     */
    public function readMessage(): string
    {
        $message = '';
        while (!stream_get_meta_data($this->connection)['eof']) {
            $symbol = stream_get_contents($this->connection, 1);
            if (false === $symbol) {
                throw NetworkException::factory(
                    $this->request,
                    'Cannot read data from socket stream'
                );
            }

            $message .= $symbol;
            if (HttpClient::CRLF . HttpClient::CRLF === substr($message, -4)) {
                break;
            }
        }

        return rtrim($message, HttpClient::CRLF);
    }

    /**
     * Create body stream
     *
     * @param array $options of stream
     *
     * @return SocketStream
     */
    public function createBodyStream(array $options = []): SocketStream
    {
        return new SocketStream($this->connection, $options);
    }

    /**
     * Close socket stream connection
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->connection) {
            stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
            unset($this->connection);
        }
    }
}
