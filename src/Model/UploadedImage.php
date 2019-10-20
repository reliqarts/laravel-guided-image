<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Model;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\UploadedFile;

final class UploadedImage implements Arrayable
{
    public const KEY_SIZE = 'size';
    public const KEY_NAME = 'name';
    private const KEY_MIME_TYPE = 'mime_type';
    private const KEY_EXTENSION = 'extension';
    private const KEY_LOCATION = 'location';
    private const KEY_FULL_PATH = 'full_path';
    private const KEY_WIDTH = 'width';
    private const KEY_HEIGHT = 'height';

    /**
     * @var UploadedFile
     */
    private $file;

    /**
     * @var string
     */
    private $destination;

    /**
     * UploadedImage constructor.
     *
     * @param UploadedFile $file
     * @param string       $destination
     */
    public function __construct(UploadedFile $file, string $destination)
    {
        $this->file = $file;
        $this->destination = $destination;
    }

    /**
     * @return UploadedFile
     */
    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->file->getClientOriginalName();
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->file->getSize();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $image = [
            self::KEY_SIZE => $this->getSize(),
            self::KEY_NAME => $this->getFilename(),
            self::KEY_MIME_TYPE => $this->file->getMimeType(),
            self::KEY_EXTENSION => $this->file->getClientOriginalExtension(),
            self::KEY_LOCATION => $this->getDestination(),
        ];
        $image[self::KEY_FULL_PATH] = urlencode(
            sprintf('%s/%s', $this->getDestination(), $this->getFilename())
        );
        list($image[self::KEY_WIDTH], $image[self::KEY_HEIGHT]) = getimagesize($this->file->getRealPath());

        return $image;
    }
}
