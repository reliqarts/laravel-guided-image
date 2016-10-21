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
 * A true guided image defines.
 */
interface Guided
{
    /**
     * Retrieve the creator (uploader) of the image.
     */
    public function creator();

    /**
     *  Get image name.
     */
    public function getName();

    /**
     *  Get image title.
     */
    public function getTitle();

    /**
     *  Get ready URL to image.
     */
    public function getUrl();

    /**
     * Whether image is safe for deleting.
     */
    public function isSafeForDelete();

    /**
     *  Removes image from database, and filesystem, if not in use.
     *  @param $force Override safety constraints.
     */
    public function remove($force = false);

    /**
     * Get routed link to photo.
     */
    public function routeResized($params = false, $type = 'resize');

    /**
     *  Get upload directory.
     */
    public static function getUploadDir();

    /**
     *  Upload and save image.
     */
    public static function upload($imageFile);
}
