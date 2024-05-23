<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

use Intervention\Image\Interfaces\DecoderInterface;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;

interface ImageManager
{
    /**
     * @throws RuntimeException
     *
     * @see \Intervention\Image\ImageManager::create()
     */
    public function create(int $width, int $height): ImageInterface;

    /**
     * @throws RuntimeException
     *
     * @see \Intervention\Image\ImageManager::read()
     */
    public function read(mixed $input, string|array|DecoderInterface $decoders = []): ImageInterface;
}
