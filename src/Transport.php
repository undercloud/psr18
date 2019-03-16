<?php
declare(strict_types = 1);

namespace Undercloud\Psr18;

use RuntimeException;
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
     * @param array $options flags
     */
    public function __construct(array $options)
    {
        $options = (object) $options;

        if (!isset($options->timeout)) {
            $options->timeout = 30;
        }

        $this->options = $options;
    }

    /**
     * Set request instance
     *
     * @param RequestInterface $request instance
     *
     * @return void
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get preferred SSL transport version
     */
    private function getPreferredSslProtocol(): string
    {
        $transports = stream_get_transports();
        $sslTransports = array_filter($transports, function ($transport) {
            return (0 === strpos($transport, 'ssl')) or (0 === strpos($transport, 'tls'));
        });

        if (!$sslTransports) {
            $transports = implode(', ', $transports);
            throw new RuntimeException(
                'No SSL/TLS transports found, avail transports is: [' . $transports . ']'
            );
        }

        rsort($sslTransports);

        return reset($sslTransports);
    }

    /**
     * Create socket stream connection
     *
     * @return void
     */
    public function connect()
    {
        $errno = $errorString = null;
        $isSecure = $this->request->getUri()->getScheme() === 'https';

        $transport = $isSecure
            ? $this->options->sslProtocol ?? $this->getPreferredSslProtocol()
            : 'tcp';

        $port = $this->request->getUri()->getPort();
        if (!$port) {
            $port = $isSecure ? 443 : 80;
        }

        $host = $this->request->getUri()->getHost();
        $uri = $transport . '://' . $host . ':' . $port;

        $timeout = $this->options->timeout;
        $flags = STREAM_CLIENT_CONNECT;

        $arguments = [$uri, $errno, $errorString, $timeout, $flags];
        if ($isSecure) {
            if (isset($this->options->ssl)) {
                $arguments[] = stream_context_create([
                    'ssl' => $this->options->ssl
                ]);
            }
        }

        if (false === ($this->connection = stream_socket_client(...$arguments))) {
            throw NetworkException::factory(
                $this->request,
                $errorString ? $errorString : 'Unknown network error'
            );
        }

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
        if (-1 === @fwrite($this->connection, (string) $data)) {
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
            $symbol = fgetc($this->connection);

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
            fclose($this->connection);
        }
    }
}
