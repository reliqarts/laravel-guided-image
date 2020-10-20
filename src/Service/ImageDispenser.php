<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Service;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\Constraint;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use ReliqArts\GuidedImage\Contract\ConfigProvider;
use ReliqArts\GuidedImage\Contract\FileHelper;
use ReliqArts\GuidedImage\Contract\GuidedImage;
use ReliqArts\GuidedImage\Contract\ImageDispenser as ImageDispenserContract;
use ReliqArts\GuidedImage\Contract\Logger;
use ReliqArts\GuidedImage\Demand\Dummy;
use ReliqArts\GuidedImage\Demand\Resize;
use ReliqArts\GuidedImage\Demand\Thumbnail;

final class ImageDispenser implements ImageDispenserContract
{
    private const KEY_IMAGE_URL = 'image url';
    private const KEY_CACHE_FILE = 'cache file';
    private const RESPONSE_HTTP_OK = Response::HTTP_OK;
    private const RESPONSE_HTTP_NOT_FOUND = Response::HTTP_NOT_FOUND;
    private const ONE_DAY_IN_SECONDS = 60 * 60 * 24;
    private const DEFAULT_IMAGE_ENCODING_FORMAT = 'png';
    private const DEFAULT_IMAGE_ENCODING_QUALITY = 90;

    private ConfigProvider $configProvider;
    private Filesystem $cacheDisk;
    private Filesystem $uploadDisk;
    private string $imageEncodingFormat;
    private int $imageEncodingQuality;
    private ImageManager $imageManager;
    private Logger $logger;
    private string $thumbsCachePath;
    private string $resizedCachePath;
    private FileHelper $fileHelper;

    /**
     * ImageDispenser constructor.
     */
    public function __construct(
        ConfigProvider $configProvider,
        FilesystemManager $filesystemManager,
        ImageManager $imageManager,
        Logger $logger,
        FileHelper $fileHelper
    ) {
        $this->configProvider = $configProvider;
        $this->cacheDisk = $filesystemManager->disk($configProvider->getCacheDiskName());
        $this->uploadDisk = $filesystemManager->disk($configProvider->getUploadDiskName());
        $this->imageManager = $imageManager;
        $this->imageEncodingFormat = $configProvider->getImageEncodingFormat();
        $this->imageEncodingQuality = $configProvider->getImageEncodingQuality();
        $this->logger = $logger;
        $this->fileHelper = $fileHelper;

        $this->prepCacheDirectories();
    }

    /**
     * {@inheritdoc}
     *
     * @return Image|Response
     */
    public function getDummyImage(Dummy $demand)
    {
        $image = $this->imageManager->canvas(
            $demand->getWidth(),
            $demand->getHeight(),
            $demand->getColor()
        );
        $image = $image->fill($demand->fill());

        // Return object or actual image
        return $demand->returnObject()
            ? $image
            : $image->response();
    }

