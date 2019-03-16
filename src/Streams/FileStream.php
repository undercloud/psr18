<?php
declare(strict_types = 1);

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
     * FileStream constructor.
     *
     * @param string      $path     to file
     * @param string|null $filename client file name
     */
    public function __construct(string $path, string $filename = '')
    {
        if (false === ($stream = @fopen($path, 'rb'))) {
            throw new InvalidArgumentException(
                error_get_last()['message']
            );
        }

        if (empty($filename)) {
            $filename = basename($path);
        }

        $this->filename = $filename;

        parent::__construct($stream);

        $mime = (new finfo)->file($path, FILEINFO_MIME_TYPE);
        if (false === $mime) {
            $mime = 'application/binary';
        }

        $this->withHeader('Content-Type', $mime);
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
}
