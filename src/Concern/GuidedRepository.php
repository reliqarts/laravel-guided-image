<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Concern;

use Illuminate\Http\UploadedFile;
use ReliqArts\Contracts\Filesystem;
use ReliqArts\GuidedImage\Contract\ImageUploader;
use ReliqArts\GuidedImage\Result;

/**
 * @method mixed delete()
 */
trait GuidedRepository
{
    /**
     * @param UploadedFile $file
     *
     * @return Result
     */
    public static function upload(UploadedFile $file): Result
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
