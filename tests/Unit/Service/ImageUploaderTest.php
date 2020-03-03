<?php

/** @noinspection PhpParamsInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Service;

use AspectMock\Proxy\FuncProxy;
use AspectMock\Test;
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
use ReliqArts\GuidedImage\Contract\ImageUploader as ImageUploaderContract;
use ReliqArts\GuidedImage\Contract\Logger;
use ReliqArts\GuidedImage\Result;
use ReliqArts\GuidedImage\Service\ImageUploader;
use ReliqArts\GuidedImage\Tests\Fixtures\Model\GuidedImage;
use ReliqArts\GuidedImage\Tests\Unit\AspectMockedTestCase;

/**
 * Class ImageUploaderTest.
 *
 * @coversDefaultClass \ReliqArts\GuidedImage\Service\ImageUploader
 *
 * @internal
 */
final class ImageUploaderTest extends AspectMockedTestCase
{
    private const ALLOWED_EXTENSIONS = ['jpg'];
    private const IMAGE_RULES = 'required|mimes:png,gif,jpeg|max:2048';
    private const UPLOAD_DIRECTORY = 'uploads/images';
    private const UPLOAD_DISK_NAME = 'public';

    /**
     * @var ConfigProvider|ObjectProphecy
     */
    private $configProvider;

    /**
     * @var FilesystemManager|ObjectProphecy
     */
    private $filesystemManager;

    /**
     * @var ObjectProphecy|ValidationFactory
     */
    private $validationFactory;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var GuidedImage|ObjectProphecy
     */
    private $guidedImage;

    /**
     * @var Logger|ObjectProphecy
     */
    private $logger;

    /**
     * @var Builder|ObjectProphecy
     */
    private $builder;

    /**
     * @var MockInterface|UploadedFile
     */
    private $uploadedFile;

    /**
     * @var Filesystem|FilesystemAdapter|ObjectProphecy
     */
    private $uploadDisk;

    /**
     * @var ImageUploaderContract
     */
    private $subject;

    /**
     * @var FuncProxy
     */
    private $pathInfoFunc;

    /**
     * @var FuncProxy
     */
    private $getImageSizeFunc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider = $this->prophesize(ConfigProvider::class);
        $this->filesystemManager = $this->prophesize(FilesystemManager::class);
        $this->validationFactory = $this->prophesize(ValidationFactory::class);
        $this->validator = $this->prophesize(Validator::class);
        $this->guidedImage = $this->prophesize(GuidedImage::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->builder = $this->prophesize(Builder::class);
        $this->uploadedFile = $this->getUploadedFileMock();
        $this->uploadDisk = $this->prophesize(FilesystemAdapter::class);
        $this->getImageSizeFunc = Test::func($this->modelNamespace, 'getimagesize', function () {
            return [400, 600];
        });

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

        $this->subject = new ImageUploader(
            $this->configProvider->reveal(),
            $this->filesystemManager->reveal(),
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
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::<public>
     */
    public function testUpload(): void
    {
        $this->guidedImage
            ->create(Argument::that(function ($argument) {
                return in_array($this->uploadedFile->getFilename(), $argument, true);
            }))
            ->shouldBeCalledTimes(1);

        $this->uploadDisk
            ->putFileAs(Argument::cetera())
            ->shouldBeCalled();

        $result = $this->subject->upload($this->uploadedFile);

        $this->getImageSizeFunc->verifyInvokedOnce();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /**
     * @covers ::__construct
     * @covers ::getUploadDestination
     * @covers ::upload
     * @covers ::validate
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::<public>
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

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($existingGuidedImage, $result->getExtra());
        $this->assertStringContainsStringIgnoringCase('reused', $result->getMessage());
        $this->assertTrue($result->isSuccess());
    }

    /**
     * @covers ::__construct
     * @covers ::upload
     * @covers ::validate
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

        $this->getImageSizeFunc->verifyNeverInvoked();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsStringIgnoringCase('invalid', $result->getError());
    }

    /**
     * @covers ::__construct
     * @covers ::upload
     * @covers ::validate
     * @covers \ReliqArts\GuidedImage\Model\UploadedImage::<public>
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

        $this->getImageSizeFunc->verifyInvokedOnce();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isSuccess());
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
