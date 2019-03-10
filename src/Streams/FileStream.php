<?php
namespace Undercloud\Psr18\Streams;

use finfo;
use InvalidArgumentException;

/**
 * Class FileStream
 *
 * @category Psr18HttpClient
 * @package  Undercloud\Psr18
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/psr18
 */
class FileStream extends SocketStream
{
    /**
     * @var string
     */
    private $filename = '';

    /**
     * @var string
     */
    private $mime = '';

    /**
     * FileStream constructor.
     *
     * @param string      $path     to file
     * @param string|null $filename client file name
     * @param string|null $mime     client mime type
     */
    public function __construct(string $path, string $filename = '', string $mime = '')
    {
        if (false === ($stream = @fopen($path, 'rb'))) {
            throw new InvalidArgumentException(
                error_get_last()['message']
            );
        }

        if (empty($filename)) {
            $filename = basename($path);
        }

        if (empty($mime)) {
            $mime = (new finfo)->file($path, FILEINFO_MIME_TYPE);
            if (false === $mime) {
                $mime = 'application/binary';
            }
        }

        $this->filename = $filename;
        $this->mime     = $mime;

        parent::__construct($stream);
    }

    /**
     * Get client file name
     *
     * @return string|null
     */
    public function getClientFilename(): string
    {
        return $this->filename;
    }

    /**
     * Get client media type
     *
     * @return string|null
     */
    public function getClientMediaType(): string
    {
        return $this->mime;
    }
}
