<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\Constraint;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use ReliqArts\Contracts\Filesystem;
use ReliqArts\GuidedImage\Contracts\ConfigProvider;
use ReliqArts\GuidedImage\Contracts\ImageDispenser as ImageDispenserContract;
use ReliqArts\GuidedImage\Contracts\Logger;
use ReliqArts\GuidedImage\DTO\DummyDemand;
use ReliqArts\GuidedImage\DTO\ResizeDemand;
use ReliqArts\GuidedImage\DTO\ThumbnailDemand;

final class ImageDispenser implements ImageDispenserContract
{
    private const KEY_IMAGE_URL = 'image url';
    private const KEY_SKIM_FILE = 'skim file';
    private const RESPONSE_HTTP_OK = Response::HTTP_OK;
    private const RESPONSE_HTTP_NOT_FOUND = Response::HTTP_NOT_FOUND;
    private const SKIM_DIRECTORY_MODE = 0777;
    private const ONE_DAY_IN_SECONDS = 60 * 60 * 24;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $skimThumbs;

    /**
     * @var string
     */
    private $skimResized;

    /**
     * ImageDispenser constructor.
     *
     * @param ConfigProvider $configProvider
     * @param Filesystem     $filesystem
     * @param ImageManager   $imageManager
     * @param Logger         $logger
     */
    public function __construct(
        ConfigProvider $configProvider,
        Filesystem $filesystem,
        ImageManager $imageManager,
        Logger $logger
    ) {
        $this->configProvider = $configProvider;
        $this->filesystem = $filesystem;
        $this->imageManager = $imageManager;
        $this->logger = $logger;

        $this->prepSkimDirectories();
    }

    /**
     * {@inheritdoc}
     *
     * @return Image|Response
     */
    public function getDummyImage(DummyDemand $demand)
    {
        $image = $this->imageManager->canvas(
            $demand->getWidth(),
            $demand->getHeight(),
            $demand->getColor()
        );
        $image = $image->fill($demand->fill());

        // Return object or actual image
        return ($demand->returnObject())
            ? $image
            : $image->response();
    }

    /**
     * {@inheritdoc}
     *
     * @return Image|Response|void
     */
    public function getResizedImage(ResizeDemand $demand)
    {
        $guidedImage = $demand->getGuidedImage();
        $width = $demand->getWidth();
        $height = $demand->getHeight();
        $skimFile = sprintf(
            '%s/%d-%d-_-%d_%d_%s',
            $this->skimResized,
            $width,
            $height,
            $demand->maintainAspectRatio() ? 1 : 0,
            $demand->allowUpSizing() ? 1 : 0,
            $guidedImage->getName()
        );

        try {
            if ($this->filesystem->exists($skimFile)) {
                $image = $this->imageManager->make($skimFile);
            } else {
                $image = $this->imageManager->make($guidedImage->getUrl());
                $image->resize($width, $height, function (Constraint $constraint) use ($demand) {
                    if ($demand->maintainAspectRatio()) {
                        $constraint->aspectRatio();
                    }
                    if ($demand->allowUpSizing()) {
                        $constraint->upsize();
                    }
                });
                $image->save($skimFile);
            }

            if ($demand->returnObject()) {
                return $image;
            }

            return new Response(
                $this->filesystem->get($skimFile),
                self::RESPONSE_HTTP_OK,
                $this->getImageHeaders($demand->getRequest(), $image) ?: []
            );
        } catch (NotReadableException | FileNotFoundException $exception) {
            $this->logger->error(
                sprintf(
                    'Exception was encountered while building resized image; %s',
                    $exception->getMessage()
                ),
                [
                    self::KEY_IMAGE_URL => $guidedImage->getUrl(),
                    self::KEY_SKIM_FILE => $skimFile,
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
    public function getImageThumbnail(ThumbnailDemand $demand)
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
        $skimFile = sprintf(
            '%s/%d-%d-_-%s_%s',
            $this->skimThumbs,
            $width,
            $height,
            $method,
            $guidedImage->getName()
        );

        try {
            if ($this->filesystem->exists($skimFile)) {
                $image = $this->imageManager->make($skimFile);
            } else {
                /** @var Image $image */
                $image = $this->imageManager
                    ->make($guidedImage->getUrl())
                    ->{$method}($width, $height);
                $image->save($skimFile);
            }

            if ($demand->returnObject()) {
                return $image;
            }

            return new Response(
                $this->filesystem->get($skimFile),
                self::RESPONSE_HTTP_OK,
                $this->getImageHeaders($demand->getRequest(), $image) ?: []
            );
        } catch (NotReadableException | FileNotFoundException $exception) {
            $this->logger->error(
                sprintf(
                    'Exception was encountered while building thumbnail; %s',
                    $exception->getMessage()
                ),
                [
                    self::KEY_IMAGE_URL => $guidedImage->getUrl(),
                    self::KEY_SKIM_FILE => $skimFile,
                ]
            );

            abort(self::RESPONSE_HTTP_NOT_FOUND);
        }
    }

    /**
     * @return bool
     */
    public function emptyCache(): bool
    {
        return (bool)($this->filesystem->deleteDirectory($this->skimResized)
            && $this->filesystem->deleteDirectory($this->skimThumbs));
    }

    /**
     * Get image headers. Improved caching
     * If the image has not been modified say 304 Not Modified.
     *
     * @param Request $request
     * @param Image   $image
     *
     * @return array image headers
     */
    private function getImageHeaders(Request $request, Image $image): array
    {
        $filePath = sprintf('%s/%s', $image->dirname, $image->basename);
        $lastModified = $this->filesystem->lastModified($filePath);
        $modifiedSince = $request->header('If-Modified-Since', '');
        $etagHeader = trim($request->header('If-None-Match', ''));
        $etagFile = md5_file($filePath);

        // check if image hasn't changed
        if (strtotime($modifiedSince) === $lastModified || $etagFile === $etagHeader) {
            // Say not modified and kill script
            header('HTTP/1.1 304 Not Modified');
            header(sprintf('ETag: %s', $etagFile));
            exit();
        }

        // adjust headers and return
        return array_merge($this->getDefaultHeaders(), [
            'Content-Type' => $image->mime,
            'Content-Disposition' => sprintf('inline; filename=%s', $image->filename),
            'Last-Modified' => date(DATE_RFC822, $lastModified),
            'Etag' => $etagFile,
        ]);
    }

    private function prepSkimDirectories(): void
    {
        $this->skimResized = storage_path($this->configProvider->getSkimResizedDirectory());
        $this->skimThumbs = storage_path($this->configProvider->getSkimThumbsDirectory());

        if (!$this->filesystem->isDirectory($this->skimThumbs)) {
            $this->filesystem->makeDirectory($this->skimThumbs, self::SKIM_DIRECTORY_MODE, true);
        }

        if (!$this->filesystem->isDirectory($this->skimResized)) {
            $this->filesystem->makeDirectory($this->skimResized, self::SKIM_DIRECTORY_MODE, true);
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
}
