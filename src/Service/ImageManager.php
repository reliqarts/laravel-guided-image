<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Service;

use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\ImageManager as InterventionImageManager;
use Intervention\Image\Interfaces\DecoderInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;
use ReliqArts\GuidedImage\Contract\ImageManager as ImageManagerContract;
use RuntimeException;

final class ImageManager implements ImageManagerContract
{
    /**
     * @throws DriverException
     */
    public function __construct(private ?ImageManagerInterface $manager = null)
    {
        $this->manager = $manager ?? InterventionImageManager::gd();
    }

    /**
     * @throws RuntimeException
     */
    public function create(int $width, int $height): ImageInterface
    {
        return $this->manager->create($width, $height);
    }

    /**
     * @throws RuntimeException
     */
    public function read(mixed $input, array|string|DecoderInterface $decoders = []): ImageInterface
    {
        return $this->manager->read($input, $decoders);
    }
}
