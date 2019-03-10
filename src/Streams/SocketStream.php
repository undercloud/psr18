<?php
namespace Undercloud\Psr18\Streams;

use RuntimeException;
use Psr\Http\Message\StreamInterface;

/**
 * Class SocketStream
 *
 * @category Psr18HttpClient
 * @package  Undercloud\Psr18
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/psr18
 */
class SocketStream implements StreamInterface
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var object
     */
    private $options;

    /**
     * SocketStream constructor.
     *
     * @param resource $stream  handle
     * @param array    $options params
     */
    public function __construct($stream, array $options = [])
    {
        $this->options = (object) $options;
        $this->stream = $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        return stream_get_contents($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if (false === ($position = @ftell($this->stream))) {
            throw new RuntimeException();
        }

        return $position;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (-1 === @fseek($this->stream, $offset, $whence)) {
            throw new RuntimeException(
                'Stream does not support seeking'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return feof($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (0 === func_num_args()) {
            return stream_get_meta_data($this->stream);
        } else {
            return stream_get_meta_data($this->stream)[$key];
        }
    }
    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if (false === ($data = fread($this->stream, $length))) {
            throw new RuntimeException();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return $this->getMetadata('seekable');
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        $size = (int) fstat($this->stream)['size'];
        if (!$size) {
            if (isset($this->options->contentLength)) {
                $size = $this->options->contentLength;
            }
        }

        return $size;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $stream = $this->detach();
        fclose($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        $mode = $this->getMetadata('mode');

        return (strstr($mode, 'r') or strstr($mode, '+'));
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        $mode = $this->getMetadata('mode');

        return (
            strstr($mode, 'x')
            or strstr($mode, 'w')
            or strstr($mode, 'c')
            or strstr($mode, 'a')
            or strstr($mode, '+')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        if (false === ($length = fwrite($this->stream, $string))) {
            throw new RuntimeException();
        }

        return $length;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->getContents();
    }
}
