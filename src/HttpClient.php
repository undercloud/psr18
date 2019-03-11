<?php
namespace Undercloud\Psr18;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var object
     */
    private $options;

    /**
     * HttpClient constructor.
     *
     * @param ResponseInterface $response prototype
     * @param array             $options  flags
     */
    public function __construct(ResponseInterface $response, array $options = [])
    {
        $this->response = $response;
        $this->options = (object) $options;
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

        if ($body->getSize() and !in_array($method, ['POST','PUT','PATCH'])) {
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
            ->withHeader('Content-Length', (string) ((int) $body->getSize()))
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
                list($version,$code,$reasonPhrase) = explode(' ', $line);
            } else {
                list($name, $value) = explode(':', $line);

                $name = trim($name);
                $value = trim($value);

                $headers[$name] = $value;
            }
        }

        return [$version, $code, $reasonPhrase, $headers];
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

        $transport = new Transport($request, $this->options);
        $transport->send($message);

        $body = $request->getBody();
        while (!$body->eof()) {
            $buffer = $body->read(self::BUFFER_SIZE);
            $transport->send($buffer);
        }

        $message = $transport->readMessage();

        if (!$message) {
            throw RequestException::factory($request, 'Empty response header');
        }

        list($version, $code, $reasonPhrase, $headers) = $this->parseMessage($message);

        $this->response = $this->response
            ->withProtocolVersion($version)
            ->withStatus($code, $reasonPhrase);

        foreach ($headers as $name => $value) {
            $this->response = $this->response->withHeader($name, $value);
        }

        $body = $transport->createBodyStream([
            'contentLength' => (int) $this->response->getHeaderLine('Content-Length')
        ]);

        $this->response = $this->response->withBody($body);

        return $this->response;
    }
}
