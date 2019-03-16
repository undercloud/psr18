<?php
declare(strict_types = 1);

namespace Undercloud\Psr18;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Undercloud\Psr18\Streams\JsonStream;
use Undercloud\Psr18\Streams\MultipartStream;

/**
 * Class HttpClient
 *
 * @category Psr18HttpClient
 * @package  Undercloud\Psr18
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/psr18
 */
class HttpClient implements ClientInterface
{
    const BUFFER_SIZE = 4096;

    const CRLF = "\r\n";

    /**
     * @var object
     */
    private $options;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var Transport
     */
    private $transport;

    /**
     * HttpClient constructor.
     *
     * @param ResponseInterface $response prototype
     * @param array $options flags
     */
    public function __construct(ResponseInterface $response, array $options = [])
    {
        $this->options   = (object) $options;
        $this->response  = $response;
        $this->transport = new Transport($options);

        if (!isset($this->options->followLocation)) {
            $this->options->followLocation = true;
        }

        if (!isset($this->options->maxRedirects)) {
            $this->options->maxRedirects = 5;
        }
    }

    /**
     * Build message header
     *
     * @param RequestInterface $request instance
     *
     * @return string
     */
    private function buildMessage(RequestInterface $request): string
    {
        $method = $request->getMethod();

        if (!$method) {
            throw RequestException::factory(
                $request,
                'Request method is not defined'
            );
        }

        $protocol = $request->getProtocolVersion();
        if (!$protocol) {
            $protocol = '1.1';
        }

        $target = $request->getRequestTarget();
        if (!$target) {
            $target = '/';
        }

        $message = $method . ' ' . $target . ' HTTP/' . $protocol . self::CRLF;

        $body = $request->getBody();

        if ($body->getSize() and !in_array($method, ['POST', 'PUT', 'PATCH'])) {
            throw RequestException::factory(
                $request,
                'Method %s does not support body sending',
                $method
            );
        }

        if ($body instanceof JsonStream) {
            $request = $request
                ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        } elseif ($body instanceof MultipartStream) {
            $request = $request
                ->withHeader(
                    'Content-Type',
                    'multipart/form-data; boundary=' . $body->getBoundary()
                );
        }

        $request = $request
            ->withHeader('Content-Length', (string)((int)$body->getSize()))
            ->withHeader('Connection', 'close');

        $message .= Misc::serializePsr7Headers($request->getHeaders());

        return $message . self::CRLF;
    }

    /**
     * Parse message header
     *
     * @param string $message header
     *
     * @return array
     */
    private function parseMessage(string $message): array
    {
        $headers = [];
        $version = $code = $reasonPhrase = null;
        foreach (explode(self::CRLF, $message) as $line) {
            if (0 === strpos($line, 'HTTP/')) {
                $line = substr($line, 5);
                list($version, $code, $reasonPhrase) = explode(' ', $line);
            } else {
                list($name, $value) = explode(':', $line, 2);

                $name = trim($name);
                $name = strtolower($name);
                $value = trim($value);

                $headers[$name] = $value;
            }
        }

        return [$version, $code, $reasonPhrase, $headers];
    }

    /**
     * Redirect request 3xx
     *
     * @param RequestInterface $request instance
     * @param string           $target  URL
     *
     * @return ResponseInterface
     */
    private function redirect(RequestInterface $request, string $target): ResponseInterface
    {
        if (!$this->options->maxRedirects) {
            throw RequestException::factory($request, 'Too many redirects');
        }

        $this->options->maxRedirects--;

        if (Misc::isRelativeUrl($target)) {
            list($path, $query) = Misc::extractRelativeUrlComponents($target);
            $uri = $request
                ->getUri()
                ->withPath($path)
                ->withQuery($query);
        } else {
            $uriPrototype = get_class($request->getUri());
            /** @var UriInterface $uri */
            $uri = new $uriPrototype($target);
            $request = $request->withHeader('Host', $uri->getHost());
            $target = (
                ($uri->getPath() ? $uri->getPath() : '/') .
                ($uri->getQuery() ? ('?' . $uri->getQuery()) : '')
            );
        }

        $request = $request
            ->withUri($uri)
            ->withRequestTarget($target);

        return $this->sendRequest($request);
    }

    /**
     * Send HTTP request
     *
     * @param RequestInterface $request instance
     *
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $message = $this->buildMessage($request);

        $this->transport->setRequest($request);
        $this->transport->connect();
        $this->transport->send($message);

        $body = $request->getBody();
        while (!$body->eof()) {
            $buffer = $body->read(self::BUFFER_SIZE);
            $this->transport->send($buffer);
        }

        $message = $this->transport->readMessage();

        if (!$message) {
            throw RequestException::factory($request, 'Empty response header');
        }

        list($version, $code, $reasonPhrase, $headers) = $this->parseMessage($message);

        if ($code >= 300 and $code <= 308) {
            if ($headers['location'] and $this->options->followLocation) {
                return $this->redirect($request, $headers['location']);
            }
        }

        $this->response = $this->response
            ->withProtocolVersion($version)
            ->withStatus($code, $reasonPhrase);

        foreach ($headers as $name => $value) {
            $this->response = $this->response->withHeader($name, $value);
        }

        $body = $this->transport->createBodyStream([
            'contentLength' => (int) $this->response->getHeaderLine('Content-Length')
        ]);

        $this->response = $this->response->withBody($body);

        return $this->response;
    }
}
