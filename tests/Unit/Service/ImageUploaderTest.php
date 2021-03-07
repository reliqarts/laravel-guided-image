<?php

/**
 * @noinspection PhpParamsInspection
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpStrictTypeCheckingInspection
 */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Service;

use Exception;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\GuidedImage\Contract\ConfigProvider;
use ReliqArts\GuidedImage\Contract\FileHelper;
use ReliqArts\GuidedImage\Contract\ImageUploader as ImageUploaderContract;
use ReliqArts\GuidedImage\Contract\Logger;
use ReliqArts\GuidedImage\Result;
use ReliqArts\GuidedImage\Service\ImageUploader;
use ReliqArts\GuidedImage\Tests\Fixtures\Model\GuidedImage;
use ReliqArts\GuidedImage\Tests\Unit\TestCase;

/**
 * Class ImageUploaderTest.
 *
 * @coversDefaultClass \ReliqArts\GuidedImage\Service\ImageUploader
 *
 * @internal
 */
final class ImageUploaderTest extends TestCase
{
    private const ALLOWED_EXTENSIONS = ['jpg'];
    private const IMAGE_RULES = 'required|mimes:png,gif,jpeg|max:2048';
    private const UPLOAD_DIRECTORY = 'uploads/images';
    private const UPLOAD_DISK_NAME = 'public';
    private const UPLOADED_IMAGE_SIZE = [100, 200];
    private const TEMP_FILE_PREFIX = 'LGI_';

    /**
     * @var ConfigProvider|ObjectProphecy
     */
    private ObjectProphecy $configProvider;

    /**
     * @var FilesystemManager|ObjectProphecy
     */
    private ObjectProphecy $filesystemManager;

    /**
     * @var ObjectProphecy|ValidationFactory
     */
    private ObjectProphecy $validationFactory;

    /**
     * @var Validator|ObjectProphecy
     */
    private ObjectProphecy $validator;

    /**
     * @var GuidedImage|ObjectProphecy
     */
    private ObjectProphecy $guidedImage;

    /**
     * @var Logger|ObjectProphecy
     */
    private ObjectProphecy $logger;

    /**
     * @var Builder|ObjectProphecy
     */
    private ObjectProphecy $builder;

    /**
     * @var MockInterface|UploadedFile
     */
    private MockInterface $uploadedFile;

    /**
     * @var ObjectProphecy|FileHelper
     */
    private ObjectProphecy $fileHelper;

    /**
     * @var Filesystem|FilesystemAdapter|ObjectProphecy
     */
    private $uploadDisk;

