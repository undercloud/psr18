<?php
namespace Undercloud\Psr18;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Undercloud\Psr18\Streams\JsonStream;
use Undercloud\Psr18\Streams\MultipartStream;

class HttpClient implements ClientInterface
{
    const BUFFER_SIZE = 1024;

    const CRLF = "\r\n";

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var object
     */
    private $options;

    public function __construct(ResponseInterface $response, array $options = [])
    {
        $this->response = $response;
        $this->options = (object) $options;
    }

    private function buildMessage(RequestInterface $request)
    {
        $method   = $request->getMethod();
        $protocol = $request->getProtocolVersion();
        $target   = $request->getRequestTarget();

        $message = $method . ' ' . $target . ' HTTP/' . $protocol . self::CRLF;

        $body = $request->getBody();
        if ($body instanceof JsonStream) {
            $request = $request
                ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        } elseif ($body instanceof MultipartStream) {
            $request = $request
                ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $body->getBoundary());
        }

        $request = $request
            ->withHeader('Content-Length', (int) $request->getBody()->getSize())
            ->withHeader('Connection', 'close');

        foreach ($request->getHeaders() as $name => $values) {
            $message .= $name . ': ' . implode(', ', $values) . self::CRLF;
        }

        return $message . self::CRLF;
    }

    private function parseMessage($message)
    {
        $headers = [];
        $version = $code = $reasonPhrase = null;
        foreach (explode(self::CRLF, $message) as $line) {
            if (0 === strpos($line, 'HTTP/')) {
                $line = substr($line, 5);
                list($version,$code,$reasonPhrase) = explode(' ', $line);
                $headers = [];
            } else {
                list($name, $value) = explode(':', $line);

                $name = trim($name);
                $value = trim($value);

                $headers[$name] = $value;
            }
        }

        return [$version, $code, $reasonPhrase, $headers];
    }

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
