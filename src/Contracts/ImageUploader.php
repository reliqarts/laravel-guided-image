<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contracts;

use Illuminate\Http\UploadedFile;
use JsonSerializable;

interface ImageUploader
{
    /**
     *  Upload and save image.
     *
     * @param UploadedFile $imageFile File from request
     *                                .e.g. request->file('image');
     *
     * @return JsonSerializable
     */
    public function upload(UploadedFile $imageFile): JsonSerializable;
}
