<?php
namespace Undercloud\Psr18\Streams;

use InvalidArgumentException;

/**
 * Class TextStream
 *
 * @category Psr18HttpClient
 * @package  Undercloud\Psr18
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/psr18
 */
class TextStream extends SocketStream
{
    /**
     * TextStream constructor.
     *
     * @param string $stream  value
     * @param array  $options params
     */
    public function __construct(string $stream, array $options = [])
    {
        $options = (object) $options;
        if (!isset($options->mime)) {
            $options->mime = 'text/plain';
        }

        $dataUrl = 'data:' . $options->mime;
        if (isset($options->encoding)) {
            $dataUrl .= ';' . $options->encoding;
        }

        $dataUrl .= ',' . $stream;

        if (false === ($stream = @fopen($dataUrl, 'rb'))) {
            throw new InvalidArgumentException(
                error_get_last()['message']
            );
        }

        parent::__construct($stream);

        $this->withHeader('Content-Type', $options->mime);
    }
}
