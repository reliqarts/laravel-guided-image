<?php

/** @noinspection PhpParamsInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Services;

use AspectMock\Proxy\FuncProxy;
use AspectMock\Test;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Mockery;
use Mockery\MockInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\GuidedImage\Contracts\ConfigProvider;
use ReliqArts\GuidedImage\Contracts\ImageDispenser as ImageDispenserContract;
use ReliqArts\GuidedImage\Contracts\Logger;
use ReliqArts\GuidedImage\DTO\DummyDemand;
use ReliqArts\GuidedImage\DTO\ResizeDemand;
use ReliqArts\GuidedImage\Services\ImageDispenser;
use ReliqArts\GuidedImage\Tests\Fixtures\Models\GuidedImage;
use ReliqArts\GuidedImage\Tests\Unit\AspectMockedTestCase;
use ReliqArts\Services\Filesystem;

/**
 * Class ImageDispenserTest.
 *
 * @coversDefaultClass  \ReliqArts\GuidedImage\Services\ImageDispenser
 *
 * @internal
 */
final class ImageDispenserTest extends AspectMockedTestCase
{
    private const SKIM_RESIZED_SUB_DIRECTORY = 'RESIZED';
    private const SKIM_THUMBS_SUB_DIRECTORY = 'THUMBS';
    private const HTTP_STATUS_CODE_OK = Response::HTTP_OK;
    private const LAST_MODIFIED = 21343;
    private const IMAGE_NAME = 'my-image';
    private const IMAGE_URL = '//image_url';

    /**
     * @var ConfigProvider|ObjectProphecy
     */
    private $configProvider;

    /**
     * @var Filesystem|ObjectProphecy
     */
    private $filesystem;

    /**
     * @var ImageManager|ObjectProphecy
     */
    private $imageManager;

    /**
     * @var Logger|ObjectProphecy
     */
    private $logger;

    /**
     * @var ObjectProphecy|Request
     */
    private $request;

    /**
     * @var GuidedImage|ObjectProphecy
     */
    private $guidedImage;

    /**
     * @var ImageDispenserContract
     */
    private $subject;

    /**
     * @var string
     */
    private $skimThumbs;

    /**
     * @var string
     */
    private $skimResized;

    /**
     * @var FuncProxy
     */
    private $storagePathFunc;

    /**
     * @var FuncProxy
     */
    private $md5FileFunc;

    protected function setUp(): void
    {
        $this->configProvider = $this->prophesize(ConfigProvider::class);
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->imageManager = $this->prophesize(ImageManager::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->request = $this->prophesize(Request::class);
        $this->guidedImage = $this->prophesize(GuidedImage::class);
        $this->skimResized = self::SKIM_RESIZED_SUB_DIRECTORY;
        $this->skimThumbs = self::SKIM_THUMBS_SUB_DIRECTORY;
        $this->namespace = 'ReliqArts\\GuidedImage\\Services';

        $this->configProvider
            ->getSkimResizedDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::SKIM_RESIZED_SUB_DIRECTORY);
        $this->configProvider
            ->getSkimThumbsDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::SKIM_THUMBS_SUB_DIRECTORY);
        $this->configProvider
            ->getCacheDaysHeader()
            ->willReturn(2);
        $this->configProvider
            ->getAdditionalHeaders()
            ->willReturn([]);

        $this->filesystem
            ->isDirectory($this->skimResized)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem
            ->isDirectory($this->skimThumbs)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem
            ->lastModified(Argument::type('string'))
            ->willReturn(self::LAST_MODIFIED);

        $this->request
            ->header(Argument::cetera())
            ->willReturn('');

        $this->guidedImage
            ->getName()
            ->willReturn(self::IMAGE_NAME);
        $this->guidedImage
            ->getUrl()
            ->willReturn(self::IMAGE_URL);

        $this->storagePathFunc = Test::func(
            $this->namespace,
            'storage_path',
            function ($path) {
                return $path;
            }
        );

        $this->md5FileFunc = Test::func(
            $this->namespace,
            'md5_file',
            function ($path) {
                return $path;
            }
        );

