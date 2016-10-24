<?php

namespace ReliQArts\GuidedImage\Contracts;

use Illuminate\Http\UploadedFile;

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
     * Since a single image may be re-used this method is used to determine when an image can be safely deleted from disk.
     * @param int $safeAmount A photo is safe to delete if it is used by $safe_num amount of records.
     * @return bool|bool Whether image is safe for delete.
     */
    public function isSafeForDelete(int $safeAmount = 1);

    /**
     * Removes image from database, and filesystem, if not in use.
     * @param bool $force Override safety constraints.
     * @return ReliQArts\GuidedImage\ViewModels\Result Result object.
     */
    public function remove(bool $force = false);

    /**
     * Get routed link to photo.
     * @param array $params Parameters to pass to route.
     * @param string $type Operation to be performed on instance. (resize, thumb)
     */
    public function routeResized(array $params = null, string $type = 'resize');

    /**
     * Get upload directory.
     * @return string Upload directory.
     */
    public static function getUploadDir();

    /**
     *  Upload and save image.
     * @param Illuminate\Http\UploadedFile $imageFile Actual file from request. e.g. $request->file('image');
     * @return ReliQArts\GuidedImage\ViewModels\Result Result object.
     */
    public static function upload(UploadedFile $imageFile);
}
