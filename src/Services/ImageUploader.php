<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Services;

use Exception;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use ReliqArts\GuidedImage\Contracts\ConfigProvider;
use ReliqArts\GuidedImage\Contracts\GuidedImage;
use ReliqArts\GuidedImage\Contracts\ImageUploader as ImageUploaderContract;
use ReliqArts\GuidedImage\Contracts\Logger;
use ReliqArts\GuidedImage\Result;

final class ImageUploader implements ImageUploaderContract
{
    private const ERROR_INVALID_IMAGE = 'Invalid image size or type.';
    private const KEY_FILE = 'file';
    private const KEY_SIZE = 'size';
    private const KEY_NAME = 'name';
    private const KEY_MIME_TYPE = 'mime_type';
    private const KEY_EXTENSION = 'extension';
    private const KEY_LOCATION = 'location';
    private const KEY_FULL_PATH = 'full_path';
    private const KEY_WIDTH = 'width';
    private const KEY_HEIGHT = 'height';
    private const KEY_FILENAME = 'filename';
    private const MESSAGE_IMAGE_REUSED = 'Image reused.';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

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
     * @param ValidationFactory $validationFactory
     * @param GuidedImage       $guidedImage
     * @param Logger            $logger
     */
    public function __construct(
        ConfigProvider $configProvider,
        ValidationFactory $validationFactory,
        GuidedImage $guidedImage,
        Logger $logger
    ) {
        $this->configProvider = $configProvider;
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

        $imageRow = $this->buildImageRow($imageFile);
        $existing = $this->guidedImage
            ->where(self::KEY_NAME, $imageRow[self::KEY_NAME])
            ->where(self::KEY_SIZE, $imageRow[self::KEY_SIZE])
            ->first();

        if (!empty($existing)) {
            $result = new Result(true);

            // @noinspection PhpIncompatibleReturnTypeInspection
            return $result
                ->addMessage(self::MESSAGE_IMAGE_REUSED)
                ->setData($existing);
        }

        try {
            $imageFile->move(
                $imageRow[self::KEY_LOCATION],
                $imageRow[self::KEY_NAME] . '.' . $imageRow[self::KEY_EXTENSION]
            );
            $this->guidedImage->unguard();
            $instance = $this->guidedImage->create($imageRow);
            $this->guidedImage->reguard();

            $result = new Result(true, '', [], $instance);
        } catch (Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'imageRow' => $imageRow,
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
     * @return array
     */
    private function buildImageRow(UploadedFile $imageFile): array
    {
        $filePathInfo = pathinfo($imageFile->getClientOriginalName());
        $filename = Str::slug($filePathInfo[self::KEY_FILENAME]);
        $imageRow = [
            self::KEY_SIZE => $imageFile->getSize(),
            self::KEY_NAME => $filename,
            self::KEY_MIME_TYPE => $imageFile->getMimeType(),
            self::KEY_EXTENSION => $imageFile->getClientOriginalExtension(),
            self::KEY_LOCATION => $this->configProvider->getUploadDirectory(),
        ];
        $imageRow[self::KEY_FULL_PATH] = urlencode(
            sprintf('%s/%s.%s', $imageRow[self::KEY_LOCATION], $filename, $imageRow[self::KEY_EXTENSION])
        );
        list($imageRow[self::KEY_WIDTH], $imageRow[self::KEY_HEIGHT]) = getimagesize($imageFile->getRealPath());

        return $imageRow;
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
