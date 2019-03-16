<?php
declare(strict_types = 1);

namespace Undercloud\Psr18;

use InvalidArgumentException;

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

    /**
     * Check if URL relative
     *
     * @param string $url target
     *
     * @return bool
     */
    public static function isRelativeUrl(string $url): bool
    {
        $pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
        (?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

        return !preg_match($pattern, $url);
    }

    /**
     * Parse URL and get components
     *
     * @param string $url target
     *
     * @return array
     */
    public static function extractRelativeUrlComponents(string $url): array
    {
        if (false === ($url = parse_url($url))) {
            throw new InvalidArgumentException('Malformed URL: ' . $url);
        }

        return [$url['path'] ?? '/', $url['query'] ?? ''];
    }
}
