<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Model;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\UploadedFile;
use ReliqArts\GuidedImage\Contract\FileHelper;

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

    private FileHelper $fileHelper;
    private UploadedFile $file;
    private string $destination;

    /**
     * UploadedImage constructor.
     */
    public function __construct(FileHelper $fileHelper, UploadedFile $file, string $destination)
    {
        $this->fileHelper = $fileHelper;
        $this->file = $file;
        $this->destination = $destination;
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getFilename(): string
    {
        return str_replace(' ', '_', $this->file->getClientOriginalName());
    }

    public function getSize(): int
    {
        return $this->file->getSize();
    }

    /**
     * Get the instance as an array.
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
        [$image[self::KEY_WIDTH], $image[self::KEY_HEIGHT]] = $this->fileHelper->getImageSize(
            $this->file->getRealPath()
        );

        return $image;
    }
}
