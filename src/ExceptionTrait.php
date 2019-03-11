<?php
namespace Undercloud\Psr18;

use Psr\Http\Message\RequestInterface;

/**
 * Trait ExceptionTrait
 *
 * @category Psr18HttpClient
 * @package  Undercloud\Psr18
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/psr18
 */
trait ExceptionTrait
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Exception factory
     *
     * @param RequestInterface $request instance
     * @param string           $message error
     * @param mixed            ...$args placeholder
     *
     * @return ExceptionTrait
     */
    public static function factory(RequestInterface $request, $message, ...$args)
    {
        $message = sprintf($message, ...$args);
        $instance = new static($message);
        $instance->setRequest($request);

        return $instance;
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
     * Set request instance
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