    private ImageUploaderContract $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider = $this->prophesize(ConfigProvider::class);
        $this->filesystemManager = $this->prophesize(FilesystemManager::class);
        $this->fileHelper = $this->prophesize(FileHelper::class);
        $this->validationFactory = $this->prophesize(ValidationFactory::class);
        $this->validator = $this->prophesize(Validator::class);
        $this->guidedImage = $this->prophesize(GuidedImage::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->builder = $this->prophesize(Builder::class);
        $this->uploadedFile = $this->getUploadedFileMock();
        $this->uploadDisk = $this->prophesize(FilesystemAdapter::class);

        $this->configProvider
            ->getAllowedExtensions()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::ALLOWED_EXTENSIONS);
        $this->configProvider
            ->getImageRules()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::IMAGE_RULES);
        $this->configProvider
            ->getUploadDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::UPLOAD_DIRECTORY);
        $this->configProvider
            ->getUploadDiskName()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::UPLOAD_DISK_NAME);
        $this->configProvider
            ->generateUploadDateSubDirectories()
            ->shouldBeCalledTimes(1)
            ->willReturn(1);

        $this->filesystemManager
            ->disk(self::UPLOAD_DISK_NAME)
            ->shouldBeCalledTimes(1)
            ->willReturn($this->uploadDisk);

        $this->validationFactory
            ->make(Argument::cetera())
            ->shouldBeCalledTimes(1)
            ->willReturn($this->validator);
        $this->validator
            ->fails()
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->guidedImage
            ->where(Argument::cetera())
            ->shouldBeCalledTimes(1)
            ->willReturn($this->builder);
        $this->guidedImage
            ->unguard()
            ->shouldBeCalledTimes(1);
        $this->guidedImage
            ->reguard()
            ->shouldBeCalledTimes(1);

        $this->builder
            ->where(Argument::cetera())
            ->shouldBeCalledTimes(1)
            ->willReturn($this->builder);
        $this->builder
            ->first()
            ->shouldBeCalledTimes(1)
            ->willReturn(null);

        $this->logger
            ->error(Argument::cetera())
            ->shouldNotBeCalled();

        $this->fileHelper
            ->getImageSize(Argument::type('string'))
            ->willReturn(self::UPLOADED_IMAGE_SIZE);

        $this->subject = new ImageUploader(
            $this->configProvider->reveal(),
            $this->filesystemManager->reveal(),
            $this->fileHelper->reveal(),
            $this->validationFactory->reveal(),
            $this->guidedImage->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getUploadDestination
     * @covers ::upload
     * @covers ::validate
     * @covers ::validateFileExtension
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::toArray
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getDestination
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getFile
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getFilename
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getSize
     *
     * @throws Exception
     */
    public function testUpload(): void
    {
        $this->guidedImage
            ->create(
                Argument::that(
                    function ($argument) {
                        return in_array($this->uploadedFile->getFilename(), $argument, true);
                    }
                )
            )
            ->shouldBeCalledTimes(1);

        $this->uploadDisk
            ->putFileAs(Argument::cetera())
            ->shouldBeCalled();

        $result = $this->subject->upload($this->uploadedFile);

        self::assertInstanceOf(Result::class, $result);
        self::assertTrue($result->isSuccess());
    }

    /**
     * @covers ::__construct
     * @covers ::getUploadDestination
     * @covers ::upload
     * @covers ::validate
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::toArray
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getDestination
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getFile
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getFilename
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getSize
     *
     * @throws Exception
     */
    public function testUploadWhenFileShouldBeReused(): void
    {
        $existingGuidedImage = $this->prophesize(GuidedImage::class)
            ->reveal();

        $this->guidedImage
            ->unguard()
            ->shouldNotBeCalled();
        $this->guidedImage
            ->create(Argument::cetera())
            ->shouldNotBeCalled();
        $this->guidedImage
            ->reguard()
            ->shouldNotBeCalled();

        $this->builder
            ->first()
            ->shouldBeCalledTimes(1)
            ->willReturn($existingGuidedImage);

        $this->uploadDisk
            ->putFileAs(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logger
            ->error(Argument::cetera())
            ->shouldNotBeCalled();

        $result = $this->subject->upload($this->uploadedFile);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame($existingGuidedImage, $result->getExtra());
        self::assertStringContainsStringIgnoringCase('reused', $result->getMessage());
        self::assertTrue($result->isSuccess());
    }

    /**
     * @covers ::__construct
     * @covers ::upload
     * @covers ::validate
     *
     * @throws Exception
     */
    public function testUploadWhenValidationFails(): void
    {
        $this->configProvider
            ->getUploadDirectory()
            ->shouldNotBeCalled();
        $this->configProvider
            ->generateUploadDateSubDirectories()
            ->shouldNotBeCalled();

        $this->validator
            ->fails()
            ->shouldBeCalledTimes(1)
            ->willReturn(true);

        $this->guidedImage
            ->where(Argument::cetera())
            ->shouldNotBeCalled();
        $this->guidedImage
            ->unguard()
            ->shouldNotBeCalled();
        $this->guidedImage
            ->create(Argument::cetera())
            ->shouldNotBeCalled();
        $this->guidedImage
            ->reguard()
            ->shouldNotBeCalled();

        $this->builder
            ->where(Argument::cetera())
            ->shouldNotBeCalled();
        $this->builder
            ->first()
            ->shouldNotBeCalled();

        $this->uploadDisk
            ->putFileAs(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logger
            ->error(Argument::cetera())
            ->shouldNotBeCalled();

        $result = $this->subject->upload($this->uploadedFile);

        self::assertInstanceOf(Result::class, $result);
        self::assertFalse($result->isSuccess());
        self::assertStringContainsStringIgnoringCase('invalid', $result->getError());
    }

    /**
     * @covers ::__construct
     * @covers ::upload
     * @covers ::validate
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::toArray
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getDestination
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getFile
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getFilename
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getSize
     *
     * @throws Exception
     */
    public function testUploadWhenFileUploadFails(): void
    {
        $this->guidedImage
            ->unguard()
            ->shouldNotBeCalled();
        $this->guidedImage
            ->create(Argument::cetera())
            ->shouldNotBeCalled();
        $this->guidedImage
            ->reguard()
            ->shouldNotBeCalled();

        $this->uploadDisk
            ->putFileAs(Argument::cetera())
            ->shouldBeCalled()
            ->willThrow(Exception::class);

        $this->logger
            ->error(Argument::cetera())
            ->shouldBeCalledTimes(1);

        $result = $this->subject->upload($this->uploadedFile);

        self::assertInstanceOf(Result::class, $result);
        self::assertFalse($result->isSuccess());
    }

    /**
     * @covers ::__construct
     * @covers ::uploadFromUrl
     * @covers ::getUploadDestination
     * @covers ::upload
     * @covers ::validate
     * @covers ::validateFileExtension
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::toArray
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getDestination
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getFile
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getFilename
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::getSize
     *
     * @throws Exception
     */
    public function testUploadFromUrl(): void
    {
        $url = '//url';
        $imageContent = 'foo';
        $tempName = 'tmp.name';
        $systemTempDir = 'sys.temp';
        $mimeType = 'img/jpeg';

        $this->fileHelper
            ->getSystemTempDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn($systemTempDir);
        $this->fileHelper
            ->tempName($systemTempDir, self::TEMP_FILE_PREFIX)
            ->shouldBeCalledTimes(1)
            ->willReturn($tempName);
        $this->fileHelper
            ->getContents($url)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);
        $this->fileHelper
            ->putContents($tempName, $imageContent)
            ->shouldBeCalledTimes(1)
            ->willReturn(1234);
        $this->fileHelper
            ->getMimeType($tempName)
            ->shouldBeCalledTimes(1)
            ->willReturn($mimeType);
        $this->fileHelper
            ->mime2Ext($mimeType)
            ->shouldBeCalledTimes(1)
            ->willReturn('jpeg');
        $this->fileHelper
            ->createUploadedFile($tempName, Argument::type('string'), $mimeType)
            ->shouldBeCalledTimes(1)
            ->willReturn($this->uploadedFile);
        $this->fileHelper
            ->unlink($tempName)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);

        $this->configProvider
            ->getImageRules()
            ->shouldNotBeCalled();

        $this->validationFactory
            ->make(Argument::cetera())
            ->shouldNotBeCalled();

        $this->validator
            ->fails()
            ->shouldNotBeCalled();

        $this->guidedImage
            ->create(
                Argument::that(
                    function ($argument) {
                        return in_array($this->uploadedFile->getFilename(), $argument, true);
                    }
                )
            )
            ->shouldBeCalledTimes(1);

        $this->uploadDisk
            ->putFileAs(Argument::cetera())
            ->shouldBeCalled();

        $result = $this->subject->uploadFromUrl($url);

        self::assertInstanceOf(Result::class, $result);
        self::assertTrue($result->isSuccess());
    }

    /**
     * @return MockInterface|UploadedFile
     */
    private function getUploadedFileMock(
        string $filename = 'myimage',
        string $mimeType = 'image/jpeg',
        string $extension = 'jpg',
        int $size = 80000
    ): UploadedFile {
        return Mockery::mock(
            UploadedFile::class,
            [
                'getFilename' => $filename,
                'getClientOriginalName' => $filename,
                'getClientOriginalExtension' => $extension,
                'getMimeType' => $mimeType,
                'getSize' => $size,
                'getRealPath' => $filename,
                'move' => null,
            ]
        );
    }
}