    /**
     * {@inheritdoc}
     *
     * @return Image|Response|void
     */
    public function getResizedImage(Resize $demand)
    {
        $guidedImage = $demand->getGuidedImage();
        $width = $demand->getWidth();
        $height = $demand->getHeight();
        $cacheFilePath = sprintf(
            '%s/%d-%d-_-%d_%d_%s',
            $this->resizedCachePath,
            $width,
            $height,
            $demand->maintainAspectRatio() ? 1 : 0,
            $demand->allowUpSizing() ? 1 : 0,
            $guidedImage->getName()
        );

        try {
            if ($this->cacheDisk->exists($cacheFilePath)) {
                $image = $this->makeImageWithEncoding($this->cacheDisk->path($cacheFilePath));
            } else {
                $image = $this->makeImageWithEncoding($this->getImageFullUrl($guidedImage));
                $image->resize(
                    $width,
                    $height,
                    static function (Constraint $constraint) use ($demand) {
                        if ($demand->maintainAspectRatio()) {
                            $constraint->aspectRatio();
                        }
                        if ($demand->allowUpSizing()) {
                            $constraint->upsize();
                        }
                    }
                );
                $image->save($this->cacheDisk->path($cacheFilePath));
            }

            if ($demand->returnObject()) {
                return $image;
            }

            return new Response(
                $this->cacheDisk->get($cacheFilePath),
                self::RESPONSE_HTTP_OK,
                $this->getImageHeaders($cacheFilePath, $demand->getRequest(), $image) ?: []
            );
        } catch (NotReadableException | FileNotFoundException $exception) {
            $this->logger->error(
                sprintf(
                    'Exception was encountered while building resized image; %s',
                    $exception->getMessage()
                ),
                [
                    self::KEY_IMAGE_URL => $guidedImage->getUrl(),
                    self::KEY_CACHE_FILE => $cacheFilePath,
                ]
            );

            abort(self::RESPONSE_HTTP_NOT_FOUND);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return Image|Response|void
     */
    public function getImageThumbnail(Thumbnail $demand)
    {
        if (!$demand->isValid()) {
            $this->logger->warning(
                sprintf('Invalid demand for thumbnail image. Method: %s', $demand->getMethod()),
                [
                    'method' => $demand->getMethod(),
                ]
            );

            return abort(self::RESPONSE_HTTP_NOT_FOUND);
        }

        $guidedImage = $demand->getGuidedImage();
        $width = $demand->getWidth();
        $height = $demand->getHeight();
        $method = $demand->getMethod();
        $cacheFilePath = sprintf(
            '%s/%d-%d-_-%s_%s',
            $this->thumbsCachePath,
            $width,
            $height,
            $method,
            $guidedImage->getName()
        );

        try {
            if ($this->cacheDisk->exists($cacheFilePath)) {
                $image = $this->makeImageWithEncoding($this->cacheDisk->path($cacheFilePath));
            } else {
                /** @var Image $image */
                $image = $this->imageManager
                    ->make($this->getImageFullUrl($guidedImage))
                    ->{$method}(
                        $width,
                        $height
                    );

                $image->save($this->cacheDisk->path($cacheFilePath));
            }

            if ($demand->returnObject()) {
                return $image;
            }

            return new Response(
                $this->cacheDisk->get($cacheFilePath),
                self::RESPONSE_HTTP_OK,
                $this->getImageHeaders($cacheFilePath, $demand->getRequest(), $image) ?: []
            );
        } catch (NotReadableException | FileNotFoundException $exception) {
            $this->logger->error(
                sprintf(
                    'Exception was encountered while building thumbnail; %s',
                    $exception->getMessage()
                ),
                [
                    self::KEY_IMAGE_URL => $guidedImage->getUrl(),
                    self::KEY_CACHE_FILE => $cacheFilePath,
                ]
            );

            abort(self::RESPONSE_HTTP_NOT_FOUND);
        }
    }

    public function emptyCache(): bool
    {
        return $this->cacheDisk->deleteDirectory($this->resizedCachePath)
            && $this->cacheDisk->deleteDirectory($this->thumbsCachePath);
    }

    /**
     * Get image headers. Improved caching
     * If the image has not been modified say 304 Not Modified.
     *
     * @return array image headers
     */
    private function getImageHeaders(string $cacheFilePath, Request $request, Image $image): array
    {
        $filePath = sprintf('%s/%s', $image->dirname, $image->basename);
        $lastModified = $this->cacheDisk->lastModified($cacheFilePath);
        $modifiedSince = $request->header('If-Modified-Since', '');
        $etagHeader = trim($request->header('If-None-Match', ''));
        $etagFile = $this->fileHelper->hashFile($filePath);

        // check if image hasn't changed
        if ($etagFile === $etagHeader || strtotime($modifiedSince) === $lastModified) {
            // Say not modified and kill script
            header('HTTP/1.1 304 Not Modified');
            header(sprintf('ETag: %s', $etagFile));
            exit();
        }

        // adjust headers and return
        return array_merge(
            $this->getDefaultHeaders(),
            [
                'Content-Type' => $image->mime,
                'Content-Disposition' => sprintf('inline; filename=%s', $image->filename),
                'Last-Modified' => date(DATE_RFC822, $lastModified),
                'Etag' => $etagFile,
            ]
        );
    }

    private function prepCacheDirectories(): void
    {
        $this->resizedCachePath = $this->configProvider->getResizedCachePath();
        $this->thumbsCachePath = $this->configProvider->getThumbsCachePath();

        if (!$this->cacheDisk->exists($this->thumbsCachePath)) {
            $this->cacheDisk->makeDirectory($this->thumbsCachePath);
        }

        if (!$this->cacheDisk->exists($this->resizedCachePath)) {
            $this->cacheDisk->makeDirectory($this->resizedCachePath);
        }
    }

    private function getDefaultHeaders(): array
    {
        $maxAge = self::ONE_DAY_IN_SECONDS * $this->configProvider->getCacheDaysHeader();

        return array_merge(
            [
                'Cache-Control' => sprintf('public, max-age=%s', $maxAge),
            ],
            $this->configProvider->getAdditionalHeaders()
        );
    }

    /**
     * @param mixed $data
     * @param mixed ...$encoding
     */
    private function makeImageWithEncoding($data, ...$encoding): Image
    {
        if (empty($encoding)) {
            $encoding = [
                $this->imageEncodingFormat ?: self::DEFAULT_IMAGE_ENCODING_FORMAT,
                $this->imageEncodingQuality ?: self::DEFAULT_IMAGE_ENCODING_QUALITY,
            ];
        }

        return $this->imageManager
            ->make($data)
            ->encode(...$encoding);
    }

    private function getImageFullUrl(GuidedImage $guidedImage): string
    {
        return $this->uploadDisk->url($guidedImage->getUrl(true));
    }
}
