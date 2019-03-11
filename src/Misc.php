<?php
namespace Undercloud\Psr18;

/**
 * Class Misc
 *
 * @category Psr18HttpClient
 * @package  Undercloud\Psr18
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/psr18
 */
class Misc
{
    /**
     * Stringify array of headers
     *
     * @param array $headers list
     *
     * @return string
     */
    public static function serializePsr7Headers(array $headers): string
    {
        $message = '';
        foreach ($headers as $name => $values) {
            $message .= $name . ': ' . implode(', ', $values) . HttpClient::CRLF;
        }

        return $message;
    }
}
