<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

use Illuminate\Http\UploadedFile;
use ReliqArts\GuidedImage\Result;

interface ImageUploader
{
    /**
     *  Upload and save image.
     *
     * @param UploadedFile $imageFile File from request
     *                                .e.g. request->file('image');
     *
     * @return Result
     */
    public function upload(UploadedFile $imageFile): Result;
}
