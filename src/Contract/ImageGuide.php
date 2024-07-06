<?php

/** @noinspection PhpTooManyParametersInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Intervention\Image\Image;

interface ImageGuide
{
    /**
     * Empty skim cache.
     */
    public function emptyCache(ImageDispenser $imageDispenser): JsonResponse;

    /**
     * Get a resized Guided Image.
     *
     * @param  mixed  $width
     * @param  mixed  $height
     * @param  mixed  $aspect  Keep aspect ratio?
     * @param  mixed  $upSize  Allow up-size?
     * @return Image|string intervention Image object or actual image url
     */
    public function resized(
        ImageDispenser $imageDispenser,
        Request $request,
        GuidedImage $guidedImage,
        $width,
        $height,
        $aspect = true,
        $upSize = false
    );

    /**
     * Get a thumbnail.
     *
     * @param  string  $method  crop|fit
     * @param  int  $width
     * @param  int  $height
     * @return Image|string intervention Image object or actual image url
     */
    public function thumb(
        ImageDispenser $imageDispenser,
        Request $request,
        GuidedImage $guidedImage,
        string $method,
        $width,
        $height
    );
}
