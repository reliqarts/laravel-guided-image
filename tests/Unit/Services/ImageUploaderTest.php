<?php

/** @noinspection PhpParamsInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Services;

use AspectMock\Proxy\FuncProxy;
use AspectMock\Test;
use Exception;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\GuidedImage\Contracts\ConfigProvider;
use ReliqArts\GuidedImage\Contracts\ImageUploader as ImageUploaderContract;
use ReliqArts\GuidedImage\Contracts\Logger;
use ReliqArts\GuidedImage\Result;
use ReliqArts\GuidedImage\Services\ImageUploader;
use ReliqArts\GuidedImage\Tests\Fixtures\Models\GuidedImage;
use ReliqArts\GuidedImage\Tests\Unit\AspectMockedTestCase;

/**
 * Class ImageUploaderTest.
 *
 * @coversDefaultClass \ReliqArts\GuidedImage\Services\ImageUploader
 *
 * @internal
 */
final class ImageUploaderTest extends AspectMockedTestCase
{
    private const ALLOWED_EXTENSIONS = ['jpg'];
    private const IMAGE_RULES = 'required|mimes:png,gif,jpeg|max:2048';
    private const UPLOAD_DIRECTORY = 'uploads/images';

    /**
     * @var ConfigProvider|ObjectProphecy
     */
    private $configProvider;

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
        $this->validationFactory = $this->prophesize(ValidationFactory::class);
        $this->validator = $this->prophesize(Validator::class);
        $this->guidedImage = $this->prophesize(GuidedImage::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->builder = $this->prophesize(Builder::class);
        $this->namespace = 'ReliqArts\\GuidedImage\\Services';
        $this->uploadedFile = $this->getUploadedFileMock();
        $this->pathInfoFunc = Test::func($this->namespace, 'pathinfo', function () {
            return ['filename' => $this->uploadedFile->getFilename()];
        });
        $this->getImageSizeFunc = Test::func($this->namespace, 'getimagesize', function () {
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
            $this->validationFactory->reveal(),
            $this->guidedImage->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::buildImageRow
     * @covers ::upload
     * @covers ::validate
     */
    public function testUpload(): void
    {
        $this->guidedImage
            ->create(Argument::that(function ($argument) {
                return in_array($this->uploadedFile->getFilename(), $argument, true);
            }))
            ->shouldBeCalledTimes(1);

        $result = $this->subject->upload($this->uploadedFile);

        $this->pathInfoFunc->verifyInvokedOnce();
        $this->getImageSizeFunc->verifyInvokedOnce();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /**
     * @covers ::__construct
     * @covers ::buildImageRow
     * @covers ::upload
     * @covers ::validate
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

        $this->logger
            ->error(Argument::cetera())
            ->shouldNotBeCalled();

        $result = $this->subject->upload($this->uploadedFile);

        $this->pathInfoFunc->verifyInvokedOnce();
        $this->getImageSizeFunc->verifyInvokedOnce();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($existingGuidedImage, $result->getData());
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

        $this->logger
            ->error(Argument::cetera())
            ->shouldNotBeCalled();

        $result = $this->subject->upload($this->uploadedFile);

        $this->pathInfoFunc->verifyNeverInvoked();
        $this->getImageSizeFunc->verifyNeverInvoked();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsStringIgnoringCase('invalid', $result->getError());
    }

    /**
     * @covers ::__construct
     * @covers ::buildImageRow
     * @covers ::upload
     * @covers ::validate
     */
    public function testUploadWhenFileUploadFails(): void
    {
        $this->uploadedFile
            ->shouldReceive('move')
            ->andThrow(Exception::class);

        $this->guidedImage
            ->unguard()
            ->shouldNotBeCalled();
        $this->guidedImage
            ->create(Argument::cetera())
            ->shouldNotBeCalled();
        $this->guidedImage
            ->reguard()
            ->shouldNotBeCalled();

        $this->logger
            ->error(Argument::cetera())
            ->shouldBeCalledTimes(1);

        $result = $this->subject->upload($this->uploadedFile);

        $this->pathInfoFunc->verifyInvokedOnce();
        $this->getImageSizeFunc->verifyInvokedOnce();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isSuccess());
    }

    /**
     * @param string $filename
     * @param string $mimeType
     * @param string $extension
     * @param int    $size
     *
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
