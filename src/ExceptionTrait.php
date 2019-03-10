<?php
namespace Undercloud\Psr18;

use Psr\Http\Message\RequestInterface;

trait ExceptionTrait
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
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
     * @param RequestInterface $request instance
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
