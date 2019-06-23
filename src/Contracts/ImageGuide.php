<?php

/** @noinspection PhpTooManyParametersInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Intervention\Image\Image;

interface ImageGuide
{
    /**
     * Empty skim cache by removing SkimDir.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function emptyCache(Request $request): JsonResponse;

    /**
     * Get a thumbnail.
     *
     * @param Request     $request
     * @param GuidedImage $guidedImage
     * @param string      $method      crop|fit
     * @param int         $width
     * @param int         $height
     * @param bool        $object      whether Intervention Image should be returned
     *
     * @return Image|string intervention Image object or actual image url
     */
    public function thumb(Request $request, GuidedImage $guidedImage, string $method, $width, $height, $object = false);

    /**
     * Get a resized Guided Image.
     *
     * @param Request     $request
     * @param GuidedImage $guidedImage
     * @param int         $width
     * @param int         $height
     * @param bool        $aspect      Keep aspect ratio?
     * @param bool        $upSize      Allow up-size?
     * @param bool        $object      whether Intervention Image should be returned
     *
     * @return Image|string intervention Image object or actual image url
     */
    public function resized(
        Request $request,
        GuidedImage $guidedImage,
        $width,
        $height,
        $aspect = true,
        $upSize = false,
        $object = false
    );

    /**
     * Get dummy Guided Image.
     *
     * @param int|string   $width
     * @param int|string   $height
     * @param string       $color
     * @param bool|string  $fill
     * @param mixed|string $object
     *
     * @return Image|string intervention Image object or actual image url
     */
    public function dummy($width, $height, $color = '#eefefe', $fill = false, $object = false);
}
