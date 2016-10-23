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
     * @param integer $width 
     * @param integer $height 
     * @param bool|boolean $object Whether Intervention Image should be returned.
     * @return Image|string Intervention Image object or actual image url.
     */
    public function thumb(Request $request, Guided $guidedImage, $method = 'crop', $width, $height, $object = false);

    /**
     * Get a resized Guided Image.
     * @param Request $request
     * @param Guided $guidedImage
     * @param integer $width 
     * @param integer $height 
     * @param bool|boolean $aspect Keep aspect ratio?
     * @param bool|boolean $upsize Allow upsize?
     * @param bool|boolean $object Whether Intervention Image should be returned.
     * @return Image|string Intervention Image object or actual image url.
     */
    public function resized(Request $request, Guided $guidedImage, $width, $height, $aspect = true, $upsize = false, $object = false);

    /**
     * Get dummy Guided.
     * @param integer $width 
     * @param integer $height 
     * @param string $color 
     * @param bool|boolean $fill 
     * @return Image|string Intervention Image object or actual image url.
     */
    public function dummy($width, $height, $color = '#eefefe', $fill = false, $object = false);
}
