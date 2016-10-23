<?php

/*
 * This file is part of the GuidedImage package.
 *
 * (c) Patrick Reid <reliq@reliqarts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    public function thumb(Request $request, Guided $guidedImage, $method = 'crop', $width, $height, $object = false);

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
    public function resized(Request $request, Guided $guidedImage, $width, $height, $aspect = true, $upsize = false, $object = false);

    /**
     * Get dummy Guided.
     * @param int $width
     * @param int $height
     * @param string $color
     * @param bool|bool $fill
     * @return Image|string Intervention Image object or actual image url.
     */
    public function dummy($width, $height, $color = '#eefefe', $fill = false, $object = false);
}
