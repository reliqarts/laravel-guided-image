<?php

namespace ReliQArts\GuidedImage\Contracts;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * A true guider defines.
 */
interface Guider
{
    /**
     * Empty skim cache by removing SkimDir.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function emptyCache(Request $request): Response;

    /**
     * Get a thumbnail.
     *
     * @param Request $request
     * @param Guided  $guidedImage
     * @param string  $method      crop|fit
     * @param int     $width
     * @param int     $height
     * @param bool    $object      whether Intervention Image should be returned
     *
     * @return Image|string intervention Image object or actual image url
     */
    public function thumb(Request $request, Guided $guidedImage, string $method, $width, $height, $object = false);

    /**
     * Get a resized Guided Image.
     *
     * @param Request $request
     * @param Guided  $guidedImage
     * @param int     $width
     * @param int     $height
     * @param bool    $aspect      Keep aspect ratio?
     * @param bool    $upsize      Allow upsize?
     * @param bool    $object      whether Intervention Image should be returned
     *
     * @return Image|string intervention Image object or actual image url
     */
    public function resized(
        Request $request,
        Guided $guidedImage,
        $width,
        $height,
        $aspect = true,
        $upsize = false,
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
     * @return \Intervention\Image\Facades\Image|string intervention Image object or actual image url
     */
    public function dummy($width, $height, $color = '#eefefe', $fill = false, $object = false);
}