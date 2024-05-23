<?php

/** @noinspection PhpTooManyParametersInspection PhpUnused */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Concern;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ReliqArts\GuidedImage\Contract\GuidedImage;
use ReliqArts\GuidedImage\Contract\ImageDispenser;
use ReliqArts\GuidedImage\Demand\Resize;
use ReliqArts\GuidedImage\Demand\Thumbnail;
use ReliqArts\GuidedImage\Result;
use Symfony\Component\HttpFoundation\Response;

trait Guide
{
    /**
     * Empty skim cache.
     */
    public function emptyCache(ImageDispenser $imageDispenser): JsonResponse
    {
        $errorMessage = 'Could not clear skim directories.';

        if ($imageDispenser->emptyCache()) {
            return response()->json(
                new Result(true, '', ['Cache successfully cleared.'])
            );
        }

        return response()->json(
            new Result(false, $errorMessage, [$errorMessage])
        );
    }

    public function resized(
        ImageDispenser $imageDispenser,
        Request $request,
        GuidedImage $guidedImage,
        mixed $width,
        mixed $height,
        mixed $aspect = true,
        mixed $upSize = false
    ): Response {
        $demand = new Resize($request, $guidedImage, $width, $height, $aspect, $upSize);

        return $imageDispenser->getResizedImage($demand);
    }

    public function thumb(
        ImageDispenser $imageDispenser,
        Request $request,
        GuidedImage $guidedImage,
        mixed $method,
        mixed $width,
        mixed $height
    ): Response {
        $demand = new Thumbnail($request, $guidedImage, $method, $width, $height);

        return $imageDispenser->getImageThumbnail($demand);
    }
}
