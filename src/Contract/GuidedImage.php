<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\UploadedFile;
use ReliqArts\GuidedImage\Result;

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
     */
    public function getName(): string;

    /**
     * Get resized/thumbnail photo link.
     *
     * @param string $type   request type (thumbnail or resize)
     * @param array  $params parameters to pass to route
     */
    public function getRoutedUrl(string $type, array $params = []): string;

    /**
     *  Get image title.
     */
    public function getTitle(): string;

    /**
     *  Get URL/path to image.
     *
     * @param bool $diskRelative whether to return `full path` (relative to disk),
     *                           hence skipping call to Storage facade
     *
     * @uses \Illuminate\Support\Facades\Storage
     */
    public function getUrl(bool $diskRelative = false): string;

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
     */
    public function remove(bool $force = false): Result;

    /**
     * Get link to resized photo.
     *
     * @param array $params parameters to pass to route
     */
    public function routeResized(array $params = []): string;

    /**
     * Get link to photo thumbnail.
     *
     * @param array $params parameters to pass to route
     */
    public function routeThumbnail(array $params = []): string;

    /**
     *  Upload and save image.
     *
     * @param UploadedFile $imageFile File from request. e.g. $request->file('image');
     */
    public static function upload(UploadedFile $imageFile): Result;
}
