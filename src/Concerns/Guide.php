<?php

/** @noinspection PhpTooManyParametersInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReliqArts\GuidedImage\Contracts\GuidedImage as GuidedContract;
use ReliqArts\GuidedImage\Contracts\ImageDispenser;
use ReliqArts\GuidedImage\Demands\Dummy;
use ReliqArts\GuidedImage\Demands\Resize;
use ReliqArts\GuidedImage\Demands\Thumbnail;

trait Guide
{
    /**
     * @param ImageDispenser $imageDispenser
     * @param Request        $request
     * @param GuidedContract $guidedImage
     * @param mixed          $width
     * @param mixed          $height
     * @param bool           $aspect
     * @param bool           $upSize
     *
     * @return Response
     */
    public function resized(
        ImageDispenser $imageDispenser,
        Request $request,
        GuidedContract $guidedImage,
        $width,
        $height,
        $aspect = true,
        $upSize = false
    ): Response {
        $demand = new Resize($request, $guidedImage, $width, $height, $aspect, $upSize);

        return $imageDispenser->getResizedImage($demand);
    }

    /**
     * @param ImageDispenser $imageDispenser
     * @param Request        $request
     * @param GuidedContract $guidedImage
     * @param mixed          $method
     * @param mixed          $width
     * @param mixed          $height
     *
     * @return Response
     */
    public function thumb(
        ImageDispenser $imageDispenser,
        Request $request,
        GuidedContract $guidedImage,
        $method,
        $width,
        $height
    ): Response {
        $demand = new Thumbnail($request, $guidedImage, $method, $width, $height);

        return $imageDispenser->getImageThumbnail($demand);
    }

    /**
     * @param ImageDispenser $imageDispenser
     * @param mixed          $width
     * @param mixed          $height
     * @param mixed          $color
     * @param bool           $fill
     *
     * @return Response
     */
    public function dummy(
        ImageDispenser $imageDispenser,
        $width,
        $height,
        $color = null,
        $fill = null
    ): Response {
        $demand = new Dummy($width, $height, $color, $fill);

        return $imageDispenser->getDummyImage($demand);
    }
}
