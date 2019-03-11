<?php
namespace Undercloud\Psr18\Streams;

/**
 * Trait MultipartHeaderTrait
 *
 * @category Psr18HttpClient
 * @package  Undercloud\Psr18
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/psr18
 */
trait MultipartHeaderTrait
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Normalize header name
     *
     * @param string $name of header
     *
     * @return string
     */
    private function normalizeHeader(string $name): string
    {
        $name = strtolower($name);
        $name = explode('-', $name);
        $name = array_map('ucfirst', $name);

        return implode('-', $name);
    }

    /**
     * Assert header
     *
     * @param string $name  of header
     * @param mixed  $value of header
     *
     * @return $this
     */
    public function withHeader(string $name, $value)
    {
        $name = $this->normalizeHeader($name);
        $value = (string) $value;

        $this->headers[$name] = [$value];

        return $this;
    }

    /**
     * Unset header
     *
     * @param string $name of header
     *
     * @return $this
     */
    public function withoutHeader(string $name)
    {
        $name = $this->normalizeHeader($name);
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * Assert extra header value
     *
     * @param string $name  of header
     * @param mixed  $value of header
     *
     * @return $this
     */
    public function withAddedHeader(string $name, $value)
    {
        $name = $this->normalizeHeader($name);

        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $this->headers[$name][] = $value;

        return $this;
    }

    /**
     * Check header exists
     *
     * @param string $name of header
     *
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        $name = $this->normalizeHeader($name);

        return isset($this->headers[$name]);
    }

    /**
     * Get header value by name
     *
     * @param string $name of header
     *
     * @return array
     */
    public function getHeader(string $name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        $name = $this->normalizeHeader($name);

        return $this->headers[$name];
    }

    /**
     * Get header string value
     *
     * @param string $name of header
     *
     * @return string
     */
    public function getHeaderLine(string $name): string
    {
        if (!$this->hasHeader($name)) {
            return '';
        }

        return implode(',', $this->getHeader($name));
    }

    /**
     * Get headers array
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