        $this->subject = new ImageDispenser(
            $this->configProvider->reveal(),
            $this->filesystem->reveal(),
            $this->imageManager->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getDummyImage
     * @covers ::prepSkimDirectories
     */
    public function testGetDummyImage(): void
    {
        $width = 100;
        $height = 200;
        $color = 'fee';
        $fill = 'f00';
        $imageResponse = new Response();
        $image = $this->getImageMock($imageResponse);

        $this->imageManager
            ->canvas($width, $height, $color)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getDummyImage(
            new DummyDemand($width, $height, $color, $fill)
        );

        $this->assertSame($imageResponse, $result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
    }

    /**
     * @covers ::__construct
     * @covers ::getDummyImage
     * @covers ::prepSkimDirectories
     */
    public function testGetDummyImageWhenImageInstanceIsExpected(): void
    {
        $width = 100;
        $height = 200;
        $color = 'fee';
        $fill = 'f00';
        $image = $this->getImageMock();

        $this->imageManager
            ->canvas($width, $height, $color)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getDummyImage(
            new DummyDemand($width, $height, $color, $fill, true)
        );

        $this->assertSame($image, $result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
    }

    /**
     * @covers ::__construct
     * @covers ::getDefaultHeaders
     * @covers ::getImageHeaders
     * @covers ::getResizedImage
     * @covers ::prepSkimDirectories
     */
    public function testGetResizedImage(): void
    {
        $width = 100;
        $height = 200;
        $image = $this->getImageMock();
        $demand = new ResizeDemand(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            $width,
            $height
        );
        $skimFile = sprintf(
            '%s/%d-%d-_-%d_%d_%s',
            $this->skimResized,
            $width,
            $height,
            1,
            0,
            self::IMAGE_NAME
        );
        $imageContent = 'RAW';

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->filesystem
            ->get($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);

        $this->imageManager
            ->make($skimFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getResizedImage($demand);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(self::HTTP_STATUS_CODE_OK, $result->getStatusCode());
        $this->assertSame($imageContent, $result->getOriginalContent());

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyInvokedOnce();
    }

    /**
     * @covers ::__construct
     * @covers ::getResizedImage
     * @covers ::prepSkimDirectories
     */
    public function testGetResizedImageWhenImageInstanceIsExpected(): void
    {
        $width = 100;
        $height = 200;
        $image = $this->getImageMock();
        $demand = new ResizeDemand(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            $width,
            $height,
            true,
            false,
            true
        );
        $skimFile = sprintf(
            '%s/%d-%d-_-%d_%d_%s',
            $this->skimResized,
            $width,
            $height,
            1,
            0,
            self::IMAGE_NAME
        );

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->filesystem
            ->get($skimFile)
            ->shouldNotBeCalled();

        $this->imageManager
            ->make($skimFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getResizedImage($demand);

        $this->assertSame($image, $result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyNeverInvoked();
    }

    /**
     * @covers ::__construct
     * @covers ::getDefaultHeaders
     * @covers ::getImageHeaders
     * @covers ::getResizedImage
     * @covers ::prepSkimDirectories
     */
    public function testGetResizedImageWhenSkimFileExists(): void
    {
        $width = 100;
        $height = 200;
        $image = $this->getImageMock();
        $demand = new ResizeDemand(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            $width,
            $height
        );
        $skimFile = sprintf(
            '%s/%d-%d-_-%d_%d_%s',
            $this->skimResized,
            $width,
            $height,
            1,
            0,
            self::IMAGE_NAME
        );
        $imageContent = 'RAW';

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);

        $this->filesystem
            ->get($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);

        $this->imageManager
            ->make($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldNotBeCalled();

        $result = $this->subject->getResizedImage($demand);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(self::HTTP_STATUS_CODE_OK, $result->getStatusCode());
        $this->assertSame($imageContent, $result->getOriginalContent());

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyInvokedOnce();
    }

    /**
     * @param Response $imageResponse
     *
     * @return Image|MockInterface
     */
    private function getImageMock(Response $imageResponse = null): MockInterface
    {
        /** @var Image|MockInterface $image */
        $image = Mockery::mock(
            Image::class,
            [
                'response' => $imageResponse ?? new Response(),
            ]
        );
        $image->dirname = 'directory';
        $image->basename = 'basename';
        $image
            ->shouldReceive('fill', 'resize', 'save')
            ->andReturn($image);

        return $image;
    }
}
