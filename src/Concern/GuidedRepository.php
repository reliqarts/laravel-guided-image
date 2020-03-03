<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Concern;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReliqArts\GuidedImage\Contract\ConfigProvider;
use ReliqArts\GuidedImage\Contract\ImageUploader;
use ReliqArts\GuidedImage\Result;

/**
 * @method mixed delete()
 */
trait GuidedRepository
{
    public static function upload(UploadedFile $file): Result
    {
        /**
         * @var ImageUploader
         */
        $uploader = resolve(ImageUploader::class);

        return $uploader->upload($file);
    }

    /**
     * Removes image from database, and disk, if not in use.
     *
     * @param bool $force override safety constraints
     *
     * @throws Exception
     */
    public function remove(bool $force = false): Result
    {
        /** @var ConfigProvider $configProvider */
        $configProvider = resolve(ConfigProvider::class);
        $diskName = $configProvider->getUploadDiskName();
        $path = urldecode($this->getFullPath());

        if (!($force || $this->isSafeForDelete())) {
            return new Result(
                false,
                'Not safe to delete, hence file not removed.'
            );
        }

        if (Storage::disk($diskName)->delete($path)) {
            $this->delete();
        }

        return new Result(true);
    }
}
