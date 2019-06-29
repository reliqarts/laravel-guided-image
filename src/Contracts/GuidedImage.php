<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\UploadedFile;
use ReliqArts\GuidedImage\VO\Result;

/**
 * A true guided image defines.
 *
 * @mixin Model
 * @mixin Builder
 * @mixin QueryBuilder
 */
interface GuidedImage
{
    /**
     *  Get image name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     *  Get image title.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     *  Get ready URL to image.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Whether image is safe for deleting.
     * Since a single image may be re-used this method is used to determine
     * when an image can be safely deleted from disk.
     *
     * @param int $safeAmount a photo is safe to delete if it is used by $safe_num amount of records
     *
     * @return bool whether image is safe for delete
     */
    public function isSafeForDelete(int $safeAmount = 1): bool;

    /**
     * Removes image from database, and filesystem, if not in use.
     *
     * @param bool $force override safety constraints
     *
     * @return Result
     */
    public function remove(bool $force = false): Result;

    /**
     * Get routed link to image.
     *
     * @param array  $params parameters to pass to route
     * @param string $type   Operation to be performed on instance. (resize, thumb)
     *
     * @return string
     */
    public function routeResized(array $params = [], string $type = 'resize'): string;

    /**
     *  Upload and save image.
     *
     * @param UploadedFile $imageFile File from request. e.g. $request->file('image');
     *
     * @return Result
     */
    public static function upload(UploadedFile $imageFile): Result;
}
