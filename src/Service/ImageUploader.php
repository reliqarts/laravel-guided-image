<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Service;

use Exception;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\UploadedFile;
use ReliqArts\GuidedImage\Contract\ConfigProvider;
use ReliqArts\GuidedImage\Contract\GuidedImage;
use ReliqArts\GuidedImage\Contract\ImageUploader as ImageUploaderContract;
use ReliqArts\GuidedImage\Contract\Logger;
use ReliqArts\GuidedImage\Model\UploadedImage;
use ReliqArts\GuidedImage\Result;

final class ImageUploader implements ImageUploaderContract
{
    private const ERROR_INVALID_IMAGE = 'Invalid image size or type.';
    private const KEY_FILE = 'file';
    private const MESSAGE_IMAGE_REUSED = 'Image reused.';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Filesystem
     */
    private $uploadDisk;

    /**
     * @var ValidationFactory
     */
    private $validationFactory;

    /**
     * @var GuidedImage
     */
    private $guidedImage;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Uploader constructor.
     *
     * @param ConfigProvider    $configProvider
     * @param FilesystemManager $filesystemManager
     * @param ValidationFactory $validationFactory
     * @param GuidedImage       $guidedImage
     * @param Logger            $logger
     */
    public function __construct(
        ConfigProvider $configProvider,
        FilesystemManager $filesystemManager,
        ValidationFactory $validationFactory,
        GuidedImage $guidedImage,
        Logger $logger
    ) {
        $this->configProvider = $configProvider;
        $this->uploadDisk = $filesystemManager->disk($configProvider->getUploadDiskName());
        $this->validationFactory = $validationFactory;
        $this->guidedImage = $guidedImage;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @return Result
     */
    public function upload(UploadedFile $imageFile): Result
    {
        if (!$this->validate($imageFile)) {
            return new Result(false, self::ERROR_INVALID_IMAGE);
        }

        $uploadedImage = new UploadedImage($imageFile, $this->configProvider->getUploadDirectory());
        $existing = $this->guidedImage
            ->where(UploadedImage::KEY_NAME, $uploadedImage->getFilename())
            ->where(UploadedImage::KEY_SIZE, $uploadedImage->getSize())
            ->first();

        if (!empty($existing)) {
            $result = new Result(true);

            // @noinspection PhpIncompatibleReturnTypeInspection
            return $result
                ->addMessage(self::MESSAGE_IMAGE_REUSED)
                ->setData($existing);
        }

        try {
            $this->uploadDisk->putFileAs(
                $uploadedImage->getDestination(),
                $uploadedImage->getFile(),
                $uploadedImage->getFilename()
            );

            $this->guidedImage->unguard();
            $instance = $this->guidedImage->create($uploadedImage->toArray());
            $this->guidedImage->reguard();

            $result = new Result(true, '', [], $instance);
        } catch (Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'uploaded image' => $uploadedImage->toArray(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            $result = new Result(false, $exception->getMessage());
        }

        return $result;
    }

    /**
     * @param UploadedFile $imageFile
     *
     * @return bool
     */
    private function validate(UploadedFile $imageFile): bool
    {
        $allowedExtensions = $this->configProvider->getAllowedExtensions();
        $validator = $this->validationFactory->make(
            [self::KEY_FILE => $imageFile],
            [self::KEY_FILE => $this->configProvider->getImageRules()]
        );

        if ($validator->fails()
            || !in_array(strtolower($imageFile->getClientOriginalExtension()), $allowedExtensions, true)
        ) {
            return false;
        }

        return true;
    }
}
