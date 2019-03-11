<?php
namespace Undercloud\Psr18\Streams;

use InvalidArgumentException;

/**
 * Class JsonStream
 *
 * @category Psr18HttpClient
 * @package  Undercloud\Psr18
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/psr18
 */
class JsonStream extends TextStream
{
    /**
     * JsonStream constructor.
     *
     * @param mixed $data            to encode
     * @param int   $encodingOptions encoding options
     *
     * <code>
     * JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
     * </code>
     */
    public function __construct($data, $encodingOptions = 79)
    {
        $json = $this->encodeJson($data, $encodingOptions);
        $mime = 'application/json';

        parent::__construct($json);

        $this->withHeader('Content-Type', $mime);
    }

    /**
     * Encode JSON
     *
     * @param  mixed $data            to encode
     * @param  int   $encodingOptions encoding options
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    private function encodeJson($data, int $encodingOptions): string
    {
        // reset error
        json_encode(null);

        $json = json_encode($data, $encodingOptions);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(sprintf(
                'Unable to encode data to JSON in %s: %s',
                __CLASS__,
                json_last_error_msg()
            ));
        }

        return $json;
    }
}
