<?php

namespace ReliQArts\GuidedImage\Contracts;

use Illuminate\Http\Request;
use ReliQArts\GuidedImage\Contracts\Guided;

/**
 * A true guider defines.
 */
interface ImageGuider
{
    /**
     * Empty skim cache by removing SkimDir.
     * @param Request $request
     * @return ViewModels\Result
     */
    public function emptyCache(Request $request);

    /**
     * Get a thumbnail.
     * @param Request $request
     * @param Guided $guidedImage
     * @param string $method crop|fit
     * @param int $width
     * @param int $height
     * @param bool|bool $object Whether Intervention Image should be returned.
     * @return Image|string Intervention Image object or actual image url.
     */
    public function thumb(Request $request, Guided $guidedImage, string $method = 'crop', int $width, int $height, bool $object = false);

    /**
     * Get a resized Guided Image.
     * @param Request $request
     * @param Guided $guidedImage
     * @param int $width
     * @param int $height
     * @param bool|bool $aspect Keep aspect ratio?
     * @param bool|bool $upsize Allow upsize?
     * @param bool|bool $object Whether Intervention Image should be returned.
     * @return Image|string Intervention Image object or actual image url.
     */
    public function resized(Request $request, Guided $guidedImage, int $width, int $height, bool $aspect = true, bool $upsize = false, $object = false);

    /**
     * Get dummy Guided.
     * @param int $width
     * @param int $height
     * @param string $color
     * @param bool|bool $fill
     * @return Image|string Intervention Image object or actual image url.
     */
    public function dummy(int $width, int $height, string $color = '#eefefe', bool $fill = false, bool $object = false);
}
