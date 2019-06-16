<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Concerns;

use Illuminate\Http\UploadedFile;
use JsonSerializable;
use ReliqArts\Contracts\Filesystem;
use ReliqArts\GuidedImage\Contracts\ImageUploader;
use ReliqArts\GuidedImage\VO\Result;

/**
 * Trait GuidedRepository.
 *
 * @method mixed delete()
 */
trait GuidedRepository
{
    public static function upload(UploadedFile $file): JsonSerializable
    {
        /**
         * @var ImageUploader
         */
        $uploader = resolve(ImageUploader::class);

        return $uploader->upload($file);
    }

    /**
     * Removes image from database, and filesystem, if not in use.
     *
     * @param bool $force override safety constraints
     *
     * @return Result
     */
    public function remove(bool $force = false): Result
    {
        /**
         * @var Filesystem
         */
        $filesystem = resolve(Filesystem::class);

        if (!($force || $this->isSafeForDelete())) {
            return new Result(
                false,
                'Not safe to delete, hence file not removed.'
            );
        }

        if ($filesystem->delete(urldecode($this->getFullPath()))) {
            $this->delete();
        }

        return new Result(true);
    }
}
