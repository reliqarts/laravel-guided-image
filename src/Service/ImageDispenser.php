<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Service;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;
use ReliqArts\Contract\Logger;
use ReliqArts\GuidedImage\Contract\ConfigProvider;
use ReliqArts\GuidedImage\Contract\FileHelper;
use ReliqArts\GuidedImage\Contract\GuidedImage;
use ReliqArts\GuidedImage\Contract\ImageDispenser as ImageDispenserContract;
use ReliqArts\GuidedImage\Contract\ImageManager;
use ReliqArts\GuidedImage\Demand\Dummy;
use ReliqArts\GuidedImage\Demand\Resize;
use ReliqArts\GuidedImage\Demand\Thumbnail;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class ImageDispenser implements ImageDispenserContract
{
    private const KEY_IMAGE_URL = 'image url';

    private const KEY_CACHE_FILE = 'cache file';

    private const RESPONSE_HTTP_OK = SymfonyResponse::HTTP_OK;

    private const RESPONSE_HTTP_NOT_FOUND = SymfonyResponse::HTTP_NOT_FOUND;

    private const ONE_DAY_IN_SECONDS = 60 * 60 * 24;

    private const DEFAULT_IMAGE_ENCODING_MIME_TYPE = 'image/png';

    private const DEFAULT_IMAGE_ENCODING_QUALITY = 90;

    private Filesystem $cacheDisk;

    private Filesystem $uploadDisk;

    private string $imageEncodingMimeType;

    private int $imageEncodingQuality;

    private string $thumbsCachePath;

    private string $resizedCachePath;

    public function __construct(
        private readonly ConfigProvider $configProvider,
        FilesystemManager $filesystemManager,
        private readonly ImageManager $imageManager,
        private readonly Logger $logger,
        private readonly FileHelper $fileHelper
    ) {
        $this->cacheDisk = $filesystemManager->disk($configProvider->getCacheDiskName());
        $this->uploadDisk = $filesystemManager->disk($configProvider->getUploadDiskName());
        $this->imageEncodingMimeType = $configProvider->getImageEncodingMimeType();
        $this->imageEncodingQuality = $configProvider->getImageEncodingQuality();

        $this->prepCacheDirectories();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getDummyImage(Dummy $demand): ImageInterface
    {
        return $this->imageManager->create(
            $demand->getWidth(),
            $demand->getHeight()
        )->fill($demand->getColor());
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface|SymfonyResponse|void
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
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
                $sizingMethod = $demand->allowUpSizing() ? 'resize' : 'resizeDown';
                if ($demand->maintainAspectRatio()) {
                    $sizingMethod = $demand->allowUpSizing() ? 'scale' : 'scaleDown';
                }

                $image->{$sizingMethod}($width, $height);
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
        } catch (RuntimeException $exception) {
            return $this->handleRuntimeException($exception, $guidedImage);
        } catch (FileNotFoundException $exception) {
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
     * @return ImageInterface|SymfonyResponse|never
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function getImageThumbnail(Thumbnail $demand)
    {
        if (! $demand->isValid()) {
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
                /** @var ImageInterface $image */
                $image = $this->imageManager
                    ->read($this->getImageFullUrl($guidedImage))
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
        } catch (RuntimeException $exception) {
            return $this->handleRuntimeException($exception, $guidedImage);
        } catch (FileNotFoundException $exception) {
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
    private function getImageHeaders(string $cacheFilePath, Request $request, ImageInterface $image): array
    {
        $filePath = $image->origin()->filePath();
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
                'Content-Type' => $image->origin()->mediaType(),
                'Content-Disposition' => sprintf('inline; filename=%s', basename($image->origin()->filePath())),
                'Last-Modified' => date(DATE_RFC822, $lastModified),
                'Etag' => $etagFile,
            ]
        );
    }

    private function prepCacheDirectories(): void
    {
        $this->resizedCachePath = $this->configProvider->getResizedCachePath();
        $this->thumbsCachePath = $this->configProvider->getThumbsCachePath();

        if (! $this->cacheDisk->exists($this->thumbsCachePath)) {
            $this->cacheDisk->makeDirectory($this->thumbsCachePath);
        }

        if (! $this->cacheDisk->exists($this->resizedCachePath)) {
            $this->cacheDisk->makeDirectory($this->resizedCachePath);
        }
    }

    private function getDefaultHeaders(): array
    {
        $maxAge = self::ONE_DAY_IN_SECONDS * $this->configProvider->getCacheDaysHeader();

        return array_merge(
            [
                'X-Guided-Image' => true,
                'Cache-Control' => sprintf('public, max-age=%s', $maxAge),
            ],
            $this->configProvider->getAdditionalHeaders()
        );
    }

    /**
     * @throws RuntimeException
     */
    private function makeImageWithEncoding(mixed $data): ImageInterface
    {
        $encoder = new AutoEncoder(
            $this->imageEncodingMimeType ?: self::DEFAULT_IMAGE_ENCODING_MIME_TYPE,
            quality: $this->imageEncodingQuality ?: self::DEFAULT_IMAGE_ENCODING_QUALITY,
        );

        $encodedImage = $this->imageManager
            ->read($data)
            ->encode($encoder);

        return $this->imageManager->read($encodedImage->toFilePointer());
    }

    /**
     * @throws RuntimeException
     */
    private function getImageFullUrl(GuidedImage $guidedImage): string
    {
        return $this->uploadDisk->url($guidedImage->getUrl(true));
    }

    /**
     * @throws RuntimeException
     */
    private function handleRuntimeException(
        RuntimeException $exception,
        GuidedImage $guidedImage
    ): BinaryFileResponse {
        $errorMessage = 'Intervention image creation failed with NotReadableException;';
        $context = ['imageUrl' => $this->getImageFullUrl($guidedImage)];

        if (! $this->configProvider->isRawImageFallbackEnabled()) {
            $this->logger->error(
                sprintf('%s %s. Raw image fallback is disabled.', $errorMessage, $exception->getMessage()),
                $context
            );

            abort(self::RESPONSE_HTTP_NOT_FOUND);
        }

        return response()->file(
            $this->uploadDisk->path($guidedImage->getUrl(true)),
            array_merge(
                $this->getDefaultHeaders(),
                ['X-Guided-Image-Fallback' => true]
            )
        );
    }
}
