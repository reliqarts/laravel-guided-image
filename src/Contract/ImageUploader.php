<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

use Illuminate\Http\UploadedFile;
use ReliqArts\GuidedImage\Exception\UrlUploadFailed;
use ReliqArts\GuidedImage\Result;

interface ImageUploader
{
    /**
     *  Upload and save image.
     */
    public function upload(UploadedFile $imageFile, bool $isUrlUpload = false): Result;

    /**
     * Upload an image from remote. (allow_url_fopen must be enabled).
     *
     * @throws UrlUploadFailed
     */
    public function uploadFromUrl(string $url): Result;
}
