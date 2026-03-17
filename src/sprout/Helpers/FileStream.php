<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * Helpful examples here:
 * https://github.com/awsdocs/aws-doc-sdk-examples/blob/main/php/example_code/s3/
 */

namespace Sprout\Helpers;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

/**
 * Stream that wraps PHP files.
 *
 * @see https://www.php.net/manual/en/function.fopen.php
 */
class FileStream implements StreamInterface
{

    /** @var resource|null */
    protected $stream = null;

    /** @var array|null */
    protected $metadata = null;

    /**
     * Wrap a PHP stream into a PSR stream interface.
     *
     * @param resource $stream
     *
     * @throws InvalidArgumentException
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream must be a resource');
        }

        $this->stream = $stream;
        $this->getMetadata();
    }


    /**
     *
     * @param string $filename
     * @param string $mode
     * @return FileStream
     * @throws RuntimeException
     */
    public static function open(string $filename, string $mode): FileStream
    {
        $stream = @fopen($filename, $mode);

        if ($stream === false) {
            throw new RuntimeException("Cannot open stream: {$filename}");
        }

        return new self($stream);
    }


    /**
     * Auto-close the stream when the object is destroyed.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }



    /** @inheritdoc */
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }

            return $this->getContents();

        } catch (Throwable $e) {
            return '';
        }
    }


    /** @inheritdoc */
    public function getContents(): string
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Cannot read from non-readable stream');
        }

        $contents = @stream_get_contents($this->stream);

        if ($contents === false) {
            throw new RuntimeException('Unable to read stream');
        }

        return $contents;
    }


    /** @inheritdoc */
    public function close(): void
    {
        if ($this->stream === null) {
            return;
        }

        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->detach();
    }


    /** @inheritdoc */
    public function detach()
    {
        $stream = $this->stream;

        $this->stream = null;
        $this->metadata = null;

        return $stream;
    }


    /** @inheritdoc */
    public function getSize(): ?int
    {
        if ($this->stream === null) {
            return null;
        }

        $stats = fstat($this->stream);

        if ($stats === false) {
            return null;
        }

        // @phpstan-ignore-next-line
        return $stats['size'] ?? null;
    }


    /** @inheritdoc */
    public function isReadable(): bool
    {
        // TODO Could do more here.
        $mode = $this->getMetadata('mode');
        return in_array($mode ?? '', [
            'r', 'rb',
            'w+', 'wb+',
            'a+', 'ab+',
            'x+', 'xb+',
        ]);
    }


    /** @inheritdoc */
    public function isWritable(): bool
    {
        $mode = $this->getMetadata('mode');
        return in_array($mode ?? '', [
            'w', 'w+', 'wb+',
            'a', 'a+', 'ab+',
            'x', 'x+', 'xb+',
        ]);
    }


    /** @inheritdoc */
    public function isSeekable(): bool
    {
        $seekable = $this->getMetadata('seekable');
        return $seekable ?? false;
    }


    /** @inheritdoc */
    public function eof(): bool
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached');
        }

        return feof($this->stream);
    }


    /** @inheritdoc */
    public function tell(): int
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached');
        }

        $result = ftell($this->stream);

        if ($result === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $result;
    }


    /** @inheritdoc */
    public function rewind(): void
    {
        $this->seek(0);
    }


    /** @inheritdoc */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        $whence = (int) $whence;
        $ok = fseek($this->stream, $offset, $whence);

        if ($ok !== 0) {
            throw new RuntimeException("Unable to seek to stream position {$offset} from {$whence}");
        }
    }


    /** @inheritdoc */
    public function read($length): string
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Cannot read from non-readable stream');
        }

        $string = @fread($this->stream, $length);

        if ($string === false) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $string;
    }


    /** @inheritdoc */
    public function write($string): int
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isWritable()) {
            throw new RuntimeException('Cannot write to a non-writable stream');
        }

        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $result;
    }


    /** @inheritdoc */
    public function getMetadata($key = null)
    {
        if (!$this->stream) {
            return [];
        }

        $this->metadata ??= stream_get_meta_data($this->stream);

        if ($key) {
            return $this->metadata[$key] ?? null;
        }

        return $this->metadata;
    }
}
